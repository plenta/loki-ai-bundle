<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\AiProvider;

use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsLokiAiProvider('claude')]
class ClaudeProvider implements LokiAiProviderInterface
{
    private const ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';

    private const ANTHROPIC_API_VERSION = '2023-06-01';

    private const FALLBACK_MODELS = [
        'claude-opus-4-7' => 'Claude Opus 4.7',
        'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
        'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5',
    ];

    /** @var array<string, string>|null */
    private ?array $modelCache = null;

    public function __construct(
        protected HttpClientInterface $httpClient,
        /**
         * @var array<string, mixed>
         */
        protected array $providerConfig,
    ) {
    }

    public function chat(
        string $content,
        ?string $model = null,
        ?float $temperature = null,
        ?int $maxTokens = null,
        ?string $system = null,
    ): string {
        $apiKey = $this->providerConfig['api_key'] ?? '';

        if (empty($apiKey)) {
            throw new \RuntimeException('Claude API key is not configured. Set loki_ai.providers.claude.api_key in your config.');
        }

        $payload = [
            'model' => $model ?: ($this->providerConfig['model'] ?? 'claude-sonnet-4-6'),
            'max_tokens' => $maxTokens ?: ($this->providerConfig['max_tokens'] ?? 1024),
            'messages' => [
                ['role' => 'user', 'content' => $content],
            ],
        ];

        if (null !== $system) {
            $payload['system'] = $system;
        }

        $temp = $temperature ?: ($this->providerConfig['temperature'] ?? null);
        if (null !== $temp) {
            $payload['temperature'] = min(1.0, (float) $temp);
        }

        $response = $this->httpClient->request('POST', self::ANTHROPIC_API_URL, [
            'headers' => [
                'x-api-key' => $apiKey,
                'anthropic-version' => self::ANTHROPIC_API_VERSION,
                'content-type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $data = $response->toArray();

        return $data['content'][0]['text'] ?? throw new \RuntimeException('Unexpected response structure from Claude API.');
    }

    public function getProviderName(): string
    {
        return 'claude';
    }

    public function getLabel(): string
    {
        return 'Claude (Anthropic)';
    }

    public function initializeModels(): void
    {
    }

    public function isConfigured(): bool
    {
        return !empty($this->providerConfig['api_key'] ?? '');
    }

    /**
     * @return array<string, string> model-id => display-name
     */
    public function getAvailableModels(): array
    {
        if (null !== $this->modelCache) {
            return $this->modelCache;
        }

        if (!$this->isConfigured()) {
            return $this->modelCache = self::FALLBACK_MODELS;
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.anthropic.com/v1/models', [
                'headers' => [
                    'x-api-key' => $this->providerConfig['api_key'],
                    'anthropic-version' => self::ANTHROPIC_API_VERSION,
                ],
            ]);

            $models = [];

            foreach ($response->toArray()['data'] ?? [] as $model) {
                if (isset($model['id'], $model['display_name'])) {
                    $models[$model['id']] = $model['display_name'];
                }
            }

            $this->modelCache = $models ?: self::FALLBACK_MODELS;
        } catch (\Throwable) {
            $this->modelCache = self::FALLBACK_MODELS;
        }

        return $this->modelCache;
    }
}
