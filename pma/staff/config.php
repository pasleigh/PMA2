<?php

$school = $_COOKIE['PMA_SCHOOL'];

include("$STAFF_DIR_VAL/../config_main.php");
//include("$STAFF_DIR_VAL/../config_main_test.php");

if($debug){
    echo("__ADMIN_DIR__ ".__ADMIN_DIR__."<BR>");
    echo("__STAFF_DIR__ ".__STAFF_DIR__."<BR>");
    echo("__STUDENT_DIR__ ".__STUDENT_DIR__."<BR>");
    echo("__PROPS_DIR__ ".__PROPS_DIR__."<BR>");
}
