<?php

$twig = require_once __DIR__ . "/inc/common.inc.php";

$url = "https://api.github.com/repos/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/issues";

const CACHE_FILE = ".cache/issues";

if (!is_dir(dirname(CACHE_FILE))) mkdir(dirname(CACHE_FILE), 0777, true);

$opts = [
    "http" => [
        "ignore_errors" => true,
        "method" => "GET",
        "header" => "User-Agent: OWM Website\r\nAuthorization: token " . $_ENV["GITHUB_TOKEN"]
    ]
];

$context = stream_context_create($opts);

if (is_file(CACHE_FILE) && time() - filemtime(CACHE_FILE) < 15*60) {
    $data = json_decode(file_get_contents(CACHE_FILE));
} else {
    $contents = file_get_contents($url, false, $context);
    $data = json_decode($contents);
    file_put_contents(CACHE_FILE, $contents);
}

$issues = ($data["message"] ?? null) == "Not Found" ? [] : $data;

echo $twig->render("issues.twig", [
    "issues" => $issues,
]);
