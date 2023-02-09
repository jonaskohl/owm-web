<?php

require_once __DIR__ . "/inc/httputil.inc.php";
$twig = require_once __DIR__ . "/inc/common.inc.php";

$sha = $_GET["sha"] ?? null;
if ($sha === null || preg_match("~^[a-f0-9]{40}$~", $sha) === false) {
    http_response_code(404);
    exit;
}

$url = "https://api.github.com/repos/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/commits/".urlencode($sha ?? "");

// header("X-Debug-Url: $url");

$cacheFile = ".cache/commits.$sha";

if (!is_dir(dirname($cacheFile))) mkdir(dirname($cacheFile), 0777, true);

$opts = [
    "http" => [
        "ignore_errors" => true,
        "method" => "GET",
        "header" => "User-Agent: OWM Website\r\nAuthorization: token " . $_ENV["GITHUB_TOKEN"]
    ]
];

$context = stream_context_create($opts);

if (is_file($cacheFile)) {
    $data = json_decode(file_get_contents($cacheFile));
} else {
    $contents = file_get_contents($url, false, $context);
    $data = json_decode($contents);
    $status_line = $http_response_header[0];
    preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
    $status = intval($match[1]);
    if ($status === 200) {
        file_put_contents($cacheFile, $contents);
    } else {
        http_response_code($status);
        exit;
    }
}

header("Content-Type: application/json");
echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
