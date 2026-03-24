<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 18/04/2016
 * Time: 18:59
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

function GetStudentNotOnModulePhrase()
{

    $html_block = "<div style='background-color: #f1c40f; color: red; text-align: center;  font-size: xx-large; font-weight: bold'>\n";
    $html_block .= "You are not listed as being on this module\n";
    $html_block .= "</div>\n";

    return $html_block;
}