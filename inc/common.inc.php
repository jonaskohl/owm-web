<?php

require_once __DIR__ . "/../vendor/autoload.php";

function getenv_proxy($arg) {
    if (function_exists("apache_getenv"))
        return apache_getenv($arg);
    return getenv($arg);
}

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$twigOpts = [];
$isProduction = $_ENV["ENVIRONMENT"] === "prod";
if ($isProduction)
    $twigOpts["cache"] = __DIR__ . "/../.cache/twig";
else
    $twigOpts["debug"] = true;
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../template');
$twig = new \Twig\Environment($loader, $twigOpts);
if (!$isProduction)
    $twig->addExtension(new \Twig\Extension\DebugExtension());
$twig->addFunction(
    new \Twig\TwigFunction('getenv', function($key) {
        return $_ENV[$key] ?? null;
    })
);
$twig->addFunction(
    new \Twig\TwigFunction('owm_truncate_string', function($str, $len, $ellipsis = "...") {
        if (mb_strlen($str) < $len) return $str;
        return mb_substr($str, 0, $len) . $ellipsis;
    })
);
$twig->addFunction(
    new \Twig\TwigFunction('owm_adjust_brightness', function($hexCode, $adjustPercent) {
        $hexCode = ltrim($hexCode, '#');
        if (strlen($hexCode) == 3)
            $hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
        $hexCode = array_map('hexdec', str_split($hexCode, 2));
        foreach ($hexCode as & $color) {
            $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
            $adjustAmount = ceil($adjustableLimit * $adjustPercent);
            $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
        }
        return implode($hexCode);
    })
);
$twig->addFunction(
    new \Twig\TwigFunction('owm_get_foreground_color', function($rgbHex) {
        $r = hexdec(substr($rgbHex, 0, 2)) / 255;
        $g = hexdec(substr($rgbHex, 2, 2)) / 255;
        $b = hexdec(substr($rgbHex, 4, 2)) / 255;
        $l = (.2126 * $r + .7152 * $g + .0722 * $b);
        return $l > 0.5 ? "black" : "white";
    })
);
$twig->addFilter(new \Twig\TwigFilter('ucwords', 'ucwords'));
$twig->addFilter(new \Twig\TwigFilter('values', 'array_values'));

// if ($_ENV["ENVIRONMENT"] !== "prod") {
//     echo "<!--[debug: ENVIRONMENT=$_ENV[ENVIRONMENT]]-->\n";
// }

$refFile = __DIR__ . '/../.git/refs/heads/main';
$gitHash = is_file($refFile) ? trim(file_get_contents($refFile)) : null;
$_ENV["GIT_HASH"] = $gitHash;

$__http_host = getenv_proxy("HTTP_HOST");
if ($__http_host === false) $__http_host = $_SERVER["HTTP_HOST"];
$__request_uri = getenv_proxy("REQUEST_URI");
if ($__request_uri === false) $__request_uri = $_SERVER["REQUEST_URI"];
$_ENV["FULL_URL_PREFIX"] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$__http_host";
$_ENV["FULL_URL"] = "$_ENV[FULL_URL_PREFIX]$__request_uri";
unset($__http_host, $__request_uri);

return $twig;
