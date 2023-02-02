<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$twigOpts = [];
if ($_ENV["ENVIRONMENT"] === "prod")
    $twigOpts['cache'] = __DIR__ . '/../.cache/twig';
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../template');
$twig = new \Twig\Environment($loader, $twigOpts);
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

if ($_ENV["ENVIRONMENT"] !== "prod") {
    echo "<!--[debug: ENVIRONMENT=$_ENV[ENVIRONMENT]]-->\n";
}

$refFile = __DIR__ . '/../.git/refs/heads/main';
$gitHash = is_file($refFile) ? trim(file_get_contents($refFile)) : null;
$_ENV["GIT_HASH"] = $gitHash;

return $twig;
