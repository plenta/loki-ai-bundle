<?php

declare(strict_types=1);

namespace Plenta\LokiAiBundle\Tests\AiProvider;

use PHPUnit\Framework\TestCase;
use Plenta\LokiAiBundle\AiProvider\ClaudeProvider;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ClaudeProviderTest extends TestCase
{
    public function testGetProviderName(): void
    {
        $provider = new ClaudeProvider(
            $this->createMock(HttpClientInterface::class),
            ['api_key' => 'test', 'model' => 'claude-sonnet-4-6', 'max_tokens' => 1024],
        );

        self::assertSame('claude', $provider->getProviderName());
    }

    public function testGetLabel(): void
    {
        $provider = new ClaudeProvider($this->createMock(HttpClientInterface::class), []);

        self::assertSame('Claude (Anthropic)', $provider->getLabel());
    }

    public function testIsConfiguredReturnsTrueWhenApiKeySet(): void
    {
        $provider = new ClaudeProvider(
            $this->createMock(HttpClientInterface::class),
            ['api_key' => 'sk-ant-test'],
        );

        self::assertTrue($provider->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWhenApiKeyMissing(): void
    {
        $provider = new ClaudeProvider($this->createMock(HttpClientInterface::class), []);
        self::assertFalse($provider->isConfigured());

        $provider = new ClaudeProvider($this->createMock(HttpClientInterface::class), ['api_key' => '']);
        self::assertFalse($provider->isConfigured());
    }

    public function testGetAvailableModelsReturnsFallbackWhenNotConfigured(): void
    {
        // No api_key → isConfigured() is false → must not call the HTTP client
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::never())->method('request');

        $provider = new ClaudeProvider($httpClient, []);
        $models   = $provider->getAvailableModels();

        self::assertArrayHasKey('claude-opus-4-7', $models);
        self::assertArrayHasKey('claude-sonnet-4-6', $models);
        self::assertArrayHasKey('claude-haiku-4-5-20251001', $models);
    }

    public function testGetAvailableModelsFetchesFromApiWhenConfigured(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'data' => [
                ['id' => 'claude-new-2026',  'display_name' => 'Claude New 2026'],
                ['id' => 'claude-fast-2026', 'display_name' => 'Claude Fast 2026'],
            ],
            'has_more' => false,
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'https://api.anthropic.com/v1/models', self::anything())
            ->willReturn($response);

        $provider = new ClaudeProvider($httpClient, ['api_key' => 'sk-test']);
        $models   = $provider->getAvailableModels();

        self::assertArrayHasKey('claude-new-2026', $models);
        self::assertSame('Claude New 2026', $models['claude-new-2026']);
        self::assertArrayHasKey('claude-fast-2026', $models);
    }

    public function testGetAvailableModelsFallsBackOnApiError(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new \RuntimeException('Network error'));

        $provider = new ClaudeProvider($httpClient, ['api_key' => 'sk-test']);
        $models   = $provider->getAvailableModels();

        // Must fall back to the hardcoded list instead of throwing
        self::assertNotEmpty($models);
        self::assertArrayHasKey('claude-sonnet-4-6', $models);
    }

    public function testGetAvailableModelsIsCachedForTheRequest(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'data' => [['id' => 'claude-x', 'display_name' => 'Claude X']],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        // Second call must NOT produce a second HTTP request
        $httpClient->expects(self::once())->method('request')->willReturn($response);

        $provider = new ClaudeProvider($httpClient, ['api_key' => 'sk-test']);

        $provider->getAvailableModels();
        $provider->getAvailableModels(); // cached – no additional HTTP call
    }

    public function testChatThrowsWhenApiKeyMissing(): void
    {
        $provider = new ClaudeProvider(
            $this->createMock(HttpClientInterface::class),
            [],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/api_key/');

        $provider->chat('Hello');
    }

    public function testChatSendsCorrectRequest(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'content' => [['type' => 'text', 'text' => 'Hello back']],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'https://api.anthropic.com/v1/messages',
                self::callback(static function (array $options): bool {
                    return 'sk-test' === $options['headers']['x-api-key']
                        && 'claude-sonnet-4-6' === $options['json']['model']
                        && 'Hello' === $options['json']['messages'][0]['content'];
                }),
            )
            ->willReturn($response);

        $provider = new ClaudeProvider($httpClient, [
            'api_key' => 'sk-test',
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 1024,
            'temperature' => 0.5,
        ]);

        self::assertSame('Hello back', $provider->chat('Hello'));
    }

    public function testChatUsesOverrideModel(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'content' => [['type' => 'text', 'text' => 'ok']],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->with('POST', self::anything(), self::callback(static function (array $options): bool {
                return 'claude-opus-4-7' === $options['json']['model'];
            }))
            ->willReturn($response);

        $provider = new ClaudeProvider($httpClient, [
            'api_key' => 'sk-test',
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 1024,
        ]);

        $provider->chat('Hello', 'claude-opus-4-7');
    }

    public function testTemperatureIsCappedAtOne(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'content' => [['type' => 'text', 'text' => 'ok']],
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->with('POST', self::anything(), self::callback(static function (array $options): bool {
                return 1.0 === $options['json']['temperature'];
            }))
            ->willReturn($response);

        $provider = new ClaudeProvider($httpClient, [
            'api_key' => 'sk-test',
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 1024,
        ]);

        // Pass 1.8 (OpenAI allows this) — Claude should cap it at 1.0
        $provider->chat('Hello', null, 1.8);
    }

    public function testChatThrowsOnUnexpectedResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn(['content' => []]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $provider = new ClaudeProvider($httpClient, [
            'api_key' => 'sk-test',
            'model' => 'claude-sonnet-4-6',
            'max_tokens' => 1024,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unexpected response/');

        $provider->chat('Hello');
    }
}
