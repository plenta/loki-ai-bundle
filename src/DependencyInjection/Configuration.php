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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('loki_ai');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('open_ai')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('model')
                            ->defaultValue('gpt-4o-mini')
                        ->end()
                        ->floatNode('temperature')
                            ->defaultValue(0.5)
                        ->end()
                        ->integerNode('max_tokens')
                            ->defaultValue(100)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
