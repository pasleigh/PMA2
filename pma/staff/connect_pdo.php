<?php

/* Make connection to database */

try{
    $db = new PDO("mysql:host=$hostname; dbname=$dbName; charset=utf8", $dbusername, $password);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
} catch (PDOException $ex) {
    $this_file = __FILE__;
    $this_line = __LINE__;
    //echo("<pre>$ex</pre>");
    echo("An Error occured connecting to the database<BR>");
    echo("Go back to the home page and report this to the system admin<BR>");
    echo("<A href='../index.html'>Back to the home page</a>");
    exit();
}
?>
