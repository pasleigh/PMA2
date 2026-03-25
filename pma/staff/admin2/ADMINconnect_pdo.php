<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
(@include_once("./utility_functions.php")) OR die("Cannot find file utility_functions.php<BR>");
(@include_once("./adminusers.php")) OR die("Cannot find this file to include: adminusers.php<BR>");

if(isset($_SESSION['USERNAME'])){
    $this_staff_username = $_SESSION['USERNAME'];
    $admin_level = GetSuperAdminLevel($db,$this_staff_username) ;

    if( $admin_level > 1){
        //$staff_full_name = GetStaffNameFromUsername($username, $staff_names_default, false);

        /* Make connection to database */
        try{
            $db = new PDO("mysql:host=$hostname; dbname=$dbName; charset=utf8", $dbusername, $password);
        } catch (PDOException $ex) {
            $this_file = __FILE__;
            $this_line = __LINE__;
            echo("An Error occured connecting to the database in file: $this_file (near line $this_line)<BR>\n");
            echo("host name: $hostname<BR>\n");
            echo("database name: $dbName<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }else{
        echo("An error has occured when trying to verify the Admin user status.<BR>");
        echo("<A href='../index.php'>Go back to the main index</A>");
        exit();
    }
}else{
    echo("An error has occured when trying to verify the Admin user status.<BR> ");
    echo("Cannot find your username so cannot verify wheether admin or not.<BR> ");

    echo("<A href='../index.php'>Go back to the main index</A>");
    exit();
}

?>
