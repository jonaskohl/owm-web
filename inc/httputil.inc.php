<?php


if (!function_exists("http_parse_headers")) {
    function http_parse_headers(string $headers): array {
        $data = array_values(array_filter(array_map(function($ln) {
            if (strpos($ln, ":") === false)
                return null;
            return preg_split('~:\s*~', $ln, 2);
        }, preg_split("~(*BSR_ANYCRLF)\R~", trim($headers))), function($e) {
            return $e !== null;
        }));
        return array_combine(array_column($data, 0), array_column($data, 1));
    }
}

function parse_link_header_value(string $value): array {
    $data = array_values(array_filter(array_map(function($e) {
        $params = preg_split("~;\s*~", $e);
        $url = array_shift($params);
        if (
            strpos($url, "<") !== 0 ||
            strpos($url, ">") !== strlen($url) - 1
        ) return null;
        $url = substr($url, 1, strlen($url) - 2);
        $params = array_values(array_filter(array_map(function($p) {
            if (strpos($p, "=") === false)
                return null;
            $vals = explode("=", $p, 2);
            if (
                strpos($vals[1], '"') !== 0 ||
                strpos($vals[1], '"') !== strlen($vals[1]) - 1
            ) $vals[1] = substr($vals[1], 1, strlen($vals[1]) - 2);
            return $vals;
        }, $params), function($p) {
            return $p !== null;
        }));
        $params = array_combine(array_column($params, 0), array_column($params, 1));
        $rel = $params["rel"] ?? null;
        return [$rel, [
            "url" => $url,
            "params" => $params
        ]];
    }, preg_split("~,\s*~", $value)), function($e) {
        return $e !== null;
    }));
    return array_combine(array_column($data, 0), array_column($data, 1));
}
