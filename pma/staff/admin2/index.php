<?php
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');

(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('../staff_functions.php')) or die('Cannot open staff_functions.php<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot open staff_functions.php<BR>');

$display_block = '';
$display_block .= $connect_msg;

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

$is_module_admin_any = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, 'all', null, null, $module_level_access_phrase);

if ($is_module_admin_any) {
    $access_phrase = $module_level_access_phrase;
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin_any || $admin_access) {
    if (array_key_exists('module1', $_REQUEST) == false) {
        $_REQUEST['module1'] = null;
    }
    if (array_key_exists('year_begin', $_REQUEST) == false) {
        $_REQUEST['year_begin'] = null;
    }
    if (array_key_exists('module_short_code', $_REQUEST) == false) {
        $_REQUEST['module_short_code'] = null;
    }
    if (array_key_exists('pm_id', $_REQUEST) == false) {
        $_REQUEST['pm_id'] = null;
    }
    $module1 = $_REQUEST['module1'];
    $year_begin = $_REQUEST['year_begin'];
    $module_short_code = $_REQUEST['module_short_code'];
    $pm_id = (int)$_REQUEST['pm_id'];
    if ($pm_id != '') {
        $query = "select * from $projects_setup where id='$pm_id'";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $get_data = $results->fetch();
        $year_begin = $get_data['year_begin'];
        $module_short_code = $get_data['short_code'];
        $module1 = $get_data['module'];
    }

    // Check that this module/year/short_code combination exists
    $query = "select * from $projects_setup where module=$module1 AND year_begin=$year_begin AND short_code=$module_short_code";

    // if the form was submitted then enter it all into the database
    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = '';
    }

    // see all the POST values
    $locationdir = '../';

    $query = "select * from $projects_setup where 1 order by year_begin desc";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    //$get_data_exe = mysql_query($get_data_com) or die(" Unable to create list of entries in table: $projects_setup with query: $get_data_com");
    $project_list_options = '';
    $results_data = $results->fetchAll();
    foreach ($results_data as $get_data) {
        $year_l = $get_data['year_begin'];
        $module_number_l = $get_data['module'];
        $module_short_code_s_l = $get_data['short_code'];
        $module_prefix_l = $get_data['module_prefix'];
        $module_postfix_l = $get_data['module_postfix'];
        $module_short_desc_l = $get_data['module_short_desc'];
        $pm_id_s = $get_data['id'];
        if ($pm_id_s == $pm_id) {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        $project_list_desc = "$year_l $module_prefix_l$module_number_l$module_postfix_l - $module_short_code_s_l - $module_short_desc_l";
        $project_list_options .= "<option $selected value='$pm_id_s'>$project_list_desc</option>\n";
    }

    // Display the dropdowns to select the module and year
    if ($school != 'kmap2') {
        $display_block .= "<h1>Select the module and year  you want to modify...</H1>\n";

        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>\n";

        $display_block .= "<div><h4>Project/Module descriptions in the database:</h4>\n";
        $display_block .= "<select class='form-select' name='pm_id' >\n";
        $display_block .= $project_list_options;
        $display_block .= "</select>\n</div>";

        $display_block .= "\n<input type='hidden' name='op' value='select'>";

        $display_block .= "<p><input class='btn btn-secondary mt-3' type='submit' id='submitbutton' name='submit' value='Modify the selected module/year '></p>";

        $display_block .= '</form>';

        if ($POST_OP == 'select') {
            // Check that this module/year/short_code combination exists
            //$query = "select * from $projects_setup where module=$module1 AND year_begin=$year_begin AND short_code='$module_short_code'";
            $query = "select * from $projects_setup where id='$pm_id'";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
            //$resource = mysql_query($query);
            //$entry_exists = mysql_num_rows($resource) > 0;
            $entry_exists = $results->rowCount() > 0;
            if ($entry_exists) { // exists
                //$get_data = mysql_fetch_array($resource);
                //$year_begin = $get_data['year_begin'];
                //$module_short_code = $get_data['short_code'];
                //$module1 = $get_data['module'];

                $year_now = date('Y');
                $tead_end = $year_begin + 1;

                $display_block .= '<HR>';//"User: $_SERVER[REMOTE_USER]";

                $display_block .= "<h1><span style='color: black'>Module Admin functions for:</span> $module_short_description - $module_prefix$module1$module_postfix. <BR><span style='color: black'>Starting </span>Sept $year_begin</h1>";

                $display_block .= admin_functions_block($db, $pm_id, $project_allocated, $num_parts, $p_description, $year_begin, $this_staff_username, $admin_access);
            } else {
                $display_block .= "This combination of year (<B>$year_begin</B>) /module (<B>$module1</B>) /short code (<B>$module_short_code</B>) has not been previously set up.<BR>";
                $display_block .= "Select again or go to this <A href='./create_new_module_entry.php'>Create new Module Entry</A> link.<BR>&nbsp;<BR>\n";
            }
        }// End of POST select
    }
    // Only for super-admin
    if ($admin_access) {
        if ($school != 'kmap2') {
            $display_block .= '<BR><BR>';
            $display_block .= "<DIV style='border-style: solid; border-width: 3px; border-color: maroon; padding: 5px; background-color: Khaki' >\n";
            $display_block .= '<h1>System Level admin functions</h1>';
            $display_block .= '<OL>';
            $display_block .= "<LI><A href='./create_new_module_entry.php'>Create new Module Entry</A></LI>\n";
            $display_block .= "<LI><A href='./duplicate_module.php'>Duplicate a previously used module to create <strong>new</strong> module</A></LI>\n";
            $display_block .= "<LI><a href='./edit_module_visibility.php'>Edit which Modules are visible to staff and students</a></LI>\n";
            $display_block .= "<LI><a href='./change_school_definition.php'>Edit the name and header image</a></LI>\n";
            $display_block .= "<LI><a href='./edit_staff_table_bs.php?default_table=1'>Edit staff table </a> (This is  a <em>global</em> list of staff. Any of which may be working on any modules or tools.)</LI>\n";
            $display_block .= "<LI><a href='./upload_assessment_criteria_xlsx.php'>Upload a new Assessment Criteria table from xlsx/csv</a></LI>\n";
            //$display_block .= "<LI><a href='./edit_dash_feedback_tools.php'>Configure feedback tools</a></LI>\n";
            //$display_block .= "<BR><a href='" . $locationdir . "admin2/setup_questions_templates.php'>Edit template questions</a><br>\n";
            $display_block .= "<LI><A href='./edit_admin_users.php'>Edit System Level admin users (NOT module admin)</A></LI>\n";
            $display_block .= "<LI><A href='./edit_admin_users_module.php'>Edit Module/Tool Level admin users</A></LI>\n";
            //$display_block .= "<BR><a href='./view_staff_load_aggregated.php'>View staff project load aggregated over several modules</a><br>";
            $display_block .= '</OL>';
            $display_block .= "</DIV><BR>\n";
        } else {
            $display_block .= '<BR><BR>';
            $display_block .= "<DIV style='border-style: solid; border-width: 3px; border-color: maroon; padding: 5px; background-color: #89cc2488' >\n";
            $display_block .= '<h1>Key Assessment Information (KAI) System Level admin functions</h1>';

            $display_block .= '<h2>More common actions</h2>';
            $display_block .= '<OL>';
            $display_block .= "<LI><a href='./edit_staff_table_bs.php?default_table=1'>Add / delete staff to the <b>global</b> staff list. (They must be on this list to access the system.</LI>\n";
            $display_block .= "<LI><A href='./edit_admin_users_module.php'>Add KAI admin users</A></LI>\n";
            $display_block .= '</OL>';

            $display_block .= '<h3>Less commonnly used actions</h3>';
            $display_block .= '<OL>';
            $display_block .= "<LI><A href='./edit_admin_users.php'>Edit list of staff who have full admin access i.e. this page (this is not NOT for KAI admin)</A></LI>\n";
            $display_block .= "<LI><a href='./change_school_definition.php'>Edit the name and header image</a></LI>\n";
            $display_block .= "<LI><a href='./edit_dash_feedback_tools.php'>Configure tools display</a></LI>\n";
            $display_block .= '</OL>';
            $display_block .= "</DIV><BR>\n";
        }
    }

    // For All
    $display_block_gen_tools = "<DIV style='border-style: solid; border-width: 3px; border-color: darkorchid; padding: 5px; background-color: lightgoldenrodyellow' >\n";
    $display_block_gen_tools .= '<h1>Some general tools</h1>';
    $display_block_gen_tools .= '<UL>';
    $display_block_gen_tools .= "<LI><a href='./view_staff_load_aggregated.php'>View staff project load aggregated over several modules</a></LI>";
    $display_block_gen_tools .= '</UL>';
    $display_block_gen_tools .= "</DIV><BR>\n";
    //$display_block .= $display_block_gen_tools;
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
        <span class="navbar-brand">Setup and Modify Projects/Modules and System data</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../index.php<?php echo("?year_begin=$year_begin") ?>" class="nav-link px-2 text-white-50">Main home page</a></li>
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
