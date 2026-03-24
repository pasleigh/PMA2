<?PHP

function IsStaff($db, $username, $staff_names)
{
    $query = "SELECT * FROM $staff_names WHERE username='$username'";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        echo(" Query = $query<BR \n");
        $err_msg = $ex->getMessage();
        echo($err_msg);
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        //$staff_id = $result['staffid'];
        return true;
    }

    return false;
}

function IsStudent($db, $username, $students)
{
    $query = "SELECT * FROM $students WHERE username='$username'";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        echo(" Query = $query<BR \n");
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return false;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        //$staff_id = $result['staffid'];
        return true;
    }

    return false;
}

function GetSuperAdminLevel_staff_student($db, $username)
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

function access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names_table, &$access_response_phrase) {
    $access = false;
    if (GetSuperAdminLevel_staff_student($db, $this_staff_username)) {
        $access_phrase = "Hello $this_staff_full_name ($this_staff_username), you have <em>system level</em> admin access. Be very careful.";
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
        echo "An Error occured running query<BR>\n";
        echo(" Query = $query<BR \n");
        $err_msg = $ex->getMessage();
        echo($err_msg);
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
?>
