<?php
//directory to the staff directory
//if (array_key_exists('BASE_DIR', $_COOKIE)) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $PROPS_DIR_VAL   = __DIR__;
    $STAFF_DIR_VAL   = dirname(__DIR__);
    $BASE_DIR_VAL    = dirname(dirname(__DIR__));
    $STUDENT_DIR_VAL = dirname(dirname(__DIR__)) . '/student';
    $ADMIN_DIR_VAL   = dirname(__DIR__) . '/admin2';
    //echo("COOKIE BASE_DIR_VAL: " . $BASE_DIR_VAL . "<BR>\n");
    //echo("COOKIE STAFF_DIR_VAL: " . $STAFF_DIR_VAL . "<BR>\n");
/*

   }else{
    // This won't work on the app server or any that has a symlink for the config.php file
    // that means php sees a different file path for that file
    $BASE_DIR_VAL  = "../..";
    $STAFF_DIR_VAL = "..";
    $STUDENT_DIR_VAL = "../../student";
    $ADMIN_DIR_VAL = $STAFF_DIR_VAL . "/admin2";
    $PROPS_DIR_VAL = ".";
}
*/