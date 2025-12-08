<?php

declare(strict_types=1);

/**
 * @package       Customer
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @license       commercial
 */

namespace Plenta\LokiAiBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use OpenAI\Symfony\OpenAIBundle;
use Plenta\LokiAiBundle\LokiAiBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\StimulusBundle\StimulusBundle;

class Plugin implements BundlePluginInterface, ConfigPluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(OpenAIBundle::class),
            BundleConfig::create(StimulusBundle::class),
            BundleConfig::create(LokiAiBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    OpenAIBundle::class,
                    StimulusBundle::class,
                ]),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(__DIR__.'/../../config/config.php');
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $resource = __DIR__.'/../Controller';

        if ($loader = $resolver->resolve($resource, 'annotation')) {
            return $loader->load($resource);
        }

        if ($loader = $resolver->resolve($resource, 'attribute')) {
            return $loader->load($resource);
        }

        return null;
    }
}
