<?php

$twig = require_once __DIR__ . "/inc/common.inc.php";
require_once __DIR__ . "/inc/contenttype.inc.php";
echo $twig->render('index.twig', [
    "g_owm" => [
        "http" => [
            "content_type" => OWM_HTTP_CURRENT_CONTENT_TYPE
        ]
    ]
]);
