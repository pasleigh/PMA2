<?php
//directory to the staff directory
//if (array_key_exists('BASE_DIR', $_COOKIE)) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $PROPS_DIR_VAL   = __DIR__;
    $STAFF_DIR_VAL   = dirname(__DIR__);
    $BASE_DIR_VAL    = dirname(dirname(__DIR__));
    $STUDENT_DIR_VAL = dirname(dirname(__DIR__)) . '/student';
    $ADMIN_DIR_VAL   = dirname(__DIR__) . '/admin2';
