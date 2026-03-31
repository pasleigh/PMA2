<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./ADMINconnect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");

$display_block = "";
$display_block .= $connect_msg;
$display_block .= "<h1>Edit Staff details</h1>";
$display_block .= "<h2>Database table $staff_names2</h2>";

if (array_key_exists('op', $_POST) == false) {
    $_POST['op'] = null;
}
// if the form was submitted then enter it all into the database
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}

// see all the POST values
//foreach ($_POST as $key => $value){
//}

$selected ="";
if ($POST_OP == "new") {
    $display_block .= "<P>A New member of staff.</P>";

    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">";
    $display_block .= "<table>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Title</TD>\n";
    $display_block .= "<TD><select name=\"title_select\">\n";
    $display_block .= "<option value=\"Prof\" $selected>Prof</option>\n";
    $display_block .= "<option value=\"Dr\" $selected>Dr</option>\n";
    $display_block .= "<option value=\"Mrs\" $selected>Mrs</option>\n";
    $display_block .= "<option value=\"Ms\" $selected>Ms</option>\n";
    $display_block .= "<option value=\"Miss\" $selected>Miss</option>\n";
    $display_block .= "<option value=\"Mr\" $selected>Mr</option>\n";
    $display_block .= "</select></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Initials</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"initials\"  size=10 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>First name</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"firstname\" size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Family name</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"familyname\" size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>email</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"email\" size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>username</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"username\" size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Room</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"roomX\" size=10 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Inactive (1=not taking part in projects)</TD>\n";
    $display_block .= "<TD><select name=\"inactive_select\">\n";
    $display_block .= "<option value=\"1\" $selected>1</option>\n";
    $display_block .= "<option value=\"0\" $selected>0</option>\n";
    $display_block .= "</select></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Admin level (0=normal staff, 1=module leader, 2 or 3  do not use)</TD>\n";
    $display_block .= "<TD><select name=\"admin_level_select\">\n";
    $selected = "selected";
    $display_block .= "<option value=\"0\" $selected>0</option>\n";
    $selected = "";
    $display_block .= "<option value=\"1\" $selected>1</option>\n";
    $display_block .= "<option value=\"2\" $selected>2</option>\n";
    $display_block .= "<option value=\"3\" $selected>3</option>\n";
    $display_block .= "</select></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "</table>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_new\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Submit details of this new staff member.\"></p>";

    $display_block .= "</form>";

    $display_block .= "<hr>\n";
}
elseif ($POST_OP == "edit") {
    $display_block .= "<P>Editing the details of the member of staff.<BR>";

    $staffid = $_POST['staffname_select'];
    $display_block .= "<h3>Staffid: $staffid</h3>";
    // Look up the name
    $query = "select * from $staff_names2 where staffid=$staffid";
    //$get_list_res = mysql_query($get_list) or die(" Unable to create staff name list from database. <BR>Error msg: " . mysql_error() . "<BR>Query: $get_list");
    //$recs = mysql_fetch_assoc($get_list_res);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $recs = $results->fetch();
    $initials = $recs['initials'];
    $title = $recs['title'];
    $firstname = stripslashes($recs['firstname']);
    $familyname = stripslashes($recs['familyname']);
    $fullname = stripslashes($recs['fullname']);
    $email = stripslashes($recs['email']);
    $username = stripslashes($recs['username']);
    $room = stripslashes($recs['room']);
    $inactive = stripslashes($recs['inactive']);
    $mod_admin_level = stripslashes($recs['admin_level']);

    $display_block .= "<h3>$title $fullname</h3>\n";

    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">";
    $display_block .= "<table class=\"tableSimple\">\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Title</TD>\n";
    $display_block .= "<TD><select name=\"title_select\">\n";

    if ($title == "Prof") {
        $selected = "selected";
    } else {
        $selected = "";
    }
    $display_block .= "<option value=\"Prof\" $selected>Prof</option>\n";
    if ($title == "Dr") {
        $selected = "selected";
    } else {
        $selected = "";
    }
    $display_block .= "<option value=\"Dr\" $selected>Dr</option>\n";
    if ($title == "Mrs") {
        $selected = "selected";
    } else {
        $selected = "";
    }
    $display_block .= "<option value=\"Mrs\" $selected>Mrs</option>\n";
    if ($title == "Ms") {
        $selected = "selected";
    } else {
        $selected = "";
    }
    $display_block .= "<option value=\"Ms\" $selected>Ms</option>\n";
    if ($title == "Miss") {
        $selected = "selected";
    } else {
        $selected = "";
    }
    $display_block .= "<option value=\"Miss\" $selected>Miss</option>\n";
    if ($title == "Mr") {
        $selected = "selected";
    } else {
        $selected = "";
    }
    $display_block .= "<option value=\"Mr\" $selected>Mr</option>\n";
    $display_block .= "</select></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Initials</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"initials\" value=\"$initials\" size=10 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>First name</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"firstname\" value=\"$firstname\" size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Family name</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"familyname\" value=\"$familyname\" size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>email</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"email\" value=\"$email\" size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>username</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"username\" value=\"$username\" size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Room</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"roomX\" value=\"$room\" size=10 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Inactive (1=not taking part in projects)</TD>\n";
    $display_block .= "<TD><select name=\"inactive_select\">\n";
    if ($inactive == "1") {
        $selected = "selected";
    } else {
        $selected = "";
    }
    $display_block .= "<option value=\"1\" $selected>1</option>\n";
    if ($inactive == "0") {
        $selected = "selected";
    } else {
        $selected = "";
    }
    $display_block .= "<option value=\"0\" $selected>0</option>\n";
    $display_block .= "</select></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Admin level (0=normal staff, 1=module leader, 2 or 3  do not use)</TD>\n";
    $display_block .= "<TD><select name=\"admin_level_select\">\n";
    if($mod_admin_level == 0){$selected = "selected";}else{$selected = "";}
    $display_block .= "<option value=\"0\" $selected>0</option>\n";
    if($mod_admin_level == 1){$selected = "selected";}else{$selected = "";}
    $display_block .= "<option value=\"1\" $selected>1</option>\n";
    if($mod_admin_level == 2){$selected = "selected";}else{$selected = "";}
    $display_block .= "<option value=\"2\" $selected>2</option>\n";
    if($mod_admin_level == 3){$selected = "selected";}else{$selected = "";}
    $display_block .= "<option value=\"3\" $selected>3</option>\n";
    $display_block .= "</select></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "</table>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"staffid\" value=\"$staffid\">";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_edited\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_edited\" name=\"submit\" value=\"Submit these edits.\"></p>";

    $display_block .= "</form>";

    $display_block .= "<hr>\n";
}
elseif ($POST_OP == "submit_edited") {
    $display_block .= "Submitting: <BR>\n";

    $staffid = $_POST['staffid'];
    $title = $_POST['title_select'];
    $initials = $_POST['initials'];
    $firstname = $_POST['firstname'];
    $familyname = $_POST['familyname'];
    $fullname = "$firstname $familyname";
    $email = $_POST['email'];
    $username = $_POST['username'];
    $room = $_POST['roomX'];
    $inactive = $_POST['inactive_select'];
    $mod_admin_level = $_POST['admin_level_select'];

    $display_block .= "$staffid, $fullname, $firstname, $familyname, $initials, $title, $email, $username, $room, $inactive, $mod_admin_level .... <BR>\n";
    $query = "update $staff_names2 set initials='$initials',title='$title',firstname='$firstname',familyname='$familyname',fullname='$fullname',email='$email',username='$username',room='$room',inactive='$inactive',admin_level='$mod_admin_level' where staffid=$staffid";
    //$display_block .= $query;
    //mysql_query($query) or die(" Unable to modify data to the table $staff_names2 . <BR>Query: $query");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo("Unable to update data to the table $staff_names2<BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $display_block .= "<B>Done</B><BR><hr>\n";
}
elseif ($POST_OP == "submit_new") {
    $display_block .= "Submitting: <BR>\n";

    //$staffid = $_POST['staffid'];
    $title = $_POST['title_select'];
    $initials = $_POST['initials'];
    $firstname = $_POST['firstname'];
    $familyname = $_POST['familyname'];
    $fullname = "$firstname $familyname";
    $email = $_POST['email'];
    $username = $_POST['username'];
    $room = $_POST['roomX'];
    $inactive = $_POST['inactive_select'];
    $mod_admin_level = $_POST['admin_level_select'];

    $display_block .= "$fullname, $firstname, $familyname, $initials, $title, $email, $username, $room, $inactive, $mod_admin_level .... <BR>\n";
    $query = "INSERT INTO $staff_names2 (initials,title,firstname,familyname,fullname,email,username,room,inactive,admin_level) VALUES ('$initials','$title','$firstname','$familyname','$fullname','$email','$username','$room','$inactive','$mod_admin_level')  ";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $display_block .= "<B>Done</B><BR><hr>\n";
}

// Display the dropdowns to select the staff member
$display_block .= "<h2>Select the member of staff to edit ...</H2>\n";
$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

// Get the list of staff from the staff_names2 table
$query = "select * from $staff_names2 order by familyname";
//$get_list_res = mysql_query($get_list) or die(" Unable to create staff name list from database. <BR>Error msg: " . mysql_error() . "<BR>Query: $get_list");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$display_block .= "<select name=\"staffname_select\">\n";
$rows = $results->fetchAll();
foreach ($rows as $row) {
    //$initials = $row['initials'];
    //$staffname = stripslashes($row['fullname']);
    $firstname = stripslashes($row['firstname']);
    $familyname = stripslashes($row['familyname']);
    $staffid = stripslashes($row['staffid']);
    $display_block .= "<option value=\"$staffid\">$staffid: $firstname $familyname</option>\n";
}

$display_block .= "</select>\n<br>";

$display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"edit\">";

$display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Edit details for this member of staff.\"></p>";

$display_block .= "</form>";

//---------------------------------------------------------------------------------
$display_block .= "<hr><h2>Add a new member of staff...</H2>\n";
$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

$display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"new\">";

$display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Add details of a NEW member of staff.\"></p>";

$display_block .= "</form>";

$display_block .= "<HR><P><a href='./edit_staff_table.php'>Back to Old Staff Edit page</a></P>"

//mysql_close();
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Edit Staff details</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Admin index page</a></li>
            <li class="nav-item"><a target="_self" href="../index.php" class="nav-link px-2 text-white-50">This year module list</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
        <?php echo $display_block; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>
