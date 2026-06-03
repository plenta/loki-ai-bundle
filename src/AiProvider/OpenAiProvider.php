<?php

declare(strict_types=1);

/**
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2025-2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\AiProvider;

use Doctrine\ORM\EntityManagerInterface;
use OpenAI\Contracts\ClientContract;
use Plenta\LokiAiBundle\Entity\Model;
use Plenta\LokiAiBundle\Repository\ModelRepository;

#[AsLokiAiProvider('openai')]
class OpenAiProvider implements LokiAiProviderInterface
{
    private ClientContract|null $client = null;

    public function __construct(
        /**
         * @var array<string, mixed>
         */
        protected array $providerConfig,
        protected ModelRepository $modelRepository,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function chat(string $content, string|null $model = null, float|null $temperature = null, int|null $maxTokens = null): string
    {
        $response = $this->getClient()->chat()->create([
            'model' => $model ?: ($this->providerConfig['model'] ?? 'gpt-4o-mini'),
            'temperature' => $temperature ?: ($this->providerConfig['temperature'] ?? 0.5),
            'max_completion_tokens' => $maxTokens ?: ($this->providerConfig['max_tokens'] ?? 100),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function getLabel(): string
    {
        return 'ChatGPT (OpenAI)';
    }

    public function isConfigured(): bool
    {
        return !empty($this->providerConfig['api_key'] ?? '');
    }

    /**
     * @return array<string, string>
     */
    public function getAvailableModels(): array
    {
        if (empty($this->providerConfig['api_key'] ?? '')) {
            return [];
        }

        $return = [];

        foreach ($this->getModels() as $model) {
            $return[$model->getName()] = $model->getName();
        }

        return $return;
    }

    /**
     * @return array<Model>
     */
    public function getModels(): array
    {
        if (!$models = $this->modelRepository->findAll()) {
            $this->initializeModels();

            $models = $this->modelRepository->findAll();
        }

        return $models;
    }

    public function initializeModels(): void
    {
        if (empty($this->providerConfig['api_key'] ?? '')) {
            return;
        }

        $time = time();

        foreach ($this->getClient()->models()->list()->data as $model) {
            $entity = $this->modelRepository->findOneBy(['name' => $model->id]);

            if (!$entity) {
                $entity = new Model();
                $entity->setName($model->id);
            }

            $entity
                ->setCreated($model->created)
                ->setOwner($model->ownedBy)
            ;

            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
        $this->modelRepository->deleteOlderThan($time);
    }

    private function getClient(): ClientContract
    {
        if (null === $this->client) {
            $apiKey = $this->providerConfig['api_key'] ?? '';

            if (empty($apiKey)) {
                throw new \RuntimeException('OpenAI API key is not configured. Set loki_ai.providers.openai.api_key in your config.');
            }

            $this->client = \OpenAI::client($apiKey);
        }

        return $this->client;
    }
}
