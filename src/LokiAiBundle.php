<?php

namespace Plenta\LokiAiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LokiAiBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}