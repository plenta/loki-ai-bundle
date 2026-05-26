<?php

declare(strict_types=1);

namespace Plenta\LokiAiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Plenta\LokiAiBundle\DependencyInjection\LokiAiExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LokiAiExtensionTest extends TestCase
{
    private function buildContainer(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $extension = new LokiAiExtension();
        $extension->load([$config], $container);

        return $container;
    }

    public function testPlainStringApiKeyIsNotModified(): void
    {
        $container = $this->buildContainer([
            'providers' => [
                'openai' => ['api_key' => 'sk-hardcoded'],
            ],
        ]);

        $providers = $container->getParameter('loki_ai.providers');

        self::assertSame('sk-hardcoded', $providers['openai']['api_key']);
    }

    public function testEmptyProvidersResultsInEmptyParameter(): void
    {
        $container = $this->buildContainer([]);

        $providers = $container->getParameter('loki_ai.providers');

        self::assertSame([], $providers);
    }

    public function testMultipleProvidersAreStored(): void
    {
        $container = $this->buildContainer([
            'providers' => [
                'openai' => ['api_key' => 'sk-open', 'model' => 'gpt-4o'],
                'claude' => ['api_key' => 'sk-claude', 'model' => 'claude-sonnet-4-6'],
            ],
        ]);

        $providers = $container->getParameter('loki_ai.providers');

        self::assertArrayHasKey('openai', $providers);
        self::assertArrayHasKey('claude', $providers);
        self::assertSame('gpt-4o', $providers['openai']['model']);
    }

    public function testEmptyFallbackParameterIsRegistered(): void
    {
        // The loki_ai.empty parameter ('') is the fallback for
        // %env(default:loki_ai.empty:VAR)% references in config.yaml.
        // It must exist so DefaultEnvVarProcessor can return '' for missing env vars
        // instead of throwing EnvNotFoundException.
        $container = $this->buildContainer([]);

        self::assertTrue($container->hasParameter('loki_ai.empty'));
        self::assertSame('', $container->getParameter('loki_ai.empty'));
    }
}
