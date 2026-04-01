<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot open staff_functions.php<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot open staff_functions.php<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h2>\n";

if($access) {

    $admin_users_table = "admin_users";
    require_valid_csrf();

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }

    if (array_key_exists('new_admin_staff_id', $_POST) == false) {
        $_POST['new_admin_staff_id'] = null;
    }
    $new_admin_staff_id = $_POST['new_admin_staff_id'];

    $display_block .= "<h1>Edit System Level Admin users </h1>";

    if ($POST_OP == "selected_new_staff") {
        try {
            $results = $db->prepare("SELECT * FROM $staff_names WHERE staffid=?");
            $results->execute([$new_admin_staff_id]);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $staff_names<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        if($results->rowCount() > 0) {
            $staff_recs = $results->fetch();
            $firstname = $staff_recs['firstname'];
            $familyname = $staff_recs['familyname'];

            $fullname = $staff_recs['fullname'];
            $username = $staff_recs['username'];
            $email = $staff_recs['email'];
            $staffid = $staff_recs['staffid'];

            $mod_admin_level = 3;

            // Insert the data into the admin_users_module table
            // Need to check that it does not already exist
            try {
                $results_admin = $db->prepare("SELECT * FROM $admin_users_table WHERE username=?");
                $results_admin->execute([$username]);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            if ($results_admin->rowCount() == 0) {
                try {
                    $results_admin = $db->prepare("INSERT INTO $admin_users_table SET firstname=?,familyname=?,fullname=?,email=?,username=?,admin_level=?");
                    $results_admin->execute([$firstname, $familyname, $fullname, $email, $username, $mod_admin_level]);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo("Unable to update data to the table $admin_users_table<BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }

                $display_block .= "<B>Admin user added: $fullname</B><BR>\n";
            } else {
                $display_block .= "<B>Admin user already exists: $fullname</B><BR>Username: $username<hr>\n";
            }


        }
    } elseif ($POST_OP == "submit_modified") {
        $display_block .= "Submitting: <BR>\n";

        $admin_staff_count = $_POST['admin_staff_count'];
        for ($i = 1; $i <= $admin_staff_count; $i++) {

            $postid = "username$i";
            $username = $_POST[$postid];

            $postid = "admin_level$i";
            $mod_admin_level = $_POST[$postid];

            $staffid = $_POST["staffid$i"];

            // Should not make yourself less than 3 - as you would be ejected from the admin section
            if ($username == $this_staff_username) {
                $display_block .= "You cannont make youself a lesser level admin user<BR>";
                if ($mod_admin_level < 3) {
                    $mod_admin_level = 3;
                }
            }
            try {
                $results = $db->prepare("UPDATE $admin_users_table SET admin_level=? WHERE username=?");
                $results->execute([$mod_admin_level, $username]);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                echo("Unable to update data to the table $admin_users_table<BR>\n");
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            // loop through all _POST find the "ai" keys
            if(array_key_exists("ai_$staffid",$_POST)){
                $staff_admin_index = $staffid;
                try {
                    $results = $db->prepare("DELETE FROM $admin_users_table WHERE `id`=?");
                    $results->execute([$staff_admin_index]);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }
            }
        }
        $display_block .= "<B>Done</B><BR>\n";
    }

//---------------------------------------------------------------------------------
// Display the admin users table

    $display_block .= "<HR>";
    $display_block .= "<H2>The current Admin users table</H2>";
    $display_block .= "<p>Change the admin level as appropriate</p>";
    $display_block .= "<p> 0,1,2 = Normal staff, 3 = Full system control : Super Admin.</p>";

    $display_block .= "<form method='post' action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "'>\n";
    $display_block .= csrf_token_field();

// Get the list of staff from the staff_names2 table
    $query = "select * from $admin_users_table order by familyname";
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

    $display_block .= "<TABLE id='adminstaffinfo' class='hover compact cell-border' cellspacing='0' width='100%'>\n";
    $display_block .= "
    <thead>
      <tr>
        <th>Staff ID</th>
        <th>Full name</th>
        <th>Email</th>
        <th>Username</th>
        <th>Admin level</th>
        <th>Tick to Delete</th>
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
    $admin_staff_count = 0;
    $rows = $results->fetchAll();
    foreach ($rows as $recs) {

        $staffid = $recs['id'];
        $firstname = stripslashes($recs['firstname']);
        $familyname = stripslashes($recs['familyname']);
        $email = stripslashes($recs['email']);
        $username = stripslashes($recs['username']);
        $mod_admin_level = stripslashes($recs['admin_level']);
        $admin_staff_count++;
        $display_block .= "<TR>\n";
        $display_block .= "<TD><input type='hidden' name='staffid$admin_staff_count' value='$staffid'> $staffid</TD>\n";
        $display_block .= "<TD><input type='text' name='fullname$admin_staff_count' value='$firstname $familyname'  size=30 maxlength=40 readonly></TD>\n";
        $display_block .= "<TD><input type='text' name='email$admin_staff_count' value='$email'  size=30 maxlength=40 readonly></TD>\n";
        //$display_block .= "<TD><input type='text' name='username$admin_staff_count' value='$username'  size=8 maxlength=20 readonly></TD>\n";
        $display_block .= "<TD><span id='username$admin_staff_count'>$username</span>";
        $display_block .= "<input type='hidden' name='username$admin_staff_count' value='$username'></TD>";
        $display_block .= "<TD>" . GetAdminLevelSelect("admin_level$admin_staff_count", $mod_admin_level) . "</TD>\n";
        $name_id = "ai_$staffid";
        $display_block .= "<TD><input type='checkbox' name='$name_id' id='$name_id' value='1'></TD>\n";
        $display_block .= "</TR>\n";

    }
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type='hidden' name='op' value='submit_modified'>";
    $display_block .= "\n<input type='hidden' name='admin_staff_count' value='$admin_staff_count'>";

    $display_block .= "<p><input class='btn btn-primary' type='submit' id='submitbutton' name='submit' value='Submit changes to admin users details.'></p>";

    $display_block .= "</form>";

    //$display_block .= "<hr>\n";

//---------------------------------------------------------------------------------

    $display_block .= "<hr><h2>Add a new system-level admin person ...</H2>\n";
    $display_block .= "<form method='post' action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "'>\n";
    $display_block .= csrf_token_field();
    $NameKey = 'new_admin_staff_id';
    $MatchID = null;
    $display_block .= GetStaffSelectID($db, $staff_names, $NameKey, $MatchID);
    $display_block .= "\n<input type='hidden' name='op' value='selected_new_staff'>";

    $display_block .= "<div class='m-3'><input class='btn btn-success' type='submit' id='submitbutton_new' name='submit_new' value='Add a NEW admin user to this module/year.'></div>";

    $display_block .= "</form>";
//---------------------------------------------------------------------------------

}
    function GetAdminLevelSelect($NameKey, $MatchLevel)
    {
        $levels[1] = "0";
        $levels[2] = "1";
        $levels[3] = "2";
        $levels[4] = "3";

        $select_block = "\n<select name='$NameKey'>\n";

        for ($i = 1; $i <= 4; $i++) {
            $Level = $levels[$i];

            if ($MatchLevel == $Level) {
                $selected = "selected";
            } else {
                $selected = "";
            }

            $select_block .= "<option value='$Level' $selected>$Level</option>\n";
        }
        $select_block .= "</select>\n";

        return $select_block;
    }

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Edit Staff details - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Admin index page</a></li>
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
<script>
    $("#adminstaffinfo").dataTable();
</script>
</html>
