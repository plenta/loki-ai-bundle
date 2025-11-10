<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\EventListener\Contao\DCA;

use Contao\Message;
use Contao\DataContainer;
use Composer\InstalledVersions;
use Symfony\Component\Asset\Packages;
use Twig\Environment as TwigEnvironment;
use Symfony\Component\HttpFoundation\RequestStack;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

#[AsCallback(table: 'tl_loki_prompt', target: 'config.onload')]
class TlLokiInfoPanel
{
    public function __construct(
        protected RequestStack $requestStack,
        protected TwigEnvironment $twig,
        protected Packages $packages,
    )
    {
    }

    public function __invoke(DataContainer|null $dc = null): void
    {
        $GLOBALS['TL_CSS']['lokiBackendInfoPanel'] = $this->packages->getUrl('lokiai/backend.css', 'lokiai');
        $info = $this->twig->render('@Contao/backend/loki_infos.html.twig', [
            'version' => InstalledVersions::getVersion('plenta/loki-ai-bundle'),
        ]);

        Message::addRaw($info);
    }
}
