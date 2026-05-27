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
                ->arrayNode('providers')
                    ->info('AI provider configuration.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('api_key')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('model')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->floatNode('temperature')
                                ->defaultValue(0.5)
                                ->min(0.0)
                                ->max(1.0)
                            ->end()
                            ->integerNode('max_tokens')
                                ->defaultValue(1000)
                                ->min(1)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
