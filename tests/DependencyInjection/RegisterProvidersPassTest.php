<?php

declare(strict_types=1);

namespace Plenta\LokiAiBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Plenta\LokiAiBundle\AiProvider\LokiAiProviderInterface;
use Plenta\LokiAiBundle\AiProvider\AsLokiAiProvider;
use Plenta\LokiAiBundle\DependencyInjection\Compiler\RegisterProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RegisterProvidersPassTest extends TestCase
{
    public function testInjectsConfigForTaggedProvider(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('loki_ai.providers', [
            'dummy' => ['model' => 'dummy-model', 'api_key' => 'secret'],
        ]);

        $definition = new Definition(DummyProvider::class);
        $definition->addTag('loki_ai.provider');
        $container->setDefinition(DummyProvider::class, $definition);

        (new RegisterProvidersPass())->process($container);

        self::assertSame(
            ['model' => 'dummy-model', 'api_key' => 'secret'],
            $container->getDefinition(DummyProvider::class)->getArgument('$providerConfig'),
        );
    }

    public function testInjectsEmptyArrayWhenProviderNotConfigured(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('loki_ai.providers', []);

        $definition = new Definition(DummyProvider::class);
        $definition->addTag('loki_ai.provider');
        $container->setDefinition(DummyProvider::class, $definition);

        (new RegisterProvidersPass())->process($container);

        self::assertSame(
            [],
            $container->getDefinition(DummyProvider::class)->getArgument('$providerConfig'),
        );
    }

    public function testSkipsServiceWithoutAttribute(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('loki_ai.providers', []);

        $definition = new Definition(NoAttributeProvider::class);
        $definition->addTag('loki_ai.provider');
        $container->setDefinition(NoAttributeProvider::class, $definition);

        (new RegisterProvidersPass())->process($container);

        // No $providerConfig argument should be set
        self::assertEmpty($definition->getArguments());
    }

    public function testDoesNothingWithoutParameter(): void
    {
        $container = new ContainerBuilder();

        $definition = new Definition(DummyProvider::class);
        $definition->addTag('loki_ai.provider');
        $container->setDefinition(DummyProvider::class, $definition);

        (new RegisterProvidersPass())->process($container);

        self::assertEmpty($definition->getArguments());
    }
}

#[AsLokiAiProvider('dummy')]
class DummyProvider implements LokiAiProviderInterface
{
    public function __construct(protected array $providerConfig = []) {}
    public function chat(string $content, string|null $model = null, float|null $temperature = null, int|null $maxTokens = null): string { return ''; }
    public function getProviderName(): string { return 'dummy'; }
    public function getLabel(): string { return 'Dummy Provider'; }
    public function isConfigured(): bool { return !empty($this->providerConfig['api_key'] ?? ''); }
    public function getAvailableModels(): array { return []; }
}

class NoAttributeProvider implements LokiAiProviderInterface
{
    public function chat(string $content, string|null $model = null, float|null $temperature = null, int|null $maxTokens = null): string { return ''; }
    public function getProviderName(): string { return 'none'; }
    public function getLabel(): string { return 'No Attribute'; }
    public function isConfigured(): bool { return false; }
    public function getAvailableModels(): array { return []; }
}
