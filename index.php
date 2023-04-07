<?php

$twig = require_once __DIR__ . "/inc/common.inc.php";
require_once __DIR__ . "/inc/httputil.inc.php";
require_once __DIR__ . "/inc/contenttype.inc.php";

use League\CommonMark\GithubFlavoredMarkdownConverter;

$path = urldecode(strtok($_SERVER["REQUEST_URI"], "?"));

$routes = [];

function get($path, $closure) {
    global $routes;
    $routes[] = [$path, "GET", $closure];
}

function post($path, $closure) {
    global $routes;
    $routes[] = [$path, "POST", $closure];
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

get("/", function() use ($twig) {
    return $twig->render('index.twig', [
        "g_owm" => [
            "http" => [
                "content_type" => OWM_HTTP_CURRENT_CONTENT_TYPE
            ]
        ]
    ]);
});

get("/source", function() use ($twig) {
    $url = "https://api.github.com/repos/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/commits?per_page=100&sha=" . urlencode($_ENV["REPO_BRANCH"]);
    $CACHE_FILE = ".cache/commits";

    if (!is_dir(dirname($CACHE_FILE))) mkdir(dirname($CACHE_FILE), 0777, true);

    $opts = [
        "http" => [
            "ignore_errors" => true,
            "method" => "GET",
            "header" => "User-Agent: OWM Website\r\nAuthorization: token " . $_ENV["GITHUB_TOKEN"]
        ]
    ];

    $context = stream_context_create($opts);

    if (is_file($CACHE_FILE) && time() - filemtime($CACHE_FILE) < 15*60) {
        $data = json_decode(file_get_contents($CACHE_FILE), true);
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
        file_put_contents($CACHE_FILE, $contents);
    }

    $commits = ($data["message"] ?? null) === "Not Found" ? [] : $data;

    return $twig->render("source.twig", [
        "g_owm" => [
            "http" => [
                "content_type" => OWM_HTTP_CURRENT_CONTENT_TYPE
            ]
        ],
        "commits" => $commits,
    ]);
});

get("/source/ajax", function() {
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
    return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
});

get("/issues", function() use ($twig) {
    $url = "https://api.github.com/repos/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/issues?state=all&per_page=100";

    $CACHE_FILE = ".cache/issues";

    if (!is_dir(dirname($CACHE_FILE))) mkdir(dirname($CACHE_FILE), 0777, true);

    $opts = [
        "http" => [
            "ignore_errors" => true,
            "method" => "GET",
            "header" => "User-Agent: OWM Website\r\nAuthorization: token " . $_ENV["GITHUB_TOKEN"]
        ]
    ];

    $context = stream_context_create($opts);

    if (is_file($CACHE_FILE) && time() - filemtime($CACHE_FILE) < 15*60) {
        $data = json_decode(file_get_contents($CACHE_FILE));
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
        file_put_contents($CACHE_FILE, json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    $issues = ($data["message"] ?? null) == "Not Found" ? [] : $data;

    return $twig->render("issues.twig", [
        "g_owm" => [
            "http" => [
                "content_type" => OWM_HTTP_CURRENT_CONTENT_TYPE
            ]
        ],
        "issues" => $issues,
        "onlyshow" => ($_GET["only_show"] ?? null)
    ]);
});

get("/download", function() use ($twig) {
    $converter = new GithubFlavoredMarkdownConverter([
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ]);
    
    $url = "https://api.github.com/repos/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/releases";
    
    $CACHE_FILE = ".cache/releases";
    
    if (!is_dir(dirname($CACHE_FILE))) mkdir(dirname($CACHE_FILE), 0777, true);
    
    $opts = [
        "http" => [
            "ignore_errors" => true,
            "method" => "GET",
            "header" => "User-Agent: OWM Website\r\nAuthorization: token " . $_ENV["GITHUB_TOKEN"]
        ]
    ];
    
    $context = stream_context_create($opts);
    
    if (is_file($CACHE_FILE) && time() - filemtime($CACHE_FILE) < 15*60) {
        $data = json_decode(file_get_contents($CACHE_FILE));
    } else {
        $contents = file_get_contents($url, false, $context);
        $data = json_decode($contents);
        file_put_contents($CACHE_FILE, $contents);
    }
    
    $releases = ($data->message ?? null) == "Not Found" ? [] : $data;
    
    return $twig->render('download.twig', [
        "g_owm" => [
            "http" => [
                "content_type" => OWM_HTTP_CURRENT_CONTENT_TYPE
            ]
        ],
        "releases" => $releases,
        "converter" => $converter,
    ]);
});

get("/avatarproxy", function() {
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
            if ($base->getImageWidth() !== 16 && $base->getImageHeight() !== 16)
                $base->resizeImage(16, 16, Imagick::FILTER_QUADRATIC, 1);
            $base->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
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
});

get("/wiki", function() {
    header("Location: https://github.com/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/wiki");
});

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$pathMatched = false;
$methodMatched = false;
$lastMatchedRoute = null;

foreach ($routes as $route) {
    $route_path = $route[0];
    $route_method = count($route) > 2 ? $route[1] : "GET";
    $route_function = count($route) > 2 ? $route[2] : $route[1];
    $this_path_matches = preg_match('~^'.$route_path.'$~', $path);

    if ($this_path_matches) {
        $pathMatched = true;
        $lastMatchedRoute = $route;
    }

    if ($this_path_matches && $route_method === $_SERVER["REQUEST_METHOD"]) {
        $methodMatched = true;
        break;
    }
}

if (!$pathMatched) {
    http_response_code(404);
    echo $twig->render('404.twig', [
        "g_owm" => [
            "http" => [
                "content_type" => OWM_HTTP_CURRENT_CONTENT_TYPE
            ]
        ]
    ]);
} elseif ($pathMatched && !$methodMatched) {
    http_response_code(405);
    header("Content-Type: text/plain");
    echo "Cannot $_SERVER[REQUEST_METHOD] $_SERVER[REQUEST_URI]";
} else echo (count($lastMatchedRoute) > 2 ? $lastMatchedRoute[2] : $lastMatchedRoute[1])();
