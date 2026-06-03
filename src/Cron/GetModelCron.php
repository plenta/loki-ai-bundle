<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Plenta\LokiAiBundle\AiProvider\LokiAiGateway;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GetModelCron
{
    public function __construct(
        protected LokiAiGateway $gateway,
        #[Autowire(service: 'monolog.logger.contao.cron')]
        protected LoggerInterface $logger,
    ) {
    }

    #[AsCronJob(interval: 'daily')]
    public function getModels(): void
    {
        foreach ($this->gateway->getProviders() as $name => $provider) {
            try {
                $provider->initializeModels();
            } catch (\Exception $e) {
                $this->logger->error(
                    \sprintf('Loki AI: Could not update models for provider "%s": %s', $name, $e->getMessage()),
                    ['exception' => $e],
                );
            }
        }
    }
}
