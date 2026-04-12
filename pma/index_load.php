<?php
$school = "";
if(array_key_exists('school', $_GET)) {
    $school = $_GET['school'];
}

include("./config_main.php");

include './index.php';

?>

