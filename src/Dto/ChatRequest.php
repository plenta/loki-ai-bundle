<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Dto;

readonly class ChatRequest
{
    public function __construct(
        public string  $prompt,
        public ?string $model = null,
        public ?float  $temperature = null,
        public ?int    $maxTokens = null,
        public ?string $systemInstruction = null,
    ) {
    }
}
