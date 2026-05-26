<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\DependencyInjection\Compiler;

use Plenta\LokiAiBundle\AiProvider\AsLokiAiProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Reads #[AsLokiAiProvider('name')] from each tagged service and injects the matching
 * loki_ai.providers.{name} config block as $providerConfig into the constructor.
 * New providers need zero changes outside their own class + bundle config.
 */
class RegisterProvidersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('loki_ai.providers')) {
            return;
        }

        /** @var array<string, array<string, mixed>> $providersConfig */
        $providersConfig = $container->getParameter('loki_ai.providers');

        foreach ($container->findTaggedServiceIds('loki_ai.provider') as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass() ?? $serviceId;

            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException) {
                continue;
            }

            $attributes = $reflection->getAttributes(AsLokiAiProvider::class);

            if (empty($attributes)) {
                continue;
            }

            $providerName = $attributes[0]->newInstance()->name;

            // LokiAiExtension::load() already resolved %env(VAR)% placeholders to plain
            // strings before storing them in the 'loki_ai.providers' parameter, so we can
            // inject the config block directly without any further transformation.
            $config = $providersConfig[$providerName] ?? [];

            $definition->setArgument('$providerConfig', $config);
        }
    }
}
