<?php

/**
 * Shared staff/student identity and access-level helpers.
 *
 * Used by both the staff and student tiers to determine whether a given
 * username belongs to a known user and what privileges they hold.
 */

/**
 * Return true if the username exists in the staff table.
 *
 * @param PDO    $db          Active PDO connection.
 * @param string $username    Web-server authenticated username.
 * @param string $staff_names Staff table name.
 * @return bool
 */
function IsStaff($db, $username, $staff_names) {
    $query = "SELECT * FROM $staff_names WHERE username=:username";

    try {
        $results = $db->prepare($query);
        $results->execute([':username' => $username]);
    } catch (PDOException $ex) {
        error_log('Error in IsStaff: ' . $ex->getMessage());
        return false;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        //$staff_id = $result['staffid'];
        return true;
    }

    return false;
}

/**
 * Return true if the username exists in the students table.
 *
 * @param PDO    $db       Active PDO connection.
 * @param string $username Web-server authenticated username.
 * @param string $students Students table name.
 * @return bool
 */
function IsStudent($db, $username, $students) {
    $query = "SELECT * FROM $students WHERE username=:username";

    try {
        $results = $db->prepare($query);
        $results->execute([':username' => $username]);
    } catch (PDOException $ex) {
        error_log('Error in IsStudent: ' . $ex->getMessage());
        return false;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        //$staff_id = $result['staffid'];
        return true;
    }

    return false;
}

/**
 * Return the super-admin level for a username (0 = no admin privileges).
 *
 * Reads from the global `admin_users` table. Used by both the staff and
 * student tiers — see also GetSuperAdminLevel() in adminusers.php for
 * the staff-only variant.
 *
 * @param PDO    $db       Active PDO connection.
 * @param string $username Web-server authenticated username.
 * @return int Admin level (0 = none, positive integer = increasing privilege).
 */
function GetSuperAdminLevel_staff_student($db, $username) {
    // Returns the admin level - 0 if no admin privilidges
    //
    $admin_level = 0;
    $query = 'SELECT * FROM admin_users WHERE username=:username';

    try {
        $results = $db->prepare($query);
        $results->execute([':username' => $username]);
    } catch (PDOException $ex) {
        error_log('Error in GetSuperAdminLevel_staff_student: ' . $ex->getMessage());
        return $admin_level;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level = $result['admin_level'];
    }

    return $admin_level;
}

/**
 * Check whether the current user has super-admin access and set a display phrase.
 *
 * @param PDO    $db                    Active PDO connection.
 * @param string $this_staff_username   Authenticated username.
 * @param string $this_staff_full_name  Staff member's full display name.
 * @param string $staff_names_table     Staff table name (unused here, kept for signature parity).
 * @param string $access_response_phrase Out param: human-readable access status message.
 * @return bool True if the user has super-admin access.
 */
function access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names_table, &$access_response_phrase) {
    $access = false;
    if (GetSuperAdminLevel_staff_student($db, $this_staff_username)) {
        $access_phrase = "Hello $this_staff_full_name ($this_staff_username), you have system level admin access. Be very careful.";
        $access = true;
    } else {
        $access_phrase = "Unfortunately you, $this_staff_full_name ($this_staff_username), do not have admin access rights. <BR> Please return to the last page you came from.";
    }

    $access_response_phrase = $access_phrase;
    return $access;
}

/*
function IsStaffActive($db, $this_staff_full_name, $staff_names_table = "staff_names")
{
    $active = false;
    // Returns is the staff is active or not from the GLOBAL stafflist
    // there is a module staff_inactive list that is not used yet
    // there is a module staff_names_module list that is not used yet
    //
    $query = "SELECT * FROM $staff_names_table WHERE fullname='$this_staff_full_name'";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        pma_log_db_error($ex, __FUNCTION__);
        return $active;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $inactive = $result['inactive'];
        if($inactive == "0"){
            $active = true;
        }
    }
    return $active;
}
*/
