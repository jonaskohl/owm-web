<?php

require_once __DIR__ . "/vendor/autoload.php";

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$url = "https://api.github.com/repos/".urlencode($_ENV["REPO_AUTHOR"])."/".urlencode($_ENV["REPO_NAME"])."/commits";

const CACHE_FILE = ".cache/commits";

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

function truncate_string($str, $len, $ellipsis = "...") {
    if (mb_strlen($str) < $len) return $str;
    return mb_substr($str, 0, $len) . $ellipsis;
}

$commits = ($data["message"] ?? null) == "Not Found" ? [] : $data;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
    <meta name="msapplication-navbutton-color" content="#8000d1" />
    <meta name="theme-color" content="#8000d1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Source code &ndash; Open Wii Manager</title>
    <link rel="shortcut icon" href="img/owm.ico" type="image/x-icon" />
    <link rel="stylesheet" href="css/nunito.css" type="text/css" />
    <link rel="stylesheet" href="css/normalize.css" type="text/css" />
    <link rel="stylesheet" href="css/main.css" type="text/css" />
    <script type="text/javascript" src="js/jquery-1.12.4.min.js"></script>
    <script type="text/javascript" src="js/page.js"></script>
</head>
<body>
    <div id="header">
        <div class="wrapper">
            <img src="img/owm_text_web.png" id="logo" alt="Open Wii Manager" width="404" height="64" />
        </div>
    </div>
    <div id="nav">
        <div class="wrapper">
            <button onclick="$('#nav').toggleClass('shown')"><span>Menu</span><i class="menu-icon"></i></button>
            <a href="index.html">Home</a>
            <a href="download.php">Download</a>
            <a href="source.php" class="current">Source code</a>
            <a href="issues.php">Issues</a>
            <a href="wiki.html">Wiki</a>
        </div>
    </div>
    <div id="shadow" class="shadow"></div>
    <div id="main">
        <div class="wrapper">
            <h2>Commit history</h2>
<?php foreach ($commits as $commit): ?>
            <h3><a href="<?= htmlentities($commit->html_url, ENT_XHTML) ?>"><?= htmlentities(truncate_string($commit->commit->message, 80), ENT_XHTML) ?></a></h3>
            <p>Committed on <?= htmlentities($commit->commit->committer->date, ENT_XHTML) ?> by <a href="<?= htmlentities($commit->author->html_url, ENT_XHTML) ?>"><?= htmlentities($commit->commit->author->name, ENT_XHTML) ?></a></p>
<?php endforeach; ?>
        </div>
    </div>
    <div id="footer">
        <div id="footer-shadow" class="shadow"></div>
        <div class="wrapper">
            Open Wii Manager &ndash; A project by <a href="https://jonaskohl.de/" target="_blank">Jonas Kohl</a>
            <div id="footer-links">
                <a href="//github.com/jonaskohl/OpenWiiManager.git" target="_blank" id="github-link">GitHub</a>
            </div>
        </div>
    </div>

    <div id="forkme-container">
        <a href="http://github.com/jonaskohl/OpenWiiManager.git">
            <img src="/img/owm_web_forkme.png" alt="Fork me on GitHub" width="149" height="149" />
        </a>
    </div>
</body>
</html>
