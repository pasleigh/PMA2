<?php
//directory to the staff directory
//if (array_key_exists('BASE_DIR', $_COOKIE)) {
    $BASE_DIR_VAL = $_COOKIE['BASE_DIR'];
    $STAFF_DIR_VAL = $_COOKIE['STAFF_DIR'];
    $STUDENT_DIR_VAL = $_COOKIE['STUDENT_DIR'];
    $ADMIN_DIR_VAL = $_COOKIE['ADMIN_DIR'];
    $PROPS_DIR_VAL = $_COOKIE['PROPS_DIR'];
    //echo("COOKIE BASE_DIR_VAL: " . $BASE_DIR_VAL . "<BR>\n");
    //echo("COOKIE STAFF_DIR_VAL: " . $STAFF_DIR_VAL . "<BR>\n");
/*
}else{
    // This won't work on the app server or any that has a symlink for the config.php file
    // that means php sees a different file path for that file
    $BASE_DIR_VAL  = "../..";
    $STAFF_DIR_VAL = "..";
    $STUDENT_DIR_VAL = "../../student";
    $ADMIN_DIR_VAL = ".";
    $PROPS_DIR_VAL = $STAFF_DIR_VAL . "/proposals";
}
*/