<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 24/04/2016
 * Time: 19:35
 */
function IsStaffActive($db, $this_staff_full_name, $staff_names_table = "staff_names")
{
    $active = false;
    // Returns is the staff is active or not from the GLOBAL stafflist
    // there is a module staff_inactive list that is not used yet
    // there is a module staff_names_module list that is not used yet
    // This function should be in some utilities file
    $query = "SELECT * FROM $staff_names_table WHERE fullname='$this_staff_full_name'";
    //echo("Query: $query<BR>");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured running query<BR>\n";
        echo(" Query = $query<BR \n");
        $err_msg = $ex->getMessage();
        echo($err_msg);
        return $active;
    }

    if ($results->rowCount() > 0) {
        $result = $results->fetch();
        //var_dump($result);
        $fullname = $result['fullname'];
        $inactive = $result['inactive'];
        //echo("Fullname: $fullname, Inactive: $inactive<br>");
        if($inactive == "0"){
            $active = true;
        }
    }
    return $active;
}
function GetChoiceTable($db,$choices,$master_name,$choice_count = 5, $ranked_choice = true){

    if($choices[1] == null){
        // Not chosen
        // Say so and do not display a table of choices
        $html = "<H1>You have not yet made your selection of projects</H1>";
    }else{
        // Has chosen
        // Display choice in table
        $html = "<H1>You have selected the projects listed below</H1>\n";

        $choice_table = "<table id='holder' width='600px'>\n<tr><td>\n";

        $choice_table .= "<table id='choice' class='hover compact cell-border' cellspacing='0' width='100%'>\n";

        $choice_heading = "Count";
        if($ranked_choice){
            $choice_heading = "Order";
        }
        $choice_table .= "
            <thead>
                <tr>
                    <th>$choice_heading</th>
                    <th>Title</th>
                    <th>Supervisor</th>
                    <th>Project number</th>
                </tr>
            </thead>";

        $choice_table .= "<tbody>\n";

        $ordinal = "";
        for($i = 1; $i<$choice_count+1 ;$i++)
        {
            $choice_table .= "<tr>\n";
            if($ranked_choice) {
                if ($i == 1) {
                    $ordinal = "First";
                }
                if ($i == 2) {
                    $ordinal = "Second";
                }
                if ($i == 3) {
                    $ordinal = "Third";
                }
                if ($i == 4) {
                    $ordinal = "Forth";
                }
                if ($i == 5) {
                    $ordinal = "Fifth";
                }
            }else{
                $ordinal = $i;
            }
            $pid = $choices[$i];
            // Look up the project title and supervisor
            $query = "SELECT * FROM $master_name WHERE id = '$pid'";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                echo "An Error occured accessing the $master_name table<BR>\n";
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                exit();
            }

            if ($results->rowCount() != 0) {
                $recs = $results->fetch();
                $project_title = $recs['f_title'];
                $supervisor_name = $recs['f_supervisor'];
            }else{
                $project_title = "no title found";
                $supervisor_name = "no supervisor name found";
            }

            $choice_table .= "<td>$ordinal</td>\n";
            $choice_table .= "<td>$project_title</td>\n";
            $choice_table .= "<td>$supervisor_name</td>\n";

            $choice_table .= "<td>$pid</td>\n";
            $choice_table .= "</tr>\n";
        }

        $choice_table .= "</tbody>\n</table>\n";

        $choice_table .= "\n</tr></td>\n</table>\n <!--/holder-->";

        $html .= $choice_table;

    }
    return $html;
}


function GetSelectionTable($db,$selection,$choices,$master_name,$project_details,$prog_to_display_index,$choice_count = 5, $ranked_choice = true){
    // Display the table of choices
    // Display the ones that this student is allowed to do by programme
    // display the current choce on this table
    $html = "";

    $select_table = "<table id='holder2' width='100%'>\n<tr><td>\n";

    $select_table .= "<table id='selectionlist' class='hover compact cell-border' cellspacing='0' width='100%'>\n";


    if($choice_count == 1){
        $choice_radio_header = "<th>1st</th><th></th><th></th><th></th><th></th>";
    }elseif($choice_count == 2){
        $choice_radio_header = "<th>1st</th><th>2nd</th><th></th><th></th><th></th>";
    }elseif($choice_count == 3){
        $choice_radio_header = "<th>1st</th><th>2nd</th><th>3rd</th><th></th><th></th>";
    }elseif($choice_count == 4){
        $choice_radio_header = "<th>1st</th><th>2nd</th><th>3rd</th><th>4th</th><th></th>";
    }else{
        $choice_radio_header = "<th>1st</th><th>2nd</th><th>3rd</th><th>4th</th><th>5th</th>";
    }

    if(!$ranked_choice){
        $choice_radio_header = "<th>Select</th><th></th><th></th><th></th><th></th>";
        $choice_radio_header = "<th>Select</th><th></th><th></th><th></th><th></th>";
    }

    $select_table .= "
    <thead>
        <tr>
            <th rowspan='2'>Project number</th>
            <th rowspan='2'>Title</th>
            <th rowspan='2'>Supervisor</th>
            <th rowspan='2'>Places (chosen)</th>
            <th colspan='5'>Your choices</th>
        </tr>
        <tr>
            $choice_radio_header
        </tr>
    </thead>";

    $select_table .= "<tbody>\n";

    $query = "SELECT * FROM $master_name";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $master_name table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }

    $num_projects = $results->rowCount() ;
    if($num_projects > 0) {
        $rows = $results->fetchAll();
        $displayed_count = 0;
        foreach($rows as $recs) {
            $displayed_count++;
            $master_id = $recs['id'];
            $title = $recs['f_title'];
            $supervisor_name = $recs['f_supervisor'];

            // get the array of which programmes this project is to be offerd for
            $project_variant_flags = GetProgramVariantFlags($db, $project_details, $master_id);
            $project_variant_flags_string = "";
            for( $i = 0 ; $i < sizeof($project_variant_flags) ; $i++){
                $project_variant_flags_string .= $project_variant_flags[$i];
            }

            $show_hide  = "hide" ;// "none"
            $show_hide_style = "display: none;";
            if($prog_to_display_index < 0){
                $show_hide_style = "display: table-row;";
            }else{
                if($project_variant_flags[$prog_to_display_index] == 1){
                    $show_hide = "show";//"block";
                    $show_hide_style = "display: table-row;";
                }
            }

            $num_places_offered = GetNumProjectsOffered($db, $project_details, $master_id);
            $num_places_selected = GetProjectSelectionCount($db, $selection, $master_id);
            $num_places_selected = sprintf('%3.1f',$num_places_selected);
            $cell_colour = GetProjectPopularityColour($num_places_offered, $num_places_selected);

            $staff_active = IsStaffActive($db, $supervisor_name);

            if($num_places_offered > 0 && $staff_active) {

                $select_table .= "<tr id='MID_$master_id' name='$project_variant_flags_string' style='$show_hide_style'>\n";
                $select_table .= "<td style='text-align: center;'>$master_id ($project_variant_flags_string)</td>\n";
                //$select_table .= "<td style='text-align: center;'>$master_id ($project_variant_flags_string) [$show_hide]</td>\n";
                $select_table .= "<td>$title</td>\n";
                $select_table .= "<td>$supervisor_name</td>\n";
                $chosen_str = "";
                if($num_places_selected > 0.0){
                    $chosen_str = "($num_places_selected)";
                }
                $select_table .= "<td style='background-color: $cell_colour; text-align: center;'>$num_places_offered $chosen_str</td>\n";
                $checked = "";
                if ($choices[1] == $master_id) {
                    $checked = "checked";
                }
                if(!$ranked_choice){
                    $checked = "";
                    for($i=1; $i<$choice_count+1; $i++){
                        if ($choices[$i] == $master_id) {
                            $checked = "checked";
                            break;
                        }
                    }
                }
                $sel_id = 'm_id_' . $displayed_count . '_1';
                if($choice_count >= 1 && $ranked_choice){$type = "radio";}else{$type = "checkbox";}
                if($ranked_choice) {
                    $select_table .= "<td><input type='$type' name='rid_1' id='$sel_id' value='$master_id' $checked></td>\n";
                }else{// Not ranked so need the name to be different for each check box
                    $r_id = 'rid_1_' . $displayed_count;
                    $select_table .= "<td><input type='$type' name='$r_id' id='$sel_id' value='$master_id' $checked></td>\n";
                }
                $checked = "";
                if ($choices[2] == $master_id) {
                    $checked = "checked";
                }
                $sel_id = 'm_id_' . $displayed_count . '_2';
                if($choice_count >= 2 && $ranked_choice){$type = "radio";}else{$type = "hidden";}
                $select_table .= "<td><input type='$type' name='rid_2' id='$sel_id' value='$master_id' $checked></td>\n";
                $checked = "";
                if ($choices[3] == $master_id) {
                    $checked = "checked";
                }
                $sel_id = 'm_id_' . $displayed_count . '_3';
                if($choice_count >= 3 && $ranked_choice){$type = "radio";}else{$type = "hidden";}
                $select_table .= "<td><input type='$type' name='rid_3' id='$sel_id' value='$master_id' $checked></td>\n";
                $checked = "";
                if ($choices[4] == $master_id) {
                    $checked = "checked";
                }
                $sel_id = 'm_id_' . $displayed_count . '_4';
                if($choice_count >= 4 && $ranked_choice){$type = "radio";}else{$type = "hidden";}
                $select_table .= "<td><input type='$type' name='rid_4' id='$sel_id' value='$master_id' $checked></td>\n";
                $checked = "";
                if ($choices[5] == $master_id) {
                    $checked = "checked";
                }
                $sel_id = 'm_id_' . $displayed_count . '_5';
                if($choice_count >= 5 && $ranked_choice){$type = "radio";}else{$type = "hidden";}
                $select_table .= "<td><input type='$type' name='rid_5' id='$sel_id' value='$master_id' $checked></td>\n";
                $select_table .= "</tr>\n";
            }
        }
    }
    $select_table .= "</tbody>\n</table>\n";

    $select_table .= "\n</tr></td>\n</table> <!--/holder-->\n";

    $html .= $select_table;
    $html .= "<input type='hidden' name='num_projects' value='$num_projects'>\n";

    return $html;
}
function GetSelectionTableUnRanked($db,$selection,$choices,$master_name,$project_details,$prog_to_display_index, $choice_count = 5){
    // Display the table of choices
    // Display the ones that this student is allowed to do by programme
    // display the current choce on this table
    $html = "";

    $select_table = "<table id='holder2' width='100%'>\n<tr><td>\n";

    $select_table .= "<table id='selectionlist' class='hover compact cell-border' cellspacing='0' width='100%'>\n";

    $select_table .= "
    <thead>
        <tr>
            <th>Project number</th>
            <th>Title</th>
            <th>Supervisor</th>
            <th>Places (chosen)</th>
            <th>Your choices</th>
        </tr>
    </thead>";

    $select_table .= "<tbody>\n";

    $query = "SELECT * FROM $master_name";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $master_name table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }

    $num_projects = $results->rowCount() ;
    if($num_projects > 0) {
        $rows = $results->fetchAll();
        $displayed_count = 0;
        foreach($rows as $recs) {
            $displayed_count++;
            $master_id = $recs['id'];
            $title = $recs['f_title'];
            $supervisor_name = $recs['f_supervisor'];

            // get the array of which programmes this project is to be offerd for
            $project_variant_flags = GetProgramVariantFlags($db, $project_details, $master_id);
            $project_variant_flags_string = "";
            for( $i = 0 ; $i < sizeof($project_variant_flags) ; $i++){
                $project_variant_flags_string .= $project_variant_flags[$i];
            }

            $show_hide  = "hide" ;// "none"
            $show_hide_style = "display: none;";
            if($prog_to_display_index < 0){
                $show_hide_style = "display: table-row;";
            }else{
                if($project_variant_flags[$prog_to_display_index] == 1){
                    $show_hide = "show";//"block";
                    $show_hide_style = "display: table-row;";
                }
            }

            $num_places_offered = GetNumProjectsOffered($db, $project_details, $master_id);
            $num_places_selected = GetProjectSelectionCount($db, $selection, $master_id);
            $num_places_selected = sprintf('%3.1f',$num_places_selected);
            $cell_colour = GetProjectPopularityColour($num_places_offered, $num_places_selected);

            $staff_active = IsStaffActive($db, $supervisor_name);

            if($num_places_offered > 0 && $staff_active) {

                $select_table .= "<tr id='MID_$master_id' name='$project_variant_flags_string' style='$show_hide_style'>\n";
                $select_table .= "<td style='text-align: center;'>$master_id</td>\n";
                //$select_table .= "<td style='text-align: center;'>$master_id ($project_variant_flags_string) [$show_hide]</td>\n";
                $select_table .= "<td>$title</td>\n";
                $select_table .= "<td>$supervisor_name</td>\n";

                $chosen_str = "";
                if($num_places_selected > 0.0){
                    $chosen_str = "($num_places_selected)";
                }
                $select_table .= "<td style='background-color: $cell_colour; text-align: center;'>$num_places_offered $chosen_str</td>\n";

                $checked = "";
                for($i=1; $i<$choice_count+1; $i++){
                    if ($choices[$i] == $master_id) {
                        $checked = "checked";
                        break;
                    }
                }

                $sel_id = 'm_id_' . $displayed_count . '_1';
                $type = "checkbox";
                // Not ranked so need the name to be different for each check box
                $r_id = 'rid_1_' . $displayed_count;
                $select_table .= "<td style='text-align: center;'><input type='$type' name='$r_id' id='$sel_id' value='$master_id' $checked></td>\n";
            }
        }
    }
    $select_table .= "</tbody>\n</table>\n";

    $select_table .= "\n</tr></td>\n</table> <!--/holder-->\n";

    $html .= $select_table;
    $html .= "<input type='hidden' name='num_projects' value='$num_projects'>\n";

    return $html;
}

function GetNumProjectsOffered($db, $project_details_table, $pid){
    $num_projects = 0;

    $query = "SELECT * FROM $project_details_table WHERE master_id='$pid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $project_details_table table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }

    if ($results->rowCount() == 1) {
        $rec = $results->fetch();
        $num_projects = $rec['f_PGprojects'];
    }
    return $num_projects;

}

function GetProjectSelectionCount($db, $selection_table, $pid){
    $num_selected = 0;

    $query = "SELECT * FROM $selection_table WHERE choice1='$pid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $selection_table table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $num_choice1 = $results->rowCount();

    $query = "SELECT * FROM $selection_table WHERE choice2='$pid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $selection_table table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $num_choice2 = $results->rowCount();

    $query = "SELECT * FROM $selection_table WHERE choice3='$pid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $selection_table table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $num_choice3 = $results->rowCount();

    $query = "SELECT * FROM $selection_table WHERE choice4='$pid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $selection_table table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $num_choice4 = $results->rowCount();

    $query = "SELECT * FROM $selection_table WHERE choice5='$pid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $selection_table table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $num_choice5 = $results->rowCount();

    $num_selected = 1.0*$num_choice1;
    $num_selected += 0.8*$num_choice2;
    $num_selected += 0.6*$num_choice3;
    $num_selected += 0.4*$num_choice4;
    $num_selected += 0.2*$num_choice5;

    return $num_selected;

}

function GetProjectPopularityColour($num_projects_offered, $num_projects_chosen){
    $colour = "";

    if($num_projects_offered == 0){
        return $colour;
    }elseif($num_projects_chosen == 0){
        return $colour;
    }else{
        $score = $num_projects_chosen/$num_projects_offered;
        if($score > 1.0){
            $score = 1.0;
        }

        $value = $score * 255;
        $mid = 255/2;
        if($value > $mid){
            $R = 255;
            $G = 255-(($value-$mid)/$mid)*255;
        }else{
            $G = 255;
            $R = ($value/$mid)*255;
            //$value = $value - 255;

        }
        $B = 0;
        /*
        if (value < max/2)
	{
		red = red_max;
		green = Math.round((value/(max/2))*green_max);
	}
	else
	{
		green = green_max;
		red = Math.round((1-((value-(max/2))/(max/2)))*red_max);
	}
        */
        if($R > 255){$R=255;}
        if($R <   0){$R=0;  }
        if($G > 255){$G=255;}
        if($G <   0){$G=0;  }
        $colour = '#' . sprintf('%02x', $R) . sprintf('%02x', $G) . sprintf('%02x', $B);
    }

    return $colour;


}