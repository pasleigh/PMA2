<?php
// Turn off all error reporting

// connect to the datbase
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('./ADMINconnect_pdo.php')) or die('Cannot connect to the database using pdo<BR>');
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) or die('Cannot setup database tables<BR>');
(@include_once('./project_table_template.php')) or die('Cannot include protect_table_template.php<BR>');
(@include_once('./assess_table_template.php')) or die('Cannot include assess_table_template.php<BR>');
(@include_once('./assess_total_table_template.php')) or die('Cannot include assess_total_table_template.php<BR>');
(@include_once('./allocated_table_template.php')) or die('Cannot include allocated_table_template.php<BR>');
(@include_once('./allocated_staff_table_template.php')) or die('Cannot include allocated_staff_table_template.php<BR>');
(@include_once('./students_table_template.php')) or die('Cannot include students_table_template.php<BR>');
(@include_once('./template_functions_pdo.php')) or die('Cannot include template_functions_pdo.php<BR>');
(@include_once("$STAFF_DIR_VAL/staff_functions.php")) or die('Cannot include staff_functions.php<BR>');
(@include_once("$STAFF_DIR_VAL/markstore_functions.php")) or die('Cannot include markstore_functions.php<BR>');
(@include_once("$STAFF_DIR_VAL/table_functions.php")) or die('Cannot include table_functions.php<BR>');

(@include_once('./duplicate_module_functions.php')) or die('Cannot include duplicate_module_functions.php<BR>');

$display_block = '';
//$display_block .= $connect_msg;
$display_block .= "<div class='text-center'><h1 style='font-size: x-large; color: darkgoldenrod;'>$connect_msg</h1></div>\n";

// if the form was submitted then enter it all into the database
$post_error = false;
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
    if (array_key_exists('pm_id_old', $_POST) == false) {
        $pm_id_old = null;
        $post_error = true;
    } else {
        $pm_id_old = (int)$_POST['pm_id_old'];
    }
    if (array_key_exists('new_year_begin', $_POST) == false) {
        $new_year_begin = -1;
        $post_error = true;
    } else {
        $new_year_begin = (int)$_POST['new_year_begin'];
    }
    if (array_key_exists('new_module_short_name', $_POST) == false) {
        $new_short_name = null;
        $post_error = true;
    } else {
        $new_short_name = trim($_POST['new_module_short_name']);
    }
} else {
    $POST_OP = '';
}

$display_block .= '<div>';

if ($POST_OP == 'create') {
    if (!$post_error) {
        $display_block .= '<h2>Copying all tables from old module to a new one.....</H2>';
        //$new_short_name = trim($_POST['new_module_short_name']);
        $new_short_name_lc = strtolower($new_short_name);
        if (strlen($new_short_name) < 2) {
            echo("<B>The module short name must be between 2 and 5 characters long</B>. You have entered \"$new_short_name\"");
            exit;
        }
        if (strlen($new_short_name) > 5) {
            echo("<B>The module short name must be between 2 and 5 characters long</B>. You have entered \"$new_short_name\"");
            exit;
        }
        //$new_year_begin = $_POST['new_year_begin'];
        $new_year_end = $new_year_begin + 1;

        //$pm_id_old = $_POST['pm_id_old'];

        // Get the old module short_code and year_begin from the database
        //$short_code_old = $_POST['short_code_old'];
        //$old_year_begin = $_POST['old_year_begin'];
        $query = "SELECT * FROM $projects_setup WHERE id=$pm_id_old";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured accessing the $projects_setup table<BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $rec = $results->fetch();
        $short_code_old = $rec['short_code'];
        $old_year_begin = $rec['year_begin'];

        $print_block = "pm_id old $pm_id_old<BR>";
        $print_block .= "short_code old $short_code_old<BR>";
        $print_block .= "year_begin old $old_year_begin<BR>";
        $print_block .= "short_code new $new_short_name<BR>";
        $print_block .= "year_begin new $new_year_begin<BR>";
        $pm_id_new = create_new_module_from_old($db, $projects_setup, $projects_setup_proposals, $pm_id_old, $short_code_old, $new_short_name, $old_year_begin, $new_year_begin);
        //$pm_id_new = -1;

        if ($pm_id_new >= 0) {
            // OK
            $display_block .= "<H1>Success creating and copying data to new module $new_short_name, $new_year_begin</H1>";
            $display_block .= "<a href=\"./edit_module_settings.php?pm_id=$pm_id_new\">Go to this new module admin page to make any changes</a>";
        } else {
            // Failed
            $display_block .= '<H1>FAILED to create tables for new module</H1>';
            $display_block .= "<p>The combination of short name : \"$new_short_name\" and the year begin  \"$new_year_begin\" must be unique</P>";
        }
    } else {
        // Failed
        $display_block .= '<H1>FAILED to create tables for new module</H1>';
        $display_block .= '<p>You did not select an old programme, or the choince of Short name and Year were not correct.</p>';
        $display_block .= "<P>The combination of short name : '$new_short_name' and the year begin  '$new_year_begin' must be unique</P>";
    }
    $display_block .= '<HR>';
} else {
    // Display all from the project_setup table
    $query = "SELECT * FROM $projects_setup ORDER BY year_begin DESC, module";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $projects_setup table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $res = $results->fetchAll();

    $overview_table = "<table id='holder' width='600px'>\n<tr><td>\n";

    $overview_table .= "<table id='projects_setup' class='hover compact' cellspacing='0' width='100%'>\n";
    $overview_table .= '
    <thead>
      <tr>
        <th>Select</th>
        <th>ID</th>
        <th>Module code</th>
        <th>Description</th>
        <th>Short name/code</th>
        <th>Year begin / end</th>
      </tr>
    </thead>';

    $overview_table .= '<tbody>';

    // Go through the projects with that year_begin and see if this user is in it
    $modules_data_list = '';
    foreach ($res as $rec) {
        $pm_id = $rec['id'];
        $module1 = $rec['module'];
        $module_prefix = $rec['module_prefix'];
        $module_postfix = $rec['module_postfix'];
        $module_short_description = $rec['module_short_desc'];
        $short_code = $rec['short_code'];
        $year_begin = $rec['year_begin'];
        $year_end = $rec['year_end'];
        $year_end_short = $year_end - 2000;
        $short_code_lc = strtolower($short_code);
        //$display_block .= "$pm_id $module_prefix$module1$module_postfix $module_short_description $short_code $year_begin/$year_end<BR>";
        $overview_table .= "<tr>\n<td><input type='radio' name='pm_id_old' value='$pm_id'></td><td>$pm_id</td> <td>$module_prefix$module1$module_postfix</td><td>$module_short_description</td><td>$short_code</td>\n<td>$year_begin/$year_end</td></tr>\n";

        $modules_data_list .= "<option value='$pm_id' >[$pm_id] $module_prefix$module1$module_postfix $module_short_description ($short_code) : $year_begin/$year_end</option>\n";
    }

    $overview_table .= "</tbody>\n</table>\n";

    $overview_table .= "\n</tr></td>\n</table>\n <!--/holder-->";

    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>\n";

    $display_block .= '<h1>STEP 1: Select the module you want to duplicate</h1>';
    //$display_block .= $overview_table;

    // Display a list of years
    $module_prefix_name = 'CIVE';
    $module_postfix_name = ''; // could be "M"
    $new_short_name = 'MENG';
    $new_short_desc = 'Level 5 project';

    $display_block .= "<div class='row'>";
    $display_block .= "<div class='col-sm-6'>";
    $display_block .= "<label for='pm_id_old2' class='form-label'><strong>Old module to duplicate</strong> (Start typing to search...) </label>";
    //$display_block .= "<select name='year_select' id='year_select' list='yearlistOptions'> \n";
    $display_block .= "<select class='form-select'  id='pm_id_old' name='pm_id_old' placeholder='Type to search...'>";
    $display_block .= $modules_data_list;
    $display_block .= '</select>';
    $display_block .= '</div>';
    $display_block .= '</div>';

    // Display the dropdowns to select the module and year
    $display_block .= "<H1>STEP 2: Select a new start session / year ...</H1>\n";
    $display_block .= '<H2>Module start session/year</H2>';
    $display_block .= '<P>The <strong>year</strong> is the year of the session, that begining in <strong>September</strong>.</P>';
    $display_block .= "<div class='col-2'>";
    $display_block .= "<div class='form-group'>";
    $display_block .= "<label for='year_select' class='form-label'>Year</label>";
    //$display_block .= "<select name='year_select' id='year_select' list='yearlistOptions'> \n";
    $display_block .= "<select class='form-control'  id='new_year_begin' name='new_year_begin' placeholder='Type to search...'>";

    //$display_block .= "<datalist id='yearlistOptions'> \n";
    for ($year = 2025; $year <= 2035; $year++) {
        $display_block .= "<option value='$year'>$year</option>\n";
    }
    $display_block .= '</select>';
    $display_block .= '</div>';
    $display_block .= '</div>';
    $display_block .= " (This <strong>CAN NOT</strong> be changed later)<BR>\n";

    //$display_block .= "<H2>Module prefix and number</H2><P>E.g. For the module CIVE3708  the <strong>prefix</strong> will be CIVE, and the  <strong>number</strong> 3708</P>";
    //$display_block .= "<P><strong>Module code prefix: </strong><input type='text' name='new_module_prefix_name' value='$module_prefix_name' size=5 maxlength=4></P>";
    //$display_block .= "<P><strong>Module code number: </strong><input type='text' name='new_module_number' value='1234' size=5 maxlength=4>";
    //$display_block .= "<P><strong>Module code post-fix: </strong><input type='text' name='new_module_postfix_name' value='$module_postfix_name' size=1 maxlength=1></P>";

    $display_block .= "<H1>STEP 3: Enter a new module short name/code ...</H1>\n";
    $display_block .= '<H2>The module short name / code.</H2>';
    $display_block .= '<P> E.g. For level 3 projects you could use BENG, for level 5 project use MENG, MSc might use the programme codes (e.g. WASH, EEPM, SE, ICME).';
    $display_block .= '<BR>Ad-hoc modules e.g. IDP you might use IDP. It shoud be no more that 5 letters.<BR>Or copy the code from the table above.</P>';
    $display_block .= "<div class='col-6'>";
    $display_block .= "<div class='form-group'>";
    $display_block .= "<label for='new_module_short_name' class='form-label'><strong>Module short name</strong></label>";
    $display_block .= "<input class='form-control' type='text' name='new_module_short_name' id='new_module_short_name' value='$new_short_name' size=5 maxlength=5> (Note that this must be UNIQUE for the session/year - and it <strong>CAN NOT</strong> be changed later.)";
    $display_block .= '</div>';

    $display_block .= '<P><strong>All other details from the old module can be changed later.</strong></P>';

    $display_block .= "\n<input type='hidden' name='op' value='create'>";

    $display_block .= "<p><input class='btn btn-primary' type='submit' id='submitbutton' name='submit' value='Duplicate the chosen module using the new year and new short name'></p>";

    $display_block .= '</form>';
}// end of if ($POST_OP == "select")
$display_block .= '</div>';

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Create New Module by Duplicating another from the database</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin page</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card mb-2" id="module_leader">
                    <div class="card-header"><strong>Duplicate module</strong></div>
                    <div class="card-body">
                        <?php echo("$display_block"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- content -->
</div><!-- /wrapper -->
</body>
</html>
