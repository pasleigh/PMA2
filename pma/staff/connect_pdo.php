<?php

/* Make connection to database */

try {
    $db = new PDO("mysql:host=$hostname; dbname=$dbName; charset=utf8", $dbusername, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $ex) {
    error_log('PMA database connection error: ' . $ex->getMessage());
    echo('An error occurred connecting to the database.<BR>');
    echo('Go back to the home page and report this to the system admin<BR>');
    echo("<A href='../index.html'>Back to the home page</a>");
    exit();
}

/**
 * Log a PDO exception privately and show a generic message to the user.
 *
 * @param PDOException $ex      The caught exception.
 * @param string       $context Label for the log entry (function name, file, etc.).
 */
function pma_log_db_error(PDOException $ex, string $context = ''): void {
    $label = $context ? " [$context]" : '';
    error_log('PMA database error' . $label . ': ' . $ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
}

/**
 * Log an internal error detail privately and show a generic message to the user.
 *
 * @param string $detail  Internal detail (query, variable values, etc.) — never shown to browser.
 * @param string $context Label for the log entry.
 */
function pma_log_error(string $detail, string $context = ''): void {
    $label = $context ? " [$context]" : '';
    error_log('PMA error' . $label . ': ' . $detail);
    echo "An error occurred. Please contact the system administrator.<BR>\n";
}
