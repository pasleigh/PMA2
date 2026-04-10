<?php

// Prefer session value; fall back to cookie only if session not yet populated
$school = $_SESSION['PMA_SCHOOL'] ?? ($_COOKIE['PMA_SCHOOL'] ?? '');

include($STAFF_DIR_VAL . '/../config_main.php');

if($debug){
    echo("__ADMIN_DIR__ ".__ADMIN_DIR__."<BR>");
    echo("__STAFF_DIR__ ".__STAFF_DIR__."<BR>");
    echo("__STUDENT_DIR__ ".__STUDENT_DIR__."<BR>");
    echo("__PROPS_DIR__ ".__PROPS_DIR__."<BR>");
}
