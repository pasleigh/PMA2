<?php
//directory to the staff directory
if (session_status() === PHP_SESSION_NONE) {
    //ini_set('session.name', getenv('SESSION_NAME') ?: 'PMA');
    session_start();
}
$STUDENT_DIR_VAL = __DIR__;
$BASE_DIR_VAL    = dirname(__DIR__);
$STAFF_DIR_VAL   = dirname(__DIR__) . '/staff';
$ADMIN_DIR_VAL   = dirname(__DIR__) . '/staff/admin2';
$PROPS_DIR_VAL   = dirname(__DIR__) . '/staff/proposals';


if (!function_exists('get_server_username')) {
    function get_server_username()
    {
        // Returns the username from $_SERVER, stripping domain suffix after '@'
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