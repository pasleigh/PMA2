<?php

/**
 * Admin privilege helpers — full version used by the admin2 tier.
 *
 * This file extends adminusers.php with additional functions:
 *   - admin_functions_block() — renders the admin navigation HTML panel
 *   - IsModuleAdmin2() — like IsModuleAdmin() but also sets an access phrase
 *   - access_check_response() — checks both super-admin and project-admin
 *
 * Functions duplicated from adminusers.php (IsAdmin, IsProjectAdmin,
 * GetSuperAdminLevel, GetProjectAdminLevel, IsModuleAdmin,
 * GetModuleAdminLevelFromPID, GetModuleAdminLevelFromShortCode) are
 * re-declared here because admin pages include this file instead of adminusers.php.
 */

/**
 * Return true if the username has any super-admin privilege (level > 0).
 *
 * @param PDO    $db       Active PDO connection.
 * @param string $username Web-server authenticated username.
 * @return bool
 */
function IsAdmin($db, $username) {
    if (GetSuperAdminLevel($db, $username) > 0) {
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
function IsProjectAdmin($db, $username, $staff_names_table) {
    if (GetProjectAdminLevel($db, $username, $staff_names_table) > 0) {
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
function GetSuperAdminLevel($db, $username) {
    // Returns the admin level - 0 if no admin privilidges
    //
    $admin_level = 0;
    $query = 'SELECT * FROM admin_users WHERE username=:username';

    try {
        $results = $db->prepare($query);
        $results->execute([':username' => $username]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        return $admin_level;
        //exit();
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
function GetProjectAdminLevel($db, $username, $staff_names) {
    // Returns the project admin level - 0 if no admin privileges
    //
    $admin_level = 0;
    $query = "SELECT * FROM $staff_names WHERE username=:username";

    try {
        $results = $db->prepare($query);
        $results->execute([':username' => $username]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        return $admin_level;
        //exit();
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level = $result['admin_level'];
    }

    return $admin_level;
}

/**
 * Return an HTML admin navigation panel for a given module.
 *
 * Renders buttons linking to all admin functions: module settings, student/staff
 * editors, data uploads, email tools, and the marking dashboard. Shown at the
 * top of admin pages for quick navigation within a module.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $pm_id            Project-module ID.
 * @param string $project_allocated Allocation table name (used to count students).
 * @param int    $num_parts        Number of assessment parts.
 * @param array  $p_description    Array of part description strings indexed by part number.
 * @param string $year_begin       Academic year start (e.g. '2024').
 * @param string $username         Currently logged-in username.
 * @param bool   $admin_access     True if user has full admin access (shows extra options).
 * @return string HTML block string.
 */
function admin_functions_block($db, $pm_id, $project_allocated, $num_parts, $p_description, $year_begin, $username, $admin_access) {
    $year_end = $year_begin + 1;
    $locationdir = '../';
    $admin_func_block = '';

    $admin_func_block .= "<DIV style='border-style: solid; border-width: 3px; border-color: green; padding: 5px; background-color: Linen' >\n";

    // Check access
    if (!$admin_access) {
        // Check if module access
        if (!IsModuleAdmin($db, $username, $pm_id, null, null)) {
            $admin_func_block .= '<H1>You do not appear to have module level access to this module/year</H1>';
            $admin_func_block .= '<H3>If you think that this is not correct please contact the manager of this system.</H3>';
            $admin_func_block .= "</DIV><BR>\n";
            return $admin_func_block;
        }
    }

    // Shortcuts to the most used actions - the full list follows below
    $admin_func_block .= "<div class='border border-secondary rounded bg-white p-3 mb-3'>\n";
    $admin_func_block .= "<h4 class='mb-3'>Common Module level actions</h4>\n";
    $admin_func_block .= "<a class='btn btn-success me-2 mb-2' href='" . $locationdir . "admin2/modify_module_entry.php?pm_id=$pm_id'>Modify module description</a>\n";
    $admin_func_block .= "<a class='btn btn-success me-2 mb-2' href='" . $locationdir . "admin2/modify_student_table_xlsx.php?pm_id=$pm_id'>Modify/upload students data</a>\n";
    $admin_func_block .= "<a class='btn btn-success me-2 mb-2' href='" . $locationdir . "admin2/upload_student_project_allocation_xlsx.php?pm_id=$pm_id'>Modify/upload allocations</a>\n";
    $admin_func_block .= "</div>\n";

    $admin_func_block .= "<OL>\n";
    $admin_func_block .= "<LI class='itemHeader'>Project allocation</LI>";

    $num_allocated = CountAllocationTable($db, $project_allocated);
    $admin_func_block .= "<span>The allocation has been uploaded with $num_allocated students.</span>";
    $admin_func_block .= '<UL>';
    //$admin_func_block .= "<LI><a href=\"" . $locationdir . "admin2/upload_student_project_allocation.php?pm_id=$pm_id\">Upload csv file of allocations</A> (This ADDs to and changes existing list entries.)</LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/upload_student_project_allocation_xlsx.php?pm_id=$pm_id\">Upload xlsx/csv file of allocations</A> (This ADDs to and changes existing list entries.)</LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/adhoc_student_project_allocation.php?pm_id=$pm_id\">Setup an adhoc (Self defined) project or modify an individual student's project</A></LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/download_student_project_selection.php?pm_id=$pm_id\">Download csv file of student selections</A></LI>";
    $admin_func_block .= '</UL>';

    $admin_func_block .= "<LI class='itemHeader'>Project up/down load</LI>";
    $admin_func_block .= '<UL>';
    //$admin_func_block .= "<LI><a href=\"" . $locationdir . "admin2/upload_download_project_proposals.php?pm_id=$pm_id\">Upload/Download csv file of project proposals</A> (Allows bulk copying from previous year's project proposal lists. Be sure to use the Minor Modifications link to fix any upload errors.)</LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/upload_download_project_proposals_xlsx.php?pm_id=$pm_id\">Upload/Download xlsx/csv file of project proposals</A> (Allows bulk copying from previous year's project proposal lists. Be sure to use the Minor Modifications link to fix any upload errors.)</LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/edit_project_proposals_quick.php?pm_id=$pm_id\">Review / Modify / Delete of project proposals</A> (The long list may take a few seconds to load, have patience.)</LI>";
    $admin_func_block .= '</UL>';

    $admin_func_block .= "<LI class='itemHeader'>Setup/view module components and dates</LI>";
    $admin_func_block .= '<UL>';
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/modify_module_entry.php?pm_id=$pm_id\">Modify module description, split and dates.</a></LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/match_associated_programmes.php?pm_id=$pm_id\">Match associated programmes  Full and short names.</a></LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/modify_locked_parts.php?pm_id=$pm_id\">Modify locked status of module and parts.</a></LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/edit_project_areas.php?pm_id=$pm_id\">Modify the project area descriptions.</a></LI>";
    //$admin_func_block .= "<LI><a href=\"" . $locationdir . "admin2/modify_student_table.php?pm_id=$pm_id\">Modify/Upload student info data (CSV file)</a></LI>\n";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/modify_student_table_xlsx.php?pm_id=$pm_id\">Modify/Upload (xlsx/csv) student info data.</a></LI>\n";
    $admin_func_block .= '</UL><BR>';

    $admin_func_block .= '<UL>';
    //$admin_func_block .= "<LI><a href=\"" . $locationdir . "admin2/upload_totals_for_part.php?pm_id=$pm_id\">Bulk Upload csv of marks directly for an assessment part.</a></LI>\n";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/upload_totals_for_part_xlsx.php?pm_id=$pm_id\">Bulk Upload xlsx/csv of marks directly for an assessment part.</a></LI>\n";
    $admin_func_block .= '<LI><a href="' . $locationdir . "./projectmarking_bs.php?pm_id=$pm_id&modify=1\">Marking page with modify option</A></LI>";
    $admin_func_block .= '</UL>';

    $admin_func_block .= "<LI class='itemHeader'>Setup/view assessment questions </LI>";
    $admin_func_block .= '<B>Edit assessment questions from a template</B>';
    $admin_func_block .= '<UL>';
    for ($i = 1; $i <= $num_parts; $i++) {
        $p_desc = $p_description[$i];
        $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/setup_questions3.php?pm_id=$pm_id&part=$i\">Setup Part $i ($p_desc) questions for $year_begin-$year_end</a></LI>\n";
    }
    $admin_func_block .= '</UL>';

    $admin_func_block .= '<B>Replace assessment questions by copying from another module/part</B>';
    $admin_func_block .= '<UL>';
    for ($i = 1; $i <= $num_parts; $i++) {
        $p_desc = $p_description[$i];
        $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/setup_questions3_copy.php?pm_id=$pm_id&part=$i\">Replace Part $i ($p_desc) questions for $year_begin-$year_end</a></LI>\n";
    }
    $admin_func_block .= '</UL>';

    $admin_func_block .= "<LI class='itemHeader'>Email staff and students</LI>";
    $admin_func_block .= '<B>General email about the module</B>';
    $admin_func_block .= '<UL>';
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/email_project_submission_request_to_staff.php?pm_id=$pm_id&op=select\">Email staff about module (with option to send a list of existing projects)</a></LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/email_students.php?pm_id=$pm_id\">Email all students on each programme</a> </LI>";
    $admin_func_block .= '</UL>';

    $admin_func_block .= '<B>Email about project allocation</B>';
    $admin_func_block .= '<UL>';
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/email_project_allocation_to_staff.php?pm_id=$pm_id&op=select\">Email staff about students they have been allocated to supervise</a></LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/email_project_allocation_to_students.php?pm_id=$pm_id&op=select\">Email all students to notify them of the project allocation</a></LI>";
    $admin_func_block .= '</UL>';

    $admin_func_block .= "<LI class='itemHeader'>Metrics</LI>";
    $admin_func_block .= '<UL>';
    //$admin_func_block .= "<LI><a href=\"" . $locationdir . "admin2/view_staff_load.php?pm_id=$pm_id\">View staff project load for this module</a></LI>";
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/view_staff_load_single.php?pm_id=$pm_id\">View staff project load for this module</a></LI>";
    $admin_func_block .= '</UL>';

    $admin_func_block .= "<LI class='itemHeader'>Deletion</LI>";
    $admin_func_block .= '<UL>';
    $admin_func_block .= '<LI><a href="' . $locationdir . "admin2/delete_module.php?pm_id=$pm_id\">Delete the module entry and all its content!</a> (Only use as a <B>desparate</B> measure.)</LI>";
    $admin_func_block .= '</UL>';

    $admin_func_block .= "</OL>\n";

    $admin_func_block .= "</DIV><BR>\n";
    return $admin_func_block;
}

/**
 * Check whether the current user has super-admin or project-admin access, and set a display phrase.
 *
 * Unlike the staff_student_functions.php version, this also accepts project-admins.
 *
 * @param PDO    $db                    Active PDO connection.
 * @param string $this_staff_username   Authenticated username.
 * @param string $this_staff_full_name  Staff member's full display name.
 * @param string $staff_names_table     Staff table name (used for project-admin check).
 * @param string $access_response_phrase Out param: human-readable access status message.
 * @return bool True if the user has any admin access (super or project).
 */
function access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names_table, &$access_response_phrase) {
    $access = false;
    if (IsAdmin($db, $this_staff_username)) {
        $access_phrase = "You, $this_staff_full_name ($this_staff_username), have system level admin access. Be very careful.";
        $access = true;
    } elseif (IsProjectAdmin($db, $this_staff_username, $staff_names_table)) {
        $access_phrase = "You, $this_staff_full_name ($this_staff_username), have module level admin access. Be careful.";
        $access = true;
    } else {
        $access_phrase = "You, $this_staff_full_name ($this_staff_username), do not have admin access rights. <BR> Please return to where you came from.";
    }

    $access_response_phrase = $access_phrase;
    return $access;
}

/**
 * Return true if the username has admin access to a specific module.
 *
 * @param PDO         $db         Active PDO connection.
 * @param string      $username   Web-server authenticated username.
 * @param string|null $pm_id      Project-module ID, 'all', or null to use short_code lookup.
 * @param string|null $short_code Module short code (used when $pm_id is null).
 * @param string|null $year_begin Academic year start (used with $short_code).
 * @return bool
 */
function IsModuleAdmin($db, $username, $pm_id, $short_code, $year_begin) {
    // Can test on either:
    // $pm_id
    // short_code and year_begin combination, for normal modules
    // short_code, for cwt, dist, pega, hamid
    // pm_id = all, wii search teh whole table for any module i.e. to find if the user has access to at least 1 module

    $admin_level_module = 0;
    if ($pm_id != null) { // this also functions for : cwt, dist, pega, hamid
        $admin_level_module = GetModuleAdminLevelFromPID($db, $username, $pm_id);
    } else {
        $admin_level_module = GetModuleAdminLevelFromShortCode($db, $username, $short_code, $year_begin);
    }

    if ($admin_level_module > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Like IsModuleAdmin() but also sets a human-readable access phrase out-param.
 *
 * Used on admin pages that need to display a context-appropriate message
 * alongside the access check result.
 *
 * @param PDO         $db                    Active PDO connection.
 * @param string      $username              Web-server authenticated username.
 * @param string      $full_name             Staff member's full display name.
 * @param string|null $pm_id                 Project-module ID, 'all', or null.
 * @param string|null $short_code            Module short code (used when $pm_id is null).
 * @param string|null $year_begin            Academic year start.
 * @param string      $access_response_phrase Out param: human-readable access status message.
 * @return bool True if the user has module admin access.
 */
function IsModuleAdmin2($db, $username, $full_name, $pm_id, $short_code, $year_begin, &$access_response_phrase) {
    // Can test on either:
    // $pm_id
    // short_code and year_begin combination, for normal modules
    // short_code, for cwt, dist, pega, hamid
    // pm_id = all, wii search teh whole table for any module i.e. to find if the user has access to at least 1 module

    $admin_level_module = 0;
    if ($pm_id != null) { // this also functions for : cwt, dist, pega, hamid
        $admin_level_module = GetModuleAdminLevelFromPID($db, $username, $pm_id);
    } else {
        $admin_level_module = GetModuleAdminLevelFromShortCode($db, $username, $short_code, $year_begin);
    }

    if ($admin_level_module > 0) {
        $access_response_phrase = "You, $full_name ($username), have <em>module level</em> admin access. Be careful how you use it.";
        return true;
    } else {
        $access_response_phrase = "You, $full_name ($username), do not have <em>module level</em> admin access. Please return to where you came from.";
        return false;
    }
}

/**
 * Return the module admin level for a given project-module ID.
 *
 * @param PDO    $db       Active PDO connection.
 * @param string $username Web-server authenticated username.
 * @param string $pm_id    Project-module ID or 'all'.
 * @return int 0 = no privilege, positive = admin level.
 */
function GetModuleAdminLevelFromPID($db, $username, $pm_id) {
    // Returns the project admin level for a module, 0 if no admin privilidges
    //
    $admin_level_module = 0;
    try {
        if ($pm_id == 'all') {
            $results = $db->prepare('SELECT * FROM admin_users_module WHERE username=:username');
            $results->execute([':username' => $username]);
        } else {
            $results = $db->prepare('SELECT * FROM admin_users_module WHERE project_id=:pm_id AND username=:username');
            $results->execute([':pm_id' => $pm_id, ':username' => $username]);
        }
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        return $admin_level_module;
        //exit();
    }

    if ($results->rowCount() > 0) {
        if ($pm_id == 'all') {
            $admin_level_module = 1;
        } else {
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
 * @return int 0 = no privilege, positive = admin level.
 */
function GetModuleAdminLevelFromShortCode($db, $username, $short_code, $year_begin) {
    // Returns the project admin level for a module, 0 if no admin privilidges
    //
    $admin_level_module = 0;
    $query = 'SELECT * FROM admin_users_module WHERE short_code=:short_code AND year_begin=:year_begin AND username=:username';

    try {
        $results = $db->prepare($query);
        $results->execute([':short_code' => $short_code, ':year_begin' => $year_begin, ':username' => $username]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        return $admin_level_module;
        //exit();
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        $admin_level_module = $result['admin_level'];
    }

    return $admin_level_module;
}
