<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Controller;

use OCA\ClaudeChat\Service\ClaudeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

class AdminController extends Controller {

    private const MODELS = [
        'claude-opus-4-6',
        'claude-sonnet-4-6',
        'claude-haiku-4-5-20251001',
    ];

    public function __construct(
        string $appName,
        IRequest $request,
        private IConfig $config,
        private ClaudeService $claudeService,
    ) {
        parent::__construct($appName, $request);
    }
#[NoCSRFRequired]
    public function getSettings(): JSONResponse {
        return new JSONResponse([
            'api_key'       => $this->maskKey($this->config->getAppValue('claudechat', 'api_key', '')),
            'model'         => $this->claudeService->getModel(),
            'max_tokens'    => $this->claudeService->getMaxTokens(),
            'system_prompt' => $this->claudeService->getSystemPrompt(),
            'models'        => self::MODELS,
            'configured'    => $this->claudeService->isConfigured(),
        ]);
    }

#[NoCSRFRequired]
public function saveSettings(): JSONResponse {
    $raw = file_get_contents('php://input');
    file_put_contents('/tmp/claudechat_debug.txt', 
        "BODY: " . $raw . "\n" . 
        "PARAMS: " . json_encode($this->request->getParams()) . "\n"
    );
    $body = json_decode($raw, true);
    if (!empty($body['api_key']) && !str_contains($body['api_key'], '***')) {
        $this->config->setAppValue('claudechat', 'api_key', $body['api_key']);
    }
    return new JSONResponse(['status' => 'saved']);
}    
    
    private function maskKey(string $key): string {
        if (strlen($key) < 8) return $key;
        return substr($key, 0, 8) . str_repeat('*', 20);
    }
}
