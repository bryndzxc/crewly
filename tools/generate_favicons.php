<?php

declare(strict_types=1);

// One-time utility script.
// Generates visible favicons from storage/images/crewly_logo.png with a light background.

$root = dirname(__DIR__);
$srcPath = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'crewly_logo.png';
$outFavicon = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'favicon.png';
$outApple = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'apple-touch-icon.png';

if (!is_file($srcPath)) {
    fwrite(STDERR, "Source logo not found: {$srcPath}\n");
    exit(1);
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "GD extension is required to generate favicons.\n");
    exit(1);
}

$src = @imagecreatefrompng($srcPath);
if (!$src) {
    fwrite(STDERR, "Failed to read PNG: {$srcPath}\n");
    exit(1);
}

$srcW = imagesx($src);
$srcH = imagesy($src);
if ($srcW <= 0 || $srcH <= 0) {
    fwrite(STDERR, "Invalid source dimensions.\n");
    exit(1);
}

/**
 * @param resource $src
 */
$render = function ($size) use ($src, $srcW, $srcH) {
    $canvas = imagecreatetruecolor($size, $size);

    // White background for contrast on dark tabs.
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $size, $size, $white);

    // Preserve alpha for the logo itself.
    imagealphablending($canvas, true);

    // Fit logo within the square with padding.
    $padding = (int) max(2, round($size * 0.14));
    $maxW = $size - ($padding * 2);
    $maxH = $size - ($padding * 2);

    $scale = min($maxW / $srcW, $maxH / $srcH);
    $dstW = (int) max(1, floor($srcW * $scale));
    $dstH = (int) max(1, floor($srcH * $scale));

    $dstX = (int) floor(($size - $dstW) / 2);
    $dstY = (int) floor(($size - $dstH) / 2);

    imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);

    return $canvas;
};

$favicon = $render(64);
$apple = $render(180);

if (!imagepng($favicon, $outFavicon, 9)) {
    fwrite(STDERR, "Failed to write {$outFavicon}\n");
    exit(1);
}

if (!imagepng($apple, $outApple, 9)) {
    fwrite(STDERR, "Failed to write {$outApple}\n");
    exit(1);
}

imagedestroy($favicon);
imagedestroy($apple);
imagedestroy($src);

fwrite(STDOUT, "Generated:\n- {$outFavicon}\n- {$outApple}\n");
