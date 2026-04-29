<?php
// connect to the datbase
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');

(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');

(@include_once('../markstore_functions.php')) or die('Cannot include markstore_functions.php<BR>');
(@include_once('./markstore_functions_admin.php')) or die('Cannot include markstore_functions_admin.php<BR>');
(@include_once('../proposals/project_display_functions_staff.php')) or die('Cannot include project_display_functions_staff.php<BR>');

(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

$display_block = '';
$display_block .= $connect_msg;

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = (int)$_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin || $admin_access) {
    require_valid_csrf();

    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = '';
    }

    if ($POST_OP == 'delete') {
        // delete the entries in the uniqe programme names table
        // $students_programmes
        $query = "TRUNCATE TABLE $students_programmes";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
        }
    }

    // Get the list of distinct programmes and any returned value, setting to -1 should there not be any
    $query = "SELECT DISTINCT programme FROM $students ";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }

    // Check and Update the students_programmes_table as necessary
    $rows = $results->fetchAll();
    $i = 0;
    foreach ($rows as $recs) {
        $prog_distinct_names[$i] = $recs['programme'];

        $prog_full_name = $prog_distinct_names[$i];
        try {
            $results = $db->prepare("SELECT * FROM $students_programmes WHERE prog_full_name=?");
            $results->execute([$prog_full_name]);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        if ($results->rowCount() == 1) {
            // Get the index value
            $rec_p = $results->fetch();
            $prog_short_name_index[$i] = $rec_p['prog_short_name_index'];
        } else {
            // Put the programme full name in the table
            try {
                $results = $db->prepare("INSERT INTO $students_programmes SET prog_full_name=?");
                $results->execute([$prog_full_name]);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $prog_short_name_index[$i] = -1;
        }
        $i++;
    }
    $num_distinct_progs = $i;

    $changed_message = '';

    if ($POST_OP == 'commit') {
        // Get the list of programmes and any returned value, setting to -1 should there not be any
        // The students_programmes_table should have an uptodate list matching the web page
        $query = "SELECT * FROM $students_programmes ";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        $rows = $results->fetchAll();
        $i = 0;
        foreach ($rows as $recs) {
            $prog_distinct_names[$i] = $recs['prog_full_name'];
            $prog_full_name = $prog_distinct_names[$i];
            $NameKey = "prog_select$i";
            if (isset($_POST[$NameKey])) {
                $prog_short_name_index[$i] = $_POST[$NameKey];
            } else {
                $prog_short_name_index[$i] = -1;
            }
            $p_index = $prog_short_name_index[$i];

            // Update the database
            try {
                $results = $db->prepare("UPDATE $students_programmes SET prog_short_name_index=? WHERE prog_full_name=?");
                $results->execute([$p_index, $prog_full_name]);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }

            $i++;
        }

        $changed_message .= "<h2>Table $students_programmes updated</h2>";
    }

    $instructions = "<h3>This form is for linking programme short names used for <span style='font-face: bold; text-decoration: underline ;'>Project Proposals</span>
to the <span style='font-face: bold; text-decoration: underline ;'> real</span> Programme Names<BR> from the student data list. (This is so that students on a particular programme are only shown projects that are relevant to them)</h3>";
    $instructions .= "<div class='feedbackInstructions'> Select from the list short-name list the name that you want the programme full name to be associated with</div>";

    $display_block .= $changed_message;

    $display_block .= "<form method='post' action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?pm_id=' . htmlspecialchars($pm_id, ENT_QUOTES, 'UTF-8') . "'>\n";
    $display_block .= csrf_token_field();

    $display_block .= "<h1>$module_prefix$module1$module_postfix $module_short_description </h1>
             <div align='left'>\n";

    $display_block .= "<table class='tableSimple' >\n";

    $display_block .= "<tr><td colspan='2'>$instructions</tr>\n";

    $display_block .= '<tr><td><b>Programme Full name</b></td>';
    $display_block .= '<td><B>Select the corresponding programme short name</b></td></tr>';

    for ($i = 0; $i < $num_distinct_progs; $i++) {
        $prog_full_name = $prog_distinct_names[$i];
        $NameKey = "prog_select$i";
        $MatchID = $prog_short_name_index[$i];

        $display_block .= "<TR><td>$prog_full_name</td>";
        $select_block = GetProgramVariantsSelectStaff($db, $projects_setup_proposals, $pm_id, $NameKey, $MatchID);
        $display_block .= "<td>$select_block</td></TR>";
    }
    $display_block .= "\n<input type='hidden' name='op' value='commit'>\n";

    $display_block .= "<TR><td colspan='2'><input class='btn btn-primary' type='submit' name='submit' value='Modify programme name associations'></td></TR>\n";
    $display_block .= "</table>\n";
    $display_block .= "</form>\n";

    $display_block .= '<hr>';
    $display_block .= "<form method='post' action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?pm_id=' . htmlspecialchars($pm_id, ENT_QUOTES, 'UTF-8') . "'>\n";
    $display_block .= csrf_token_field();

    $display_block .= "<h1>Delete the list of Distinct Programme Full Names</h1>\n";
    $display_block .= "<div align='left'>\n";
    $display_block .= "<div>You might want to do this if you have changed the list of programmes by changing the student list. <BR>DON'T make a habit of this ... it may have unwanted consequences</div>\n";

    $display_block .= "<div><BR><input class='btn btn-danger' type='submit' name='submit_delete' value='Delete the Distinct Programme Full Names list'></div>\n";
    $display_block .= "<input type='hidden' name='op' value='delete'>\n";

    $display_block .= "</div>\n";

    $display_block .= "</form>\n";
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
        <span class="navbar-brand">Match programm short names with programme full (real) names:- <?php echo("$module_short_description  $module_prefix$module1$module_postfix  $year_begin/$year_end") ?></span>
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
        <?php echo("$display_block"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>

