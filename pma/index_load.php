<?php
$school = "";
if(array_key_exists('school', $_GET)) {
    $school = $_GET['school'];
}
$debug = false;
if(array_key_exists('debug', $_GET)) {
    $debug = true;
}

include("./config_main.php");
//include("./config_main_test.php");

include './index.php';

?>

