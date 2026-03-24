<?php
// connect to the datbase
(@include_once("ADMINconnect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");

(@include_once("assess_table_template.php")) OR die("Cannot include assess_table_template.php<BR>");
(@include_once("assess_total_table_template.php")) OR die("Cannot include assess_total_table_template.php<BR>");
(@include_once("allocated_table_template.php")) OR die("Cannot include allocated_table_template.php<BR>");
(@include_once("students_table_template.php")) OR die("Cannot include students_table_template.php<BR>");
(@include_once("template_functions_pdo.php")) OR die("Cannot include template_functions.php<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot staff_functions.php<BR>");


$display_block = "";
$display_block .= $connect_msg;

// if the form was submitted then enter it all into the database
//echo($_POST['op'] . "<BR>\n");
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}
//echo("here<BR>");
// see all the POST values
//foreach ($_POST as $key => $value){
//  echo "{$key} = {$value}<BR>\r\n";
//}
if ($POST_OP == "modify") {
    $display_block .= "<h2>Updating the project data $module_prefix$module1 ($short_code) $year_begin ...</h2>";
    $module1 = trim($_POST['module1_code']);
    $module_prefix = trim($_POST['module_prefix']);
    $module_short_description = trim($_POST['module_short_desc']);

    $staff_close = trim($_POST['staff_close_date']);
    $sel_open = trim($_POST['student_selection_open_date']);
    $sel_close = trim($_POST['student_selection_close_date']);
    $alloc_date = trim($_POST['allocation_date']);

    $accept_var = trim($_POST['accept_var']);
    $pass_mark = trim($_POST['pass_mark']);
    $module_leader_id = trim($_POST['staff_id']);
    $module_leader_fullname = GetStaffNameFromID($db, $module_leader_id, $staff_names2, 0);
    $min_feedback_words = trim($_POST['min_feedback_words']);

    $query = "update $projects_setup set module='$module1', module_prefix='$module_prefix', module_short_desc='$module_short_description',
            staff_close='$staff_close', sel_open='$sel_open', sel_close='$sel_close', alloc_date='$alloc_date',
            accept_var='$accept_var', pass_mark='$pass_mark', module_leader='$module_leader_fullname', min_feedback_words='$min_feedback_words'
             WHERE id='$pm_id'";
    //echo $action . "<BR>";
    //mysql_query($action) or die(" Unable to update  project entry to the table $projects_setup. <BR /> Query = $action");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    for ($part = 1; $part <= $num_parts; $part++) {
        $p_submit = "p$part" . "_submission_date";
        $submit_dates_part = $_POST[$p_submit];
        $p_marking = "p$part" . "_marking_date";
        $markby_dates_part = $_POST[$p_marking];

        $p_use = "use_p$part";
        $p_percent = "p$part" . "_percent";
        $weights_part = $_POST[$p_percent];
        $p_num_markers = "p$part" . "_num_markers";
        $num_markers_part = $_POST[$p_num_markers];
        $p_max = "p$part" . "_max";
        $max_marks_part = $_POST[$p_max];
        $p_is_numeric = "p$part" . "_numeric";
        $is_numeric_part = 0;
        if (isset($_POST[$p_is_numeric])) {
            $is_numeric_part = 1;
        }
        $p_desc = "p$part" . "_desc";
        $descs_part = $_POST[$p_desc];

        //$name = "p$part"."_template";
        //$template_tables[$part] = $_POST[$name];
        $query = "update  $projects_setup set $p_submit='$submit_dates_part', $p_marking='$markby_dates_part', $p_use='1', $p_percent='$weights_part',
                        $p_num_markers='$num_markers_part', $p_max='$max_marks_part', $p_desc='$descs_part', $p_is_numeric='$is_numeric_part' WHERE id='$pm_id'";
        //mysql_query($action) or die(" Unable to update project entry to the table $projects_setup. <BR /> Query = $action");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
    }

    // Refresh the project_setup data for display below
    $query = "select * from $projects_setup where id=$pm_id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    //$get_setup_list = mysql_query($get_setup);// or die(" Unable to get database configuration.");
    //if (mysql_errno() != 0) {
    //   $errornum = mysql_errno();
    //   $errormessage = mysql_error();
    //   printf("ERROR: $errornum<br>\n$errormessage<BR>\n$get_setup\n");
    //   exit;
    //}

    //$setup_res = mysql_fetch_array($get_setup_list);
    $setup_res = $results->fetch();
    $module1 = $setup_res['module'];
    $module_prefix = $setup_res['module_prefix'];
    $module_short_description = $setup_res['module_short_desc'];
    $short_code = $setup_res['short_code'];
    $year_begin = $setup_res['year_begin'];
    $year_end = $setup_res['year_end'];
    $short_code = $setup_res['short_code'];

    $staff_close = $setup_res['staff_close'];
    $sel_open = $setup_res['sel_open'];
    $sel_close = $setup_res['sel_close'];
    $alloc_date = $setup_res['alloc_date'];
    //echo("staff close: $staff_close<BR>");
    //echo("sel_open: $sel_open<BR>");
    //echo("sel_close: $sel_close<BR>");
    //echo("alloc_date: $alloc_date<BR>");

    $num_parts = $setup_res['num_parts'];
    $accept_var = $setup_res['accept_var'];
    $pass_mark = $setup_res['pass_mark'];
    $module_leader_fullname = $setup_res['module_leader'];
    $min_feedback_words = $setup_res['min_feedback_words'];

    $p_submission_date = array();
    $p_marking_date = array();
    $p_use = array();
    $p_percent = array();
    $p_max = array();
    $p_description = array();

    for ($i = 1; $i <= 5; $i++) {
        $submissin_date = "p$i" . "_submission_date";
        $marking_date = "p$i" . "_marking_date";
        $num_markers = "p$i" . "_num_markers";
        $use = "use_p$i";
        $percent = "p$i" . "_percent";
        $max = "p$i" . "_max";
        $desc = "p$i" . "_desc";
        $p_is_numeric = "p$i" . "_numeric";

        $p_submission_date[$i] = $setup_res[$submissin_date];
        $p_marking_date[$i] = $setup_res[$marking_date];
        $p_num_markers[$i] = $setup_res[$num_markers];
        $p_use[$i] = $setup_res[$use];
        $p_percent[$i] = $setup_res[$percent];
        $p_max[$i] = $setup_res[$max];
        $p_description[$i] = $setup_res[$desc];
        $p_numeric[$i] = $setup_res[$p_is_numeric];
    }
    $display_block .= "<h2> ... Done.</h2>";
    $display_block .= "<HR>";

}// end of if ($POST_OP == "select")


// Display the dropdowns to select the module and year
$display_block .= "<H1>Modify project entry ...</H1>\n";

$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

$display_block .= "<table class=\"tableSimple\">\n";
$display_block .= "<tr><td colspan=\"2\"><H2>Some project/module details</H2></td></tr>";
$display_block .= "<tr><td>Project/module ID</td><td>$pm_id</td></tr>\n";
$display_block .= "<tr><td>Year begin</td><td>$year_begin</td></tr>\n";
$display_block .= "<tr><td>Year end</td><td>$year_end</td></tr>\n";
$display_block .= "<tr><td>Module code</td><td>
                          <input type=\"text\" id=\"module_prefix\" name=\"module_prefix\" value=\"$module_prefix\" size=\"5\">
                          <input type=\"text\" id=\"module1_code\" name=\"module1_code\" value=\"$module1\" size=\"15\">
                          </td></tr>\n";
$display_block .= "<tr><td>Module short name</td><td>$short_code</td></tr>\n";
$display_block .= "<tr><td>Short description</td><td><input type=\"text\" id=\"module_short_desc\" name=\"module_short_desc\" value=\"$module_short_description\" size=\"25\"> (Up to 25 characters, not blank)</td></tr>\n";

$display_block .= "<tr><td colspan=\"2\"><H2>Some key dates:</H2></td></tr>";

$name = "staff_close_date";
$display_block .= "<TR><TD>Project proposal date close for staff</td><td><input type=\"text\" id=\"$name\" name=\"$name\"  value=\"$staff_close\"size=20 maxlength=15></td></tr>\n";
$name = "student_selection_open_date";
$display_block .= "<TR><TD>Student selection opens</td><td><input type=\"text\" id=\"$name\" name=\"$name\"  value=\"$sel_open\"size=20 maxlength=15></td></tr>\n";
$name = "student_selection_close_date";
$display_block .= "<TR><TD>Student selection closes</td><td><input type=\"text\" id=\"$name\" name=\"$name\"  value=\"$sel_close\"size=20 maxlength=15></td></tr>\n";
$name = "allocation_date";
$display_block .= "<TR><TD>Project to be allocated by</td><td><input type=\"text\" id=\"$name\" name=\"$name\"  value=\"$alloc_date\"size=20 maxlength=15></td></tr>\n";


$display_block .= "<tr><td colspan=\"2\"><H2>Assessment Parts:</H2></td></tr>";

$display_block .= "<tr><td>Number of assessment parts: </td><td>$num_parts</td></tr>\n";
$display_block .= "<tr><td>Module leader:  </td><td>" . GetStaffSelectID($db, $staff_names2, "staff_id", GetStaffIDFromName($db, $module_leader_fullname, $staff_names2)) . "</td></tr>\n";
$display_block .= "<tr><td>Acceptable variation between markers : </td><td><input type=\"number\" name=\"accept_var\" value=\"$accept_var\" min=\"0\" max=\"100\" size=\"3\"> %</td></tr>\n";
$display_block .= "<tr><td>Pass mark:  </td><td><input type=\"number\" name=\"pass_mark\" value=\"$pass_mark\" min=\"0\" max=\"100\" size=\"3\"> %</td></tr>\n";
$display_block .= "<tr><td>Minimum number of feedback words:  </td><td><input type=\"number\" name=\"min_feedback_words\" value=\"$min_feedback_words\" min=\"0\" max=\"100\" size=\"3\"></td></tr>\n";
$display_block .= "</table>\n";


for ($part = 1; $part <= $num_parts; $part++) {
    $i = $part;
    $display_block .= "<table class=\"tableSimple\">\n";
    $display_block .= "<TR><TD colspan=\"2\"><H3>Part $part:</H3></td></tr>\n";
    $name = "p$part" . "_desc";
    $desc = $p_description[$i];//$_POST[$name];
    $display_block .= "<TR><TD>Description of assesment part $part: </td><td><input type=\"text\" name=\"$name\"  value=\"$desc\"size=25 maxlength=25></td></tr>\n";
    //$name = "p$part"."_template";
    //$display_block .="<P> Select a questions template for  part $part: " . GetQsTemplatesSelect($name,$template_tables[$part]) ."</P>";
    $name = "p$part" . "_num_markers";
    $num_markers = $p_num_markers[$i];//$_POST[$name];
    $display_block .= "<TR><TD>Number of markers for part $part: </td><td><input type=\"number\" name=\"$name\" value=\"$num_markers\" min=\"1\" max=\"3\" size=6 maxlength=5></td></tr>\n";
    $name = "p$part" . "_percent";
    $weight = $p_percent[$i];//$_POST[$name];;
    $display_block .= "<TR><TD>% weight of part $part: </td><td><input type=\"number\" name=\"$name\" value=\"$weight\" min=\"0\" max=\"100\" size=6 maxlength=5></td></tr>\n";
    $name = "p$part" . "_max";
    $max_mark = $p_max[$i];//$_POST[$name];
    $display_block .= "<TR><TD>Maximum mark for part $part: </td><td><input type=\"number\" name=\"$name\" value=\"$max_mark\" min=\"1\" max=\"100\" size=6 maxlength=5></td></tr>\n";
    $name = "p$part" . "_numeric";
    $is_numeric = $p_numeric[$i];//$_POST[$name];
    $is_checked = "";
    if ($is_numeric == 1) {
        $is_checked = "checked";
    }
    $display_block .= "<TR><TD>Numeric (checked) or phrases (not checked) response format for part $part:<BR>(Questions format must match this choice.) </td><td><input type=\"checkbox\" name=\"$name\" value=\"$is_numeric\" $is_checked></td></tr>\n";
    $name = "p$part" . "_submission_date";
    $date = $p_submission_date[$i];//$_POST[$name];
    $display_block .= "<TR><TD>Submission date for part $part: </td><td><input type=\"text\" id=\"$name\" name=\"$name\"  value=\"$date\"size=20 maxlength=15></td></tr>\n";
    $name = "p$part" . "_marking_date";
    $date = $p_marking_date[$i];//$_POST[$name];
    $display_block .= "<TR><TD>Mark-by date for part $part: </td><td><input type=\"text\" id=\"$name\" name=\"$name\"  value=\"$date\"size=20 maxlength=15></td></tr>\n";
    $display_block .= "</table>\n";
}
$display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"modify\">";

$display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit the modified values for $module_prefix$module1, $year_begin \"></p>\n";

$display_block .= "</form>\n";

?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <TITLE>Create new project entry</TITLE>
    <link rel="stylesheet" type="text/css" href="../css/pagedesc.css"/>
    <!-- Load jQuery from Google's CDN -->
    <!-- Load jQuery UI CSS  -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"/>
    <!-- Load jQuery JS -->
    <script src="https://code.jquery.com/jquery-1.9.1.js"></script>
    <!-- Load jQuery UI Main JS  -->
    <script src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script type="text/javascript">
        $(function () {
            var pickerOpts = {
                //dateFormat:"d MM yy"
                dateFormat: $.datepicker.ATOM
            };
            $("#staff_close_date").datepicker(pickerOpts);
            $("#student_selection_open_date").datepicker(pickerOpts);
            $("#student_selection_close_date").datepicker(pickerOpts);
            $("#allocation_date").datepicker(pickerOpts);
            $("#p1_marking_date").datepicker(pickerOpts);
            $("#p2_submission_date").datepicker(pickerOpts);
            $("#p1_submission_date").datepicker(pickerOpts);
            $("#p1_marking_date").datepicker(pickerOpts);
            $("#p2_submission_date").datepicker(pickerOpts);
            $("#p2_marking_date").datepicker(pickerOpts);
            $("#p3_submission_date").datepicker(pickerOpts);
            $("#p3_marking_date").datepicker(pickerOpts);
            $("#p4_submission_date").datepicker(pickerOpts);
            $("#p4_marking_date").datepicker(pickerOpts);
            $("#p5_submission_date").datepicker(pickerOpts);
            $("#p5_marking_date").datepicker(pickerOpts);
        });
    </script>

</HEAD>
<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">Create New Project Entry in the database </span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>

    </div><!-- /content -->

    <div id="footer">
        <strong>Cliking the links below without committing the changes means they will all be lost.</strong><BR>Return
        to the
        <a href="<?php echo "$_SERVER[PHP_SELF]?pm_id=$pm_id" ?>">Reload this page</a>
        or go to the
        <a href="./index.php">Admin index page</a>.
        or go to the
        <a href="../index.php<?php echo("?year_begin=$year_begin") ?>">Year index page</a>.
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
