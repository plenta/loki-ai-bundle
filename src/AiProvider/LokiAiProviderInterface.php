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
    public function chat(string $content, string|null $model, float|null $temperature, int|null $maxTokens): string;

    public function getProviderName(): string;

    /**
     * Human-readable label shown in the backend select field.
     */
    public function getLabel(): string;

    /**
     * Returns true when the provider is ready to use (e.g. API key is configured).
     * Unconfigured providers are hidden in the backend.
     */
    public function isConfigured(): bool;

    /**
     * @return array<string, string>
     */
    public function getAvailableModels(): array;
}
