<?php

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

if (empty($_GET["url"])) {
    http_response_code(400);
    echo "No URL given";
    exit;
}

$url = $_GET["url"];

if (!preg_match('~^https?://avatars.githubusercontent.com/.*~', $url)) {
    http_response_code(400);
    echo "Wrong URL given";
    exit;
}

$context = stream_context_create([
    'http' => [
        'headers' => getallheaders(),
        'ignore_errors' => true
    ]
]);

$tempFile = __DIR__ . "/.cache/avatar_" . bin2hex(random_bytes(10)) . ".tmp.png";
$cacheFile = __DIR__ . "/.cache/avatar_" . hash("sha1", $url) . ".png";

if (!is_file($cacheFile) || time() - filemtime($cacheFile) > 15*60) {
    try {
        copy($url, $tempFile, $context);
    
        $base = new Imagick($tempFile);
        $mask = new Imagick(__DIR__ . "/img/owm_circle_mask_16.png");
    
        $base->compositeImage($mask, Imagick::COMPOSITE_COPYOPACITY, 0, 0);
        $base->writeImage($cacheFile);
    } finally {
        unlink($tempFile);
    }
}

header("Content-Type: image/png");
header("Content-Length: " . filesize($cacheFile));
$seconds_to_cache = 15*60;
$ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
header("Expires: $ts");
header("Pragma: cache");
header("Cache-Control: max-age=$seconds_to_cache");
readfile($cacheFile);
