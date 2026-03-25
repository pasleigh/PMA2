<?php
/**
 * Admin role and privilege helpers (staff-side).
 *
 * Two privilege dimensions exist:
 *  - Super admin: stored in the global `admin_users` table (system-wide access).
 *  - Project/module admin: stored in `staff_names.admin_level` or `admin_users_module`
 *    (scoped to a specific module or project).
 *
 * The more complete admin_functions.php includes these same functions plus
 * the `admin_functions_block()` HTML builder and `IsModuleAdmin2()`.
 */

/**
 * Return true if the username has any super-admin privilege (level > 0).
 *
 * @param PDO    $db       Active PDO connection.
 * @param string $username Web-server authenticated username.
 * @return bool
 */
function IsAdmin($db, $username){
    if(GetSuperAdminLevel($db,$username) > 0){
        return true;
    }
    return false;
}
/**
 * Return true if the username has project-admin privilege in the staff table.
 *
 * @param PDO    $db                Active PDO connection.
 * @param string $username          Web-server authenticated username.
 * @param string $staff_names_table Staff table name.
 * @return bool
 */
function IsProjectAdmin($db, $username, $staff_names_table){
    if(GetProjectAdminLevel($db,$username, $staff_names_table) > 0){
        return true;
    }
    return false;
}

/**
 * Return the super-admin level from the global `admin_users` table.
 *
 * @param PDO    $db       Active PDO connection.
 * @param string $username Web-server authenticated username.
 * @return int 0 = no privilege, positive = increasing admin level.
 */
function GetSuperAdminLevel($db, $username)
{
    // Returns the admin level - 0 if no admin privilidges
    //
    $admin_level = 0;
    try {
        $results = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $results->execute([$username]);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $admin_level;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level = $result['admin_level'];
    }

    return $admin_level;
}

/**
 * Return the project-admin level from the staff names table.
 *
 * @param PDO    $db          Active PDO connection.
 * @param string $username    Web-server authenticated username.
 * @param string $staff_names Staff table name.
 * @return int 0 = no privilege, positive = increasing admin level.
 */
function GetProjectAdminLevel($db, $username, $staff_names)
{
    // Returns the project admin level - 0 if no admin privilidges
    //
    $admin_level = 0;
    try {
        $results = $db->prepare("SELECT * FROM $staff_names WHERE username = ?");
        $results->execute([$username]);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $admin_level;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level = $result['admin_level'];
    }

    return $admin_level;
}

/**
 * Return true if the username has admin access to a specific module.
 *
 * Can look up by $pm_id (preferred) or by $short_code + $year_begin.
 * Pass $pm_id = 'all' to check whether the user has admin rights on *any* module.
 *
 * @param PDO         $db         Active PDO connection.
 * @param string      $username   Web-server authenticated username.
 * @param string|null $pm_id      Project-module ID, 'all', or null to use short_code lookup.
 * @param string|null $short_code Module short code (used when $pm_id is null).
 * @param string|null $year_begin Academic year start (used with $short_code).
 * @return bool
 */
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
/**
 * Return the module admin level for a given project-module ID.
 *
 * Pass $pm_id = 'all' to check for any module (returns 1 if any found).
 *
 * @param PDO    $db       Active PDO connection.
 * @param string $username Web-server authenticated username.
 * @param string $pm_id    Project-module ID or 'all'.
 * @return int 0 = no privilege, positive = admin level for that module.
 */
function GetModuleAdminLevelFromPID($db, $username, $pm_id)
{
    // Returns the project admin level for a module, 0 if no admin privilidges
    //
    $admin_level_module = 0;
    try {
        if($pm_id == 'all'){
            $results = $db->prepare("SELECT * FROM admin_users_module WHERE username = ?");
            $results->execute([$username]);
        }else{
            $results = $db->prepare("SELECT * FROM admin_users_module WHERE project_id = ? AND username = ?");
            $results->execute([$pm_id, $username]);
        }
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $admin_level_module;
    }

    if ($results->rowCount() > 0) {
        if($pm_id == 'all'){
            $admin_level_module = 1;
        }else{
            $result = $results->fetch();
            $admin_level_module = $result['admin_level'];
        }
    }

    return $admin_level_module;
}
/**
 * Return the module admin level looked up by short code and year.
 *
 * @param PDO    $db         Active PDO connection.
 * @param string $username   Web-server authenticated username.
 * @param string $short_code Module short code (e.g. 'EEE3007').
 * @param string $year_begin Academic year start (e.g. '2024').
 * @return int 0 = no privilege, positive = admin level for that module.
 */
function GetModuleAdminLevelFromShortCode($db, $username, $short_code, $year_begin)
{
    // Returns the project admin level for a module, 0 if no admin privilidges
    //
    $admin_level_module = 0;
    try {
        $results = $db->prepare("SELECT * FROM admin_users_module WHERE short_code = ? AND year_begin = ? AND username = ?");
        $results->execute([$short_code, $year_begin, $username]);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $admin_level_module;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level_module = $result['admin_level'];
    }

    return $admin_level_module;
}

?>
