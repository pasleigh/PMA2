<?php
(@include_once("./set_dir_student.php")) OR die("Cannot find this file to include: set_dir_student.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./project_display_functions.php")) OR die("Cannot include project_display_functions.php");

(@include_once("./student_functions.php")) OR die("Cannot include student_function.php<BR>");
(@include_once("./selection_functions.php")) OR die("Cannot include selection_function.php<BR>");
(@include_once("$STAFF_DIR_VAL/proposals/project_display_functions_staff.php")) OR die("Cannot include project_display_functions_staff.php<BR>");
(@include_once("$STAFF_DIR_VAL/table_functions.php")) OR die("Cannot include table_functions.php<BR>");

(@include_once("$STAFF_DIR_VAL/admin2/utility_functions.php")) OR die("Cannot include utility_functions.php");

$year_end_short = $year_end - 2000;
$display_block = "";
$display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>Project Selection</div>";
$display_block .= "<div style='font-size: large; font-weight: bold; text-align: center'>$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short</div>";

$student_username = get_server_username();
$is_staff = isStaff($db, $student_username);
$is_student_current = false;
if($is_staff == false) {
    $is_student_current = isStudent_on_this_module($db, $pm_id, $student_username, $students);
}

if($is_student_current == false and $is_staff == false){
    $display_block .= GetStudentNotOnModulePhrase();
}else {
    $staff_text = "";
    if ($is_staff) {
        $staff_text = "(staff)";
        $student_name = GetStaffNameFromUsername($db, $student_username, $staff_names_default, null);
        $sid = "0";
        $is_staff_int = 1;
        $student_programme = "N/A as staff";
        $prog_to_display_index = -1;

        $student_programme_short_name = "N/A as staff";
        $student_programme_short_name_index = -1;

    } else {
        $student_name = GetStudentNameFromUsername($db, $student_username, $students);
        $sid = GetStudentIDFromUsername($db, $student_username, $students);
        $student_programme = GetStudentProgrammeFromUsername($db, $student_username, $students);

        // Get the short name
        $student_programme_short_name = GetProgrammeShortName($db, $student_programme, $students_programmes,$projects_setup_proposals, $pm_id );
        // Get the index of the short name (thiis is also the position in the flag array for a project)
        $student_programme_short_name_index = GetProgrammeShortNameIndex($db, $student_programme, $students_programmes);
        $prog_to_display_index =$student_programme_short_name_index;

        $is_staff_int = 0;
    }

    $display_block .= "<H1>You are $student_name ( $student_username $staff_text)</H1>\n";
    $display_block .= "<H2>On Programme: $student_programme</H2>\n";
    $display_block .= "<H3>Programme short name: <span id='id_short_name'>$student_programme_short_name ($student_programme_short_name_index)</span></H3>\n";

    if($is_staff){
        $display_block .= "<div style='background-color: darkgrey; border: 1px solid; padding: 7px;'>";

        $display_block .= "<H2>As staff you can select a programme, then only projects offered for that programme will be displayed.</H2>";
        $display_block .= "<div>Select which programme to display projects for:<span id='prog_displayed_name'></span></div>";
        $NameKey = "prog_select";
        $MatchID = $student_programme_short_name_index;
        $select_block = GetProgramVariantsSelectStaff($db, $projects_setup_proposals, $pm_id, $NameKey, $MatchID);
        $display_block .= "<div>$select_block</div>";
        $display_block .= "<div>Or select this checkbox to display all projects: <input type='checkbox' id='prog_select_all'></div>";
        $display_block .= "</div>";
    }

    if (!isset($_REQUEST['op'])) {$_REQUEST['op'] = null;}
    $POST_OP = $_REQUEST['op'];

    if (!isset($_REQUEST['rid_1'])) {$_REQUEST['rid_1'] = null;}
    if (!isset($_REQUEST['rid_2'])) {$_REQUEST['rid_2'] = null;}
    if (!isset($_REQUEST['rid_3'])) {$_REQUEST['rid_3'] = null;}
    if (!isset($_REQUEST['rid_4'])) {$_REQUEST['rid_4'] = null;}
    if (!isset($_REQUEST['rid_5'])) {$_REQUEST['rid_5'] = null;}
    $new_choices[0] = 0;
    $new_choices[1] = $_REQUEST['rid_1'];
    $new_choices[2] = $_REQUEST['rid_2'];
    $new_choices[3] = $_REQUEST['rid_3'];
    $new_choices[4] = $_REQUEST['rid_4'];
    $new_choices[5] = $_REQUEST['rid_5'];

    /*
    echo("_REQUEST<BR>");
    foreach ($_REQUEST as $key => $value){
        echo "{$key} = {$value}<BR>\r\n";
    }
    */

    // Get the project date details
    // $staff_close
    // $sel_open
    // $sel_close
    // $alloc_date
    $sel_close_date = $sel_close . " 23:59";
    $str_sel_close_date = date("j F Y, H:i", strtotime($sel_close_date ));

    $sel_open_date = $sel_open . " 00:00";
    $str_sel_open_date = date("j F Y, H:i", strtotime($sel_open_date ));

    $now_datetime = date("Y-m-d H:i:s");
    $close_date_diff = day_diff($now_datetime, $sel_close_date); // date1 - date2
    $open_date_diff = day_diff($now_datetime, $sel_open_date);

    if($close_date_diff > 0){
        // Closed
        $str_closing_string = "<span style='color: darkred;'>Project selection is now closed. It has been closed for " . secondsToTime(-1*$close_date_diff * 24 * 60 * 60) . "<BR> You can VIEW but you will NOT be able to make or change your selection.</span>";
    }else{
        // open
        $str_closing_string = "The project selection closes in " . secondsToTime($close_date_diff * 24 * 60 * 60);

    }

    // Date criteria, but should not have reached this page should they not be true
    // $close_date_diff > 1.0 the close date has passed
    // $open_date_diff < 0.0 opens in the future

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

    $setup_res = $results->fetch();

    // get theses two from the DB
    //$ranked_choice = true;
    //$choice_number = 3;
    $choice_number = $setup_res['choice_number'];
    $ranked_choice_int = $setup_res['ranked_choice'];
    $ranked_choice = true;
    if($ranked_choice_int != 1){
        $ranked_choice = false;
        // set the choice number to 1 so that the validation works with no adjustments

    }

    $use_project_area_int = $setup_res['use_project_area'];
    $use_project_area = true;
    if($use_project_area_int != 1){
        $use_project_area = false;
    }
    $use_project_selection_int = $setup_res['use_project_selection'];
    $use_project_selection = true;
    if($use_project_selection_int != 1){
        $use_project_selection = false;
    }

    if(!$ranked_choice){
        if (!isset($_REQUEST['urid_1'])) {$_REQUEST['urid_1'] = null;}
        if (!isset($_REQUEST['urid_2'])) {$_REQUEST['urid_2'] = null;}
        if (!isset($_REQUEST['urid_3'])) {$_REQUEST['urid_3'] = null;}
        if (!isset($_REQUEST['urid_4'])) {$_REQUEST['urid_4'] = null;}
        if (!isset($_REQUEST['urid_5'])) {$_REQUEST['urid_5'] = null;}
        $new_choices[1] = $_REQUEST['urid_1'];
        $new_choices[2] = $_REQUEST['urid_2'];
        $new_choices[3] = $_REQUEST['urid_3'];
        $new_choices[4] = $_REQUEST['urid_4'];
        $new_choices[5] = $_REQUEST['urid_5'];
    }
    $nc1 = $new_choices[1];
    $nc2 = $new_choices[2];
    $nc3 = $new_choices[3];
    $nc4 = $new_choices[4];
    $nc5 = $new_choices[5];

    //$ranked_choice = true;
    //$choice_number = 3;

    if($ranked_choice){
        $choice_text = "you are required to choose <b>$choice_number</b> projects which you would wish to undertake.
        Organise them in a ranked order from your first (most preferred) choice to your last.<BR>
        <b>You must want to do all $choice_number projects as you must accept the project you are allocated.</b>";
    }else{
        $choice_text = "you are required to choose <b>$choice_number</b> projects which you would wish to undertake.<BR>
        <b>There is no preference order and you may be allocated any of those selected.</b>";
    }
    // Store this number on the page
    $display_block .= "\n<input type='hidden' name='choice_number' id='choice_number' value='$choice_number'>\n";
    $display_block .= "\n<input type='hidden' name='ranked_choice_int' id='ranked_choice_int' value='$ranked_choice_int'>\n";

    // Selection instructions
    $display_block .= "<p>Please read the following instructions very carefully.</p>

        <div style='background-color: seashell; border: 1px solid; border-top-right-radius: 0em; border-radius: 0em 0em 0em 0em; padding: 7px;'>
       <p>The process of project selection is as follows:</p>

       <p>Examine the details of the projects by clicking on the projects listed via the link below.</P>

       <p>The projects choices you make and the popularity is calculated in <i>realtime</i>
       and displayed on the selection table to help you judge your chance of getting any particular project  </P>

       <P>If you need
       further information about a project which interests you then contact
       the principal supervisor in person or by email.</p>

       <p>BETWEEN <span style='color:red;'>$str_sel_open_date </span>and
      <span style='color:red;'>$str_sel_close_date</span> $choice_text
	    </p>
	    <p style='font-weight: bold;'>$str_closing_string </p>
	    </div>
	    ";

    if($POST_OP == "submit"){
        // enter the selection in to the table
        $query = "REPLACE INTO $selection VALUES (now(), '$sid', '$student_username', '$student_name', '$new_choices[1]',
                 '$new_choices[2]', '$new_choices[3]', '$new_choices[4]', '$new_choices[5]')";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        $query = "UPDATE $students SET sel=1 WHERE sid='$sid'";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        $update = "";
        //require('emailstudent.php');
    }

    // Display Choice table if a choice has previously been made
    $query = "SELECT * FROM $selection WHERE sid ='$sid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $rec = $results->fetch();

    if ($results->rowCount() == 1) {
        $old_choices[0] = 0;
        $old_choices[1] = $rec['choice1'];
        $old_choices[2] = $rec['choice2'];
        $old_choices[3] = $rec['choice3'];
        $old_choices[4] = $rec['choice4'];
        $old_choices[5] = $rec['choice5'];
        //require('projectlistPG.php');
    }else{
        for($i = 0; $i < 6; $i++){
            $old_choices[$i] = null;
        }
    }

    if(!$is_staff){
        $display_block .= GetChoiceTable($db,$old_choices,$master_name,$choice_number,$ranked_choice);
    }

//--------------Project Area selection Code -----------------------------------------------------------------------
    // Currently not used:
    $use_project_area = false;
    if($use_project_area) {
        // create the table if necessary
        create_project_areas_table_structure($db,$projects_areas);

        $query = "SELECT * FROM $projects_areas";
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
        $num_areas = $results->rowCount();
        $sortable_text = "<div>\n";
        $sortable_text .= "<ul id='sortable'>\n";
        foreach ($rows as $recs) {
            $id = $recs['id'];
            $area = $recs['area'];
            $area_description = $recs['description'];
            $text = "<strong>$area :</strong> $description";
            $sortable_text .= "<li class='ui-state-default' id='$id'><span class='ui-icon ui-icon-arrowthick-2-n-s'></span>$text</li>";
        }

        $sortable_text .= "</ul>\n";

        $sortable_text .= "<a href='#' data-role='button' data-inline='true' onclick='list_sortable();'>show me the list</a>";
        $display_block .= $sortable_text;

    }
//--------------End of project Area selection Code -----------------------------------------------------------------------

    $display_block .= "<H1>Here is the list of projects that are on offer</H1>";

    $display_block .= "<form method='post' onsubmit='return validate_form();' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";
    if($ranked_choice) {
        $display_block .= GetSelectionTable($db, $selection, $old_choices, $master_name, $project_details, $prog_to_display_index, $choice_number, $ranked_choice);
    }else{
        $display_block .= GetSelectionTableUnRanked($db, $selection, $old_choices, $master_name, $project_details, $prog_to_display_index, $choice_number);
    }

    // Store the un ranked values in hidded inputs
    $display_block .= "\n<input type='hidden' name='urid_1' id='urid_1' value='$nc1'>\n";
    $display_block .= "\n<input type='hidden' name='urid_2' id='urid_2' value='$nc2'>\n";
    $display_block .= "\n<input type='hidden' name='urid_3' id='urid_3' value='$nc3'>\n";
    $display_block .= "\n<input type='hidden' name='urid_4' id='urid_4' value='$nc4'>\n";
    $display_block .= "\n<input type='hidden' name='urid_5' id='urid_5' value='$nc5'>\n";
/*
    $display_block .= "<input type='hidden' name='choice1' id='choice1' value='-1'>\n";
    $display_block .= "<input type='hidden' name='choice2' id='choice2' value='-2'>\n";
    $display_block .= "<input type='hidden' name='choice3' id='choice3' value='-3'>\n";
    $display_block .= "<input type='hidden' name='choice4' id='choice4' value='-4'>\n";
    $display_block .= "<input type='hidden' name='choice5' id='choice5' value='-5'>\n";
*/
    $display_block .= "<BR>\n<input type=\"button\" id=\"validate\" name=\"btn_validate\" value=\"Check all selected\" >\n";
    $display_block .= "<input type=\"hidden\" name=\"op\" value=\"submit\">";
    $display_block .= "<input type='hidden' name='is_staff' id='is_staff' value='$is_staff_int'>\n";
    $display_block .= "<p>\n";
    if( $close_date_diff > 0.0 ){
        $display_block .=  "<span style='color: firebrick; font-weight: bold'> Project Selection has now closed as the submission date has passed by " .  secondsToTime(-1 * $close_date_diff * 24 * 3600) . "</span>\n";
    }else {
        $display_block .= " <input class='buttonstyle' type='submit' id='submitbutton' name='submit' value='Click to submit the selection to the database'  >\n";
        $display_block .= "<span style='color: firebrick;'> Click this or your selection / change of selection will not be recorded</span>\n";
    }
    $display_block .= "</p>\n</form>\n";

}
?>

<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php
(@include_once("$STUDENT_DIR_VAL/$desktop_head")) OR die("Cannot find this file to include: $desktop_head<BR>");
?>
<script type="text/javascript">
    var selectionSize = 5;
    var ranked_choice_int = 1;
    var counter =0;
    $(document).ready(function () {

        selectionSize = Number($('input#choice_number').val());
        ranked_choice_int = Number($('input#ranked_choice_int').val());
        var selections = new Array(selectionSize);
        for (var i = 0; i < selectionSize; i++) selections[i] = -1;

        $("#validate").click(function validate() {
            //var num1 = 0;
            //$('input[id^="m_id1_"]').each(function () {
            //    //val = $(this).is(':checked');
            //    if($(this).is(':checked')){
            //        num1++;
            //    }
            //});
            //alert("Number of 1 radio buttons checked: " + num1);
            if(ranked_choice_int == 1) {
                if (validate_selection()) {
                    alert("All " + selectionSize + " selected.")
                }
            }else{
                if (validate_selection_unranked()) {
                    alert("All " + selectionSize + " selected.")
                }
            }
        });

        $("input:radio").change(function () {
            var value = parseInt($(this).val());

            // get the index from the name
            var name = $(this).attr("name");

            var index;
            index = name.slice(4);
            index = parseInt(index) - 1;

            // is this in the list
            for (var i = 0; i < selections.length; i++) {
                if (value == selections[i]) {
                    alert(" This selection is has already been made. Select again.");
                    // uncheck it
                    $(this).attr('checked', false);
                    // take it out of the list
                    selections[index] = -1;
                    return;
                }
            }
            // If go to here then it is not in the list
            selections[index] = value;

        });
        $("input:checkbox").change(function () {
            var value = parseInt($(this).val());

            // get the index from the name
            var name = $(this).attr("name");

            var count_checked = 0;
            $("input:checkbox[type=checkbox]:checked").each(function(){
                if(name.slice(0,3) == 'rid') {
                    count_checked++;
                    //selections.push($(this).val());
                }
            });

            if(count_checked > selectionSize){
                alert("You have chosen too many. \nThe maximum number you can select is " + selectionSize);
                // uncheck it
                $(this).prop('checked', false);
                return;
            }

            count_checked = 0;
            $("input:checkbox[type=checkbox]:checked").each(function(){
                if(name.slice(0,3) == 'rid') {
                    selections[count_checked] = $(this).val();
                    count_checked++;
                }
            });

        });

        $( function() {
            $( "#sortable" ).sortable();
            $( "#sortable" ).disableSelection();
        } );
    });
    function list_sortable(){
        var mymsg = "";
        // Get an arry of the sortable
        var sortedIDs = $( "#sortable" ).sortable( "toArray" );
        for(var i = 0 ; i < sortedIDs.length ; i++){
            mymsg = mymsg + sortedIDs[i] + " : "
        }
        alert(mymsg);
    }
    function validate_form2() {
        //alert("validating form");
        validate();
        var num_not_selected = Number(CheckAllFeedback());
        num_not_selected += Number(CheckAllSelected());
        var num_not_enough_feedback = Number(CountAllFeedback());
        //alert("Num not seleectd = " + num_not_selected );
        //return false;
        if (num_not_selected == 0 && num_not_enough_feedback == 0) {
            //alert("All done.");
            return true;
        } else {
            //alert(num_not_selected + " questions and " +  num_not_enough_feedback + " feedback still need to be completed.");
            alert(num_not_selected + " question(s) need answering.\n" + num_not_enough_feedback + " feedback section(s) do(es) not have enough words entered.");
        }
        return false;

    }
    function validate_form() {
        var is_staff = $('input#is_staff').val();
        if(is_staff==1){
            alert("As a member of staff you cannot submit a choice.");
            return false;
        }
        var result;
        if(ranked_choice_int == 1){
            result = validate_selection();
        }else{
            result = validate_selection_unranked();
        }

        if(result){
            //alert("Calling set selection");
            //set_selection();
        }
        return result;
    }
    function validate_selection() {
        var num;
        var my_msg = "You must make" + selectionSize + " unique choices\n";
        var hasChosen = true;
        var num_projects;
        //alert("Validating");
        num_projects = $('[name="num_projects"]').val();
        for( i = 1 ; i <= num_projects ; i++) {
            num = 0;
            $('input[id^="m_id_' + i + '_"]').each(function () {
                //val = $(this).is(':checked');
                if ($(this).is(':checked')) {
                    num++;
                }
            });
            //alert("Number of " + i + " radio buttons checked: " + num);
            if(num>1){
                hasChosen = false;
                my_msg += "  You have selected " + num + " times project number " + i +"\n";
            }
        }

        // now need to check for at least one selected in each first to last
        for( j = 1 ; j <= selectionSize ; j++) {
            num = 0;
            $('input[name^="rid_' + j + '"]').each(function () {
                //val = $(this).is(':checked');
                if ($(this).is(':checked')) {
                    num++;
                }
            });
            //alert("Number of " + j + " radio buttons checked: " + num);
            if(num<1){
                hasChosen = false;
                my_msg += "  You have not selected for choice number " + j +"\n";
            }
        }
        if(!hasChosen){
            alert(my_msg);
        }
        return hasChosen;
    }
    function validate_selection_unranked() {
        var num;
        var my_msg ="You have chosen too many. \nThe maximum number you can select is " + selectionSize + "\n";
        var hasChosen = true;
        var num_projects;
        //alert("Validating");

        var count_checked = 0;
        $("input:checkbox[type=checkbox]:checked").each(function(){
            var name = $(this).attr("name");
            if(name.slice(0,3) == 'rid') {
                count_checked++;
                //selections.push($(this).val());
            }
        });

        if(count_checked > selectionSize){
            alert(my_msg);
            return false;
        }

        counter = 0;
        $("input:checkbox[type=checkbox]:checked").each(function(i){
            var name = $(this).attr("name");
            if(name.slice(0,3) == 'rid') {
                //selections[i] = $(this).val();
                counter = i+1;
                if(counter == 1){
                    $('#urid_1').val($(this).val());
                }else if(counter == 2){
                    $('#urid_2').val($(this).val());
                }else if(counter == 3){
                    $('#urid_3').val($(this).val());
                }else if(counter == 4){
                    $('#urid_4').val($(this).val());
                }else if(counter == 5){
                    $('#urid_5').val($(this).val());
                }
            }
        });

        if(!hasChosen){
            alert(my_msg);
        }
        return hasChosen;
    }

    function set_selection() {
        // get the selection and put it in the DOM so it appears in the _POST variable
        for( j = 1 ; j <= selectionSize ; j++) {
            num = 0;
            $('input[name^="rid_' + j + '_"]').each(function () {
                //val = $(this).is(':checked');
                if ($(this).is(':checked')) {
                    // get the number and return
                    var myName = this.name;
                    var pid = myName.substring(4);
                    alert(myName + ": pip:" + pid);
                    // set the pid in
                    var choice_id = 'choice' + i;
                    value  = $('[name="' + choice_id +'"]').val();
                    //alert(value);
                    $('[name="' + choice_id +'"]').val(pid);
                    //var element = document.getElementById(checked_id);
                    //element.value = pid;
                }
            });
        }
        return -1;
    }

</script>

<script>
    $(function () {
        $("#choice").dataTable(
            {
                "bSort": false,
                "paging": false,
                "ordering": false,
                "info": false,
                "searching": false
            }
        );
    });
    $(function () {
        $("#selectionlist").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false
            }
        );
    });

    $(function () {
        $("#prog_select").change(function () {
            var text = $("#prog_select option:selected").text();
            var prog_index = $(this).val();
            //alert(text + " : " + prog_index);
            var mid_string = 'x\n';
            $("#prog_select_all").removeAttr('checked');

            $("#id_short_name").html(text + " (" + prog_index + ")");

            $('tr[id^=MID_]').each(function(){
                var prog_index_string = $(this).attr('name');
                var prog_index_value = parseInt(prog_index_string.substr(prog_index,1));
                if(prog_index_value == 1){
                    $(this).show();
                }else{
                    $(this).hide();
                }
            });
            //alert(mid_string);

        });

        $("#prog_select_all").change(function () {
            if(this.checked == true){
                $("#id_short_name").html("Project for all programmes are being shown");
                //alert("Checked");
                $('tr[id^=MID_]').each(function(){
                    $(this).show();
                });
            }else{
                //alert("Un checked");
            }
        });
        $('table').filter(function(index){
            //return this.id.substring(0,4) == 'Tab_';
        });
    });

</script>
<style type="text/css">
    .buttonstyle
    {
        background: red;
        background-position: 0px -401px;
        border: solid 1px #000000;
        color: #ffffff;
        height: 21px;
        margin-top: -1px;
        padding-bottom: 2px;
        border-radius: 8px;

    }
    .buttonstyle:hover {
        background: yellow;
        background-position: 0px -501px;
        color: #FF0000;

    }

    #sortable { list-style-type: none; margin: 0; padding: 0; width: 60%; }
    #sortable li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1.4em; height: 18px; }
    #sortable li span { position: absolute; margin-left: -1.3em; }

</style>
<body>
<div id="wrapper">

    <div id="header">
        <span
            class="header">Selection table for <?PHP echo("$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo("$display_block"); ?>

    </div><!-- /content -->

    <div id="footer">
        <span><BR><a target="_self" href="./projects_indexm_all.php<?PHP echo("?pm_id=$pm_id"); ?>"
                     style="color: dimgray;"><?PHP echo("Index"); ?></a></span>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</body>

</html>