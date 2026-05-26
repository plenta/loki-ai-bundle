<?php

declare(strict_types=1);

namespace Plenta\LokiAiBundle\Tests\AiProvider;

use PHPUnit\Framework\TestCase;
use Plenta\LokiAiBundle\AiProvider\LokiAiGateway;
use Plenta\LokiAiBundle\AiProvider\LokiAiProviderInterface;
use Plenta\LokiAiBundle\Exception\PromptException;

class LokiAiGatewayTest extends TestCase
{
    private function makeProvider(string $name, bool $configured = true): LokiAiProviderInterface
    {
        $mock = $this->createMock(LokiAiProviderInterface::class);
        $mock->method('getProviderName')->willReturn($name);
        $mock->method('getLabel')->willReturn(ucfirst($name));
        $mock->method('isConfigured')->willReturn($configured);

        return $mock;
    }

    public function testGetProviderReturnsCorrectProvider(): void
    {
        $openai = $this->makeProvider('openai');
        $claude = $this->makeProvider('claude');

        $gateway = new LokiAiGateway([$openai, $claude]);

        self::assertSame($openai, $gateway->getProvider('openai'));
        self::assertSame($claude, $gateway->getProvider('claude'));
    }

    public function testGetProviderDefaultsToOpenai(): void
    {
        $openai = $this->makeProvider('openai');
        $gateway = new LokiAiGateway([$openai]);

        self::assertSame($openai, $gateway->getProvider(null));
        self::assertSame($openai, $gateway->getProvider(''));
    }

    public function testGetProviderThrowsForUnknownProvider(): void
    {
        $gateway = new LokiAiGateway([$this->makeProvider('openai')]);

        $this->expectException(PromptException::class);
        $this->expectExceptionMessageMatches('/"gemini"/');

        $gateway->getProvider('gemini');
    }

    public function testGetProvidersReturnsAll(): void
    {
        $openai = $this->makeProvider('openai');
        $claude = $this->makeProvider('claude');

        $gateway = new LokiAiGateway([$openai, $claude]);
        $providers = $gateway->getProviders();

        self::assertArrayHasKey('openai', $providers);
        self::assertArrayHasKey('claude', $providers);
        self::assertCount(2, $providers);
    }

    public function testErrorMessageListsAvailableProviders(): void
    {
        $gateway = new LokiAiGateway([$this->makeProvider('openai'), $this->makeProvider('claude')]);

        try {
            $gateway->getProvider('gemini');
            self::fail('Expected PromptException');
        } catch (PromptException $e) {
            self::assertStringContainsString('openai', $e->getMessage());
            self::assertStringContainsString('claude', $e->getMessage());
        }
    }

    public function testIsConfiguredReflectsProviderState(): void
    {
        $configured = $this->makeProvider('openai', true);
        $unconfigured = $this->makeProvider('claude', false);

        $gateway = new LokiAiGateway([$configured, $unconfigured]);

        self::assertTrue($gateway->getProvider('openai')->isConfigured());
        self::assertFalse($gateway->getProvider('claude')->isConfigured());
    }

    public function testGetLabelReturnsProviderLabel(): void
    {
        $provider = $this->makeProvider('openai');
        $gateway = new LokiAiGateway([$provider]);

        self::assertSame('Openai', $gateway->getProvider('openai')->getLabel());
    }

    public function testBrokenProviderIsSkippedDuringConstruction(): void
    {
        $good = $this->makeProvider('openai');

        // A provider whose getProviderName() throws simulates a service that
        // cannot be initialised (e.g. unresolved env variable before cache:clear).
        $broken = $this->createMock(LokiAiProviderInterface::class);
        $broken->method('getProviderName')->willThrowException(new \RuntimeException('env var missing'));

        $gateway = new LokiAiGateway([$broken, $good]);

        // The broken provider must be silently skipped; the good one still works.
        self::assertCount(1, $gateway->getProviders());
        self::assertSame($good, $gateway->getProvider('openai'));
    }

    public function testGetProviderThrowsWhenNoProvidersAvailable(): void
    {
        $gateway = new LokiAiGateway([]);

        $this->expectException(PromptException::class);
        $this->expectExceptionMessageMatches('/No AI providers are available/');

        $gateway->getProvider('openai');
    }

    public function testGetProvidersReturnsEmptyArrayWhenNoneRegistered(): void
    {
        $gateway = new LokiAiGateway([]);

        self::assertSame([], $gateway->getProviders());
    }
}
