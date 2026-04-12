<?php

// Prefer session value; fall back to cookie only if session not yet populated
$school = $_SESSION['PMA_SCHOOL'] ?? ($_COOKIE['PMA_SCHOOL'] ?? '');

include($STAFF_DIR_VAL . '/../config_main.php');
