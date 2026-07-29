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

class ImageResizer
{
    private const MAX_WIDTH = 600;

    /**
     * Returns [base64-encoded JPEG, 'image/jpeg'] scaled to MAX_WIDTH.
     * Supports JPEG, PNG, GIF, WebP.
     *
     * @return array{0: string, 1: string}
     */
    public function resizeToBase64(string $absolutePath): array
    {
        if (!\extension_loaded('gd')) {
            throw new \RuntimeException('PHP extension "gd" is required for Vision support.');
        }

        $imageInfo = @getimagesize($absolutePath);

        if (false === $imageInfo) {
            throw new \RuntimeException(sprintf('Cannot read image: %s', $absolutePath));
        }

        [$origWidth, $origHeight, $type] = $imageInfo;

        $source = match ($type) {
            \IMAGETYPE_JPEG => imagecreatefromjpeg($absolutePath),
            \IMAGETYPE_PNG  => imagecreatefrompng($absolutePath),
            \IMAGETYPE_GIF  => imagecreatefromgif($absolutePath),
            \IMAGETYPE_WEBP => imagecreatefromwebp($absolutePath),
            default => throw new \RuntimeException(sprintf('Unsupported image type (type=%d): %s', $type, $absolutePath)),
        };

        if (false === $source) {
            throw new \RuntimeException(sprintf('Failed to load image: %s', $absolutePath));
        }

        if ($origWidth > self::MAX_WIDTH) {
            $newWidth  = self::MAX_WIDTH;
            $newHeight = (int) round($origHeight * self::MAX_WIDTH / $origWidth);
            $canvas    = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG/GIF
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($source);
        } else {
            $canvas = $source;
        }

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = ob_get_clean();
        imagedestroy($canvas);

        return [base64_encode($jpeg), 'image/jpeg'];
    }
}
