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

use Plenta\LokiAiBundle\Exception\PromptException;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class LokiAiGateway
{
    /**
     * @var array<string, LokiAiProviderInterface>
     */
    private array $providers = [];

    public function __construct(
        #[TaggedIterator('loki_ai.provider')]
        iterable $providers,
    ) {
        // The TaggedIterator is lazy: each provider is instantiated when first accessed.
        // Catch any error (e.g. missing env var before cache:clear) so that one broken
        // provider does not prevent the whole gateway – and the rest of the backend – from loading.
        foreach ($providers as $provider) {
            try {
                $this->providers[$provider->getProviderName()] = $provider;
            } catch (\Throwable) {
                // Provider could not be initialised (e.g. unresolved env variable).
                // It will simply not appear in the backend select field.
            }
        }
    }

    public function getProvider(string|null $name): LokiAiProviderInterface
    {
        $name = $name ?: 'openai';

        if ([] === $this->providers) {
            throw new PromptException(
                'No AI providers are available. Please configure at least one API key.',
            );
        }

        if (!isset($this->providers[$name])) {
            throw new PromptException(\sprintf(
                'AI provider "%s" not found. Available: %s',
                $name,
                implode(', ', array_keys($this->providers)),
            ));
        }

        return $this->providers[$name];
    }

    /**
     * @return array<string, LokiAiProviderInterface>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
