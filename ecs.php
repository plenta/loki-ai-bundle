<?php

declare(strict_types=1);

/*
 * @package       Customer
 * @copyright     Copyright (c) 2024, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @license       commercial
 */

use Contao\EasyCodingStandard\Fixer\NoLineBreakBetweenMethodArgumentsFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use SlevomatCodingStandard\Sniffs\Namespaces\ReferenceUsedNamesOnlySniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $date = date('Y');

    $header = <<<EOF
    Loki AI Bundle for Contao Open Source CMS
    
    @copyright     Copyright (c) $date, Plenta.io
    @author        Plenta.io <https://plenta.io>
    @link          https://github.com/plenta/
    EOF;

    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, [
        'header' => $header,
    ]);

    $ecsConfig->skip([
        ReferenceUsedNamesOnlySniff::class => [
            '*/Entity/*',
        ],
        NoLineBreakBetweenMethodArgumentsFixer::class,
    ]);
};

/*return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/contao',
        __DIR__.'/src',
        // __DIR__.'/tests',
        __DIR__.'/ecs.php',
    ])
    /*->withSkip([
        ReferenceUsedNamesOnlySniff::class => [
            'config/contao.php',
        ],
    ]) //end comment
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => $header,
    ])
    ->withParallel()
    ->withCache(sys_get_temp_dir().'/ecs/ecs')
;*/
