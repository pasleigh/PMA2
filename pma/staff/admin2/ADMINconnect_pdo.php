<?php
(@include_once("./utility_functions.php")) OR die("Cannot find file utility_functions.php<BR>");
(@include_once("./adminusers.php")) OR die("Cannot find this file to include: adminusers.php<BR>");

if(isset($_COOKIE['USERNAME'])){
//if (array_key_exists('REMOTE_USER', $_SERVER)==true){
//    $this_staff_username = $_SERVER['REMOTE_USER'];
    $this_staff_username = $_COOKIE['USERNAME'];
    $admin_level = GetSuperAdminLevel($db,$this_staff_username) ;


    //echo("user $username has super admin level = $admin_level<BR>\n");
    if( $admin_level > 1){
        //$staff_full_name = GetStaffNameFromUsername($username, $staff_names_default, false);
        //echo("User $staff_full_name<BR>");

        /* Make connection to database */
        try{
            $db = new PDO("mysql:host=$hostname; dbname=$dbName; charset=utf8", $dbusername, $password);
        } catch (PDOException $ex) {
            $this_file = __FILE__;
            $this_line = __LINE__;
            echo("An Error occured connecting to the database in file: $this_file (near line $this_line)<BR>\n");
            echo("host name: $hostname<BR>\n");
            echo("database name: $dbName<BR>\n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
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
