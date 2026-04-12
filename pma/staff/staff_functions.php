<?php

/**
 * Staff lookup and HTML-widget helpers.
 *
 * All functions that accept a $db parameter expect an active PDO connection.
 * All functions that accept a $staff_table / $staff_names parameter expect
 * the table name string set by setup_titles.php ($staff_names variable).
 */

/**
 * Return a staff member's display name looked up by their numeric ID.
 *
 * @param PDO    $db          Active PDO connection.
 * @param int    $staff_id    Numeric staff ID (staffid column).
 * @param string $staff_table Staff table name.
 * @param int    $usetitle    1 = prepend title (Dr, Prof, etc.), 0 = full name only.
 * @return string Display name, or empty string if not found.
 */
function GetStaffNameFromID($db, $staff_id, $staff_table, $usetitle) {
    $name = '';
    if ($staff_id == '') {
        return $name;
    }
    $get_list_query = "select * from $staff_table where staffid=:staff_id";
    try {
        $get_list_res = $db->prepare($get_list_query);
        $get_list_res->execute([':staff_id' => $staff_id]);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from database. In GetStaffNameFromID: ' . $ex->getMessage());
        return $name;
    }

    $recs = $get_list_res->fetch();

    if ($recs) {
        //$initials = $recs['initials'];
        $fullname = $recs['fullname'];
        $fullname = stripslashes($fullname);
        //$firstname = stripslashes($recs['firstname']);
        //$familyname = stripslashes($recs['familyname']);
        $title = $recs['title'];
        $title = stripslashes($title);

        if ($usetitle == 1) {
            $name = $title . ' ' . $fullname;
        } else {
            $name = $fullname;
        }
    } else {
        $name = '';
    }

    return $name;
}
/**
 * Return a staff member's email address looked up by their numeric ID.
 *
 * @param PDO    $db          Active PDO connection.
 * @param int    $staff_id    Numeric staff ID.
 * @param string $staff_table Staff table name.
 * @return string Email address, or empty string if not found.
 */
function GetStaffEmailFromID($db, $staff_id, $staff_table) {
    $name = '';
    if ($staff_id == '') {
        return $name;
    }
    $get_list_query = "select * from $staff_table where staffid=:staff_id";
    try {
        $get_list_res = $db->prepare($get_list_query);
        $get_list_res->execute([':staff_id' => $staff_id]);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from database. In GetStaffEmailFromID: ' . $ex->getMessage());
        return $name;
    }

    $recs = $get_list_res->fetch();

    if ($recs) {
        $email = $recs['email'];
        if ($email) {
            $email = stripslashes($recs['email']);
        }
    } else {
        $email = '';
    }

    return $email;
}

/**
 * Return a staff member's email address looked up by their full name.
 *
 * @param PDO    $db             Active PDO connection.
 * @param string $staff_fullname Full name as stored in the staff table.
 * @param string $staff_table    Staff table name.
 * @return string Email address, or empty string if not found.
 */
function GetStaffEmailFromFullname($db, $staff_fullname, $staff_table) {
    $staff_email = '';
    $staff_id = GetStaffIDFromName($db, $staff_fullname, $staff_table);
    if ($staff_id != '') {
        $staff_email = GetStaffEmailFromID($db, $staff_id, $staff_table);
    }

    return $staff_email;
}
/**
 * Return a staff member's login username looked up by their full name.
 *
 * @param PDO    $db             Active PDO connection.
 * @param string $staff_fullname Full name as stored in the staff table.
 * @param string $staff_table    Staff table name.
 * @return string Login username, or empty string if not found.
 */
function GetStaffUsernameFromFullname($db, $staff_fullname, $staff_table) {
    $username = '';
    if ($staff_fullname == '') {
        return $username;
    }
    $get_list_query = "select * from $staff_table where fullname=:staff_fullname";
    try {
        $get_list_res = $db->prepare($get_list_query);
        $get_list_res->execute([':staff_fullname' => $staff_fullname]);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from database. In GetStaffNameFromFullname: ' . $ex->getMessage());
        return $username;
    }

    $recs = $get_list_res->fetch();

    if ($recs) {
        $username = $recs['username'];
        if ($username) {
            $username = stripslashes($recs['username']);
        }
    }

    return $username;
}
/**
 * Return a staff member's display name looked up by their login username.
 *
 * This is the primary auth check function: a non-empty return confirms the
 * username exists in the staff table and the user may proceed.
 *
 * @param PDO    $db             Active PDO connection.
 * @param string $staff_username Web-server authenticated username.
 * @param string $staff_table    Staff table name.
 * @param int    $usetitle       1 = prepend title (Dr, Prof, etc.), 0 = full name only.
 * @return string Display name ('Unknown' if username found but name missing, '' if not found at all).
 */
function GetStaffNameFromUsername($db, $staff_username, $staff_table, $usetitle) {
    $name = '';
    if ($staff_username == '') {
        return $name;
    }
    $get_list_query = "select * from $staff_table where username=:staff_username";
    try {
        $get_list_res = $db->prepare($get_list_query);
        $get_list_res->execute([':staff_username' => $staff_username]);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from database. In GetStaffNameFromUsername: ' . $ex->getMessage());
        return $name;
    }

    $recs = $get_list_res->fetch();

    if ($recs) {
        //$initials = $recs['initials'];
        $fullname = stripslashes($recs['fullname']);
        //$firstname = stripslashes($recs['firstname']);
        //$familyname = stripslashes($recs['familyname']);
        $title = stripslashes($recs['title']);
    } else {
        $fullname = 'Unknown';
        $title = 'unknown';
    }

    if ($usetitle == 1) {
        $name = $title . ' ' . $fullname;
    } else {
        $name = $fullname;
    }

    return $name;
}

/**
 * Return the numeric staff ID looked up by exact full name.
 *
 * @param PDO    $db             Active PDO connection.
 * @param string $staff_fullname Exact full name as stored in the staff table.
 * @param string $staff_table    Staff table name.
 * @return string Numeric staff ID, or empty string if not found.
 */
function GetStaffIDFromName($db, $staff_fullname, $staff_table) {
    $staff_id = '';
    if ($staff_fullname == '') {
        return $staff_id;
    }
    $get_list_query = "select * from $staff_table where fullname=:staff_fullname";
    try {
        $get_list_res = $db->prepare($get_list_query);
        $get_list_res->execute([':staff_fullname' => $staff_fullname]);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from database. In GetStaffIDFromName: ' . $ex->getMessage());
        return $staff_id;
    }
    $rows = $get_list_res->fetchAll();

    foreach ($rows as $recs) {
        $staff_id = $recs['staffid'];
        //$initials = $recs['initials'];
        $fullname = stripslashes($recs['fullname']);
        //$firstname = stripslashes($recs['firstname']);
        //$familyname = stripslashes($recs['familyname']);
        //$title = stripslashes($recs['title']);
        if ($fullname == $staff_fullname) {
            return $staff_id;
        }
    }

    return $staff_id;
}

/**
 * Return the staff ID matched by family name with optional space-separated first-name initial.
 *
 * Handles formats: "Sleigh", "Sleigh A", "Sleigh Andrew".
 *
 * @param PDO    $db               Active PDO connection.
 * @param string $staff_family_name Family name, optionally followed by a space and first name/initial.
 * @param string $staff_table      Staff table name.
 * @return string Numeric staff ID ('0' if not found).
 */
function GetStaffIDFromFamilyNameAndInitial($db, $staff_family_name, $staff_table) {
    // Finds match for:
    // Sleigh
    // Sleigh A
    // Sleigh Andrew
    //
    $staff_id = '0';
    if ($staff_family_name == '') {
        return $staff_id;
    }

    // Split the family name as it may contain a trailing initial
    // Code needed to deal with tha
    //

    $staff_family_name_split = explode(' ', $staff_family_name);

    $params = [':familyname' => '%' . $staff_family_name_split[0] . '%'];
    $get_list_query = "select * from $staff_table where familyname LIKE :familyname";

    if (count($staff_family_name_split) > 1) {
        $get_list_query .= ' AND firstname LIKE :firstname';
        $params[':firstname'] = $staff_family_name_split[1] . '%';
    }

    try {
        $get_list_res = $db->prepare($get_list_query);
        $get_list_res->execute($params);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from database. In GetStaffIDFromFamilyNameAndInitial: ' . $ex->getMessage());
        return $staff_id;
    }
    //$num_staff_matches = $get_list_res->rowCount();

    $rows = $get_list_res->fetchAll();

    foreach ($rows as $recs) {
        $staff_id = $recs['staffid'];
        //$initials = $recs['initials'];
        //$fullname = stripslashes($recs['fullname']);
        //$firstname = stripslashes($recs['firstname']);
        //$familyname = stripslashes($recs['familyname']);
        //$title = stripslashes($recs['title']);
        return $staff_id;
        //
        //        if($familyname == $staff_family_name){
        //            //echo("$familyname ($staff_id)<BR>");
        //            return $staff_id;
        //        }
        //
    }

    return $staff_id;
}

/**
 * Return the staff ID matched by family name with optional comma-separated first-name initial.
 *
 * Handles formats: "Sleigh", "Sleigh, A", "Sleigh, Andy".
 * Used when importing from systems that format names as "Family, First".
 *
 * @param PDO    $db               Active PDO connection.
 * @param string $staff_family_name Family name, optionally followed by ", First".
 * @param string $staff_table      Staff table name.
 * @return string Numeric staff ID ('0' if not found).
 */
function GetStaffIDFromFamilyNameAndInitial2($db, $staff_family_name, $staff_table) {
    // Finds match for:
    // Sleigh
    // Sleigh, A
    // Sleigh, Andy
    //
    $staff_id = '0';
    if ($staff_family_name == '') {
        return $staff_id;
    }

    // Split the family name as it may contain a trailing initial
    $staff_family_name_split = explode(', ', $staff_family_name);

    $params = [':familyname' => '%' . $staff_family_name_split[0] . '%'];
    $get_list_query = "select * from $staff_table where familyname LIKE :familyname";

    if (count($staff_family_name_split) > 1) {
        $firstname = trim($staff_family_name_split[1]);
        $get_list_query .= ' AND firstname LIKE :firstname';
        $params[':firstname'] = $firstname . '%';
    }

    try {
        $get_list_res = $db->prepare($get_list_query);
        $get_list_res->execute($params);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from database. In GetStaffIDFromFamilyNameAndInitial2: ' . $ex->getMessage());
        return $staff_id;
    }

    //$num_staff_matches = $get_list_res->rowCount();

    $rows = $get_list_res->fetchAll();

    foreach ($rows as $recs) {
        $staff_id = $recs['staffid'];
        //$initials = $recs['initials'];
        //$fullname = stripslashes($recs['fullname']);
        //$firstname = stripslashes($recs['firstname']);
        //$familyname = stripslashes($recs['familyname']);
        //$title = stripslashes($recs['title']);
        return $staff_id;
        //
        //        if($familyname == $staff_family_name){
        //            //echo("$familyname ($staff_id)<BR>");
        //            return $staff_id;
        //        }
        //
    }

    return $staff_id;
}

/**
 * Return true if a staff record exists for the given login username.
 *
 * @param PDO    $db             Active PDO connection.
 * @param string $staff_username Login username.
 * @param string $staff_table    Staff table name.
 * @return bool
 */
function DoesStaffExistByUsername($db, $staff_username, $staff_table) {
    $staff_id = GetStaffIDFromUsername($db, $staff_username, $staff_table);
    if ($staff_id == '') {
        return false;
    } else {
        return true;
    }
}

/**
 * Return the numeric staff ID looked up by login username.
 *
 * @param PDO    $db             Active PDO connection.
 * @param string $staff_username Login username.
 * @param string $staff_table    Staff table name.
 * @return string Numeric staff ID, or empty string if not found.
 */
function GetStaffIDFromUsername($db, $staff_username, $staff_table) {
    $staff_id = '';
    if ($staff_username == '') {
        return $staff_id;
    }
    $get_list_query = "select * from $staff_table where username=:staff_username";
    try {
        $get_list_res = $db->prepare($get_list_query);
        $get_list_res->execute([':staff_username' => $staff_username]);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from database. In GetStaffIDFromUsername: ' . $ex->getMessage());
        return $staff_id;
    }

    $rows = $get_list_res->fetchAll();

    foreach ($rows as $recs) {
        $staff_id = $recs['staffid'];
        //$initials = $recs['initials'];
        //$fullname = stripslashes($recs['fullname']);
        //$firstname = stripslashes($recs['firstname']);
        //$familyname = stripslashes($recs['familyname']);
        //$title = stripslashes($recs['title']);
        $username = stripslashes($recs['username']);
        if ($username == $staff_username) {
            return $staff_id;
        }
    }

    return $staff_id;
}

/**
 * Return an HTML `<select>` element listing all active staff ordered by family name.
 *
 * @param PDO    $db          Active PDO connection.
 * @param string $staff_table Staff table name.
 * @param string $NameKey     The `name` and `id` attribute value for the select element.
 * @param int    $MatchID     Staff ID of the option to pre-select (staffid column).
 * @return string HTML `<select>...</select>` string.
 */
function GetStaffSelectID($db, $staff_table, $NameKey, $MatchID) {
    $staff_select_block = "\n<select name=\"$NameKey\" id=\"$NameKey\" class='form-control'>\n";
    $staff_select_block .= "<option value=\"-1\">--- Select Staff Name ---</option>\n";
    $get_staff_list_query = "select * from $staff_table order by familyname";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from table: ' . $ex->getMessage());
        return $staff_select_block;
    }

    $rows = $get_staff_list_res->fetchAll();

    foreach ($rows as $recs) {
        $initials = $recs['initials'];
        $staffname = stripslashes($recs['fullname']);
        $staffid = $recs['staffid'];
        $inactive = $recs['inactive'];

        if ($inactive == 0) {
            if ($staffid == $MatchID) {
                $staff_select_block .= "<option value=\"$staffid\" selected=\"selected\">$staffname</option>\n";
            } else {
                $staff_select_block .= "<option value=\"$staffid\">$staffname</option>\n";
            }
        }
    }
    $staff_select_block .= "</select>\n";

    return $staff_select_block;
}

/**
 * Return an array of active staff members suitable for JSON encoding.
 *
 * Each element has keys: 'id' (staffid) and 'staff_name' (full name).
 *
 * @param PDO    $db          Active PDO connection.
 * @param string $staff_table Staff table name.
 * @return array<int, array{id: string, staff_name: string}>
 */
function get_staff_list_json($db, $staff_table) {
    $staff_array = [];
    $err_msg = '';

    $get_staff_list_query = "select * from $staff_table order by familyname";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        return $staff_array;
    }

    $rows = $get_staff_list_res->fetchAll();

    foreach ($rows as $recs) {
        //$initials = $recs['initials'];
        $staff_name = stripslashes($recs['fullname']);
        $staffid = $recs['staffid'];
        $inactive = $recs['inactive'];

        if ($inactive == 0) {
            $this_staff = [
                'id' => $staffid,
                'staff_name' => "$staff_name"
            ];
            $staff_array[] = $this_staff;
        }
    }
    return $staff_array;
}

/**
 * Return an HTML `<select>` listing all schools from the KMAP definition table.
 *
 * @param PDO    $db      Active PDO connection.
 * @param string $NameKey The `name` and `id` attribute value for the select element.
 * @param int    $MatchID School ID to pre-select.
 * @return string HTML `<select>...</select>` string.
 */
function GetSchoolSelectID($db, $NameKey, $MatchID) {
    $definition_table = GetKMAPDefinitionTableName();
    $school_select_block = "\n<select name='$NameKey' id='$NameKey' class='form-control'>\n";
    $school_select_block .= "<option value='-1'>--- Select a School ---</option>\n";
    $get_school_list_query = "SELECT * FROM $definition_table WHERE 1";
    try {
        $get_school_list_res = $db->query($get_school_list_query);
    } catch (PDOException $ex) {
        error_log('Unable to create school name list from table: ' . $ex->getMessage());
        return $school_select_block;
    }

    $rows = $get_school_list_res->fetchAll();

    foreach ($rows as $recs) {
        $school_title = stripslashes($recs['school_title']);
        $school_short_name = stripslashes($recs['school_short_name']);
        $school_id = $recs['id'];

        if ($school_id == $MatchID) {
            $school_select_block .= "<option value='$school_id' selected='selected'>$school_title ($school_short_name)</option>\n";
        } else {
            $school_select_block .= "<option value='$school_id'>$school_title ($school_short_name)</option>\n";
        }
    }
    $school_select_block .= "<option value='0'>An external School</option>\n";

    $school_select_block .= "</select>\n";

    return $school_select_block;
}

/**
 * Return an HTML `<select>` listing all semesters from the KMAP Banner PTRM codes table.
 *
 * @param PDO    $db      Active PDO connection.
 * @param string $NameKey The `name` and `id` attribute value for the select element.
 * @param string $MatchID Banner semester code to pre-select.
 * @return string HTML `<select>...</select>` string.
 */
function GetSemesterSelectID($db, $NameKey, $MatchID) {
    $semester_table = GetKMAPBannerPTRMCodesTableName();
    $semester_select_block = "\n<select name='$NameKey' id='$NameKey' class='form-control'>\n";
    //$semester_select_block .= "<option value='-1'>--- Select a Semester ---</option>\n";
    $get_semester_list_query = "SELECT * FROM $semester_table WHERE 1";
    try {
        $get_semester_list_res = $db->query($get_semester_list_query);
    } catch (PDOException $ex) {
        error_log('Unable to create semester list from table: ' . $ex->getMessage());
        return $semester_select_block;
    }

    $rows = $get_semester_list_res->fetchAll();

    foreach ($rows as $recs) {
        $semester_id = stripslashes($recs['id']);
        $semester_banner_code = stripslashes($recs['banner_code']);
        $semester_banner_text = stripslashes($recs['banner_text']);

        if ($semester_banner_code == $MatchID) {
            $semester_select_block .= "<option value='$semester_banner_code' selected='selected'>$semester_banner_code, [$semester_banner_text]</option>\n";
        } else {
            $semester_select_block .= "<option value='$semester_banner_code'>$semester_banner_code, [$semester_banner_text]</option>\n";
        }
    }

    $semester_select_block .= "</select>\n";

    return $semester_select_block;
}
/**
 * Return an HTML `<select>` for staff with a custom `onchange` handler.
 *
 * Same as GetStaffSelectID() but the select element includes an inline
 * `onchange` attribute, used where selecting a staff member triggers JS.
 *
 * @param PDO    $db          Active PDO connection.
 * @param string $staff_table Staff table name.
 * @param string $NameKey     The `name` attribute value for the select element.
 * @param int    $MatchID     Staff ID to pre-select.
 * @param string $OnChange    JavaScript expression for the `onchange` attribute.
 * @return string HTML `<select>...</select>` string.
 */
function GetStaffSelectIDWithOnChange($db, $staff_table, $NameKey, $MatchID, $OnChange) {
    $staff_select_block = "\n<select name=\"$NameKey\" onchange=\"$OnChange\">\n";
    $staff_select_block .= "<option value=\"-1\">--- Select Staff Name ---</option>\n";
    $get_staff_list_query = "select * from $staff_table order by familyname";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from table: ' . $ex->getMessage());
        return $staff_select_block;
    }

    $rows = $get_staff_list_res->fetchAll();

    foreach ($rows as $recs) {
        $initials = $recs['initials'];
        $staffname = stripslashes($recs['fullname']);
        $staffid = $recs['staffid'];
        $inactive = $recs['inactive'];

        if ($inactive == 0) {
            if ($staffid == $MatchID) {
                $staff_select_block .= "<option value=\"$staffid\" selected=\"selected\">$staffname</option>\n";
            } else {
                $staff_select_block .= "<option value=\"$staffid\">$staffname</option>\n";
            }
        }
    }
    $staff_select_block .= "</select>\n";

    return $staff_select_block;
}

/**
 * Return a string of `<option>` elements for all active staff (no wrapping `<select>`).
 *
 * Use this when you need to inject options into an existing select element.
 *
 * @param PDO    $db          Active PDO connection.
 * @param string $staff_table Staff table name.
 * @return string HTML `<option>` elements string.
 */
function GetAllStaffSelectOptions($db, $staff_table) {
    $staff_select_options_block = '';
    $staff_select_options_block .= "<option value='-1'>--- Select Staff Name ---</option>\n";
    $get_staff_list_query = "select * from $staff_table order by familyname";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from table: ' . $ex->getMessage());
        return $staff_select_options_block;
    }

    $rows = $get_staff_list_res->fetchAll();

    foreach ($rows as $recs) {
        $initials = $recs['initials'];
        $staff_fullname = stripslashes($recs['fullname']);
        $staffid = $recs['staffid'];
        $inactive = $recs['inactive'];

        if ($inactive == 0) {
            $staff_select_options_block .= "<option value='$staffid'>$staff_fullname</option>\n";
        }
    }
    $staff_select_options_block .= "</select>\n";

    return $staff_select_options_block;
}

/**
 * Return true if the given staff ID and full name refer to the same record.
 *
 * Used to validate that a form-submitted name matches the submitted ID,
 * preventing tampering where only the ID is used in a query.
 *
 * @param PDO    $db          Active PDO connection.
 * @param string $staff_table Staff table name.
 * @param int    $StaffID     Staff ID to look up.
 * @param string $StaffName   Full name to compare against.
 * @return bool True if the name in the DB matches $StaffName.
 */
function CompareIDandFullname($db, $staff_table, $StaffID, $StaffName) {
    $get_staff_list_query = "select * from $staff_table where staffid = :staff_id";
    try {
        $get_staff_list_res = $db->prepare($get_staff_list_query);
        $get_staff_list_res->execute([':staff_id' => $StaffID]);
    } catch (PDOException $ex) {
        error_log('Unable to create staff name list from table: ' . $ex->getMessage());
        return false;
    }
    $recs = $get_staff_list_res->fetch();

    //$initials = $recs['initials'];
    //$staffname = stripslashes($recs['fullname']);
    //$staffid = $recs['staffid'];
    //$inactive = $recs['inactive'];
    $fullname = $recs['fullname'];

    if ($StaffName == $fullname) {
        return true;
    }

    return false;
}

/**
 * Return the number of projects supervised by a staff member.
 *
 * @param PDO    $db                        Active PDO connection.
 * @param string $projects_master_name_table Projects master table name.
 * @param int    $staffid                   Staff ID to count for.
 * @return int Number of supervised projects.
 */
function CountSupervisedProjectsByID($db, $projects_master_name_table, $staffid) {
    $num_supervise = 0;

    $query = "select * from $projects_master_name_table where f_supervisor_id = :staffid";
    try {
        $results = $db->prepare($query);
        $results->execute([':staffid' => $staffid]);
    } catch (PDOException $ex) {
        error_log('Error in ' . __FUNCTION__ . ': ' . $ex->getMessage());
        exit();
    }
    $num_supervise = $results->rowCount();

    return $num_supervise;
}
/**
 * Return the number of projects for which a staff member is the second marker.
 *
 * @param PDO    $db                    Active PDO connection.
 * @param string $project_details_table Project details table name.
 * @param int    $staffid               Staff ID to count for.
 * @return int Number of projects being second-marked by this staff member.
 */
function CountSecondMarkedProjectsByID($db, $project_details_table, $staffid) {
    $num_second_mark = 0;
    $query = "select * from $project_details_table where f_assocstaff_id = :staffid";
    try {
        $results = $db->prepare($query);
        $results->execute([':staffid' => $staffid]);
    } catch (PDOException $ex) {
        error_log('Error in ' . __FUNCTION__ . ': ' . $ex->getMessage());
        exit();
    }
    $num_second_mark = $results->rowCount();

    return $num_second_mark;
}

/**
 * Convert Windows-1252 "smart quote" characters to their HTML entity equivalents.
 *
 * Handles curly quotes, em/en dashes, and ellipsis characters that are
 * commonly pasted in from Word or other Windows applications. This prevents
 * encoding corruption when the content is stored or displayed as UTF-8.
 *
 * @param string $string Input string potentially containing smart quotes.
 * @return string String with smart quote bytes replaced by HTML entities.
 */
/**
 *  Ã¢â‚¬Ëœ  8216  curly left single quote

 *  Ã¢â‚¬â„¢  8217  apostrophe, curly right single quote

 *  Ã¢â‚¬Å“  8220  curly left double quote

 *  Ã¢â‚¬Â  8221  curly right double quote

 *  Ã¢â‚¬â€  8212  em dash

 *  Ã¢â‚¬â€œ  8211  en dash

 *  Ã¢â‚¬Â¦  8230  ellipsis
 */
function convert_smart_quotes($string) {
    $search = [
        //'&',
        //'<',
        //'>',
        //'"',
        chr(212),
        chr(213),
        chr(210),
        chr(211),
        chr(209),
        chr(208),
        chr(201),
        chr(145),
        chr(146),
        chr(147),
        chr(148),
        chr(151),
        chr(150),
        chr(133)
    ];

    $replace = [
        //'&amp;',
        //'&lt;',
        //'&gt;',
        //'&quot;',
        '&#8216;',
        '&#8217;',
        '&#8220;',
        '&#8221;',
        '&#8211;',
        '&#8212;',
        '&#8230;',
        '&#8216;',
        '&#8217;',
        '&#8220;',
        '&#8221;',
        '&#8211;',
        '&#8212;',
        '&#8230;'
    ];
    return str_replace($search, $replace, $string);
}
function IsStaffB($db, $username, $staff_names) {
    $query = "SELECT * FROM $staff_names WHERE username=:username";

    try {
        $results = $db->prepare($query);
        $results->execute([':username' => $username]);
    } catch (PDOException $ex) {
        error_log('Error in IsStaffB: ' . $ex->getMessage());
        return false;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        //$staff_id = $result['staffid'];
        return true;
    }

    return false;
}
