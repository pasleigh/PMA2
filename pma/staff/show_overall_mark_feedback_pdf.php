<?php
// Turn off all error reporting
(@include_once('./set_dir_staff.php')) or die('Cannot find this file to include: set_dir_staff.php<BR>');
(@include_once($STAFF_DIR_VAL . '/config.php')) or die('Cannot find this file to include: config.php<BR>');
(@include_once($STAFF_DIR_VAL . '/setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once($STAFF_DIR_VAL . '/connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once($STAFF_DIR_VAL . '/setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once($STAFF_DIR_VAL . '/staff_functions.php')) or die('Cannot include staff_function.php<BR>');
(@include_once($STAFF_DIR_VAL . '/show_minor_part_feedback_form.php')) or die('Cannot include show_minor_part_feedback<BR>');
//(@include_once($STAFF_DIR_VAL . "/show_mark_questions.php")) OR die("Cannot include show_mark_questions2<BR>");
(@include_once($STAFF_DIR_VAL . '/markstore_functions.php')) or die('Cannot include markstore_function.php<BR>');
(@include_once($STAFF_DIR_VAL . '/admin2/admin_functions.php')) or die('Cannot include admin functions<BR>');
if (array_key_exists('sid', $_GET) == false) {
    $_GET['sid'] = null;
}
if (array_key_exists('pid', $_GET) == false) {
    $_GET['pid'] = null;
}
if (array_key_exists('isnumeric', $_GET) == false) {
    $_GET['isnumeric'] = 0;
}
//$module1 = $_GET['module1'];
//$year_begin = $_GET['year_begin'];
$display_block = '';
$sid = $_GET['sid'];
$project_id = $_GET['pid'];
$is_numeric = $_GET['isnumeric'];

$project_marking_page = 'projectmarking_bs.php';

$display_block .= "<h1 class='intro'>$module_short_description</h1>";
$display_block .= "<h1>$module_prefix$module1$module_postfix , $year_begin/$year_end </h1>";
//$display_block .= "<h2>$short_code</h2>";
$display_block .= "<div>Passmark for module: $pass_mark</div>";
$display_block .= "<div>Module Leader: $module_leader_fullname</div>";

$display_block .= "<div>Number of assessment parts: $num_parts</div>";

// Get the student name from SID
$query = "select * from $students where sid = $sid";
//$get_list_res = mysql_query($get_list) or die(" Unable to create list from $students table. Query: $get_list");
//$recs = mysql_fetch_array($get_list_res);
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$recs = $results->fetch();
$student_name = $recs['name'];
$username = $recs['username'];
$programme = $recs['programme'];
$email = $username . $local_email_domain;
$sel = $recs['sel'];

$display_block .= '<BR>';
$display_block .= "<div class='bolder'>Student: <span class='h3size'>$student_name</span></div>";
$display_block .= "<div>SID: <span class='h3size'>$sid</span></div>";
$display_block .= "<div>Username: <span class='h3size'>$username</span></div>";
$display_block .= "<div>Email: <span class='h3size'>$email</span></div>";
$display_block .= "<div>Programme of study: <span class='h3size'>$programme</span></div>";

//$display_block .= "<h1 class='intro'>Marks:</h1>";
$display_block .= '<BR>';

$query = "select * from $markstore_totals where SID=$sid";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$num_marks_in_list = $results->rowCount();

if ($num_marks_in_list > 0) {
    // Loop through the parts to get the number of columns required for each
    for ($i = 1; $i <= $num_parts; $i++) {
        if ($p_num_markers[$i] == 1) {
            $number_of_cols[$i] = 1;
        } elseif ($p_num_markers[$i] == 2) {
            if (CountThirdMarkersRequired($db, $i, $markstore_totals) > 0) {
                $number_of_cols[$i] = 4;
            } else {
                $number_of_cols[$i] = 3;
            }
        } else {
            $number_of_cols[$i] = 4;
        }
    }

    $overview_table = "<table id='holder' width='600px'>\n<tr><td>\n";

    $overview_table .= "<table id='marksummary' class='hover compact' cellspacing='0' width='100%'>\n";
    $overview_table .= '
    <thead>
      <tr>
        <th>Assessment</th>
        <th>Description</th>
        <th>Weight</th>
        <th>Mark %</th>
        <th>Feedback link</th>
      </tr>
    </thead>';

    $overview_table .= '<tbody>';

    $recs = $results->fetch();
    $total_mark = $recs['total'];
    $total_mark_s = sprintf('%5.1f%%', (float) $total_mark);

    // definitions for each part
    // Questions table
    for ($assesspart = 1 ; $assesspart <= $num_parts ; $assesspart++) {
        // get the total for each part
        $part_mark = $recs["retmark$assesspart"];
        $part_mark_s = sprintf('%5.1f%%', (float) $part_mark);
        $pc_for_part = $p_percent[$assesspart];
        $part_desc = $p_description[$assesspart];
        //$display_block .= "<div class='bolder'>Part $assesspart: $part_desc. ($pc_for_part%). Mark: $part_mark_s</div>";

        $link = '';
        $who = 3;

        $p_is_numeric = $p_numeric[$assesspart];
        // Get the number of markers
        if ($p_num_markers[$assesspart] == 1) {
            $who = 1;
            $num_markers = 1;
            $link = "<a href='assess_minor_part_questions_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&module1=$module1&year_begin=$year_begin&part=$assesspart&topdf=1&force_pdf=1&who=$who&isnumeric=$p_is_numeric'>feedback pdf</A> ($num_markers marker)\n";
        } elseif ($p_num_markers[$assesspart] == 2) {
            $num_markers = 2;
            if (IsThirdMarkerRequired($db, $sid, $assesspart, $markstore_totals)) {
                $num_markers++;
                $link = "<a href='assess_minor_part_questions_3_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&module1=$module1&year_begin=$year_begin&part=$assesspart&topdf=1&force_pdf=1&who=$who&isnumeric=$p_is_numeric'>feedback pdf</A> ($num_markers markers)\n";
            }
            $link = "<a href='assess_minor_part_questions_3_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&module1=$module1&year_begin=$year_begin&part=$assesspart&topdf=1&force_pdf=1&who=$who&isnumeric=$p_is_numeric'>feedback pdf</A> ($num_markers markers)\n";
        } else {
            $num_markers = 3;
            $link = "<a href='assess_minor_part_questions_3_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&module1=$module1&year_begin=$year_begin&part=$assesspart&topdf=1&force_pdf=1&who=$who&isnumeric=$p_is_numeric'>feedback pdf</A> ($num_markers markers)\n";
        }

        $overview_table .= "<tr>\n<td><B>Part $assesspart</B></td> <td>$part_desc</td><td>$pc_for_part%</td><td>$part_mark_s</td>\n<td>$link</td></tr>\n";

        $num_markers_for_part = $p_num_markers[$assesspart];

        $assessqs_year = $assessqs[$assesspart];

        // Questions categories table
        $assess_cats = $assessqs_cats[$assesspart];

        // description
        $assesstype_text = $p_description[$assesspart];

        // markbook
        for ($who = 1 ; $who <= 3 ; $who++) {
            $markstore = $markstores[$assesspart][$who];
        }
        $mark_max = $p_max[$assesspart];
        $mark_pc = $p_percent[$assesspart];
    }
    //$display_block .= "<h2>Final mark: $total_mark</h2>";
    $overview_table .= "<tr>\n<td></td> <td></td><td><B>Total mark</B></td><td><strong>$total_mark_s</strong></td><td></td>\n</tr>\n";
    $overview_table .= "</tbody>\n</table>\n";

    $overview_table .= "\n</tr></td>\n</table>\n <!--/holder-->";
    $display_block .= $overview_table;
} else {
    // No marks
    $display_block .= '<h3>There are no final marks for this student.</h3>';
}
$admin_link_text = '';
$show_admin_link = 'none';
$admin_link = '';
if (IsAdmin($db, $this_staff_username)) {
    $admin_link = './admin2/';
    $admin_link_text = 'Super Admin';
    $show_admin_link = 'block';
} elseif (IsModuleAdmin($db, $this_staff_username, 'all', null, null)) {
    $admin_link = './admin2/';
    $admin_link_text = 'Admin';
    $show_admin_link = 'block';
}

?>

<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php
(@include_once("$mobile_head_bs")) or die("Cannot find this file to include: $desktop_head<BR>");
?>
<script>
    $(function () {
        $("#marksummary").dataTable(
            {
                "bSort": false,
                "paging": false,
                "ordering": false,
                "info": false,
                "searching": false
            }
        );
    })
</script>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Overall Assessment Feedback : <?php echo("$module_short_description  $module_prefix$module1$module_postfix,  $year_begin/$year_end") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./<?php echo("$project_marking_page?pm_id=$pm_id") ?>" class="nav-link px-2 text-white-50">Full marking list</a></li>
            <li class="nav-item"><a target="_self" href="./<?php echo("$project_marking_page?pm_id=$pm_id&user=1") ?>" class="nav-link px-2 text-white-50">User marking list</a></li>
            <li class="nav-item"><a href="./index.php" class="nav-link px-2 text-white-50">This year module list</a></li>
        </ul>
        <div class="navbar-brand ms-auto" style="display: <?php echo($show_admin_link);?>">
            <a target='_top' class="navbar-brand" href='<?php echo("$admin_link"); ?>'>
                <i class="bi bi-gear"></i>
                <?php echo("$admin_link_text"); ?>
            </a>
        </div>
    </div>
</nav>
<div id="wrapper">

    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class='card'>
                    <div class='card-header'>Overall Assessment Feedback</div>
                    <div class="card-body">

                <?php echo("$display_block"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- content -->

</div><!-- /wrapper -->
</BODY>
</HTML>