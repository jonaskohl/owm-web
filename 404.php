<?php

http_response_code(404);
$twig = require_once __DIR__ . "/inc/common.inc.php";
echo $twig->render('404.twig');
