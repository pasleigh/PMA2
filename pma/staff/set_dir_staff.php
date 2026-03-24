<?php

    $BASE_DIR_VAL = $_COOKIE['BASE_DIR'];
    $STAFF_DIR_VAL = $_COOKIE['STAFF_DIR'];
    $STUDENT_DIR_VAL = $_COOKIE['STUDENT_DIR'];
    $ADMIN_DIR_VAL = $_COOKIE['ADMIN_DIR'];
    $PROPS_DIR_VAL = $_COOKIE['PROPS_DIR'];

if (!function_exists('base_url')) {
    function base_url($atRoot=FALSE, $atCore=FALSE, $parse=FALSE){
        if (isset($_SERVER['HTTP_HOST'])) {
            $http = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $hostname = $_SERVER['HTTP_HOST'];
            $dir =  str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

            $core = preg_split('@/@', str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(dirname(__FILE__))), NULL, PREG_SPLIT_NO_EMPTY);
            $core = $core[0];

            $tmplt = $atRoot ? ($atCore ? "%s://%s/%s/" : "%s://%s/") : ($atCore ? "%s://%s/%s/" : "%s://%s%s");
            $end = $atRoot ? ($atCore ? $core : $hostname) : ($atCore ? $core : $dir);
            $base_url = sprintf( $tmplt, $http, $hostname, $end );
        }
        else $base_url = 'http://localhost/';

        if ($parse) {
            $base_url = parse_url($base_url);
            if (isset($base_url['path'])) if ($base_url['path'] == '/') $base_url['path'] = '';
        }

        return $base_url;
    }
}
$DEFAULT_ROOT_URL = "https://eps-apps.leeds.ac.uk/";
$DEFAULT_ROOT_URL = base_url(TRUE);
$DEFAULT_ERR = "<BR>Try again with this link: <A href='$DEFAULT_ROOT_URL'>$DEFAULT_ROOT_URL</A> and select your School from the <strong>Projects</strong> section.<BR>";

if (!function_exists('get_server_username')) {
    function get_server_username()
    {
        // Returns the username from $_SERVER
        $username = "username not set";
        if (array_key_exists('REMOTE_USER', $_SERVER)) {
            $username = $_SERVER['REMOTE_USER'];
            if (str_contains($username, "@")) {
                $pos = strpos($username, "@");
                $username = substr($username, 0, $pos);
            }
        }
        return $username;
    }
}