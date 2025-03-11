<?php

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