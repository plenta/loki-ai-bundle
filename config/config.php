<?php

declare(strict_types=1);

/*
 * Propstack extension for Contao Open Source CMS
 *
 * @copyright Copyright (c) 2021-2024, Plenta.io
 *
 * @author Plenta.io <https://plenta.io>
 * @see https://plenta.io
 * @license commercial
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
            'contaopropstack' => [
                'json_manifest_path' => '%kernel.project_dir%/'.$webDir.'/bundles/contaopropstack/manifest.json',
            ],
        ],
    ],
]);
