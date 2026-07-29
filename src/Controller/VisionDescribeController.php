<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

namespace Plenta\LokiAiBundle\Controller;

use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Job\Jobs;
use Contao\Message;
use Plenta\LokiAiBundle\Message\VisionDescribeMessage;
use Plenta\LokiAiBundle\Repository\PromptRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('%contao.backend.route_prefix%/_loki/vision', defaults: ['_scope' => 'backend'])]
class VisionDescribeController extends AbstractBackendController
{
    public function __construct(
        private readonly Jobs $jobs,
        private readonly MessageBusInterface $messageBus,
        private readonly PromptRepository $promptRepository,
    ) {
    }

    #[Route('/describe/{promptId}/{uuid}', name: 'loki_vision_describe')]
    public function describe(int $promptId, string $uuid, Request $request): Response
    {
        $referer = $request->query->get('redirect') ?? $this->generateUrl('contao_backend');

        $prompt = $this->promptRepository->find($promptId);

        if (null === $prompt || !$prompt->isPublished() || !$prompt->isVisionPrompt()) {
            Message::addError('Vision prompt not found or not published.');

            return new RedirectResponse($referer);
        }

        $job = $this->jobs->createJob('loki_vision');
        $this->jobs->persist($job);

        $this->messageBus->dispatch(new VisionDescribeMessage($promptId, $uuid, $job->getUuid()));

        return new RedirectResponse($referer);
    }
}
