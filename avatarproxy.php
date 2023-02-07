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

$result = file_get_contents($url, false, $context);

foreach ($http_response_header as $h)
    header($h);
header("X-OWM-IsProxy: ?1");
echo $result;
