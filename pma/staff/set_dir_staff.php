<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$STAFF_DIR_VAL = __DIR__;
$BASE_DIR_VAL = dirname(__DIR__);
$STUDENT_DIR_VAL = dirname(__DIR__) . '/student';
$ADMIN_DIR_VAL = __DIR__ . '/admin2';
$PROPS_DIR_VAL = __DIR__ . '/proposals';

if (!function_exists('base_url')) {
    function base_url($atRoot = false, $atCore = false, $parse = false) {
        if (isset($_SERVER['HTTP_HOST'])) {
            $http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $hostname = $_SERVER['HTTP_HOST'];
            $dir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

            $core = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__))), 0, PREG_SPLIT_NO_EMPTY);
            $core = $core[0];

            $tmplt = $atRoot ? ($atCore ? '%s://%s/%s/' : '%s://%s/') : ($atCore ? '%s://%s/%s/' : '%s://%s%s');
            $end = $atRoot ? ($atCore ? $core : $hostname) : ($atCore ? $core : $dir);
            $base_url = sprintf($tmplt, $http, $hostname, $end);
        } else {
            $base_url = 'http://localhost/';
        }

        if ($parse) {
            $base_url = parse_url($base_url);
            if (isset($base_url['path'])) {
                if ($base_url['path'] == '/') {
                    $base_url['path'] = '';
                }
            }
        }

        return $base_url;
    }
}
$DEFAULT_ROOT_URL = 'https://eps-apps.leeds.ac.uk/';
$DEFAULT_ROOT_URL = base_url(true);
$DEFAULT_ERR = "<BR>Try again with this link: <A href='$DEFAULT_ROOT_URL'>$DEFAULT_ROOT_URL</A> and select your School from the <strong>Projects</strong> section.<BR>";

if (!function_exists('get_server_username')) {
    function get_server_username() {
        // Returns the username from $_SERVER
        $username = "username_not_set";
        if (array_key_exists('REMOTE_USER', $_SERVER)) {
            $username = $_SERVER['REMOTE_USER'];
            if (str_contains($username, "@")) {
                $pos = strpos($username, "@");
                $username = substr($username, 0, $pos);
            }
        }elseif(array_key_exists('LOCAL_USER', $_SERVER)){
            $username = $_SERVER['LOCAL_USER'];
        }
        return $username;
    }
}
