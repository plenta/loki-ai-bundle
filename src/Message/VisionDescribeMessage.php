<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('contao_prio_normal')]
class VisionDescribeMessage
{
    public function __construct(
        public readonly int $promptId,
        public readonly string $uuid,
        public readonly string $jobUuid,
    ) {
    }
}
