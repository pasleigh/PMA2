<?php
function GetStaffNameFromID($db, $staff_id, $staff_table, $usetitle)
{
    $name = "";
    if ($staff_id == "") {
        return $name;
    }
//echo("Staff table $staff_table<BR>");
    $get_list_query = "select * from $staff_table where staffid=$staff_id";
    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from database. In GetStaffNameFromID. Query: $get_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

        return $name;
    }

    $recs = $get_list_res->fetch();

    if($recs) {
        //$initials = $recs['initials'];
        $fullname = $recs['fullname'];
        $fullname = stripslashes($fullname);
        //$firstname = stripslashes($recs['firstname']);
        //$familyname = stripslashes($recs['familyname']);
        $title = $recs['title'];
        $title = stripslashes($title);
        //echo("Full name $fullname<BR>");


        if ($usetitle == 1) {
            $name = $title . " " . $fullname;
        } else {
            $name = $fullname;
        }
    }else{
        $name = "";
    }

    return $name;
}
function GetStaffEmailFromID($db, $staff_id, $staff_table)
{
    $name = "";
    if ($staff_id == "") {
        return $name;
    }
//echo("Staff table $staff_table<BR>");
    $get_list_query = "select * from $staff_table where staffid=$staff_id";
    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from database. In GetStaffEmailFromID. Query: $get_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

        return $name;
    }

    $recs = $get_list_res->fetch();

    if($recs) {
        $email = $recs['email'];
        if ($email) {
            $email = stripslashes($recs['email']);
        }
    }else{
        $email = "";
    }

    return $email;
}

function GetStaffEmailFromFullname($db, $staff_fullname, $staff_table)
{
    $staff_email = "";
    $staff_id = GetStaffIDFromName($db, $staff_fullname, $staff_table);
    if($staff_id != ""){
        $staff_email = GetStaffEmailFromID($db, $staff_id, $staff_table);
    }

    return $staff_email;
}
function GetStaffUsernameFromFullname($db, $staff_fullname, $staff_table){
    $username = "";
    if ($staff_fullname == "") {
        return $username;
    }
    $get_list_query = "select * from $staff_table where fullname='$staff_fullname'";
    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from database. In GetStaffNameFromFullname. Query: $get_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
        return $username;
    }

    $recs = $get_list_res->fetch();

    if($recs){
        $username = $recs['username'];
        if($username){
            $username = stripslashes($recs['username']);
        }
    }

    return $username;
}
function GetStaffNameFromUsername($db, $staff_username, $staff_table, $usetitle)
{
    $name = "";
    if ($staff_username == "") {
        return $name;
    }
//echo("Staff table $staff_table<BR>");
    $get_list_query = "select * from $staff_table where username='$staff_username'";
    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from database. In GetStaffNameFromUsername. Query: $get_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
        return $name;
    }

    $recs = $get_list_res->fetch();

    if($recs){
        //$initials = $recs['initials'];
        $fullname = stripslashes($recs['fullname']);
        //$firstname = stripslashes($recs['firstname']);
        //$familyname = stripslashes($recs['familyname']);
        $title = stripslashes($recs['title']);
        //echo("Full name $fullname<BR>");
    }else{
        $fullname = "Unknown";
        $title = "unknown";
    }


    if ($usetitle == 1) {
        $name = $title . " " . $fullname;
    } else {
        $name = $fullname;
    }

    return $name;
}

function GetStaffIDFromName($db, $staff_fullname, $staff_table)
{
    $staff_id = "";
    if ($staff_fullname == "") {
        return $staff_id;
    }
    $get_list_query = "select * from $staff_table where fullname='$staff_fullname'";
    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from database. In GetStaffIDFromName. Query: $get_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

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
            //echo("$staff_fullname ($staff_id)<BR>");
            return $staff_id;
        }
    }

    //echo("$staff_fullname ($staff_id)<BR>");
    return $staff_id;
}

function GetStaffIDFromFamilyNameAndInitial($db, $staff_family_name, $staff_table)
{
    // Finds match for:
    // Sleigh
    // Sleigh A
    // Sleigh Andrew
    //
    $staff_id = "0";
    if ($staff_family_name == "") {
        return $staff_id;
    }

    // Split the family name as it may contain a trailing initial
    // Code needed to deal with tha
    //

    $staff_family_name_split = explode(" ", $staff_family_name);


    $get_list_query = "select * from $staff_table where familyname LIKE '%{$staff_family_name_split[0]}%'";

    if (count($staff_family_name_split) > 1) {
        $get_list_query .= " AND firstname LIKE '{$staff_family_name_split[1]}%'";
    }

    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from database. In  GetStaffIDFromFamilyNameAndInitial. Query: $get_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

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

    //echo("$familyname ($staff_id)<BR>");
    return $staff_id;
}

function GetStaffIDFromFamilyNameAndInitial2($db, $staff_family_name, $staff_table)
{
    // Finds match for:
    // Sleigh
    // Sleigh, A
    // Sleigh, Andy
    //
    $staff_id = "0";
    if ($staff_family_name == "") {
        return $staff_id;
    }

    // Split the family name as it may contain a trailing initial
    $staff_family_name_split = explode(", ", $staff_family_name);
    
    $get_list_query = "select * from $staff_table where familyname LIKE '%{$staff_family_name_split[0]}%'";

    if (count($staff_family_name_split) > 1) {
        $firstname = trim($staff_family_name_split[1]);
        $get_list_query .= " AND firstname LIKE '{$firstname}%'";
    }

    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from database. In  GetStaffIDFromFamilyNameAndInitial. Query: $get_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

        return $staff_id;
    }
    //echo($get_list_query . "<BR>");

    //$num_staff_matches = $get_list_res->rowCount();

    $rows = $get_list_res->fetchAll();

    foreach ($rows as $recs) {
        $staff_id = $recs['staffid'];
        //echo("staff id: $staff_id<BR>");
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

    //echo("$familyname ($staff_id)<BR>");
    return $staff_id;
}

function DoesStaffExistByUsername($db, $staff_username, $staff_table){
    $staff_id = GetStaffIDFromUsername($db, $staff_username, $staff_table);
    if($staff_id == ""){
        return false;
    }else{
        return true;
    }

}

function GetStaffIDFromUsername($db, $staff_username, $staff_table)
{
    $staff_id = "";
    if ($staff_username == "") {
        return $staff_id;
    }
    $get_list_query = "select * from $staff_table where username='$staff_username'";
    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from database. In GetStaffIDFromUsername. Query: $get_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

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
            //echo("$staff_username, $fullname ($staff_id)<BR>");
            return $staff_id;
        }
    }

    //echo("$staff_fullname ($staff_id)<BR>");
    return $staff_id;
}

function GetStaffSelectID($db, $staff_table, $NameKey, $MatchID)
{
    $staff_select_block = "\n<select name=\"$NameKey\" id=\"$NameKey\" class='form-control'>\n";
    $staff_select_block .= "<option value=\"-1\">--- Select Staff Name ---</option>\n";
    $get_staff_list_query = "select * from $staff_table order by familyname";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from table. Query: $get_staff_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

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

function get_staff_list_json($db, $staff_table){
    $staff_array = array();
    $err_msg = "";

    $get_staff_list_query = "select * from $staff_table order by familyname";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

    $rows = $get_staff_list_res->fetchAll();

    foreach ($rows as $recs) {
        //$initials = $recs['initials'];
        $staff_name = stripslashes($recs['fullname']);
        $staffid = $recs['staffid'];
        $inactive = $recs['inactive'];

        if ($inactive == 0) {
            $this_staff = array(
                'id'=>$staffid,
                'staff_name'=>"$staff_name"
            );
            $staff_array[] = $this_staff;
        }
    }
    return $staff_array;
}

function GetSchoolSelectID($db,  $NameKey, $MatchID)
{
    $definition_table = GetKMAPDefinitionTableName();
    $school_select_block = "\n<select name='$NameKey' id='$NameKey' class='form-control'>\n";
    $school_select_block .= "<option value='-1'>--- Select a School ---</option>\n";
    $get_school_list_query = "SELECT * FROM $definition_table WHERE 1";
    try {
        $get_school_list_res = $db->query($get_school_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create school name list from table. Query: $get_school_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

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

function GetSemesterSelectID($db,  $NameKey, $MatchID)
{
    $semester_table = GetKMAPBannerPTRMCodesTableName();
    $semester_select_block = "\n<select name='$NameKey' id='$NameKey' class='form-control'>\n";
    //$semester_select_block .= "<option value='-1'>--- Select a Semester ---</option>\n";
    $get_semester_list_query = "SELECT * FROM $semester_table WHERE 1";
    try {
        $get_semester_list_res = $db->query($get_semester_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create semester list from table. Query: $get_semester_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

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
function GetStaffSelectIDWithOnChange($db, $staff_table, $NameKey, $MatchID, $OnChange)
{
    $staff_select_block = "\n<select name=\"$NameKey\" onchange=\"$OnChange\">\n";
    $staff_select_block .= "<option value=\"-1\">--- Select Staff Name ---</option>\n";
    $get_staff_list_query = "select * from $staff_table order by familyname";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from table. Query: $get_staff_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

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

function GetAllStaffSelectOptions($db, $staff_table){
    $staff_select_options_block = "";
    $staff_select_options_block .= "<option value='-1'>--- Select Staff Name ---</option>\n";
    $get_staff_list_query = "select * from $staff_table order by familyname";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from table. Query: $get_staff_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

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

function CompareIDandFullname($db, $staff_table, $StaffID, $StaffName)
{
    $get_staff_list_query = "select * from $staff_table where staffid = $StaffID";
    try {
        $get_staff_list_res = $db->query($get_staff_list_query);
    } catch (PDOException $ex) {
        echo("Unable to create staff name list from table. Query: $get_staff_list_query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");

        return FALSE;
    }
    $recs = $get_staff_list_res->fetch();

    //$initials = $recs['initials'];
    //$staffname = stripslashes($recs['fullname']);
    //$staffid = $recs['staffid'];
    //$inactive = $recs['inactive'];
    $fullname = $recs['fullname'];

    if ($StaffName == $fullname) {
        return TRUE;
    }

    return FALSE;
}

function CountSupervisedProjectsByID($db,$projects_master_name_table,$staffid)
{
    $num_supervise = 0;

    $query = "select * from $projects_master_name_table where f_supervisor_id = $staffid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_supervise = $results->rowCount();

    return $num_supervise;
}
function CountSecondMarkedProjectsByID($db,$project_details_table,$staffid){
    $num_second_mark = 0;
    $query = "select * from $project_details_table where f_assocstaff_id = $staffid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_second_mark = $results->rowCount();

    return $num_second_mark;
}

/**

 *  Ã¢â‚¬Ëœ  8216  curly left single quote

 *  Ã¢â‚¬â„¢  8217  apostrophe, curly right single quote

 *  Ã¢â‚¬Å“  8220  curly left double quote

 *  Ã¢â‚¬Â  8221  curly right double quote

 *  Ã¢â‚¬â€  8212  em dash

 *  Ã¢â‚¬â€œ  8211  en dash

 *  Ã¢â‚¬Â¦  8230  ellipsis

 */
function convert_smart_quotes($string)
{
    $search = array(
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
    );

    $replace = array(
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
    );
    return str_replace($search, $replace, $string);
}