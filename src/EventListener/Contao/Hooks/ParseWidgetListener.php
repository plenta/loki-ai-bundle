<?php

declare(strict_types=1);

/**
 * @package       Customer
 * @copyright     Copyright (c) 2025, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @license       commercial
 */

namespace Plenta\LokiAiBundle\EventListener\Contao\Hooks;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageModel;
use Contao\Widget;
use Plenta\LokiAiBundle\Repository\FieldRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment as TwigEnvironment;

class ParseWidgetListener
{
    public function __construct(
        protected ScopeMatcher $scopeMatcher,
        protected RequestStack $requestStack,
        protected FieldRepository $fieldRepository,
        protected TwigEnvironment $twigEnvironment,
        protected Packages $packages,
    ) {
    }

    #[AsHook(hook: 'parseWidget')]
    public function onParseWidget(string $buffer, Widget $widget)
    {
        if (!$widget->dataContainer) {
            return $buffer;
        }

        if (!$this->scopeMatcher->isBackendRequest($this->requestStack->getCurrentRequest())) {
            return $buffer;
        }

        $fields = $this->fieldRepository->findByTableNameAndField($widget->dataContainer->table, $widget->name);

        if (!$fields) {
            return $buffer;
        }

        $GLOBALS['TL_JAVASCRIPT']['lokiBackend'] = $this->packages->getUrl('lokiai/backend.js', 'lokiai');
        $GLOBALS['TL_CSS']['lokiButton'] = $this->packages->getUrl('lokiai/button.css', 'lokiai');

        $text = '';

        foreach ($fields as $field) {
            if ('tl_page' === $field->getTableName() && $field->getParent()->getRootPage()) {
                $page = PageModel::findByPk($widget->dataContainer->id)->loadDetails();

                if (!\in_array($field->getParent()->getRootPage(), $page->trail, true)) {
                    continue;
                }
            }

            $text .= $this->twigEnvironment->render('@Contao/backend/prompt_button.html.twig', [
                'widget' => $widget,
                'field' => $field,
                'objectId' => $widget->dataContainer->id,
            ]);
        }

        return str_replace('</h3>', $text.'</h3>', $buffer);
    }
}
