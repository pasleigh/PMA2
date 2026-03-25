<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 30/03/2016
 * Time: 21:25
 */
function DisplayProjectSelectBlockStaff($db, $proj_id, $staff_names2, $title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flags, $option_flags_v, $option_flags_m, $program_variants, $program_variant_flags)
{
    $project_block = "";

    if (is_null($f_PGprojects)) {
        $num_projects = 1;
    } else {
        $num_projects = $f_PGprojects;
    }
    /*
    $project_block .= "<P>Project Title/Theme (<span  class='required'>required</span>)<br>\n";
    $project_block .= "<span class='footnote-size'>Provide a research theme that is wide and can cover several projects. <BR>\n";
    $project_block .= "The precise project title may change following discussions with the individual students.</span>";
    $project_block .= "<br></P>\n";
    $project_block .= "<input type=\"text\" name=\"f_title\" id=\"f_title\" value='$title' size=75 maxlength=150>\n";

    $project_block .= "<P>Principal Supervisor  (<span class='required'>required</span>)<br>\n";

    $project_block .= GetStaffSelectID($db, $staff_names2, 'f_supervisor_id', $f_supervisor_id);

    $project_block .= "<P>Suggested Second Marker (<span class='required'>required</span>)<BR>";
    $project_block .= "<span class='footnote-size'>This suggestion will help us.";
    $project_block .= "Be aware that it may change at final marking stage.</span>";
    $project_block .= "<br></P> ";

    $project_block .= GetStaffSelectID($db, $staff_names2, 'f_assocstaff_id', $f_assocstaff_id);

    $project_block .= "<P>Project Outline  (<span class='required'>required</span>)\n";

    $project_block .= "<A HREF='format.html' onClick=\"return popup(this, 'info')\">Formatting Tips</a><br>\n";
    $project_block .= "<span class='footnote-size'>\n";
    $project_block .= "Give the students a picture of the theme and highlight possibile topics of interest within it.";
    $project_block .= "The aim here is to grab their interest.</span></P>\n";
    $project_block .= "<textarea name=\"f_outline\"  id=\"f_outline\" cols=75 rows=10 wrap=virtual  style='background-color: lightgrey; color: black ;'>$f_outline</textarea>\n";

    $project_block .= "<P>Number of Projects  (<span class='required'>required</span>)<br>\n";

    $project_block .= "<input type=\"number\" min=\"0\" max=\"10\" name=\"f_PGprojects\" id=\"f_PGprojects\" value=\"$num_projects\" size=4 maxlength=4>\n";

    */
    // Need to look up the flag on the $projects_setup table
    $num_project_variants = 0;
    if(is_array($program_variants)){
        $num_project_variants = sizeof($program_variants);
    }
    if($num_project_variants == 0){
        $project_block .= "<p>There are no programme variants to specify projects for.</p>\n";
    }else {
        $project_block .= "<h3>Programme(s) that this project is offered for:</h3>\n";
        $project_block .= "<fieldset data-role=\"controlgroup\" data-type=\"horizontal\">\n";
        for ($i = 0; $i < $num_project_variants; $i++) {
            $name = "prog$i";
            if ($program_variant_flags[$i] == 1) {
                $flag = "Yes";
                $icon = "'../icons/_0007_Tick.png'";
                $is_checked = "checked";
                $is_avaiable = 1;
                $remark = "This project is available for this programme";
            } else {
                $flag = "No";
                $icon = "'../icons/_0006_Cross.png'";
                $is_checked = "";
                $is_avaiable = 0;
                $remark = "NOT offered for this programme";
            }
            $project_block .= "<input type='checkbox' name='$name' id='$name' $is_checked >\n";
            $project_block .= "<label for='$name'>$program_variants[$i]</label>\n";
        }
        $project_block .= "</fieldset>\n";
    }

    for ($i = 0; $i < sizeof($option); $i++) {
        if ($option_flags[$i] == 1) {
            $name = "f" . $option[$i];
            $text = $option_text[$i];
            $value = $option_value[$i];

            $visible_text = "";
            //$vis_flag = $option_flags_v[$i];
            //$mand_flag = $option_flags_m[$i];
            if($option_flags_v[$i] != 1){
                $visible_text = " (<span class='notice'>Not visible to students</span>)";
            }
            $required ="";
            $required_text ="";
            if($option_flags_m[$i] == 1){
                $required = "required";
                $required_text = "(<span class=''required'>required</span>)";
            }

            $project_block .= "<div class='row'>";
            $project_block .= "<div class='form-group'>";
            $project_block .= "<label for='$name'>$text $visible_text $required_text</label>\n";
            $project_block .= "<textarea id='$name' name='$name' class='form-control col-xs-12' wrap='virtual'  $required>$value</textarea>\n";
            $project_block .= "</div>";
            $project_block .= "</div><BR>";

        }
    }
    return $project_block;
}

function DisplayProjectBlockStaff($db, $proj_id, $staff_names2, $title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flags, $option_flags_v, $option_flags_m,  $program_variants, $program_variant_flags)
{
    $project_block = "";

    if (is_null($f_PGprojects)) {
        $num_projects = 1;
    } else {
        $num_projects = $f_PGprojects;
    }

    $project_block .= "<h3>Project Title/Theme </h3>\n";
    $project_block .= "<div class='simple' >Project No. $proj_id: <span class='simple-white'>$title</span></div>\n";

    $project_block .= "<h3>Principal Supervisor </h3>\n";
    $supervisor_name = GetStaffNameFromID($db, $f_supervisor_id, $staff_names2, NULL);
    $project_block .= "<div class='simple' >$supervisor_name</div>\n";
    $project_block .= "\n";

    $project_block .= "<h3>Suggested Second Marker</h3>";
    $f_assocstaff = GetStaffNameFromID($db, $f_assocstaff_id, $staff_names2, NULL);
    $project_block .= "<div class='simple' >$f_assocstaff</div>\n";
    $project_block .= "\n";

    $project_block .= "<h3>Project Outline  </h3>\n";
    $f_outline = stripcslashes($f_outline);
    $f_outline = nl2br($f_outline);
    $project_block .= "<div class='simple-white' >$f_outline</div>\n";

    $project_block .= "<h3>Number of Projects </h3>\n";
    $project_block .= "<div class='simple' >$num_projects</div>\n";

    $project_block .= "<h3>Programme(s) that this project is offered for:</h3>\n";

    $num_project_variants = 0;
    if(is_array($program_variants)){
        $num_project_variants = sizeof($program_variants);
    }
    if($num_project_variants > 0) {
        for($i = 0 ; $i < $num_project_variants ; $i++){
            //$project_block .= "$program_variant_flags[$i]<BR>";
            if($program_variant_flags[$i] == 1){
                $flag = "Yes";
                $icon = "'../icons/_0007_Tick.png'";
                $remark = "This project is avaiable for this programme";
            }else{
                $flag = "No";
                $icon = "'../icons/_0006_Cross.png'";
                $remark = "NOT offered for this programme";
            }
            //<img width=\"26\" src=$icon  border=\"0\" alt=\"$this_mark_s click to $remark\" title=\"$this_mark_s click to $remark\">
            $project_block .= "<img width='20' src=$icon border='0' alt='$remark' title='$remark'> $program_variants[$i] &nbsp;&nbsp; ";
        }
    }else{
        $project_block .= "There are no program variants";
    }
    for ($i = 0; $i < sizeof($option); $i++) {
        if ($option_flags[$i] == 1) {
            $value_text = $option_value[$i];
            $value_text = stripcslashes($value_text);
            $visible_text = "";
            //$vis_flag = $option_flags_v[$i];
            //$mand_flag = $option_flags_m[$i];
            if($option_flags_v[$i] != 1){
                $visible_text = " <span style='font-size:small; color: yellow;'>(Not visible to students)</span>";
            }

            if($value_text == "") {
                $value_text = "Nothing specified";
            }else{
            }
            $project_block .= "<h3>$option_text[$i] $visible_text</h3>\n";
            $project_block .= "<div>$value_text</div>\n";
        }
    }

    return $project_block;
}

function GetProgramVariantsStaff($db, $projects_setup_proposals_table, $pm_id, &$num_prog_variants)
{
    $prog_list = "";
    $num_prog_variants = 0;
    //------------------------------------------------------------
    // Program variant options
    // Can have up to 7 Variants
    // Get the number for this prog from the database
    $query = "SELECT * FROM $projects_setup_proposals_table WHERE proj_id='$pm_id'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo("Unable to update data to the table $projects_setup_proposals_table<BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $num = $results->rowCount();
    //$prog_list_count = 0;
    if($num > 0){
        $recs = $results->fetch();
        $prog_short_names_string = $recs['prog_short_names'];
        if(strlen($prog_short_names_string) > 1) {
            $prog_list = explode(";", $prog_short_names_string);
            $num_prog_variants = sizeof($prog_list);
        }else{
            $num_prog_variants = 0;
        }
        //$prog_list_count = sizeof($prog_list);
    }

    return $prog_list;
}

function GetProgramVariantFlagsStaff($db, $project_details_table, $master_id)
{
    for( $i = 0 ; $i < 7 ; $i++){
        $prog_variant_flags[$i] = 0;
    }
    //------------------------------------------------------------
    // Program variant flags
    // Can have up to 7 Variants
    // Get the number for this prog from the database
    $query = "SELECT * FROM $project_details_table WHERE master_id='$master_id'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo("Unable to update data to the table $project_details_table<BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $num = $results->rowCount();
    //$prog_list_count = 0;
    if($num > 0){
        $recs = $results->fetch();
        $prog_variant_flags_string = $recs['f_progs'];

        if (is_null($prog_variant_flags_string))
        {
            $prog_variant_flags_string = "0000000";
        }
        if ($prog_variant_flags_string == "")
        {
            $prog_variant_flags_string = "0000000";
        }

        for( $i = 0 ; $i < strlen($prog_variant_flags_string) ; $i++){
            $prog_variant_flags[$i] = (int)substr($prog_variant_flags_string,$i,1);
        }
    }

    return $prog_variant_flags;
}
function GetProgramVariantsSelectStaff($db, $projects_setup_proposals_table, $pm_id, $NameKey, $MatchID)
{
    $prog_variants = GetProgramVariantsStaff($db, $projects_setup_proposals_table, $pm_id, $num_prog_variants);

    $select_block = "\n<select name=\"$NameKey\" id='$NameKey'>\n";

    for($i = 0 ; $i < $num_prog_variants; $i++){
        $prog_name = $prog_variants[$i];
        if ($i == $MatchID) {
            $select_block .= "<option value=\"$i\" selected=\"selected\">$prog_name</option>\n";
        } else {
            $select_block .= "<option value=\"$i\">$prog_name</option>\n";
        }
    }
    $select_block .= "</select>\n";

    return $select_block;
}
