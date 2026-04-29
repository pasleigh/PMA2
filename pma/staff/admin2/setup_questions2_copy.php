<?php
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');

(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('./utility_functions.php')) or die('Cannot read utility_functions file<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot read markstore_functions file<BR>');
(@include_once('../staff_functions.php')) or die('Cannot read staff_functions file<BR>');

(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

$display_block = '';
$display_block .= $connect_msg;

$assess_part_description_text = '';

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin || $admin_access) {
    if (array_key_exists('part', $_GET) == false) {
        $_GET['part'] = null;
    }
    $assesspart = $_GET['part'];

    $display_block .= '<h1>Be careful you are about to delete existing questions and replace them with others.</h1>';

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    $POST_OP = $_POST['op'];

    if (array_key_exists('AssessqsSelect', $_REQUEST) == false) {
        $_REQUEST['AssessqsSelect'] = null;
    }
    $MatchID = $_REQUEST['AssessqsSelect'];

    if (array_key_exists('assessqs_new', $_REQUEST) == false) {
        $_REQUEST['assessqs_new'] = null;
    }
    $assessqs_new_table = $_REQUEST['assessqs_new'];
    if (array_key_exists('assessqs_cats_new', $_REQUEST) == false) {
        $_REQUEST['assessqs_cats_new'] = null;
    }
    $assessqs_cats_new_table = $_REQUEST['assessqs_cats_new'];

    // definitions for each part
    // Questions table
    $assessqs_current_table = $assessqs[$assesspart];
    // Questions categories table
    $assessqs_cats_current_table = $assessqs_cats[$assesspart];
    // description
    $assesstype_text = $p_description[$assesspart];
    // markbook
    $mark_max = $p_max[$assesspart];
    $mark_pc = $p_percent[$assesspart];

    $display_block .= '<h2>Current assessment part</h2>';
    $display_block .= "Title of this assessment part = <b>$assesstype_text</b><BR>\n";
    $display_block .= "Current section titles table = <b>$assessqs_cats_current_table</b><BR>\n";
    $display_block .= "Current questions table = <b>$assessqs_current_table</b><BR>\n";

    // Compile an array of the assessqs and assessqs_cats tables in teh database
    $assessqs_tables = [];
    $assessqs_cats_tables = [];

    $table_search = '%assessqs_%';
    $query = "SHOW TABLES LIKE '$table_search'";
    try {
        $setup = $db->query($query);
        $results = $setup->fetchAll();
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        exit();
    }

    $cats_count = 0;
    $qs_count = 0;
    foreach ($results as $row) {
        $table_name = $row[0];
        if (strpos($table_name, '_cats') > 0) {
            $assessqs_cats_tables[$cats_count] = $table_name;
            $cats_count++;
        } else {
            $assessqs_tables[$qs_count] = $table_name;
            $qs_count++;
        }
    }

    $select_block = '<h2>Select a replacement question table from the dropdown list</h2>';
    $NameKey = 'AssessqsSelect';
    $select_block .= "\n<select name='$NameKey' id='$NameKey' >\n";
    for ($i = 0 ; $i < count($assessqs_tables) ; $i++) {
        $value = $i + 1 . ': ' . $assessqs_tables[$i];
        $value2 = $assessqs_cats_tables[$i];
        if ($value == $MatchID) {
            $select_block .= "<option value='$i' selected='selected'>$value</option>\n";
        } else {
            $select_block .= "<option value='$i''>$value</option>\n";
        }
    }
    $select_block .= "\n</select>\n";

    if ($POST_OP == 'delete_project') {
        $display_block .= '<BR>Table that will replace existing question data: <b>' . $assessqs_tables[$MatchID] . '</b><BR><BR>';
        $display_block .= "<H1>Delete / Replace confirmation credentials</H1>\n";

        $assessqs_new_table = $assessqs_tables[$MatchID];
        $assessqs_cats_new_table = $assessqs_cats_tables[$MatchID];

        $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id&part=$assesspart'>\n";

        $display_block .= "\n<input type='hidden' name='assessqs_new' value='$assessqs_new_table'>\n";
        $display_block .= "\n<input type='hidden' name='assessqs_cats_new' value='$assessqs_cats_new_table'>\n";
        $display_block .= "\n<input type='hidden' name='op' value='delete_confirmed'>\n";

        $display_block .= 'Tick to confirm: ';
        $display_block .= "<input type='checkbox' name='confirm_delete'><br>\n";
        $display_block .= "\n<input type='hidden' name='op' value='delete_confirmed'>\n";
        $display_block .= "<p><input type='submit' id='submitbutton' name='submit' value='I confirm I wish to delete existing questions and replace them - I KNOW THAT I CANNOT GET THEM BACK.'></p>\n";
        $display_block .= "</form>\n";
        $display_block .= "<a href='./edit_module_settings.php?pm_id=$pm_id'>Or go back to module admin page</a>";
    } elseif ($POST_OP == 'delete_confirmed') {
        $confirm_delete = false;
        if (array_key_exists('confirm_delete', $_POST) == true) {
            $confirm_delete = true;
        }

        if ($confirm_delete) {
            $display_block .= "<BR><h2>Delete confirmed</h2>\n";
            $display_block .= "Check Confirm delete OK<BR>\n";
            $display_block .= "Let's replace the question tables<BR>\n";

            // Delete all tables:
            $display_block .= "<h2>These are the tables in this module that will be deleted:</h2>\n";
            $display_block .= '<b>' . $assessqs_cats_current_table . '</b><BR><BR>';
            $display_block .= '<b>' . $assessqs_current_table . '</b><BR>';

            $display_block .= "<h2>And these will replace them:</h2>\n";
            $display_block .= '<b>' . $assessqs_cats_new_table . '</b><BR><BR>';
            $display_block .= '<b>' . $assessqs_new_table . '</b><BR>';

            // replace current from new
            $query = "TRUNCATE $assessqs_cats_current_table";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
            $query = "INSERT INTO $assessqs_cats_current_table SELECT * FROM $assessqs_cats_new_table";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }

            // replace current from new
            $query = "TRUNCATE $assessqs_current_table";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
            $query = "INSERT INTO $assessqs_current_table SELECT * FROM $assessqs_new_table";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
            $display_block .= '<h2>DONE</h2>';
            $display_block .= "<a href='./edit_module_settings.php?pm_id=$pm_id'>Go back to module admin page</a>";
        } else {
            $display_block .= "<BR><h2>Delete cancelled</h2>\n";
            $display_block .= '<BR><B>The confirmation of delete was not checked. No tables were deleted</B><BR><BR>';
            $display_block .= "<a href='./edit_module_settings.php?pm_id=$pm_id'>Go back to module admin page</a>";
        }
    } else {
        $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id&part=$assesspart'>\n";
        $display_block .= $select_block;
        $display_block .= "\n<input type='hidden' name='op' value='delete_project'>";
        $display_block .= "<p><input class='btn btn-primary mt-3'  type='submit' id='submitbutton' name='submit' value='Proceed to replace the existing questions with those from the selected -  YOU ACKNOWLEDGE THAT EXISTING QUESTION DATA WILL BE OVERWRITTEN.'></p>";
        $display_block .= "</form>\n";
    }
} else {
    $display_block .= 'You do not have the appropriate admin level<BR>';
    $display_block .= "<A href='../index.php'>Go back here</A><BR>";
}

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Replace Assessment Questions - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../projectmarking_select_bs.php?pm_id=<?php echo("$pm_id") ?>" class="nav-link px-2 text-white-50">Module page</a></li>
            <li class="nav-item"><a target="_self" href="../session_modules_bs.php?year_begin= <?php echo("$year_begin") ?>" class="nav-link px-2 text-white-50">This year module list</a></li>
            <li class="nav-item"><a target="_self" href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>" class="nav-link px-2 text-white-50">Module admin page</a></li>
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin page</a></li>
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
