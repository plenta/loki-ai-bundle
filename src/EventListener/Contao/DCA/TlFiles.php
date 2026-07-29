<?php

declare(strict_types=1);

/**
 * @package       Customer
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @license       commercial
 */

namespace Plenta\LokiAiBundle\EventListener\Contao\DCA;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\FilesModel;
use Contao\Image;
use Contao\StringUtil;
use Plenta\LokiAiBundle\AiProvider\LokiAiGateway;
use Plenta\LokiAiBundle\Repository\PromptRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class TlFiles
{
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct(
        private readonly LokiAiGateway $gateway,
        private readonly RouterInterface $router,
        private readonly PromptRepository $promptRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsCallback(table: 'tl_files', target: 'list.operations.loki_vision.button_callback')]
    public function onVisionButtonCallback(
        array $row,
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
    ): string {
        if (!isset($row['type']) || 'file' !== $row['type']) {
            return '';
        }

        $path = rawurldecode((string) ($row['id'] ?? ''));
        $extension = strtolower(pathinfo($path, \PATHINFO_EXTENSION));

        if (!\in_array($extension, self::SUPPORTED_EXTENSIONS, true)) {
            return '';
        }

        $model = FilesModel::findByPath($path);

        if (null === $model) {
            return '';
        }

        $prompt = $this->promptRepository->findOneBy(['visionPrompt' => true, 'published' => true]);

        if (null === $prompt) {
            return '';
        }

        try {
            $provider = $this->gateway->getProvider($prompt->getProvider());
        } catch (\Exception $e) {
            // TODO
            return '';
        }

        if (!$provider->supportsVision()) {
            return '';
        }

        $url = $this->router->generate('loki_vision_describe', [
            'promptId' => $prompt->getId(),
            'uuid' => StringUtil::binToUuid($model->uuid),
            'redirect' => $this->requestStack->getCurrentRequest()?->getUri(),
        ]);

        return \sprintf(
            '<a href="%s" title="%s"%s>%s</a> ',
            $url,
            StringUtil::specialchars($title),
            $attributes,
            Image::getHtml($icon ?? 'visible.svg', $label),
        );
    }
}
