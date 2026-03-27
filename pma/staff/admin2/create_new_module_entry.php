<?php
// Turn off all error reporting

// connect to the datbase
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./ADMINconnect_pdo.php")) OR die("Cannot connect to the database using pdo<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./project_table_template.php")) OR die("Cannot include protect_table_template.php<BR>");
(@include_once("./assess_table_template.php")) OR die("Cannot include assess_table_template.php<BR>");
(@include_once("./assess_total_table_template.php")) OR die("Cannot include assess_total_table_template.php<BR>");
(@include_once("./allocated_table_template.php")) OR die("Cannot include allocated_table_template.php<BR>");
(@include_once("./allocated_staff_table_template.php")) OR die("Cannot include allocated_staff_table_template.php<BR>");
(@include_once("./students_table_template.php")) OR die("Cannot include students_table_template.php<BR>");
(@include_once("./template_functions_pdo.php")) OR die("Cannot include template_functions_pdo.php<BR>");
(@include_once("$STAFF_DIR_VAL/staff_functions.php")) OR die("Cannot include staff_functions.php<BR>");
(@include_once("$STAFF_DIR_VAL/markstore_functions.php")) OR die("Cannot include markstore_functions.php<BR>");

$display_block="";
$display_block .= $connect_msg;

// if the form was submitted then enter it all into the database
if (array_key_exists('op', $_POST)) {
  $POST_OP = $_POST['op']  ;
}else{
  $POST_OP = "";
}

if ($POST_OP == "create"){
    $display_block .= "<h2>Creating the new entry in the projects_setup table ...</H2>";
    // Need to check that this does not already exist
    // Check Year begin and Short_code as that is what the tables are made up from so they combination must be unique
    $new_short_name = trim($_POST['new_module_short_name']);
    $new_short_name_lc = strtolower($new_short_name);
    if (strlen($new_short_name) < 2) {
        echo("<B>The module short name must be between 2 and 5 characters long</B>. You have entered \"$new_short_name\"");
        exit;
    }
    if (strlen($new_short_name) > 5) {
        echo("<B>The module short name must be between 2 and 5 characters long</B>. You have entered \"$new_short_name\"");
        exit;
    }
    $new_year_begin = $_POST['new_year_begin'];
    $new_year_end = $new_year_begin + 1;
    $new_module_prefix = trim($_POST['new_module_prefix_name']);
    $new_module_postfix = trim($_POST['new_module_postfix_name']);
    $new_module_number = trim($_POST['new_module_number']);
    $new_short_desc = $_POST['new_module_short_desc'];

    $get_list = "select * from $projects_setup where short_code='$new_short_name' AND year_begin='$new_year_begin'";
    //$get_list_res = mysql_query($get_list) or die(" Unable to create list from $projects_setup table. Query: $get_list");
    $results = $db->query($get_list);
    $num_entries = $results->rowCount();
    if ($num_entries > 0) {
        echo("<P>This combination of year and short code exists. Year:$new_year_begin Short code:$new_short_name Choose another.</P>");
        exit;
    } else {
        // Save this to the database
        $action = "insert $projects_setup set year_begin='$new_year_begin', year_end='$new_year_end', short_code='$new_short_name',
                module='$new_module_number', module_prefix='$new_module_prefix', module_postfix='$new_module_postfix',
                 module_short_desc='$new_short_desc'
                ";
        try {
            $db->query($action);
        } catch (PDOException $ex) {
            echo "An Error occured adding the new project data<BR>\n";
            echo(" Query = $action<BR>\n");
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
    }

    // Save the rest to the setup_projects table
    //Get the id
    $action = "SELECT * from $projects_setup ";
    $get_list = "select * from $projects_setup where short_code='$new_short_name' AND year_begin='$new_year_begin'";
    try {
        $setup = $db->query($get_list);
        $setup_data = $setup->fetch();
    } catch (PDOException $ex) {
        echo "An Error occured getting the list from projects_setup<BR>\n";
        echo(" Query = $get_list<BR>\n");
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    //$get_list_res = mysql_query($get_list) or die(" Unable to create list from $projects_setup table. Query: $get_list");
    //$setup_data = mysql_fetch_array($get_list_res);
    $pm_id = $setup_data['id'];

    // Put this in the projects_setup_proposals table (keep defaults for everything else)
    // Save this to the database
    $query = "INSERT $projects_setup_proposals SET proj_id='$pm_id'";
    try {
        $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured adding the new project setup proposals<BR>\n";
        $err_msg = $ex->getMessage();
        echo("Error message: $err_msg<BR>\n");
        exit();
    }

    $new_short_desc = $_POST['new_module_short_desc'];
    $new_num_parts = $_POST['new_num_parts'];

    $action = "update  $projects_setup set num_parts='$new_num_parts', module_short_desc='$new_short_desc' WHERE id='$pm_id'";
    try {
        $db->query($action);
    } catch (PDOException $ex) {
        echo "An Error occured updating projects_setup<BR>\n";
        echo(" Query = $action<BR>\n");
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    //mysql_query($action) or die(" Unable to update new project entry to the table $projects_setup. <BR>/> Query = $action");

    $accept_var = trim($_POST['accept_var']);
    $pass_mark = trim($_POST['pass_mark']);
    $module_leader_id = trim($_POST['staff_id']);
    $module_leader_fullname = GetStaffNameFromID($db, $module_leader_id, $staff_names2, 0);
    $min_feedback_words = trim($_POST['min_feedback_words']);

    $action = "update $projects_setup set accept_var='$accept_var', pass_mark='$pass_mark', module_leader='$module_leader_fullname', min_feedback_words='$min_feedback_words' WHERE id='$pm_id'";

    try {
        $db->query($action);
    } catch (PDOException $ex) {
        echo "An Error occured updating projects_setup<BR>\n";
        echo(" Query = $action<BR>\n");
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    //mysql_query($action) or die(" Unable to update  project entry to the table $projects_setup. <BR>/> Query = $action");

    for ($part = 1; $part <= $new_num_parts; $part++) {
        $p_desc = "p$part" . "_desc";
        $descs_part = $_POST[$p_desc];;
        $name = "p$part" . "_template";
        $template_tables[$part] = $_POST[$name];
        $p_num_markers = "p$part" . "_num_markers";
        $num_markers_part = $_POST[$p_num_markers];
        $p_percent = "p$part" . "_percent";
        $weights_part = $_POST[$p_percent];
        $p_max = "p$part" . "_max";
        $max_marks_part = $_POST[$p_max];
        $p_submit = "p$part" . "_submission_date";
        $submit_dates_part = $_POST[$p_submit];
        $p_marking = "p$part" . "_marking_date";
        $markby_dates_part = $_POST[$p_marking];
        $p_use = "use_p$part";

        $action = "update  $projects_setup set $p_submit='$submit_dates_part', $p_marking='$markby_dates_part', $p_use='1', $p_percent='$weights_part',
                        $p_num_markers='$num_markers_part', $p_max='$max_marks_part', $p_desc='$descs_part' WHERE id='$pm_id'";
        //mysql_query($action) or die(" Unable to update new project entry to the table $projects_setup. <BR>/> Query = $action");
        try {
            $db->query($action);
        } catch (PDOException $ex) {
            echo "An Error occured updating projects_setup<BR>\n";
            echo(" Query = $action<BR>\n");
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
    }

    // Plus some extra project allocation dates
    $staff_close = date("Y-m-d", strtotime("+21 day"));//today + 3 weeks
    $sel_open = date("Y-m-d", strtotime("+22 day"));//today + 3 weeks and a day
    $sel_close = date("Y-m-d", strtotime("+35 day"));//today + 5 weeks
    $alloc_date = date("Y-m-d", strtotime("+40 day"));//today + 5 weeks + 5 days
    $action = "update  $projects_setup set staff_close='$staff_close', sel_open='$sel_open', sel_close='$sel_close', alloc_date='$alloc_date' WHERE id='$pm_id'";
    //mysql_query($action) or die(" Unable to update dates in new project entry to the table $projects_setup. <BR>/> Query = $action");
    try {
        $db->query($action);
    } catch (PDOException $ex) {
        echo "An Error occured updating dates in projects_setup<BR>\n";
        echo(" Query = $action<BR>\n");
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $display_block .= "<h2>creating the new database tables...</H2>";

    //
    // Project proposals tables
    //
    // Master name
    $new_table = "master_name_" . $new_short_name_lc . "_" . $new_year_begin;
    $display_block .= "$new_table ";
    $query = CreateProjectMasterNameTableQuery($new_table);
    // Check table exists
    if (!DoesTableExist($db,$new_table)) {
        //mysql_query($query) or die(" Unable to create the table - $new_table . <BR>Query: $query");
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= " ... created ...<BR>";
    } else {
        $display_block .= " ... already exists ...<BR>";
    }

    // Project details
    $new_table = "project_details_" . $new_short_name_lc . "_" . $new_year_begin;
    $display_block .= "$new_table ";
    $query = CreateProjectDetailsTableQuery($new_table);
    // Check table exists
    if (!DoesTableExist($db,$new_table)) {
        //mysql_query($query) or die(" Unable to create the table - $new_table . <BR>Query: $query");
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= " ... created ...<BR>";
    } else {
        $display_block .= " ... already exists ...<BR>";
    }

    //
    // Assess tables
    //
    for ($part = 1; $part <= 5; $part++) {
        for ($marker = 1; $marker <= 3; $marker++) {
            // assess
            $new_table = "assess_" . $new_short_name_lc . "_" . $new_year_end . "_p$part" . "_m$marker";
            $display_block .= "$new_table ";
            $query = CreateAccessTableQuery($new_table);
            //$display_block .="$query<BR>";

            // Check table exists
            if (!DoesTableExist($db,$new_table)) {
                //mysql_query($query) or die(" Unable to create the table - $new_table . <BR>Query: $query");
                try {
                    $db->query($query);
                } catch (PDOException $ex) {
                    echo "An Error occured creating table<BR>\n";
                    $err_msg = $ex->getMessage();
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }
                $display_block .= " ... created ...<BR>";
            } else {
                $display_block .= " ... already exists ...<BR>";
            }
        }
    }

    //
    // Assess total table
    //
    $new_table = "assess_total_" . $new_short_name_lc . "_" . $new_year_end;
    $display_block .= "$new_table ";
    $query = CreateAccessTotalTableQuery($new_table);
    //$display_block .="$query<BR>";

    // Check table exists

    if (!DoesTableExist($db,$new_table)) {
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= " ... created ...<BR>";
    } else {
        $display_block .= " ... already exists ...<BR>";
    }
    //mysql_query($query) or die(" Unable to create the table - $new_table . <BR>Query: $query");

    //
    // Allocated table
    //
    $new_table = "allocated_" . $new_short_name_lc . "_" . $new_year_begin;
    $display_block .= "$new_table ";
    $query = CreateAllocatedTableQuery($new_table);
    //$display_block .="$query<BR>";

    // Check table exists
    if (!DoesTableExist($db,$new_table)) {
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= " ... created ...<BR>";
    } else {
        $display_block .= " ... already exists ...<BR>";
    }
    //
    // Allocated staff table
    //
    $new_table = "allocated_" . $new_short_name_lc . "_" . $new_year_begin . "_staff";
    $display_block .= "$new_table ";
    $query = CreateAllocatedStaffTableQuery($new_table);
    //$display_block .="$query<BR>";

    // Check table exists
    if (!DoesTableExist($db,$new_table)) {
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= " ... created ...<BR>";
    } else {
        $display_block .= " ... already exists ...<BR>";
    }
    //
    // Students table
    //
    $new_table = "students_" . $new_short_name_lc . "_" . $new_year_begin;
    $display_block .= "$new_table ";
    $query = CreateStudentsTableQuery($new_table);
    //$display_block .="$query<BR>";

    // Check table exists
    if (!DoesTableExist($db,$new_table)) {
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= " ... created ...<BR>";
    } else {
        $display_block .= " ... already exists ...<BR>";
    }

    //
    // Students project selection table
    //
    $new_table = "select_" . $new_short_name_lc . "_" . $new_year_begin;
    $display_block .= "$new_table ";
    $query = CreateStudentsSelectionTableQuery($new_table);
    //$display_block .="$query<BR>";

    // Check table exists
    if (!DoesTableExist($db,$new_table)) {
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            echo("Error message: $err_msg<BR>\n");
            exit();
        }
        $display_block .= " ... created ...<BR>\n";
    } else {
        $display_block .= " ... already exists ...<BR>\n";
    }

    //
    // Students programme names table
    //
    $new_table = "students_programmes_" . $new_short_name_lc . "_" . $new_year_begin;
    $display_block .= "$new_table ";
    $query = CreateStudentsProgrammesTableQuery($new_table);
    //$display_block .="$query<BR>";

    // Check table exists
    if (!DoesTableExist($db,$new_table)) {
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            echo("Error message: $err_msg<BR>\n");
            exit();
        }
        $display_block .= " ... created ...<BR>\n";
    } else {
        $display_block .= " ... already exists ...<BR>\n";
    }

    // Question Tables
    // $new_num_parts. create all tables just in case
    for ($part = 1; $part <= 5; $part++) {
        $table = "assessqs_" . $new_short_name_lc . "_" . $new_year_end . "_p$part";
        $p_table = "p$part" . "_template";
        $table_template = $_POST[$p_table];
        $table_cats_template = "assessqs_cats_template_" . substr($table_template,18);
        //$display_block .="$table<BR>";
        // Check table exists
        if (!DoesTableExist($db,$table)) {
            $query = "CREATE TABLE $table LIKE $table_template";
            try {
                $result = $db->query($query);
            } catch (PDOException $ex) {
                echo "An Error occured check if table exists<BR>\n";
                $err_msg = $ex->getMessage();
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            //$display_block .="$query<BR>";
            $display_block .= "$table ... created<BR>";
            $query = "INSERT INTO $table SELECT * FROM $table_template";
            //$display_block .="$query<BR>";
            $display_block .= "$table ... copied data<BR>";
            try {
                $result = $db->query($query);
            } catch (PDOException $ex) {
                echo "An Error occured copying data from table $table_template to $table<BR>\n";
                $err_msg = $ex->getMessage();
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
        } else {
            $display_block .= "$table ... already exists ...<BR>";
        }

        // Corresponding cats tables
        $table = "assessqs_cats_" . $new_short_name_lc . "_" . $new_year_end . "_p$part";
        //$display_block .="$table<BR>";
        // Check table exists
        if (!DoesTableExist($db,$table)) {
            /*
            if (strpos($table_template, "obj") > 0) {
                $table_cats_template = "assessqs_cats_template_obj";
            } else if (strpos($table_template, "effort") > 0) {
                $table_cats_template = "assessqs_cats_template_effort";
            } else if (strpos($table_template, "pres") > 0) {
                $table_cats_template = "assessqs_cats_template_pres";
            } else if (strpos($table_template, "report") > 0) {
                $table_cats_template = "assessqs_cats_template_report";
            } else if (strpos($table_template, "numeric") > 0) {
                // just insert a cats
                echo("The questions table is $table_template <BR>");
                echo("The questions table root name is " . substr($table_template,18) . "<BR>");
                $table_cats_template = "assessqs_cats_template_" . substr($table_template,18);
                echo "the cats table is $table_cats_template<BR>\n";
                //die("The table $table_template is unrecognised<BR>");
            }
            */

            $query = "CREATE TABLE $table LIKE $table_cats_template";
            //$display_block .="$query<BR>";
            try {
                $result = $db->query($query);
            } catch (PDOException $ex) {
                echo "An Error occured creating table<BR>\n";
                $err_msg = $ex->getMessage();
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $display_block .= "$table ... created<BR>";
            $query = "INSERT INTO $table SELECT * FROM $table_cats_template";
            //$display_block .="$query<BR>";
            try {
                $result = $db->query($query);
            } catch (PDOException $ex) {
                echo "An Error occured copying data from table $table_template to $table<BR>\n";
                $err_msg = $ex->getMessage();
                exit();
            }
            $display_block .= "$table ... copied data<BR>";
        } else {
            $display_block .= "$table ... already exists ...<BR>";
        }
    }

    // Staff table
    $table = "staff_names_" . $new_short_name_lc . "_" . $new_year_begin;
    $staff_table = "staff_names_s";
    $table_template = $_POST[$staff_table];
    //$display_block .="$table<BR>";
    // Check table exists
    if (!DoesTableExist($db,$table)) {
        $query = "CREATE TABLE $table LIKE $table_template";
        //$display_block .="$query<BR>";
        try {
            $result = $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured creating table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= "$table ... created<BR>";

        $query = "INSERT INTO $table SELECT * FROM $table_template";
        //$display_block .="$query<BR>";
        try {
            $result = $db->query($query);
        } catch (PDOException $ex) {
            echo "An Error occured copying data from the table $table_template to $table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= "$table ... copied data<BR>";
    } else {
        $display_block .= "$table ... already exists ...<BR>";
    }

    $display_block .= "<HR>";

} else {

// Display a list of years
    $module_prefix_name = "CIVE";
    $module_postfix_name = ""; // could be "M"
    $new_short_name = "MENG";
    $new_short_desc = "Level 5 project";

// Display the dropdowns to select the module and year
    $display_block .= "<H1>Create a new module entry ...</H1>\n";
    $display_block .= "<P>This will create all the database tables - so get it right!</P>";
    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">\n";
    $display_block .= "<H2>Module start session/year</H2>";
    $display_block .= "<P>The <strong>year</strong> is the year of the session, that begining in Spetember.</P>";
    $display_block .= "<P><strong>Year: </strong><select name=\"new_year_begin\" > \n";

    for ($year = 2016; $year <= 2030; $year++) {
        $display_block .= "<option value=\"$year\" >$year</option>\n";
    }
    $display_block .= "</select> (This <strong>CAN NOT</strong> changed later)</P>\n";

    $display_block .= "<H2>Module prefix and number</H2><P>E.g. For the module CIVE3708  the <strong>prefix</strong> will be CIVE, and the  <strong>number</strong> 3708</P>";
    $display_block .= "<P><strong>Module code prefix: </strong><input type=\"text\" name=\"new_module_prefix_name\" value=\"$module_prefix_name\" size=5 maxlength=4></P>";
    $display_block .= "<P><strong>Module code number: </strong><input type=\"text\" name=\"new_module_number\" value=\"1234\" size=5 maxlength=4>";
    $display_block .= "<P><strong>Module code post-fix: </strong><input type=\"text\" name=\"new_module_postfix_name\" value=\"$module_postfix_name\" size=1 maxlength=1></P>";
    $display_block .= "<H2>The module short name.</H2>";
    $display_block .= "<P> E.g. For level 3 projects you could use BENG, for level 5 project use MENG, MSc might use the programme codes (e.g. WASH, EEPM, SE, ICME).  <BR>Ad-hoc modules e.g. IDP you might use IDP. It shoud be no more that 4 letters.</P>";
    $display_block .= "<P><strong>Module short name</strong> <input type=\"text\" name=\"new_module_short_name\" value=\"$new_short_name\" size=5 maxlength=5> (Note that this must be UNIQUE for the session/year - and it <strong>CAN NOT</strong> be changed later.)</P>";
    $display_block .= "<P><strong>Module short description</strong> <input type=\"text\" name=\"new_module_short_desc\" value=\"$new_short_desc\" size=25 maxlength=25> (Up to 25 characters, not blank)</P>";

    $display_block .= "<H2>Assessment management details:</H2>";

    $display_block .= "<p>Module leader:  </td><td>" . GetStaffSelectID($db, $staff_names2, "staff_id", GetStaffIDFromName($db, $module_leader_fullname, $staff_names2)) . "</p>\n";
    $display_block .= "<p>Acceptable variation between markers : </td><td><input type=\"number\" name=\"accept_var\" value=\"$accept_var\" min=\"0\" max=\"100\" size=\"3\"> %</p>\n";
    $display_block .= "<p>Pass mark:  </td><td><input type=\"number\" name=\"pass_mark\" value=\"$pass_mark\" min=\"0\" max=\"100\" size=\"3\"> %</p>\n";
    $display_block .= "<p>Minimum number of feedback words:  </td><td><input type=\"number\" name=\"min_feedback_words\" value=\"$min_feedback_words\" min=\"0\" max=\"100\" size=\"3\"></p>\n";

    $display_block .= "<H2>Assessment Parts:</H2>";
    $display_block .= "<P><strong>Number of assessment parts: </strong><input type=\"number\" name=\"new_num_parts\" value=\"5\" min=\"1\" max=\"5\" size=6 maxlength=5> (1 to 5 - this <strong>CAN NOT</strong> be changed later)";

    $descs[1] = "Objectives";
    $descs[2] = "Presentation";
    $descs[3] = "Effort";
    $descs[4] = "Report";
    $descs[5] = "Poster";
    $weights[1] = "10";
    $weights[2] = "10";
    $weights[3] = "10";
    $weights[4] = "60";
    $weights[5] = "10";
    $template_tables[1] = "assessqs_template_obj";
    $template_tables[2] = "assessqs_template_pres";
    $template_tables[3] = "assessqs_template_effort";
    $template_tables[4] = "assessqs_template_report";
    $template_tables[5] = "assessqs_template_pres";
    $template_cats_tables[1] = "assessqs_cats_template_obj";
    $template_cats_tables[2] = "assessqs_cats_template_pres";
    $template_cats_tables[3] = "assessqs_cats_template_effort";
    $template_cats_tables[4] = "assessqs_cats_template_report";
    $template_cats_tables[5] = "assessqs_cats_template_pres";;

    for ($part = 1; $part <= 5; $part++) {
        $display_block .= "<H3>Part $part:</H3>";
        $name = "p$part" . "_desc";
        $desc = $descs[$part];
        $display_block .= "<P>Description of assesment part $part: <input type=\"text\" name=\"$name\"  value=\"$desc\" size=25 maxlength=25>";
        $name = "p$part" . "_template";
        $display_block .= "<P> Select a questions template for  part $part: " . GetQsTemplatesSelect($db, $name, $template_tables[$part]) . "</P>";
        $name = "p$part" . "_num_markers";
        $display_block .= "<P>Number of markers for part $part: <input type=\"number\" name=\"$name\" value=\"1\" min=\"1\" max=\"3\" size=6 maxlength=5>";
        $name = "p$part" . "_percent";
        $weight = $weights[$part];
        $display_block .= "<P>% weight of part $part: <input type=\"number\" name=\"$name\" value=\"$weight\" min=\"0\" max=\"100\" size=6 maxlength=5>";
        $name = "p$part" . "_max";
        $display_block .= "<P>Maximum mark for part $part: <input type=\"number\" name=\"$name\" value=\"100\" min=\"1\" max=\"100\" size=6 maxlength=5>";
        $name = "p$part" . "_submission_date";
        $date = "2016-09-24";//date('Y-m-d');//"2014-09-24";
        $display_block .= "<P>Submission date for part $part: <input type=\"text\" id=\"$name\" name=\"$name\"  value=\"$date\" size=20 maxlength=15>";
        $name = "p$part" . "_marking_date";
        $display_block .= "<P>Mark-by date for part $part: <input type=\"text\" id=\"$name\" name=\"$name\"  value=\"$date\" size=20 maxlength=15>";
    }

    $display_block .= "<H2>Staff names list:</H2>";
    $name = "staff_names_s";
    $staff_names_match = "staff_names";
    //$display_block .= "<P> Select a previous list of staff names to use: " . GetStaffNameTemplatesSelect($db, $name, $staff_names_match) . "</P>";
    $display_block .= "<P> All modules use the global list of staff names: <b>$staff_names</b>. </P>";
    $display_block .= "\n<input type=\"hidden\" name=\"$name\" value=\"$staff_names\">";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"create\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Setup a new set of tables for the above module\"></p>";

    $display_block .= "</form>";

}// end of if ($POST_OP == "select")

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Create New Project Entry in the database</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="setup_projects_for_year.php<?php echo "?pm_id=$pm_id" ?>" class="nav-link px-2 text-white-50">Reload this page</a></li>
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Admin index page</a></li>
            <li class="nav-item"><a target="_self" href="../index.php<?php echo("?year_begin=$year_begin") ?>" class="nav-link px-2 text-white-50">Year index page</a></li>
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
