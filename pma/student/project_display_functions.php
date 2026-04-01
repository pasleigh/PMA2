<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 30/03/2016
 * Time: 21:25
 */
function DisplayProjectSelectBlock($db, $proj_id, $staff_names2, $title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flags, $option_flags_v, $option_flags_m, $program_variants, $program_variant_flags)
{
    $project_block = "";

    if (is_null($f_PGprojects)) {
        $num_projects = 1;
    } else {
        $num_projects = $f_PGprojects;
    }
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
    $project_block .= "<textarea name=\"f_outline\"  id=\"f_outline\" cols=75 rows=10 wrap=virtual  style='background-color: darkgrey ;'>$f_outline</textarea>\n";
    $project_block .= "<P>Number of Projects  (<span class='required'>required</span>)<br>\n";

    $project_block .= "<input type=\"number\" min=\"0\" max=\"10\" name=\"f_PGprojects\" id=\"f_PGprojects\" value=\"$num_projects\" size=4 maxlength=4>\n";

    // Need to look up the flag on the $projects_setup table
    $num_project_variants = 0;
    if (is_array($program_variants)) {
        $num_project_variants = sizeof($program_variants);
    }
    if ($num_project_variants == 0) {
        $project_block .= "<p>There are no programme variants to specify projects for.</p>\n";
    } else {
        $project_block .= "<h3>Programme(s) that this project is offered for:</h3>\n";
        $project_block .= "<fieldset data-role=\"controlgroup\" data-type=\"horizontal\">\n";
        for ($i = 0; $i < $num_project_variants; $i++) {
            $name = "prog$i";
            if ($program_variant_flags[$i] == 1) {
                $flag = "Yes";
                $icon = "'../staff/icons/_0007_Tick.png'";
                $is_checked = "checked";
                $is_avaiable = 1;
                $remark = "This project is avaiable for this programme";
            } else {
                $flag = "No";
                $icon = "'../staff/icons/_0006_Cross.png'";
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
        if (($option_flags[$i] == 1) && ($option_flags_v[$i] == 1)) {
            $name = "f" . $option[$i];
            $text = $option_text[$i];
            $value = $option_value[$i];
            $project_block .= "<P>$text </P>\n";
            $project_block .= "<textarea name='$name' cols='75' rows='4' wrap='virtual' style='height: inherit; background-color:  darkgrey ;'>$value</textarea><BR>\n";
        }
    }
    return $project_block;
}

function DisplayProjectBlock($db, $proj_id, $staff_names2, $title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flags, $option_flags_v,$option_flags_m, $program_variants, $program_variant_flags)
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
    $f_outline = stripslashes($f_outline);
    // Some tidying up of things that should not have ben put there from the project entry process
    //$f_outline = str_replace("&gt;", ">", $f_outline);
    //$f_outline = str_replace("&lt;", "<", $f_outline);
    //$f_outline = str_replace("&quot;", '"', $f_outline);
    $f_outline = nl2br($f_outline);
    $project_block .= "<div class='simple-white' >$f_outline</div>\n";

    $project_block .= "<h3>Number of Projects </h3>\n";
    $project_block .= "<div class='simple' >$num_projects</div>\n";

    $project_block .= "<h3>Programme(s) that this project is offered for:</h3>\n";

    $num_prog_variants = 0;
    if(is_array($program_variants)){
        $num_project_variants = sizeof($program_variants);
    }
    if($num_prog_variants) {
        for ($i = 0; $i < $num_prog_variants; $i++) {
            //$project_block .= "$program_variant_flags[$i]<BR>";
            if ($program_variant_flags[$i] == 1) {
                $flag = "Yes";
                $icon = "'../staff/icons/_0007_Tick.png'";
                $remark = "This project is avaiable for this programme";
            } else {
                $flag = "No";
                $icon = "'../staff/icons/_0006_Cross.png'";
                $remark = "NOT offered for this programme";
            }
            //<img width=\"26\" src=$icon  border=\"0\" alt=\"$this_mark_s click to $remark\" title=\"$this_mark_s click to $remark\">
            $project_block .= "<img width='20' src=$icon border='0' alt='$remark' title='$remark'> $program_variants[$i] &nbsp;&nbsp; ";
        }
    }else{
        $project_block .= "Not offered on other programmmes.";
    }

    for ($i = 0; $i < sizeof($option); $i++) {
        if (($option_flags[$i] == 1) && ($option_flags_v[$i] == 1)) {
            $value_text = $option_value[$i];
            if($value_text == "") {
                $value_text = "Nothing specified";
            }else{
                $project_block .= "<h3>$option_text[$i]</h3>\n";
                $project_block .= "<div>$value_text</div>\n";
            }
        }
    }

    return $project_block;
}

function GetProgramVariants($db, $projects_setup_proposals_table, $pm_id, &$num_prog_variants)
{
    $prog_list = "";
    $num_prog_variants = 0;
    //------------------------------------------------------------
    // Program variant options
    // Can have up to 7 Variants
    // Get the number for this prog from the database
    $query = "SELECT * FROM $projects_setup_proposals_table WHERE proj_id=:pm_id";
    try {
        $results = $db->prepare($query);
        $results->execute([':pm_id' => $pm_id]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
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

function GetProgramVariantFlags($db, $project_details_table, $master_id)
{
    for( $i = 0 ; $i < 7 ; $i++){
        $prog_variant_flags[$i] = 0;
    }
    //------------------------------------------------------------
    // Program variant flags
    // Can have up to 7 Variants
    // Get the number for this prog from the database
    $query = "SELECT * FROM $project_details_table WHERE master_id=:master_id";
    try {
        $results = $db->prepare($query);
        $results->execute([':master_id' => $master_id]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
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
function GetProgramVariantsSelect($db, $projects_setup_proposals_table, $pm_id, $NameKey, $MatchID)
{
    $prog_variants = GetProgramVariants($db, $projects_setup_proposals_table, $pm_id, $num_prog_variants);

    $select_block = "\n<select name=\"$NameKey\">\n";

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

function GetProgrammeShortName($db, $student_programme_full_name, $students_programmes_table, $projects_setup_proposals_table, $pm_id)
{
    //
    $prog_short_name = "Not found";

    $query = "SELECT * FROM $students_programmes_table WHERE prog_full_name=:prog_full_name";
    try {
        $results = $db->prepare($query);
        $results->execute([':prog_full_name' => $student_programme_full_name]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }

    $num = $results->rowCount();
    //$prog_list_count = 0;

    if($num > 0){
        $recs = $results->fetch();
        $index = $recs['prog_short_name_index'];

        $prog_variants = GetProgramVariants($db, $projects_setup_proposals_table, $pm_id, $num_prog_variants);

        $prog_short_name = $prog_variants[$index];
    }

    return $prog_short_name;
}
function GetProgrammeShortNameIndex($db, $student_programme_full_name, $students_programmes_table){
    $index = -1;

    $query = "SELECT * FROM $students_programmes_table WHERE prog_full_name=:prog_full_name";
    try {
        $results = $db->prepare($query);
        $results->execute([':prog_full_name' => $student_programme_full_name]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }

    $num = $results->rowCount();
    //$prog_list_count = 0;
    if($num > 0){
        $recs = $results->fetch();
        $index = (int)($recs['prog_short_name_index']);
    }

    return $index;
}