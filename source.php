<?php

$twig = require_once __DIR__ . "/inc/common.inc.php";

$url = "https://api.github.com/repos/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/commits?sha=" . urlencode($_ENV["REPO_BRANCH"]);

const CACHE_FILE = ".cache/commits";

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

$commits = ($data->message ?? null) == "Not Found" ? new stdClass() : $data;

foreach ($commits as &$commit) {
    $url = $commit->url;
    $subCacheFile = ".cache/commits." . $commit->sha;
    if (!is_dir(dirname($subCacheFile))) mkdir(dirname($subCacheFile), 0777, true);
    if (is_file($subCacheFile)) {
        $data = json_decode(file_get_contents($subCacheFile));
    } else {
        $contents = file_get_contents($url, false, $context);
        $data = json_decode($contents);
        file_put_contents($subCacheFile, $contents);
    }
    $commit->_details = $data;
}

echo $twig->render("source.twig", [
    "commits" => $commits,
]);
