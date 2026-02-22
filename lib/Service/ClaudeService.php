<?php

declare(strict_types=1);

namespace OCA\ClaudeChat\Service;

use OCP\IConfig;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;

class ClaudeService {

    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    public function __construct(
        private IConfig $config,
        private IClientService $clientService,
        private LoggerInterface $logger,
    ) {}

    private function getApiKey(): string {
        return $this->config->getAppValue('claudechat', 'api_key', '');
    }

    public function getModel(): string {
        return $this->config->getAppValue('claudechat', 'model', 'claude-sonnet-4-6');
    }

    public function getMaxTokens(): int {
        return (int) $this->config->getAppValue('claudechat', 'max_tokens', '4096');
    }

    public function getSystemPrompt(): string {
        return $this->config->getAppValue(
            'claudechat',
            'system_prompt',
            'You are a helpful AI assistant integrated into Nextcloud. Be concise and helpful.'
        );
    }

    /**
     * Send messages to Claude and return the response text.
     *
     * @param array $messages  Array of ['role' => 'user'|'assistant', 'content' => string]
     * @return string
     * @throws \RuntimeException on API errors
     */
    public function chat(array $messages): string {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key is not configured. Please ask your administrator to set it up.');
        }

        $client = $this->clientService->newClient();

        $payload = [
            'model'      => $this->getModel(),
            'max_tokens' => $this->getMaxTokens(),
            'system'     => $this->getSystemPrompt(),
            'messages'   => $messages,
        ];

	try {
            $encoded = json_encode($payload);

            $response = $client->post(self::API_URL, [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => self::API_VERSION,
                    'content-type'      => 'application/json',
                ],
                'body'    => json_encode($payload),
                'timeout' => 120,
            ]);

            $data = json_decode($response->getBody(), true);

            if (!isset($data['content'][0]['text'])) {
                $this->logger->error('Unexpected Claude API response', ['data' => $data]);
                throw new \RuntimeException('Unexpected response from Claude API.');
            }

            return $data['content'][0]['text'];

        } catch (\Exception $e) {
            $this->logger->error('Claude API request failed', ['exception' => $e->getMessage()]);
            throw new \RuntimeException('Claude API error: ' . $e->getMessage());
        }
    }

    /**
     * Analyze a file's text content.
     */
    public function analyzeFile(string $fileContent, string $fileName, string $userQuestion): string {
        $messages = [
            [
                'role'    => 'user',
                'content' => "File: **{$fileName}**\n\n```\n{$fileContent}\n```\n\nUser question: {$userQuestion}",
            ]
        ];
        return $this->chat($messages);
    }

    public function isConfigured(): bool {
        return !empty($this->getApiKey());
    }
}
