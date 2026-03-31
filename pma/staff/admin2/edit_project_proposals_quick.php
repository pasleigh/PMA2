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
        $display_block .= "<TD><input type='text' id='title_id$project_count' value='$project_title'></TD>";
        $display_block .= "<TD>$supervisor_name" . GetStaffSelectID($db, $staff_names, "supervisor_id$project_count", $supervisor_id) . "</TD>";
        $display_block .= "<TD>$second_supervisor_name" . GetStaffSelectID($db, $staff_names, "second_supervisor_id$project_count", $second_supervisor_id) . "</TD>";
        $display_block .= "<TD><INPUT type='number' id='number$project_count' name='number$project_count' max='10' value='$num_projects'></TD>";
        $display_block .= "<TD>$progs<BR>$progs_chks</TD>";

        $checked = "";
        $display_block .= "<TD><input type=\"checkbox\" id='delete$project_count' name=\"delete$project_count\" value=\"1\" $checked></TD>\n";

        $display_block .= "</TR>\n";

        $project_count++;
    }

    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_edited\">";
    $display_block .= "\n<input type=\"hidden\" name=\"project_count\" value=\"$project_count\">";

    //$display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit changes to project details.\"></p>";

    //$display_block .= "</form>\n";

    $display_block .= "<button id='listPost_btn' type='button'>Submit changes to project details</button><BR><BR>";
} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
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

        $( "#listPost_btn" ).click(function() {
            //alert( "Handler for .click() called." );
            var projects_list = [];
            $('[id^="master_id"]').each(function() {
                var this_row_obj = this;
                var master_id_id  = this.id;
                var project_index = master_id_id.substring(9);
                var master_id = $(this).val();

                var title_id = "title_id"+project_index;
                var super_id = "supervisor_id"+project_index;
                var second_id = "second_supervisor_id"+project_index;
                var number_id = "number" + project_index;
                var delete_id = "delete" + project_index;

                var progs_id = "pr_" + project_index;

                var title = $('#' + title_id ).val();
                var sup = $('#' + super_id + ' option:selected').html();
                var sup_id = $('#' + super_id).val();
                var second_sup = $('#' + second_id + ' option:selected').html();
                var second_sup_id = $('#' + second_id).val();
                var number_projs = $('#' + number_id ).val();

                var del_chk = 0;
                if ($('#' + delete_id ).prop('checked')) {
                    del_chk = 1;
                }

                // Get the project
                var progs_str = '';
                for(var i = 0; i < 7; i++){
                    var this_prog_flag = 0;
                    if($('#pr_' + master_id + '_' + i).prop('checked')){
                        this_prog_flag = 1;
                    }
                    progs_str += this_prog_flag.toString();
                    //console.log(progs_str + ' : ' + this_prog_flag)
                }

                var jq = '[id^="' + progs_id + '"]';

                var this_project = {
                    project_index : project_index,
                    master_id : master_id,
                    title : title,
                    supervisor: sup,
                    supervisor_id: sup_id,
                    second_supervisor : second_sup,
                    second_supervisor_id : second_sup_id,
                    number_projects : number_projs,
                    delete : del_chk,
                    progs_str : progs_str
                };
                projects_list.push(this_project);
            });

            for(var i = 0 ; i < projects_list.length ; i++){
                var project_index = projects_list[i].project_index;
                var master_id = projects_list[i].master_id;
                var title = projects_list[i].title;
                var sup = projects_list[i].supervisor;
                var second_sup = projects_list[i].second_supervisor;
                var number_projs = projects_list[i].number_projects;
                var del_chk = projects_list[i].delete;
                var progs_str = projects_list[i].progs_str;

                //console.log(project_index + ' : ' + master_id + ' : ' + title + ' : ' + sup + ' : ' + second_sup + ' : ' + number_projs + ' : ' + del_chk + ' : ' + progs_str);
            }

            var form_data = new FormData();
            form_data.append('projects_list', JSON.stringify(projects_list));
            form_data.append('pm_id', "<?php echo($pm_id) ?>");
            $.ajax({
                type: 'post',
                url: 'edit_project_proposals_push_to_db.php',
                data: form_data,
                contentType:false,
                processData:false,
                dataType: "json",
                success:function(mydata){
                    // reset all arrays
                    //console.log(JSON.stringify(mydata));
                    $('#result').html('<H2>AJAX RESULT:</H2>' + JSON.stringify(mydata));

                    // No need to do anything with the returned data
                    // It should have all been pushed into the database
                    // could inform the user that it has been entered
                    alert("Project changes were saved");

                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('An error occurred... Look at the console (F12 or Ctrl+Shift+I, Console tab) for more information!');

                    //$('#result').html('<p>status code: ' + jqXHR.status + '</p><p>errorThrown: ' + errorThrown + '</p><p>jqXHR.responseText:</p><div>' + jqXHR.responseText + '</div>');
                    console.log('jqXHR:');
                    console.log(jqXHR);
                    console.log(jqXHR.responseText);
                    console.log('textStatus:');
                    console.log(textStatus);
                    console.log('errorThrown:');
                    console.log(errorThrown);
                }
            });
        });

    });

</script>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Quick Modification of Projects- <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
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
        <?php echo $display_block; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>
