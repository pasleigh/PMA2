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
//$access = false;
//$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
//$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h2>\n";

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db,$this_staff_username, $this_staff_full_name, $staff_names,$access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase );
if($is_module_admin){
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if($is_module_admin || $admin_access) {

    $admin_users_table = "admin_users";
    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
// if the form was submitted then enter it all into the database
//echo("OP=$_POST['op'] . <BR>\n");
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }
//echo("POST_OP=$POST_OP <BR>\n");

// see all the POST values
//foreach ($_POST as $key => $value){
//  echo "{$key} = {$value}<BR>\r\n";
//}
// Get the project
    if (!isset($_REQUEST['pm_id'])) {
        $_REQUEST['pm_id'] = null;
    }
    $pm_id = $_REQUEST['pm_id'];

    // create the table if necessary
    create_project_areas_table_structure($db,$projects_areas);

    $display_block .= "<h1><span style='color: black'>Edit Project Areas for:</span> $module_short_description - $module_prefix$module1$module_postfix <span style='color: black'>starting </span>Sept $year_begin</h1>";

    $display_block .= "<h1><span style='color: red'>Note that Project Areas are currently NOT used in selection of projects</span></h1>";

    if ($POST_OP == "submit_modified") {
        $display_block .= "Submitting changes in text and checked items to delete: <BR>\n";

        // Loop through to find the "id" key
        $area_count = 0;
        foreach ($_POST as $key => $value)
        {
            if(substr($key,0,2)=="id"){

                $area_count++;
                $key2 = $key;
                $area_id = $_POST[$key2];
                $key2 = "area$area_count";
                $area = $_POST[$key2];
                $key2 = "description$area_count";
                $area_description = $_POST[$key2];

                $query = "UPDATE $projects_areas SET area='$area', description='$area_description' WHERE `id`='$area_id'";
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


        // loop through all _POST find the "ai" keys
        //echo("<PRE>");
        foreach ($_POST as $key => $value)
        {
            if(substr($key,0,3)=="ai_"){
                $area_index = substr($key,3);

                $query = "DELETE FROM $projects_areas WHERE `id`='$area_index'";
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
        $display_block .= "<B>Done</B><BR><hr>\n";
    }elseif($POST_OP == "new"){
        $area_new = $_REQUEST['area_new'];
        $description_new = $_REQUEST['description_new'];
        //echo("$area_new : $description_new");

        $query = "INSERT INTO $projects_areas SET area='$area_new',description='$description_new'";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $projects_areas<BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }

        $display_block .= "<B>New area added: $area_new, $description_new</B><BR><hr>\n";

    }

    $display_block .= "<HR>";
    $display_block .= "<H2>Project Areas table: $projects_areas</H2>";
    $display_block .= "<h1><span style='color: black'>The sortable list of Project Areas - as the students see it (drag to reorder)</span> </h1>";


// Display the sortable
    $query = "SELECT * FROM $projects_areas";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $rows = $results->fetchAll();
    $num_areas = $results->rowCount();
    $sortable_text = "<div>\n";
    $sortable_text .= "<ul id='sortable'>\n";
    foreach ($rows as $recs) {
        $id = $recs['id'];
        $area = $recs['area'];
        $area_description = $recs['description'];
        $text = "<strong>$area :</strong> $area_description";
        $sortable_text .= "<li class='ui-state-default' id='$id'><span class='ui-icon ui-icon-arrowthick-2-n-s'></span>$text</li>";
    }

    $sortable_text .= "</ul>\n";
    $display_block .= $sortable_text;

//---------------------------------------------------------------------------------
// Display the project areas table

    $display_block .= "<HR>";
    $display_block .= "<h1><span style='color: black'>The editable list of Project Areas</span> </h1>";


    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

// Get the list of staff from the staff_names2 table
    $query = "SELECT * FROM $projects_areas";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }

    $display_block .= "<TABLE id=\"areas\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $display_block .= "
    <thead>
      <tr>
        <th>ID</th>
        <th>Project Area</th>
        <th>Description</th>
        <th>Tick to delete</th>    
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
    $count = 0;
    $rows = $results->fetchAll();
    foreach ($rows as $recs) {

        $id = $recs['id'];
        $area = stripslashes($recs['area']);
        $description = stripslashes($recs['description']);
        $count++;
        $display_block .= "<TR>\n";
        $display_block .= "<TD><input type=\"hidden\" name=\"id$count\" value=\"$id\"> $id</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"area$count\" value=\"$area\"  size=30 maxlength=50 ></TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"description$count\" value=\"$description\"  size=60 maxlength=150 ></TD>\n";
        $name_id = "ai_$id";
        $display_block .= "<TD><input type='checkbox' name='$name_id' id='$name_id' value='1'></TD>\n";
        $display_block .= "</TR>\n";
    }
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_modified\">";
    $display_block .= "\n<input type=\"hidden\" name=\"areas_count\" value=\"$count\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit changes to admin users details.\"></p>";

    $display_block .= "</form>";

    $display_block .= "<hr>\n";


//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Add a new area to the list...</H2>\n";
    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"new\">";
    $display_block .= "New Area: <input type=\"text\" name=\"area_new\" value=\"\"  size=30 maxlength=50 ></TD>\n";
    $display_block .= " Description: <input type=\"text\" name=\"description_new\" value=\"\"  size=60 maxlength=150 ></TD>\n";
    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Add the above project area to the list.\"></p>";

    $display_block .= "</form>";

//---------------------------------------------------------------------------------

} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}

?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>
    $(function () {
        $("#areas").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false
            }
        );
    })
    $( function() {
        $( "#sortable" ).sortable();
        $( "#sortable" ).disableSelection();
    } );
</script>
<BODY>
<div id="wrapper">
    <div id="header">
        <span
            class="header">Edit area details - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div class="footer_link" id="footer">
        <P>Return to the
            <a href="../projectmarking_select.php?pm_id=<?php echo("$pm_id") ?>">Module page</a> -
            <a href="../session_modules.php?year_begin= <?php echo("$year_begin") ?>">Year index page</a> -
            <a href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>">Module admin page</a> -
            <a href="./index.php">Main admin page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
