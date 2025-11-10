<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Plenta\LokiAiBundle\OpenAi\Api;

class GetModelCron
{
    public function __construct(protected Api $api)
    {
    }

    #[AsCronJob(interval: 'daily')]
    public function getModels()
    {
        $this->api->initializeModels();
    }
}