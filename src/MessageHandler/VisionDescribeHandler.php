<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\MessageHandler;

use Contao\CoreBundle\Job\Jobs;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Plenta\LokiAiBundle\AiProvider\ImageResizer;
use Plenta\LokiAiBundle\AiProvider\LokiAiGateway;
use Plenta\LokiAiBundle\Message\VisionDescribeMessage;
use Plenta\LokiAiBundle\Repository\PromptRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class VisionDescribeHandler
{
    public const DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
        Analysiere das Bild und gib AUSSCHLIESSLICH das folgende JSON zurück – ohne weiteren Text, ohne Markdown:
        {"title":"...","alt":"..."}

        Regeln:
        - "title": Prägnanter Titel des Hauptmotivs (max. 80 Zeichen, keine Floskeln wie "Bild von" oder "Foto zeigt")
        - "alt": Sachliche, detaillierte Bildbeschreibung für Menschen mit Sehbehinderung (max. 125 Zeichen, beginnt direkt mit dem Motiv)
        PROMPT;

    public function __construct(
        private readonly Jobs $jobs,
        private readonly LokiAiGateway $gateway,
        private readonly ImageResizer $imageResizer,
        private readonly Connection $connection,
        private readonly PromptRepository $promptRepository,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        #[Autowire(service: 'monolog.logger.contao.error')]
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(VisionDescribeMessage $message): void
    {
        $job = $this->jobs->getByUuid($message->jobUuid);

        if (!$job || $job->isCompleted()) {
            return;
        }

        $job = $job->markPending();
        $this->jobs->persist($job);

        try {
            $prompt = $this->promptRepository->find($message->promptId);

            if (null === $prompt || !$prompt->isPublished() || !$prompt->isVisionPrompt()) {
                throw new \RuntimeException('Vision prompt not found or not published.');
            }

            $file = $this->connection->createQueryBuilder()
                ->select('f.*')
                ->from('tl_files', 'f')
                ->where('f.uuid = :uuid')
                ->setParameter('uuid', StringUtil::uuidToBin($message->uuid))
                ->executeQuery()
                ->fetchAssociative()
            ;

            if (!$file) {
                throw new \RuntimeException(sprintf('File not found for UUID "%s".', $message->uuid));
            }

            [$imageData, $mimeType] = $this->imageResizer->resizeToBase64($this->projectDir.'/'.$file['path']);

            $raw = $this->gateway->getProvider($prompt->getProvider())->chat(
                $this->buildVisionPrompt($prompt->getVisionSystemPrompt(), $prompt->getPrompt()),
                $prompt->getModel() ?: null,
                $prompt->getTemperature(),
                $prompt->getMaxTokens(),
                $imageData,
                $mimeType,
            );

            $aiData = $this->parseJson($raw);

            if (null === $aiData) {
                $truncated = mb_strlen($raw) > 0 && !str_ends_with(trim($raw), '}');

                $this->logger->error(sprintf(
                    'Loki AI Vision: Could not parse AI response as JSON for file "%s"%s. Raw response: %s',
                    $file['path'],
                    $truncated ? ' (response appears truncated — increase maxTokens)' : '',
                    $raw,
                ));

                $job = $job->markFailed([$truncated ? 'loki.vision.error_truncated' : 'loki.vision.error_json']);
                $this->jobs->persist($job);

                return;
            }

            $languages = array_filter(array_map('trim', explode(',', $prompt->getVisionLanguages())));

            if (empty($languages)) {
                $languages = ['de'];
            }

            $rawMeta = $file['meta'] ?? '';

            if (\is_resource($rawMeta)) {
                $rawMeta = stream_get_contents($rawMeta);
            }

            $meta = StringUtil::deserialize($rawMeta, true) ?: [];

            foreach ($languages as $lang) {
                $existing = $meta[$lang] ?? [];

                if (empty($existing['title'])) {
                    $existing['title'] = $aiData['title'] ?? '';
                }

                if (empty($existing['alt'])) {
                    $existing['alt'] = $aiData['alt'] ?? '';
                }

                $meta[$lang] = $existing;
            }

            $this->connection->createQueryBuilder()
                ->update('tl_files')
                ->set('meta', ':meta')
                ->where('uuid = :uuid')
                ->setParameter('meta', serialize($meta))
                ->setParameter('uuid', $file['uuid'])
                ->executeQuery()
            ;

            $job = $job->markCompleted();
            $this->jobs->persist($job);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Loki AI Vision: %s', $e->getMessage()), ['exception' => $e]);

            $job = $job->markFailed([$e->getMessage()]);
            $this->jobs->persist($job);
        }
    }

    private function buildVisionPrompt(string|null $systemPrompt, string|null $userContext): string
    {
        $instruction = preg_replace('/^[ \t]+/m', '', trim($systemPrompt ?: self::DEFAULT_SYSTEM_PROMPT));
        $userContext  = trim($userContext ?? '');

        if ('' === $userContext) {
            return $instruction;
        }

        return $userContext."\n\n".$instruction;
    }

    /**
     * @return array<string, string>|null
     */
    private function parseJson(string $raw): ?array
    {
        $decoded = json_decode(trim($raw), true);

        if (\is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[^{}]*\}/s', $raw, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (\is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
