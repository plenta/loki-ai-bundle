<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\AiProvider;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsLokiAiProvider
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
