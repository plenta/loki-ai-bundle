<?php

declare(strict_types=1);

/*
 * Loki AI Bundle for Contao Open Source CMS
 *
 * @copyright     Copyright (c) 2026, Plenta.io
 * @author        Plenta.io <https://plenta.io>
 * @link          https://github.com/plenta/
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

/** @var ContainerBuilder $container */
$fileSystem = new Filesystem();
$projectDir = $container->getParameter('kernel.project_dir');

if ($fileSystem->exists($projectDir.'/web')) {
    $webDir = 'web'; // backwards compatibility
} else {
    $webDir = 'public';
}

$container->loadFromExtension('framework', [
    'assets' => [
        'packages' => [
            'lokiai' => [
                'json_manifest_path' => '%kernel.project_dir%/'.$webDir.'/bundles/lokiai/manifest.json',
            ],
        ],
    ],
]);
