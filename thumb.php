<?php
// Thumbnail on-demand con cache. Uso: thumb.php?p=studio/imm/.../b1.jpg&w=600
$p = (string)($_GET['p'] ?? '');
$w = min(1600, max(80, (int)($_GET['w'] ?? 600)));

// sicurezza: niente .., solo cartelle consentite, relative a /new/
$p = str_replace(['..', "\0"], '', $p);
$p = ltrim($p, '/');
if (!(strpos($p, 'studio/imm/') === 0 || strpos($p, 'MAT/') === 0)) { http_response_code(403); exit; }

$src = '/var/www/html/new/' . $p;
if (!is_file($src)) { http_response_code(404); exit; }

$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0777, true);
$cache = $cacheDir . '/' . md5($p . '|' . $w) . '.jpg';

function out_jpeg($file) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($file);
    exit;
}

if (is_file($cache) && filemtime($cache) >= filemtime($src)) out_jpeg($cache);

$info = @getimagesize($src);
if (!$info) { header('Content-Type: ' . mime_content_type($src)); readfile($src); exit; }
$ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
switch ($ext) {
    case 'png':  $im = @imagecreatefrompng($src);  break;
    case 'gif':  $im = @imagecreatefromgif($src);  break;
    case 'webp': $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false; break;
    default:     $im = @imagecreatefromjpeg($src);
}
if (!$im) { header('Content-Type: ' . $info['mime']); readfile($src); exit; }

$sw = imagesx($im); $sh = imagesy($im);
$scale = min($w / $sw, 1);
$dw = max(1, (int)round($sw * $scale));
$dh = max(1, (int)round($sh * $scale));
$dst = imagecreatetruecolor($dw, $dh);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, $dw, $dh, $white);
imagecopyresampled($dst, $im, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
@imagejpeg($dst, $cache, 80);
imagedestroy($dst);
imagedestroy($im);

if (is_file($cache)) out_jpeg($cache);
// fallback se la cache non è scrivibile
header('Content-Type: image/jpeg');
imagejpeg(imagecreatefromjpeg($src));
