<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot open staff_functions.php<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot open markstore_functions.php<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h2>\n";

//print_r2($_POST);

if($access) {

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    if (array_key_exists('pm_id', $_POST) == false) {
        $_POST['pm_id'] = null;
    }
    $pm_id = $_POST['pm_id'];

    if (array_key_exists('pm_id_selected', $_POST) == false) {
        $_POST['pm_id_selected'] = null;
    }
    $pm_id_selected = $_POST['pm_id_selected'];

    if (array_key_exists('new_admin_staff_id', $_POST) == false) {
        $_POST['new_admin_staff_id'] = null;
    }
    $new_admin_staff_id = $_POST['new_admin_staff_id'];

    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }

    if(!DoesTableExist($db, $admin_users_module_table)){
        create_admin_users_module_table_structure($db);
    }

    $display_block .= "<h1>Edit Module Level Admin users </h1>";

    $query = "SELECT * FROM $projects_setup WHERE 1 ORDER BY year_begin DESC";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }

    $project_list_options = "";
    $results_data = $results->fetchAll();
    $pm_id_first = null;
    foreach ($results_data as $get_data) {
        $year_l = $get_data['year_begin'];
        $module_number_l = $get_data['module'];
        $module_short_code_s_l = $get_data['short_code'];
        $module_prefix_l = $get_data['module_prefix'];
        $module_postfix_l = $get_data['module_postfix'];
        $module_short_desc_l = $get_data['module_short_desc'];
        $pm_id_s = $get_data['id'];
        if($pm_id_first == null){
            // need this to get first on select list
            $pm_id_first = $pm_id_s;
        }

        //echo( "$module <BR>");
        if ($pm_id_s == $pm_id_selected) {
            $selected = "selected";
        } else {
            $selected = "";
        }
        $project_list_desc = "$year_l $module_prefix_l$module_number_l$module_postfix_l - $module_short_code_s_l - $module_short_desc_l";
        $project_list_options .= "<option $selected value=\"$pm_id_s\">$project_list_desc</option>\n";
    }
    if($pm_id_selected == null){$pm_id_selected = $pm_id_first;}
    // plus the feedback tools
    $selected = "";
    $project_list_desc = "Hamid - How Am I Doing";
    if ($pm_id_selected == 'hamid'){$selected = "selected";}else{$selected = "";}
    $project_list_options .= "<option $selected value='hamid'>$project_list_desc</option>\n";

    $project_list_desc = "CWT - Coursework Timetables";
    if ($pm_id_selected == 'cwt'){$selected = "selected";}else{$selected = "";}
    $project_list_options .= "<option $selected value='cwt'>$project_list_desc</option>\n";

    $project_list_desc = "DIST - Distribution charts";
    if ($pm_id_selected == 'dist'){$selected = "selected";}else{$selected = "";}
    $project_list_options .= "<option $selected value='dist'>$project_list_desc</option>\n";

    $project_list_desc = "PEGA - Workload visualisations";
    if ($pm_id_selected == 'pega'){$selected = "selected";}else{$selected = "";}
    $project_list_options .= "<option $selected value='pega'>$project_list_desc</option>\n";

    $project_list_desc = "KMAP - Key Assessment Info. Mapping";
    if ($pm_id_selected == 'kmap'){$selected = "selected";}else{$selected = "";}
    $project_list_options .= "<option $selected value='kmap'>$project_list_desc</option>\n";

    $project_list_desc = "All - show all module level users";
    if ($pm_id_selected == 'all'){$selected = "selected";}else{$selected = "";}
    $project_list_options .= "<option $selected value='all'>$project_list_desc</option>\n";

    // Display the dropdowns to select the module and year
    $display_block .= "<h2>Select the module/year you want to view/edit Module Level Admin Users for...</H1>\n";

    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">\n";
    $display_block .= "<P>Project/Module descriptions in the database: ";
    $display_block .= "<select name='pm_id_selected' id='module_select'>\n";
    $display_block .= $project_list_options;
    $display_block .= "</select>\n</P>";
    $display_block .= "<input type='hidden' name='op' value='select'>";
    $display_block .= "<p><input type='submit' id='submitbutton' name='submit' value='List admin user for the selected module/year'></p>";
    $display_block .= "</form>";

    if ($POST_OP == "selected_new_staff") {
        // write a query to get the username of the id selected
        $query = "SELECT * FROM $staff_names WHERE staffid='$new_admin_staff_id'";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $staff_names<BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }
        if($results->rowCount() > 0) {
            $staff_recs = $results->fetch();

            $fullname = $staff_recs['fullname'];
            $username = $staff_recs['username'];
            $staffid = $staff_recs['staffid'];

            if($pm_id_selected == "hamid") {
                $short_code = $pm_id_selected;
                //$pm_id_selected = -1;
            }elseif ($pm_id_selected == "cwt") {
                $short_code = $pm_id_selected;
                //$pm_id_selected = -2;
            }elseif ($pm_id_selected == "dist") {
                $short_code = $pm_id_selected;
                //$pm_id_selected = -3;
            }elseif ($pm_id_selected == "pega") {
                $short_code = $pm_id_selected;
                //$pm_id_selected = -4;
            }elseif ($pm_id_selected == "kmap") {
                $short_code = $pm_id_selected;
                //$pm_id_selected = -5;
            }elseif ($pm_id_selected == "all") {
                $short_code = $pm_id_selected;
                //$pm_id_selected = -6;
            }else{
                $query = "SELECT * FROM $projects_setup WHERE id='$pm_id_selected'";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    echo(" Query = $query<BR> \n");
                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                    exit();
                }

                $module_rec = $results->fetch();
                $year_begin = $module_rec['year_begin'];
                $module_prefix = $module_rec['module_prefix'];
                $module_postfix = $module_rec['module_postfix'];
                $module_number = $module_rec['module'];
                $short_code = $module_rec['short_code'];
                $module_short_desc = $module_rec['module_short_desc'];
            }

            // Insert the data into the admin_users_module table
            // Need to check that it does not already exist
            $query = "SELECT * FROM $admin_users_module_table WHERE username='$username' AND project_id='$pm_id_selected' ";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                exit();
            }
            if ($results->rowCount() == 0) {
                $query = "INSERT INTO $admin_users_module_table SET 
                      project_id='$pm_id_selected',username='$username',
                      staffid='$staffid',fullname='$fullname',
                      year_begin='$year_begin',short_code='$short_code',
                      admin_level='1'";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo("Unable to update data to the table $admin_users_module_table<BR>\n");
                    echo(" Query = $query<BR> \n");
                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                    exit();
                }

                $display_block .= "<B>Admin user added: $fullname</B><BR><hr>\n";
            } else {
                $display_block .= "<B>Admin user already exists: $fullname</B><BR>Username: $username, Project ID: $pm_id_selected<hr>\n";
            }
        }
    }

    // delete the checked
    if ($POST_OP == "submit_modified") {

        // loop through all _POST find the "ai" keys
        //echo("<PRE>");
        foreach ($_POST as $key => $value)
        {
            if(substr($key,0,3)=="ai_"){
                $staff_admin_index = substr($key,3);
                //echo "$staff_admin_index<BR>";
                $query = "DELETE FROM $admin_users_module_table WHERE `index`='$staff_admin_index'";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo(" Query = $query<BR> \n");
                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                    exit();
                }
            }
            //echo "$key = $value<BR>";
        }
        //echo("</PRE>");
    }
    //if ($POST_OP == "select"  || $new_admin_staff_id != null) {
    {
        // Display the admin users table for this module

        $display_block .= "<H2>The current Admin users for this Module</H2> ";

        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>\n";

        // Get the list of staff from
        $query = "SELECT * FROM $admin_users_module_table WHERE project_id='$pm_id_selected'";
        if($pm_id_selected == 'all'){
            // Show all users on every module withe module level access
            $query = "SELECT * FROM $admin_users_module_table WHERE 1";
        }

        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }

        $display_block .= "<TABLE id='adminstaffinfo' class='hover compact cell-border' cellspacing='0' width='100%'>\n";
        $display_block .= "
            <thead>
              <tr>
                <th>Project id</th>
                <th>staff id</th>
                <th>username</th>
                <th>Full name</th>
                <th>year begin</th>
                <th>short code</th>
                <th>Admin level</th>
                <th>Tick to delete</th>        
              </tr>
            </thead>";
        $display_block .= "<tbody>\n";
        $admin_staff_count = 0;
        $rows = $results->fetchAll();

        foreach ($rows as $recs) {
            $admin_user_index = $recs['index'];
            $project_id = $recs['project_id'];
            $staff_username = $recs['username'];
            $staffid = $recs['staffid'];
            $staff_fullname = $recs['fullname'];
            $year_begin = $recs['year_begin'];
            $short_code = $recs['short_code'];
            $admin_level = $recs['admin_level'];

            $staff_id = 0;
            $admin_staff_count++;
            $display_block .= "<TR>\n";
            $display_block .= "<TD>$project_id</TD>\n";
            $display_block .= "<TD>$staffid</TD>\n";
            $display_block .= "<TD>$staff_username</TD>\n";
            $display_block .= "<TD>$staff_fullname</TD>\n";
            $display_block .= "<TD>$year_begin</TD>\n";
            $display_block .= "<TD>$short_code</TD>\n";
            $display_block .= "<TD>$admin_level</TD>\n";
            $name_id = "ai_$admin_user_index";
            $display_block .= "<TD>$admin_user_index <input type='checkbox' name='$name_id' id='$name_id' value='1'></TD>\n";
            $display_block .= "</TR>\n";
        }
        $display_block .= "</tbody>\n";
        $display_block .= "</TABLE>\n";

        $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_modified\">";
        $display_block .= "\n<input type=\"hidden\" name=\"pm_id_selected\" value=\"$pm_id_selected\">";
        $display_block .= "\n<input type=\"hidden\" name=\"admin_staff_count\" value=\"$admin_staff_count\">";

        $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Delete ticked staff from admin list for this module/year.\"></p>";

        $display_block .= "</form>";

        //---------------------------------------------------------------------------------
        if($pm_id_selected != 'all') {
            $display_block .= "<hr><h2>Add a new admin person to the above selected module / year ...</H2>\n";
            $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">\n";
            $NameKey = 'new_admin_staff_id';
            $MatchID = null;
            $display_block .= GetStaffSelectID($db, $staff_names, $NameKey, $MatchID);
            $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"selected_new_staff\">";
            $display_block .= "\n<input type=\"hidden\" name=\"pm_id_selected\" value=\"$pm_id_selected\">";

            $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit_new\" value=\"Add a NEW admin user to this module/year.\"></p>";

            $display_block .= "</form>";
        }


    }


//---------------------------------------------------------------------------------




//---------------------------------------------------------------------------------

}


?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>
    $(function () {
        $("#adminstaffinfo").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false,
                "columnDefs": [
                    {"className": "dt-center", "targets": "_all"}
                ]
            }
        );
        $("#staffinfo").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false
            }
        );
        $("#new_admin_staff_id").selectize({
            placeholder: 'Start typing any part of the name ...',
            sortField: 'text'
        })
    })
</script>
<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">Module Level Admin Users Editing</span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div class="footer_link" id="footer">
        <P>
            <a href="./index.php">Admin index page</a>
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
