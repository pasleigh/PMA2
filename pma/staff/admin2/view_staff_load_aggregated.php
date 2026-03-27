<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("$STAFF_DIR_VAL/markstore_functions.php")) OR die("Cannot get markstore functions<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot get utility_functions<BR>");
(@include_once("$STAFF_DIR_VAL/staff_student_functions.php")) OR die("Cannot get utility_functions<BR>");

if(IsStaff($db,$this_staff_username,$staff_names)) {

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }

    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }

    // see all the POST values
    #foreach ($_POST as $key => $value){
    #  echo "{$key} = {$value}<BR>\r\n";
    #}

    $display_block = "";
    $display_block .= $connect_msg;
    $display_block .= "<h1>View staff project load over a range of modules/projects for session <span id='id_year_begin'> $year_begin/$year_end</span></h1>\n";

    // Get a list of years
    $query = "SELECT DISTINCT year_begin FROM $projects_setup ORDER BY year_begin DESC";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $rows = $results->fetchAll();
    $year_list_options = "";
    $count = 0;
    foreach ($rows as $recs) {
        $year_begin = $recs['year_begin'];
        $year_end = $year_begin + 1;
        $year_end_short = $year_end - 2000;
        $year_list_options .= "<option value='$year_begin'>$year_begin/$year_end_short</option>\n";
        // Store the most recent year
        if ($count == 0) {
            $display_block .= "<input type='hidden' value='$year_begin' id='latest_year'>\n";
        }
        $count++;
    }
    $display_block .= "<P>Years avilable in the database: \n";
    $display_block .= "<select name='year_list' onchange=update_year_selected(this.value);>\n";
    $display_block .= $year_list_options;
    $display_block .= "</select>\n</P>";

    // Get a list of all projects for this year
    //$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

    //$query = "select * from $projects_setup where year_begin=$year_begin order by id";
    $query = "select * from $projects_setup order by id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $display_block .= "<DIV id='id_module_list'>\n";
    $count = 0;
    $rows = $results->fetchAll();
    foreach ($rows as $recs) {
        $id = $recs['id'];
        $module = $recs['module'];
        $module_prefix = $recs['module_prefix'];
        $module_short_desc = $recs['module_short_desc'];
        $short_code = $recs['short_code'];
        $my_key = "chk_id$count";
        $ischecked = "";
        if (isset($_POST[$my_key])) {
            $ischecked = "checked";
        }
        $ischecked = "checked";
        //$display_block .= "\n<P>$module_prefix$module, $module_short_desc, $short_code, $id</P>";
        $display_block .= "<span style='display:none;' id='m$id'>";
        $display_block .= "$module_prefix$module $module_short_desc: $short_code ($id) ";
        $display_block .= "<input type='checkbox' name='chk_id$id'  id='chk_id$id' value='$id' $ischecked onchange=update_module_list(this.value);>";
        $display_block .= "<BR></span>\n";
        $count++;
    }

    $display_block .= "<span id='anon_span'><BR>";
    $display_block .= "Hide names on chart";
    $display_block .= "<input type='checkbox' name='anon_chk'  id='anon_chk' value='1' checked onchange=update_module_list(this.value);>";
    $display_block .= "<BR></span>\n";

    $display_block .= "</DIV>\n";

    // The chart
    $display_block .= "<div id='ContainerProjectsChart' style='height: 1580px; border-style: solid; border-width: 3px; margin-top: 10px; margin-bottom: 10px;  border-color: black; padding: 5px; display: none;'></div>\n";

    // The table
    $max_num_modules = 20;
    $num_columns = 2 + 2 * $max_num_modules + 2;
    $display_block .= "<BR><H1>Number of Module Supervisions and Second Marking per staff</H1>";
    $display_block .= "<p>The <em>Total summed</em> = sum of supervised  + sum of second marked  * 0.1</p>";

    $display_block .= "<div id='datatable_div'>";
    $display_block .= "<table width=\"100%\" cellspacing=\"3\" cellpadding=\"0\" id=\"data-entry\" class='hover compact cell-border'></table>";
    $display_block .= "</div>";

    $display_block .= "<div id='mod_table' style='height: 580px; width:100%; border-style: solid; border-width: 2px; margin-top: 10px; margin-bottom: 10px;  border-color: silver; padding: 5px; display: none;'></div>\n";

}else{
    $display_block = "<H1>You do not appear to be a member of staff</H1>";
    $display_block .= "<P>Please contact the manager of this system if you think this is wrong</P>";
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
        <span class="navbar-brand">Staff supervision details - aggregated for session <?php echo("$year_begin/$year_end") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../" class="nav-link px-2 text-white-50">Year index page</a></li>
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
