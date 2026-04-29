<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
(@include_once('./utility_functions.php')) or die('Cannot find file utility_functions.php<BR>');
(@include_once('./adminusers.php')) or die('Cannot find this file to include: adminusers.php<BR>');

if (!function_exists('pma_log_db_error')) {
    function pma_log_db_error(PDOException $ex, string $context = ''): void {
        $label = $context ? " [$context]" : '';
        error_log('PMA database error' . $label . ': ' . $ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
    }
}

if (!function_exists('pma_log_error')) {
    function pma_log_error(string $detail, string $context = ''): void {
        $label = $context ? " [$context]" : '';
        error_log('PMA error' . $label . ': ' . $detail);
        echo "An error occurred. Please contact the system administrator.<BR>\n";
    }
}

if (array_key_exists('REMOTE_USER', $_SERVER) || array_key_exists('LOCAL_USER', $_SERVER)){
    $this_staff_username = get_server_username();
    $admin_level = GetSuperAdminLevel($db, $this_staff_username) ;

    if ($admin_level > 1) {
        /* Make connection to database */
        try {
            $db = new PDO("mysql:host=$hostname; dbname=$dbName; charset=utf8", $dbusername, $password);
        } catch (PDOException $ex) {
            error_log('PMA database connection error [ADMINconnect_pdo]: ' . $ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } else {
        echo('An error has occured when trying to verify the Admin user status.<BR>');
        echo("<A href='../index.php'>Go back to the main index</A>");
        exit();
    }
} else {
    echo('An error has occured when trying to verify the Admin user status.<BR> ');
    echo('Cannot find your username so cannot verify whether admin or not.<BR> ');

    echo("<A href='../index.php'>Go back to the main index</A>");
    exit();
}
