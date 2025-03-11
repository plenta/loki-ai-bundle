<?php

declare(strict_types=1);

/**
 * Plenta Jobs Basic Geo Search Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2024, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\OpenAi;

use Contao\System;
use Doctrine\ORM\EntityManagerInterface;
use OpenAI\Contracts\ClientContract;
use Plenta\LokiAiBundle\Entity\Model;
use Plenta\LokiAiBundle\Repository\ModelRepository;

class Api
{
    public function __construct(
        protected ClientContract $openAiClient,
        protected array $openAi,
        protected ModelRepository $modelRepository,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function chat($content, ?string $model = null, ?float $temperature = null, ?int $maxTokens = null): string
    {
        $response = $this->openAiClient->chat()->create([
            'model' => $model ?: $this->openAi['model'],
            'temperature' => $temperature ?: $this->openAi['temperature'],
            'max_completion_tokens' => $maxTokens ?: $this->openAi['max_tokens'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
        ]);

        return $response->choices[0]->message->content;
    }

    public function getModels()
    {
        if (!$models = $this->modelRepository->findAll()) {
            $this->initializeModels();

            $models = $this->modelRepository->findAll();
        }

        return $models;
    }

    public function initializeModels()
    {
        $time = time();

        foreach ($this->openAiClient->models()->list()->data as $model) {
            $entity = $this->modelRepository->findBy(['name' => $model->id]);

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
}
