<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot read staff_functions file<BR>");

(@include_once("../proposals/project_display_functions_staff.php")) OR die("Cannot include project_display_functions_staff.php");
(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg ;

// Check the admin level
//$access = false;
//$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
//$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

//----------------------------------------------------------------------------------------------------
//$display_block .= "<h1 style='background-color: #f1c40f; text-align: center;  font-size: xx-large'>THIS PAGE IS STILL UNDER CONSTRUCTION<BR>DO NOT CLICK THE BUTTONS</h1>";
//$access = false;
//----------------------------------------------------------------------------------------------------


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

    $display_block .= "<h1>Quick minor modification of projects </h1>";
    $display_block .= "<h3>Database tables: $master_name and $project_details</h3>";

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    $POST_OP = $_POST['op'];

    if (array_key_exists('project_count', $_POST) == false) {
        $_POST['project_count'] = null;
    }

// see all the POST values
/*    echo("POST size = " . count($_POST) ."<BR>");
    $post_index = 0;
foreach ($_POST as $key => $value){
  echo "$post_index {$key} = {$value}<BR>\r\n";
  $post_index++;
}
*/
if ($POST_OP == "submit_edited") {
    $project_count = $_POST['project_count'];
    for ($i = 1; $i < $project_count; $i++) {

        //$display_block .= "Submitting: <BR>\n";
        //$postid = "title$i";
        //$title = $_POST[$postid];
        $postid = "master_id$i";
        $master_id = $_POST[$postid];
        $postid = "supervisor_id$i";
        $supervisor_id = $_POST[$postid];
        $supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff_names, false);
        $postid = "second_supervisor_id$i";
        $second_supervisor_id = $_POST[$postid];
        $second_supervisor_name = GetStaffNameFromID($db, $second_supervisor_id, $staff_names, false);
        $postid = "number$i";
        $number_of_projects = $_POST[$postid];

        // programme variant
        $prog_variant_str = "";
        for($j = 0 ; $j < 7 ; $j++){
            $pr_chk_id_key = "pr_$master_id" . "_$j";
            $prog_flag = "0";
            if(array_key_exists($pr_chk_id_key, $_REQUEST))
            {
                $prog_flag = "1";
            }
            $prog_variant_str .= $prog_flag;
        }
        //echo("pr str ($master_id): $prog_variant_str <BR>");

        $postid = "delete$i";
        if (array_key_exists($postid, $_POST) == false) {
            $_POST[$postid] = 0;
        }
        $delete_chk = intval($_POST[$postid]);

        //$display_block .= "$master_id, $supervisor_id, $second_supervisor_id, $number_of_projects, $delete_chk .... <BR>\n";
        if($delete_chk == 0) {
            $query_details = "UPDATE $project_details set f_assocstaff='$second_supervisor_name', f_assocstaff_id='$second_supervisor_id',f_PGprojects='$number_of_projects', f_progs='$prog_variant_str' where master_id=$master_id";
            $query_master = "UPDATE $master_name set f_supervisor='$supervisor_name',f_supervisor_id='$supervisor_id' where id=$master_id";
        }else{
            // Delete entry from bother tables
            $query_details = "DELETE FROM $project_details WHERE master_id=$master_id";
            $query_master = "DELETE FROM $master_name WHERE id=$master_id";
        }

        //$display_block .= "project details query: $query_details <BR>";
        try {
            $results = $db->query($query_details);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $project_details<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }

        //$display_block .= "master query: $query_master <BR>";
        try {
            $results = $db->query($query_master);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $master_name<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }

    }
    $display_block .= "<B>All updated</B><BR><hr>\n";

}


    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

// Get the list of proposals from the $master_name table
    $query = "SELECT * FROM $master_name ORDER BY id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
//$display_block = "<TABLE  class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">\n";
    $display_block .= "<TABLE id=\"proposalinfo\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $display_block .= "
    <thead>
      <tr>
        <th>Index</th>
        <th>Project id</th>
        <th>Title</th>
        <th>Supervisor</th>
        <th>Second marker</th>
        <th>How many</th>
        <th>Programmes</th>
        <th>Checked=DELETE</th>
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
    $project_count = 1;
    $rows = $results->fetchAll();
//echo("$query<BR>\n");
//print_r2($results);
//print_r2($rows);
//if ($results->fetch(PDO::FETCH_NUM) > 0 ) {
    foreach ($rows as $recs) {

        $master_id = $recs['id'];
        $project_title = $recs['f_title'];
        $supervisor_name = $recs['f_supervisor'];
        $supervisor_id = GetStaffIDFromName($db, $supervisor_name, $staff_names);
        //$supervisor_name .= " ($supervisor_id)";


        $query = "SELECT * FROM $project_details WHERE master_id=$master_id";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $p_rec = $results->fetch();
        $second_supervisor_name = $p_rec['f_assocstaff'];
        $second_supervisor_id = $p_rec['f_assocstaff_id'];
        $second_supervisor_id = GetStaffIDFromName($db, $second_supervisor_name, $staff_names);
        //$second_supervisor_name .= " ($second_supervisor_id)";
        $num_projects = $p_rec['f_PGprojects'];
        $progs = $p_rec['f_progs'];

        $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_progs_variants);
        $progs_chks = "";
        for($i = 0; $i < $num_progs_variants; $i++){
            $prog_variant_flags[$i] = (int)substr($progs,$i,1);
            $checked = "";
            if($prog_variant_flags[$i] == 1){
                $checked = "checked";
            }
            $chk_id = "pr_$master_id" . "_$i";
            $prog_variant = $program_variants[$i];
            $progs_chks .= "<input type='checkbox' title='$prog_variant' name='$chk_id' id='$chk_id' $checked />";
        }

        $project_index = sprintf('%04d', $project_count);
        $display_block .= "<TR>\n";
        $display_block .= "<TD>$project_index</TD>";
        $display_block .= "<TD><input type=\"hidden\" id=\"master_id$project_count\" name=\"master_id$project_count\" value=\"$master_id\">$master_id</TD>";
        $display_block .= "<TD><input type='text' id='title_id$project_count' value='$project_title' readonly></TD>";
        $display_block .= "<TD>$supervisor_name" . GetStaffSelectID($db, $staff_names, "supervisor_id$project_count", $supervisor_id) . "</TD>";
        $display_block .= "<TD>$second_supervisor_name" . GetStaffSelectID($db, $staff_names, "second_supervisor_id$project_count", $second_supervisor_id) . "</TD>";
        $display_block .= "<TD><INPUT type='number' id='number$project_count' name='number$project_count' max='10' value='$num_projects'></TD>";
        $display_block .= "<TD>$progs<BR>$progs_chks</TD>";

        $checked = "";
        $display_block .= "<TD><input type=\"checkbox\" id='delete$project_count' name=\"delete$project_count\" value=\"1\" $checked></TD>\n";

        $display_block .= "</TR>\n";

        $project_count++;
    }
//}
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_edited\">";
    $display_block .= "\n<input type=\"hidden\" name=\"project_count\" value=\"$project_count\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit changes to project details.\"></p>";

    $display_block .= "</form>\n";

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
        $("#proposalinfo").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false
            }
        );


    });


</script>
<BODY>
<div id="wrapper">
    <div id="header">
        <span
            class="header">Quick Modification of Projects- <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
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
