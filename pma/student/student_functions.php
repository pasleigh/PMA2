<?php
/**
 * Student lookup helpers for the student-facing tier.
 *
 * All functions accept a $students parameter which is the module-specific
 * students table name (set by setup_titles.php).
 */

/**
 * Return a student's display name looked up by their login username.
 *
 * @param PDO    $db               Active PDO connection.
 * @param string $student_username Web-server authenticated username.
 * @param string $students         Students table name.
 * @return string Student name, or empty string if not found.
 */
function GetStudentNameFromUsername($db,$student_username,$students)
{
    $student_name = "";

    $query = "SELECT * FROM $students WHERE username='$student_username'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $students table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    if ($results->rowCount() > 0) {
        $recs = $results->fetch();
        $student_name = $recs['name'];
    }

    return $student_name;
}

/**
 * Return a student's numeric ID (sid) looked up by their login username.
 *
 * @param PDO    $db               Active PDO connection.
 * @param string $student_username Web-server authenticated username.
 * @param string $students         Students table name.
 * @return string Numeric student ID, or empty string if not found.
 */
function GetStudentIDFromUsername($db,$student_username,$students)
{
    $student_id = "";

    $query = "SELECT * FROM $students WHERE username='$student_username'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $students table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    if ($results->rowCount() > 0) {
        $recs = $results->fetch();
        $student_id = $recs['sid'];
    }

    return $student_id;
}
/**
 * Return a student's degree programme looked up by their login username.
 *
 * @param PDO    $db               Active PDO connection.
 * @param string $student_username Web-server authenticated username.
 * @param string $students         Students table name.
 * @return string Programme string, or empty string if not found.
 */
function GetStudentProgrammeFromUsername($db,$student_username,$students)
{
    $student_programme = "";

    $query = "SELECT * FROM $students WHERE username='$student_username'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $students table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    if ($results->rowCount() > 0) {
        $recs = $results->fetch();
        $student_programme = $recs['programme'];
    }

    return $student_programme;
}

/**
 * Return a student's login username looked up by their numeric ID.
 *
 * @param PDO    $db         Active PDO connection.
 * @param int    $student_id Numeric student ID (sid column).
 * @param string $students   Students table name.
 * @return string Login username, or empty string if not found.
 */
function GetStudentUsernameFromID($db,$student_id,$students)
{
    $student_username = "";

    $query = "SELECT * FROM $students WHERE sid='$student_id'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $students table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    if ($results->rowCount() > 0) {
        $recs = $results->fetch();
        $student_username = $recs['username'];
    }

    return $student_username;
}

/**
 * Return a student's degree programme looked up by their numeric ID.
 *
 * @param PDO    $db         Active PDO connection.
 * @param int    $student_id Numeric student ID (sid column).
 * @param string $students   Students table name.
 * @return string Programme string, or empty string if not found.
 */
function GetStudentProgrammeFromID($db,$student_id,$students)
{
    $student_programme = "";

    $query = "SELECT * FROM $students WHERE sid='$student_id'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $students table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    if ($results->rowCount() > 0) {
        $recs = $results->fetch();
        $student_programme = $recs['programme'];
    }

    return $student_programme;
}

/**
 * Return true if the username exists in the global `staff_names` table.
 *
 * Used on student pages to allow staff to also view student-facing content.
 *
 * @param PDO    $db       Active PDO connection.
 * @param string $username Web-server authenticated username.
 * @return bool
 */
function isStaff($db, $username)
{
    $result = false;

    $query = "SELECT * FROM staff_names WHERE username='$username'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the staff_names table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    if ($results->rowCount() > 0) {
        $result = true;
    }
    return $result;
}
/**
 * Return true if the student username appears in the module's students table.
 *
 * Note: $pm_id is accepted for signature compatibility but is not used in the
 * current query — the students table is already module-specific.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $pm_id            Project-module ID (currently unused in query).
 * @param string $student_username Web-server authenticated username.
 * @param string $students         Students table name.
 * @return bool
 */
function isStudent_on_this_module($db, $pm_id, $student_username, $students)
{
    $result = false;

    $query = "SELECT * FROM $students WHERE username='$student_username'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $students table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    if ($results->rowCount() != 0) {
        $result = true;
    }

    return $result;
}

/**
 * Return an HTML warning banner for a student who is not listed on the current module.
 *
 * @return string HTML div block with a bold yellow/red warning message.
 */
function GetStudentNotOnModulePhrase()
{

    $html_block = "<div style='background-color: #f1c40f; color: red; text-align: center;  font-size: xx-large; font-weight: bold'>\n";
    $html_block .= "You are not listed as being on this module\n";
    $html_block .= "</div>\n";

    return $html_block;
}

function GetStudentFriendlyName(string $name): array {
    // Split "Surname, Firstname A. B." on the first comma
    $parts = explode(',', $name, 2);
    if (count($parts) < 2) {
        return ['surname' => trim($name), 'firstname' => ''];
    }
    $surname   = trim($parts[0]);
    // First whitespace-delimited token after the comma is the firstname;
    // anything after that is initials ("A.", "B. C.", etc.)
    $tokens    = preg_split('/\s+/', trim($parts[1]));
    $firstname = $tokens[0] ?? '';
    return ['surname' => $surname, 'firstname' => $firstname];
}
