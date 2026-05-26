<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\DependencyInjection;

use Plenta\LokiAiBundle\AiProvider\AsLokiAiProvider;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class LokiAiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config'),
        );

        $loader->load('services.yaml');

        // Any class tagged #[AsLokiAiProvider('name')] is automatically tagged as loki_ai.provider.
        // RegisterProvidersPass then injects the matching config block.
        $container->registerAttributeForAutoconfiguration(
            AsLokiAiProvider::class,
            static function (ChildDefinition $definition, AsLokiAiProvider $attribute): void {
                $definition->addTag('loki_ai.provider');
            },
        );

        // Used as the fallback value in %env(default:loki_ai.empty:VAR)% references.
        // When an API-key env var is not set, the default: processor returns this empty
        // string, which causes isConfigured() to return false instead of throwing
        // EnvNotFoundException.
        $container->setParameter('loki_ai.empty', '');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Store the provider config verbatim.  Any %env(default:loki_ai.empty:VAR)%
        // references in the config are resolved by Symfony's DefaultEnvVarProcessor at
        // runtime: if the env var is set its value is used; if it is absent (or empty)
        // the loki_ai.empty parameter ('') is returned instead, which prevents
        // EnvNotFoundException and makes isConfigured() return false so the provider is
        // simply hidden from the backend select field.
        $container->setParameter('loki_ai.providers', $config['providers']);
    }
}
