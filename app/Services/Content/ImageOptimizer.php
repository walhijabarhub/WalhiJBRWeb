<?php

namespace App\Services\Content;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Shrinks uploaded JPEG/PNG/WebP images on the public disk so the longest side is
 * at most MAX_DIMENSION pixels, and applies the EXIF orientation of JPEG photos.
 * The file keeps its path, name and format. Anything unexpected (GIF, unreadable
 * file, very large pixel count that would not fit in PHP's memory limit, GD error)
 * leaves the original untouched.
 */
class ImageOptimizer
{
    public const MAX_DIMENSION = 1920;

    /**
     * GD needs about 4 bytes per pixel, counted against PHP's memory_limit (128 MB in
     * production): 16 MP is ~64 MB plus the resized copy. Larger images are kept as
     * uploaded (the 2 MB upload limit makes them rare).
     */
    private const MAX_PIXELS = 16_000_000;

    private const QUALITY = 82;

    public static function shrink(string $publicPath): void
    {
        try {
            self::process(Storage::disk('public')->path($publicPath));
        } catch (Throwable $e) {
            Log::warning('Image optimisation skipped.', ['path' => $publicPath, 'error' => $e->getMessage()]);
        }
    }

    private static function process(string $file): void
    {
        $info = @getimagesize($file);
        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return;
        }

        [$width, $height, $type] = $info;
        if ($width * $height > self::MAX_PIXELS) {
            return;
        }

        // The production image builds GD without WebP; such uploads are kept as is.
        if ($type === IMAGETYPE_WEBP && ! (function_exists('imagecreatefromwebp') && function_exists('imagewebp'))) {
            return;
        }

        $orientation = $type === IMAGETYPE_JPEG && function_exists('exif_read_data')
            ? (int) (@exif_read_data($file)['Orientation'] ?? 1)
            : 1;
        $scale = min(1, self::MAX_DIMENSION / max($width, $height));

        if ($scale === 1 && $orientation === 1) {
            return;
        }

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG => @imagecreatefrompng($file),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file),
        };
        if (! $source instanceof GdImage) {
            return;
        }

        $image = $scale < 1 ? self::resize($source, $width, $height, $scale) : $source;
        $image = self::orient($image, $orientation);

        $tmp = $file.'.tmp';
        $written = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $tmp, self::QUALITY),
            IMAGETYPE_PNG => imagepng($image, $tmp, 6),
            IMAGETYPE_WEBP => imagewebp($image, $tmp, self::QUALITY),
        };

        if ($written && filesize($tmp) > 0) {
            rename($tmp, $file);
        } else {
            @unlink($tmp);
        }
    }

    private static function resize(GdImage $source, int $width, int $height, float $scale): GdImage
    {
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $target = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $target;
    }

    /** Applies EXIF orientations 3, 6 and 8 (rotations); mirrored variants are rare and left as is. */
    private static function orient(GdImage $image, int $orientation): GdImage
    {
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }
}
