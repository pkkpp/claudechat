<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Controller;

use OCA\ClaudeChat\Db\Conversation;
use OCA\ClaudeChat\Db\ConversationMapper;
use OCA\ClaudeChat\Db\Message;
use OCA\ClaudeChat\Db\MessageMapper;
use OCA\ClaudeChat\Service\ClaudeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

class ChatController extends Controller {

    public function __construct(
        string $appName,
        IRequest $request,
        private ConversationMapper $conversationMapper,
        private MessageMapper $messageMapper,
        private ClaudeService $claudeService,
        private IUserSession $userSession,
        private IRootFolder $rootFolder,
    ) {
        parent::__construct($appName, $request);
    }

    private function getUserId(): string {
    	$user = $this->userSession->getUser();
	if ($user === null) {
        	throw new \RuntimeException('Not logged in');
    	}
    	return $user->getUID();
    }

    // -----------------------------------------------------------------------
    // Conversations
    // -----------------------------------------------------------------------

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getConversations(): JSONResponse {
        $conversations = $this->conversationMapper->findAllForUser($this->getUserId());
        return new JSONResponse(array_map(fn($c) => $c->jsonSerialize(), $conversations));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function newConversation(): JSONResponse {
        $conv = new Conversation();
        $conv->setUserId($this->getUserId());
        $conv->setTitle('New conversation');
        $conv->setCreatedAt(time());
        $conv->setUpdatedAt(time());
        $conv = $this->conversationMapper->insert($conv);
        return new JSONResponse($conv->jsonSerialize());
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getMessages(int $id): JSONResponse {
        try {
            $this->conversationMapper->findForUser($id, $this->getUserId());
        } catch (DoesNotExistException) {
            return new JSONResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }
        $messages = $this->messageMapper->findByConversation($id);
        return new JSONResponse(array_map(fn($m) => $m->jsonSerialize(), $messages));
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function deleteConversation(int $id): JSONResponse {
        try {
            $conv = $this->conversationMapper->findForUser($id, $this->getUserId());
        } catch (DoesNotExistException) {
            return new JSONResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
        }
        $this->messageMapper->deleteByConversation($id);
        $this->conversationMapper->delete($conv);
        return new JSONResponse(['status' => 'ok']);
    }

    // -----------------------------------------------------------------------
    // Send message
    // -----------------------------------------------------------------------

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function sendMessage(): JSONResponse {


        $userText = trim($this->request->getParam('message', ''));
        $conversationId = (int) $this->request->getParam('conversation_id', 0);
    
        // Fallback: JSON Body
        if (empty($userText)) {
            $raw = file_get_contents('php://input');
            $body = json_decode($raw, true) ?? [];
            $userText = trim($body['message'] ?? '');
            $conversationId = (int) ($body['conversation_id'] ?? 0);
        }	    

        // Verify conversation belongs to this user
        try {
            $conv = $this->conversationMapper->findForUser($conversationId, $this->getUserId());
        } catch (DoesNotExistException) {
            return new JSONResponse(['error' => 'Conversation not found'], Http::STATUS_NOT_FOUND);
        }

        // Auto-title after first message
        if ($conv->getTitle() === 'New conversation' && strlen($userText) > 0) {
            $conv->setTitle(mb_substr($userText, 0, 60) . (mb_strlen($userText) > 60 ? '…' : ''));
        }

        // Load history
        $history = $this->messageMapper->findByConversation($conversationId);
        $apiMessages = array_map(fn($m) => ['role' => $m->getRole(), 'content' => $m->getContent()], $history);

        // Add new user message
        $apiMessages[] = ['role' => 'user', 'content' => $userText];

        // Call Claude
        try {
            $assistantText = $this->claudeService->chat($apiMessages);
        } catch (\RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        // Persist user message
        $userMsg = new Message();
        $userMsg->setConversationId($conversationId);
        $userMsg->setRole('user');
        $userMsg->setContent($userText);
        $userMsg->setCreatedAt(time());
        $this->messageMapper->insert($userMsg);

        // Persist assistant message
        $assistantMsg = new Message();
        $assistantMsg->setConversationId($conversationId);
        $assistantMsg->setRole('assistant');
        $assistantMsg->setContent($assistantText);
        $assistantMsg->setCreatedAt(time());
        $this->messageMapper->insert($assistantMsg);

        // Update conversation timestamp
        $conv->setUpdatedAt(time());
        $this->conversationMapper->update($conv);

        return new JSONResponse([
            'user_message'      => $userMsg->jsonSerialize(),
            'assistant_message' => $assistantMsg->jsonSerialize(),
            'conversation'      => $conv->jsonSerialize(),
        ]);
    }

    // -----------------------------------------------------------------------
    // File analysis
    // -----------------------------------------------------------------------

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function analyzeFile(): JSONResponse {

      // Versuche zuerst Form-Parameter, dann JSON
       $filePath       = $this->request->getParam('file_path', '');
	$userQuestion   = trim($this->request->getParam('question', 'Please summarize and analyze this file.'));
	$conversationId = (int) $this->request->getParam('conversation_id', 0);
    
	// Fallback: JSON Body
	if (empty($filePath)) {
	    $body = json_decode(file_get_contents('php://input'), true) ?? [];
	    $filePath       = $body['file_path'] ?? '';
	    $userQuestion   = trim($body['question'] ?? 'Please summarize and analyze this file.');
	    $conversationId = (int) ($body['conversation_id'] ?? 0);
	}

	    
        // Verify conversation belongs to user
        try {
            $conv = $this->conversationMapper->findForUser($conversationId, $this->getUserId());
        } catch (DoesNotExistException) {
            return new JSONResponse(['error' => 'Conversation not found'], Http::STATUS_NOT_FOUND);
        }

        // Read the file from Nextcloud
        try {
            $userFolder = $this->rootFolder->getUserFolder($this->getUserId());
            $file = $userFolder->get($filePath);
	    $content = $file->getContent();

	    $mime = $file->getMimeType();
	    $binaryMimes = [
	        'application/pdf',
	        'application/zip',
		    'application/vnd.oasis',
		    'application/vnd.openxmlformats',
		    'application/msword',
		    'application/vnd.ms-',
		    'image/',
		    'audio/',
		    'video/',
		    'application/octet-stream',
	    ];
	    $isBinary = false;
	    foreach ($binaryMimes as $m) {
		    if (str_starts_with($mime, $m)) { $isBinary = true; break; }
	    }
	    if ($isBinary) {
		    return new JSONResponse(['error' => 'Binärdateien (ODT, DOCX, PDF, Bilder...) können nicht gelesen werden. Bitte Textdateien verwenden (txt, md, csv, json, xml, html...).'], Http::STATUS_BAD_REQUEST);
	    }

            $fileName = $file->getName();
        } catch (\Exception $e) {
            return new JSONResponse(['error' => 'File not found: ' . $e->getMessage()], Http::STATUS_NOT_FOUND);
        }

        // Limit content size to avoid huge API calls (100 KB)
        if (strlen($content) > 102400) {
            $content = substr($content, 0, 102400) . "\n\n[... file truncated at 100 KB ...]";
        }

        try {
            $assistantText = $this->claudeService->analyzeFile($content, $fileName, $userQuestion);
        } catch (\RuntimeException $e) {
            return new JSONResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }

        $combinedUserContent = "📎 **{$fileName}**\n\n{$userQuestion}";

        // Save both messages
        $userMsg = new Message();
        $userMsg->setConversationId($conversationId);
        $userMsg->setRole('user');
        $userMsg->setContent($combinedUserContent);
        $userMsg->setCreatedAt(time());
        $this->messageMapper->insert($userMsg);

        $assistantMsg = new Message();
        $assistantMsg->setConversationId($conversationId);
        $assistantMsg->setRole('assistant');
        $assistantMsg->setContent($assistantText);
        $assistantMsg->setCreatedAt(time());
        $this->messageMapper->insert($assistantMsg);

        $conv->setUpdatedAt(time());
        $this->conversationMapper->update($conv);

        return new JSONResponse([
            'user_message'      => $userMsg->jsonSerialize(),
            'assistant_message' => $assistantMsg->jsonSerialize(),
        ]);
    }
}
