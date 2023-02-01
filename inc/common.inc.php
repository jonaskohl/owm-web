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
        return getenv($key);
    })
);
return $twig;
