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

interface LokiAiProviderInterface
{
    public function chat(string $content, string|null $model, float|null $temperature, int|null $maxTokens, ?string $imageData = null, ?string $imageMimeType = null): string;

    public function supportsVision(): bool;

    public function getProviderName(): string;

    public function getLabel(): string;

    public function isConfigured(): bool;

    public function initializeModels(): void;

    /**
     * @return array<string, string>
     */
    public function getAvailableModels(): array;
}
