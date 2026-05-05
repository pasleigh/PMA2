<?php

//directory to the staff directory
//if (array_key_exists('BASE_DIR', $_COOKIE)) {
if (session_status() === PHP_SESSION_NONE) {
    //ini_set('session.name', getenv('SESSION_NAME') ?: 'PMA');
    session_start();
}
$PROPS_DIR_VAL = __DIR__;
$STAFF_DIR_VAL = dirname(__DIR__);
$BASE_DIR_VAL = dirname(dirname(__DIR__));
$STUDENT_DIR_VAL = dirname(dirname(__DIR__)) . '/student';
$ADMIN_DIR_VAL = dirname(__DIR__) . '/admin2';

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
