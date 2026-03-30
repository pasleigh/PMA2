<?php
//directory to the staff directory
//if (array_key_exists('BASE_DIR', $_COOKIE)) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $STUDENT_DIR_VAL = __DIR__;
    $BASE_DIR_VAL    = dirname(__DIR__);
    $STAFF_DIR_VAL   = dirname(__DIR__) . '/staff';
    $ADMIN_DIR_VAL   = dirname(__DIR__) . '/staff/admin2';
    $PROPS_DIR_VAL   = dirname(__DIR__) . '/staff/proposals';


if (!function_exists('get_server_username')) {
    function get_server_username()
    {
        // Returns the username from $_SERVER, stripping domain suffix after '@'
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