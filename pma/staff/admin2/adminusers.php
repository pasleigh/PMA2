<?php
function IsAdmin($db, $username){
    if(GetSuperAdminLevel($db,$username) > 0){
        return true;
    }
    return false;
}
function IsProjectAdmin($db, $username, $staff_names_table){
    if(GetProjectAdminLevel($db,$username, $staff_names_table) > 0){
        return true;
    }
    return false;
}

function GetSuperAdminLevel($db, $username)
{
    // Returns the admin level - 0 if no admin privilidges
    //
    $admin_level = 0;
    $query = "SELECT * FROM admin_users WHERE username='$username'";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        echo(" Query = $query<BR \n");
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $admin_level;
        //exit();
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level = $result['admin_level'];
    }

    return $admin_level;
}

function GetProjectAdminLevel($db, $username, $staff_names)
{
    // Returns the project admin level - 0 if no admin privilidges
    //
    $admin_level = 0;
    $query = "SELECT * FROM $staff_names WHERE username='$username'";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        echo(" Query = $query<BR \n");
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $admin_level;
        //exit();
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level = $result['admin_level'];
    }

    //echo("project admin level = $admin_level<BR>");
    //echo("staff names = $staff_names<BR>");
    //echo("username = $username<BR>");
    //echo("query = $query<BR>");

    return $admin_level;
}


function IsModuleAdmin($db, $username, $pm_id, $short_code, $year_begin){
    // Can test on either:
    // $pm_id
    // short_code and year_begin combination, for normal modules
    // short_code, for cwt, dist, pega, hamid
    // pm_id = all, wii search teh whole table for any module i.e. to find if the user has access to at least 1 module

    $admin_level_module = 0;
    if($pm_id != null){ // this also functions for : cwt, dist, pega, hamid
        $admin_level_module = GetModuleAdminLevelFromPID($db,$username, $pm_id);
    }else{
        $admin_level_module = GetModuleAdminLevelFromShortCode($db,$username, $short_code, $year_begin);
    }

    if($admin_level_module > 0){
        return true;
    }else {
        return false;
    }
}
function GetModuleAdminLevelFromPID($db, $username, $pm_id)
{
    // Returns the project admin level for a module, 0 if no admin privilidges
    //
    $admin_level_module = 0;
    if($pm_id == 'all'){
        $query = "SELECT * FROM admin_users_module WHERE username='$username'";
    }else{
        $query = "SELECT * FROM admin_users_module WHERE project_id='$pm_id' AND username='$username'";
    }

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        echo(" Query = $query<BR \n");
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $admin_level_module;
        //exit();
    }

    if ($results->rowCount() > 0) {
        if($pm_id == 'all'){
            $admin_level_module = 1;
        }else{
            $result = $results->fetch();
            $admin_level_module = $result['admin_level'];
        }
    }

    //echo("module admin level = $admin_level_module<BR>");
    //echo("query = $query<BR>");

    return $admin_level_module;
}
function GetModuleAdminLevelFromShortCode($db, $username, $short_code, $year_begin)
{
    // Returns the project admin level for a module, 0 if no admin privilidges
    //
    $admin_level_module = 0;
    $query = "SELECT * FROM admin_users_module WHERE short_code='$short_code' AND year_begin='$year_begin' AND username='$username'";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        echo(" Query = $query<BR \n");
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $admin_level_module;
        //exit();
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level_module = $result['admin_level'];
    }

    //echo("module admin level = $admin_level_module<BR>");
    //echo("query = $query<BR>");

    return $admin_level_module;
}



?>
