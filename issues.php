<?php

require_once __DIR__ . "/inc/httputil.inc.php";
require_once __DIR__ . "/inc/contenttype.inc.php";
$twig = require_once __DIR__ . "/inc/common.inc.php";

$url = "https://api.github.com/repos/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/issues?state=all&per_page=100";

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
    $data = json_decode($contents, true);
    $headers = http_parse_headers(implode("\r\n", $http_response_header));
    while (isset($headers["Link"])) {
        $fields = parse_link_header_value($headers["Link"]);
        if (!isset($fields["next"]))
            break;
        $url = $fields["next"]["url"];
        $contents = file_get_contents($url, false, $context);
        $data = array_merge($data, json_decode($contents, true));
        $headers = http_parse_headers(implode("\r\n", $http_response_header));
    }
    file_put_contents(CACHE_FILE, json_encode($data, JSON_UNESCAPED_SLASHES));
}

$issues = ($data["message"] ?? null) == "Not Found" ? [] : $data;

echo $twig->render("issues.twig", [
    "g_owm" => [
        "http" => [
            "content_type" => OWM_HTTP_CURRENT_CONTENT_TYPE
        ]
    ],
    "issues" => $issues,
    "onlyshow" => ($_GET["only_show"] ?? null)
]);
