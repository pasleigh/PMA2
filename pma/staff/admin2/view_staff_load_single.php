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
    if (array_key_exists('pm_id', $_REQUEST) == false) {
        $_REQUEST['pm_id'] = null;
    }

    $query = "SELECT * FROM $projects_setup WHERE id=$pm_id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $rows = $results->fetch();

    $nowarray = getdate();
    $staff_close_str = $rows['staff_close'];
    $staff_close_date = strtotime($staff_close_str);
    $month_i = date("m", $staff_close_date);
    $day_i = date("d", $staff_close_date);
    $year_i = date("Y", $staff_close_date);
    $close = mktime(17, 0, 0, $month_i, $day_i, $year_i);
    $cldate = "5pm, " . date("D", $staff_close_date) . " " . $day_i . " " . date("M", $staff_close_date) . " " . $year_i;
    $module1 = $rows['module'];
    $year_begin = $rows['year_begin'];
    $year_end_short = $year_begin - 2000 + 1;
    $module_short_code = $rows['short_code'];
    $module_leader_fullname = $rows['module_leader'];
    $module_leader_id = GetStaffIDFromName($db, $module_leader_fullname,$staff2);
    $module_leader_email = GetStaffEmailFromID($db, $module_leader_id, $staff2);



    $display_block = "";
    $display_block .= $connect_msg;
    $project_summary_title = "$module_short_description ($module_short_code), $module_prefix$module1$module_postfix,  for year $year_begin/$year_end_short";
    $display_block .= "<h1>View staff project load over for the module/project: $project_summary_title</h1>\n";

    $display_block .= "<input type='hidden' value='$year_begin' id='latest_year'>\n";

    // Get a list of all projects for this year
    //$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

    //$query = "select * from $projects_setup where year_begin=$year_begin order by id";
    /*
    $query = "select * from $projects_setup order by id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    */
    $display_block .= "<DIV id='id_module_list'>\n";
    $count = 0;
    //$rows = $results->fetchAll();
    //foreach ($rows as $recs)
    {
        $id = $rows['id'];
        $module = $rows['module'];
        $module_prefix = $rows['module_prefix'];
        $module_short_desc = $rows['module_short_desc'];
        $short_code = $rows['short_code'];
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
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<style>
    div.bordered {
        border-style: solid;
        border-width: 3px;
    }
</style>

<script src="../frameworks/highcharts/highcharts.js"></script>
<script src="../frameworks/highcharts/exporting.js"></script>
<script src="../js/staff_load_projects_chart.js"></script>


<script>

    function update_module_list(val) {
        //$("#level_select_div").show();
        //alert("Module update value = " + val);
        // refresh the module list
        //update_level_list(6);
        var module_id = val;

        staff_name_list = [];
        staff_project_data = [];
        //module_name_list = [];
        //redraw_chart_and_table();
        update_chart_data();
        draw_staff_load_chart();
    }

    function update_year_selected(val) {
        var year_begin = parseInt(val);
        var year_end = year_begin + 1;
        $("#id_year_begin").html(val + "/" + year_end);
        //alert("Module update value = " + val);
        // refresh the module list
        //update_level_list(6);

        $.ajax({
            type: 'post',
            url: 'fetch_module_list_for_year.php',
            data: {
                get_year_end: year_end,
                get_year_begin: year_begin
            },
            dataType: "json",
            error: function (jqXHR, textStatus, errorThrown) {
                alert('An error occurred... Look at the console (F12 or Ctrl+Shift+I, Console tab) for more information!');

                $('#result').html('<p>status code: ' + jqXHR.status + '</p><p>errorThrown: ' + errorThrown + '</p><p>jqXHR.responseText:</p><div>' + jqXHR.responseText + '</div>');
                console.log('jqXHR:');
                console.log(jqXHR);
                console.log('textStatus:');
                console.log(textStatus);
                console.log('errorThrown:');
                console.log(errorThrown);
            },
            success: function (mydata, textStatus, jqXHR) {
                staff_name_list = [];
                staff_project_data = [];
                module_name_list = [];
                returned_staff_project_data = mydata;

                redraw_chart_and_table();
            }
        });
    }

function redraw_chart_and_table()
{
    var mydata = returned_staff_project_data;
    //console.log(JSON.stringify(mydata));
    //console.log(mydata.ids);
    // hide all check boxes
    $('span[id^="m"]').each(function(){$(this).hide();});
    //$("#id_module_list").find("span")..each(function(){this.hide()});
    for(var i = 0; i < mydata.ids.length ; i++){
        $('#m' + mydata.ids[i]).show();
    }

    var table_de = $('#data_entry').DataTable();
    table_de.clear().draw();

    var anon_chk = $('#anon_chk').is(':checked');

    var staffdata = mydata.staff_id_name;

    // redraw datatable
    var pm_id = <?php echo($pm_id)?>;
    $('#data-entry').remove();
    $('#datatable_div').html('<table width="100%" cellspacing="3" cellpadding="0" id="data-entry" class="hover compact cell-border"></table>');

    var my_columns = [];
    my_columns.push( {"sTitle": "Use on Chart" });
    my_columns.push( {"sTitle": "Name" });
    var label;
    for(i = 0 ; i < mydata.ids.length ; i++){
        if(parseInt(mydata.ids[i]) == pm_id) {
            label = mydata.modules_desc[mydata.ids[i]] + "\n" + mydata.modules[i];

            my_columns.push({"sTitle": label});
            my_columns.push({"sTitle": "2nd"});

            module_name_list.push(label);
        }
    }
    my_columns.push({"sTitle": "Total super." });
    my_columns.push({"sTitle": "Total 2nd"});

    my_columns.push({"sTitle": "Total summed"});

    var num_modules = 1;// mydata.ids.length;


    var rows_de = [];
    var count = 1;
    for (var key in staffdata) {
        if (staffdata.hasOwnProperty(key)) {
            var staffid = key;
            var staff_obj = staffdata[key];
            var full_name = staff_obj[1] + ", " + staff_obj[2];
            if(anon_chk){
            //    full_name = "Name " + count;
            }
            var family_name = staff_obj[1];
            var first_name = staff_obj[2];
            var inactive = parseInt(staff_obj[3]);
            var id = "use_name" + key;
            var name = key.toString();
            var checked = "checked";
            if (inactive > 0) {
                checked = "";
            }else{
                var my_row_data = [];
                my_row_data[0] = "<input  value='1' name='" + name + "' " + "id='" + id + "'" + checked + " type='checkbox' />";
                my_row_data[1] = full_name;

                // Loop through the module names
                var sum_super = 0;
                var sum_second = 0;
                var sum_all = 0;
                second_factor = 0.1;
                for(i = 0  ; i < num_modules ; i++) {
                    var staff_obj = mydata.staff_supervision[staffid];
                    //var mod_supervision_obj = staff_obj[mydata.ids[i]];
                    var mod_supervision_obj = staff_obj[pm_id];
                    var num_supervised = mod_supervision_obj[0];
                    var num_second_marked = mod_supervision_obj[1];
                    // Only use if checked
                    var check_id = "#chk_id" + pm_id;//mydata.ids[i];
                    if($(check_id).is(':checked'))
                    {
                        sum_super += num_supervised;
                        sum_second += num_second_marked;
                        my_row_data[2+i*2] = num_supervised;
                        my_row_data[2+i*2+1] = num_second_marked;
                        sum_all += num_supervised + second_factor*num_second_marked;
                        count++;
                    }else{
                        my_row_data[2+i*2] = "-";
                        my_row_data[2+i*2+1] = "-";
                    }

                }

/*
                my_row_data[2+2*mydata.ids.length] = sum_super;
                my_row_data[2+2*mydata.ids.length+1] = sum_second;

                my_row_data[2+2*mydata.ids.length+2] = sum_all.toFixed(1);
*/
                my_row_data[2+2*num_modules] = sum_super;
                my_row_data[2+2*num_modules+1] = sum_second;

                my_row_data[2+2*num_modules+2] = sum_all.toFixed(1);


                rows_de.push(my_row_data);
            }
        }
    }



    $('#data-entry').dataTable({
        //"bSort":    false,
        "paging": false,
        //"ordering": false,
        //"info":     false,
        "searching": false,
        "pageLength": "All",
        "columnDefs": [
            {"className": "dt-center", "targets": "_all"}
        ],
        "aoColumns": my_columns,
        data: rows_de
    });

    update_chart_data();

    var module_list = "";
    for (i = 0; i < mydata.modules.length; i++) {
        module_list += mydata.modules[i] + "(" + mydata.ids[i] + ")";
        module_list += "<input type='checkbox' name='chk_id" + i + "' id='chk_id" + i + "' value='" + mydata.ids[i] + " ' ><BR>\n";
    }

    $('#mod_table').html(module_list);

    //$('input[id^="chk_id"]').click(function () {
    //    update_module_list(this.value);
    //});

    draw_staff_load_chart();
}
function update_chart_data(){
    var mydata = returned_staff_project_data;

    staff_project_data = [];
    module_name_list = [];

    var num_modules = mydata.ids.length;
    var staffdata = mydata.staff_id_name;

    var label;

    var anon_chk = $('#anon_chk').is(':checked');

    // Chart data
    var staff_module_data = [];
    var staff_check_id;
    for(i = 0  ; i < num_modules ; i++) {
        staff_module_data = [];
        // Only use module if checked
        check_id = "#chk_id" + mydata.ids[i];
        if ($(check_id).is(':checked')) {
            // Add module name to list
            label = mydata.modules_desc[mydata.ids[i]] + " : " + mydata.modules[i];
            module_name_list.push(label);
            for (key in staffdata) {
                if (staffdata.hasOwnProperty(key)) {
                    staffid = key;
                    staff_obj = mydata.staff_supervision[staffid];
                    mod_supervision_obj = staff_obj[mydata.ids[i]];
                    num_supervised = mod_supervision_obj[0];
                    num_second_marked = mod_supervision_obj[1];

                    staff_check_id = "#use_name" + staffid;
                    if ($(staff_check_id).is(':checked')) {

                        staff_module_data.push({
                            y: num_supervised + second_factor * num_second_marked,
                            super: num_supervised,
                            second: num_second_marked
                        });

                    }

                }
            }
            staff_project_data.push(staff_module_data);
        }
    }

    // Store the staff data for the chart
    var count = 1;
    for (key in staffdata) {
        if (staffdata.hasOwnProperty(key)) {
            var staffid = key;
            var staff_obj = staffdata[key];
            var full_name = staff_obj[0];
            if(anon_chk){
                full_name = "Name " + count;
            }
            // Only use staff if checked
            staff_check_id = "#use_name" + staffid;
            if($(staff_check_id).is(':checked'))
            {
                staff_name_list.push(full_name);
                count++;
            }
        }
    }

}
function draw_staff_load_chart(){
    //my_projects_chart.series[0].name = "Level 3 Projects";
    //my_projects_chart.series[0].data = L3_projects;
    var my_projects_count_chart = my_projects_chart;
    my_projects_count_chart.title.text = "Project supervision: " + "<?php echo($project_summary_title); ?>";//'Project Supervision for all modules';
    my_projects_count_chart.subtitle.text = 'Comparative Overview of Project Supervision <BR>(bar lengths include a 10% weight for second marking)';
    my_projects_count_chart.yAxis.title.text = "Supervised Projects Count";
    my_projects_count_chart.yAxis.max = null;

    my_projects_count_chart.series = [];
    for( var i = 0; i < module_name_list.length ; i++){
        my_projects_count_chart.series[i] = {
            name: module_name_list[i],
            data: staff_project_data[i]
        };
    }

    my_projects_count_chart.exporting.filename = 'projects_count_chart_' + (new Date()).getFullYear();

    my_projects_count_chart.xAxis.categories = staff_name_list ;

    $('#ContainerProjectsChart').show();
    $('#ContainerProjectsChart').highcharts(my_projects_count_chart);
}


$(document).ready(function() {
    $("#module_list_table").dataTable(
        {
            //"bSort":    false,
            "paging": false,
            //"ordering": false,
            //"info":     false,
            "searching": false,
            "pageLength": "All",
            "columnDefs": [
                {"className": "dt-center", "targets": "_all"}
            ]
        }
    );

    var latest_year = $('#latest_year').val();
    update_year_selected(latest_year);
});
//});
</script>


<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">Staff supervision details for a single module/project <?php echo("$year_begin/$year_end_short") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
        <P>
            <a href="../">Year index page </a> &nbsp;&nbsp;
            <a href="./index.php">Main admin page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
