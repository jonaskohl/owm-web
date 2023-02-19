<?php

// text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8

function http_parse_accept_header_value(string $accept_header_value): array {
    $accept_header_value = trim($accept_header_value);
    $entries = preg_split('~,\s*~', $accept_header_value);
    $data = [];
    foreach ($entries as $entry) {
        $data_entry = [
            "q" => 1
        ];
        $parts = preg_split('~;\s*~', $entry);
        $content_type = array_shift($parts);
        if (count($parts) > 0) {
            foreach ($parts as $part) {
                $subparts = explode("=", $part, 2);
                $data_entry[$subparts[0]] = $subparts[1];
            }
        }
        $data[$content_type] = $data_entry;
    }
    return $data;
}

function http_is_content_type_accepted(string $content_type, bool $match_wildcard_type = false): bool {
    $http_accept = http_parse_accept_header_value($_SERVER["HTTP_ACCEPT"]);
    list($mime_type, $mime_subtype) = explode("/", $content_type, 2);

    if ($mime_subtype === "*") {
        return false;
    }
    
    foreach ($http_accept as $a_content_type=>$a_data) {
        if ($match_wildcard_type && $a_content_type === "*/*") return true;
        list($a_mime_type, $a_mime_subtype) = explode("/", $a_content_type, 2);
        if ($mime_type === $a_mime_type && ($a_mime_subtype === "*" || $a_mime_subtype === $mime_subtype)) {
            return true;
        }
    }

    return false;
}

if (http_is_content_type_accepted("application/xhtml+xml")) {
    define("OWM_HTTP_CURRENT_CONTENT_TYPE", "application/xhtml+xml");
} else {
    define("OWM_HTTP_CURRENT_CONTENT_TYPE", "text/html");
}

header("Content-Type: " . OWM_HTTP_CURRENT_CONTENT_TYPE . "; charset=UTF-8");
