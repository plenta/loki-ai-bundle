<?php

declare(strict_types=1);

/**
 * @package       Customer
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @license       commercial
 */

namespace Plenta\LokiAiBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Plenta\LokiAiBundle\AiProvider\LokiAiProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class GetModelCron
{
    public function __construct(
        /** @param iterable<LokiAiProviderInterface> $providers */
        #[AutowireIterator('plenta.loki_ai.provider')]
        protected iterable $providers,
    ) {
    }

    #[AsCronJob(interval: 'daily')]
    public function getModels(): void
    {
        foreach ($this->providers as $provider) {
            if (!$provider->isConfigured()) {
                continue;
            }

            $provider->initializeModels();
        }
    }
}
