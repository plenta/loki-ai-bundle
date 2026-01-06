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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Adds the bundle services to the container.
 */
class LokiAiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config'),
        );

        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('loki_ai.open_ai.model', $config['open_ai']['model']);
        $container->setParameter('loki_ai.open_ai.temperature', $config['open_ai']['temperature']);
        $container->setParameter('loki_ai.open_ai.max_tokens', $config['open_ai']['max_tokens']);
    }
}
