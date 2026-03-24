<?php
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) OR die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./connect_pdo.php")) OR die("Cannot connect to the database<BR>");

/* The project_setup query is now done in setup_tables.php
$get_data_query = "select * from $projects_setup where id=$pm_id";
*/
(@include_once("./setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./staff_functions.php")) OR die("Cannot include staff_function.php<BR>");
(@include_once("./markstore_functions.php")) OR die("Cannot include markstore_function.php<BR>");
(@include_once("./admin2/adminusers.php")) OR die("Cannot setup admin users<BR>");

if (array_key_exists('modify', $_REQUEST) == false) {
    $_REQUEST['modify'] = null;
}
$modify = $_REQUEST['modify'];

$num_limit = -1;
$top_bar_prefix = "";
if (array_key_exists('user', $_REQUEST) == false) {
    $_REQUEST['user'] = null;
}
if ($_REQUEST['user'] == 1) {
    $num_limit = 0;
    $top_bar_prefix = "$this_staff_full_name:";
}

$display_block = "";
$display_block = "";
$display_block .= $connect_msg;
//$display_block .= "Marking table for $module_short_description  $module_prefix$module1  $year_begin/$year_end\n";

$overview_table = "<table id=\"holder\" width=\"600px\">\n<tr><td>\n";

$overview_table .= "<table id=\"geninfo\" class=\"hover compact\" cellspacing=\"0\" width=\"100%\">\n";
$overview_table .= "
    <thead>
      <tr>
        <th>Description</th>
        <th>Value</th>
      </tr>
    </thead>";

$overview_table .= "
    <tbody>
      <tr>
        <td>Module</td> <td>$module_prefix$module1$module_postfix</td>
      </tr>
      <tr>
        <td>Description</td> <td>$module_short_description  ($short_code)</td>
      </tr>
      <tr>
        <td>Number of assessments</td> <td>$num_parts</td>
      </tr>
    ";
for ($i = 1; $i <= $num_parts; $i++) {
    //$ii=$i+1;
    $overview_table .= "<tr>\n<td><B>Part $i</B></td> <td>$p_description[$i]</td>\n</tr>\n";
    $overview_table .= "<tr>\n<td>Submission date</td> <td>$p_submission_date[$i]</td>\n</tr>\n";
    $overview_table .= "<tr>\n<td>Mark by date</td> <td>$p_marking_date[$i]</td>\n</tr>\n";
    $overview_table .= "<tr>\n<td>Percent of final mark</td> <td>$p_percent[$i]</td>\n</tr>\n";
    $overview_table .= "<tr>\n<td>Mark out of (max mark): </td> <td>$p_max[$i]</td>\n</tr>\n";
    $overview_table .= "<tr>\n<td>Numbers of markers </td> <td>$p_num_markers[$i]</td>\n</tr>\n";
}
$overview_table .= "</tbody>\n</table>\n";

$overview_table .= "\n</tr></td>\n</table>\n <!--/holder-->";

//$display_block .= $overview_table;

// Draw the main table

// Loop through the parts to get the number of columns required for each
for ($i = 1; $i <= $num_parts; $i++) {
    //echo("Num markers ($i) = $p_num_markers[$i]<BR>");
    if ($p_num_markers[$i] == 1) {
        $number_of_cols[$i] = 2;
    } else if ($p_num_markers[$i] == 2) {
        if (CountThirdMarkersRequired($db, $i, $markstore_totals) > 0) {
            $number_of_cols[$i] = 4;
        } else {
            $number_of_cols[$i] = 3;
        }
    } else {
        $number_of_cols[$i] = 4;
    }
}
// Headers
$display_block .= "<table id=\"studentlist\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
$display_block .= "
    <thead>
      <tr>
        <th rowspan=\"2\">Student name</th>
        <th rowspan=\"2\">SID</th>
        <th rowspan=\"2\">Username</th>
        <th rowspan=\"2\">Project ID</th>
        <th rowspan=\"2\">Project Title</th>
        <th rowspan=\"2\">Supervisor</th>
        <th rowspan=\"2\">Second marker</th>
        ";


// Loop through the parts
for ($i = 1; $i <= $num_parts; $i++) {

    $query = "select * from $project_allocated order by projectid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $num_students = $results->rowCount();
    $num_marked = 0;
    $part = $i;
    $rows = $results->fetchAll();
    foreach ($rows as $recs) {
        $sid = $recs['sid'];
        $inactive = $recs['inactive'];
        if ($inactive == 1) {
            $num_students--;
        } else {
            if (HaveAllMarkersMarked($db, $sid, $part, $p_num_markers[$part], $markstores, $markstore_totals)) {
                $num_marked++;
            }
        }
    }


    //echo("Num markers ($i) = $p_num_markers[$i]<BR>");
    $num_cols = $number_of_cols[$i];
    /*
    for($j=1; $j<=$num_cols ; $j++ ){
      //echo("Num col ($j)<BR>");
      if($j < $num_cols){
        $display_block .= "<th>Part $i:<BR>$p_description[$i]</th>";
      }else{
        $display_block .= "<th>Part $i:<BR>$p_description[$i]</th>";
      }
    }
    */
    if ($i % 2 == 1) {
        $cell_style = "style=\"background-color: linen;\"";
    } else {
        $cell_style = "";
    }
    //$cell_style = "";
    $display_block .= "<th colspan=\"$num_cols\" $cell_style>Part $i: $p_description[$i] ($p_percent[$i]%)<BR>($num_marked/$num_students)</th>";

}

$display_block .= "<th rowspan=\"2\">Final Total</th>";
$display_block .= "\n</tr>\n";

// Loop through the parts - second row
$display_block .= "\n<tr>\n";
for ($i = 1; $i <= $num_parts; $i++) {
    //echo("Num markers ($i) = $p_num_markers[$i]<BR>");
    $num_cols = $number_of_cols[$i];

    for ($j = 1; $j <= $num_cols; $j++) {
        //echo("Num col ($j)<BR>");
        if ($num_cols == 2) {
            if ($j == 1) {
                $display_block .= "<th>Marker $j</th>";
            } else {
                $display_block .= "<th>Total</th>";
            }
        } else {
            if ($j < $num_cols) {
                $display_block .= "<th>Marker $j</th>";
            } else {
                $display_block .= "<th>Agreed Total</th>";
            }
        }
    }
}
$display_block .= "\n</tr>\n";

$display_block .= "\n</thead>\n";
$display_block .= "<tbody>\n";

$display_block .= "<HR>";

// list all the allocated students
$query = "select * from $project_allocated order by projectid";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo(" Query = $query<BR> \n");
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
    exit();
}
$allocated_students_rows = $results->fetchAll();

// list all the students
$query = "select * from $students order by name";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo(" Query = $query<BR> \n");
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
    exit();
}
$all_students_rows = $results->fetchAll();

foreach ($all_students_rows as $student_recs) {

    $sid = $student_recs['sid'];
    $student_name = $student_recs['name'];
    $username = $student_recs['username'];
    $programme = $student_recs['programme'];
    $sel = $student_recs['sel'];

    $is_allocated = IsSIDAllocated($db,$sid,$project_allocated);
    $is_active = IsSIDActive($db,$sid,$project_allocated);

    //echo("SID: $sid, is_active=$is_active, is_allocated=$is_allocated<BR>\n");
//    $project_id = $recs['projectid'];
//    $project_master_id = $recs['masterid'];
//    $sid = $recs['sid'];
//    $inactive = $recs['inactive'];
//    $supervisor_id = $recs['supervisor_id'];
//    $second_marker_id = $recs['second_marker_id'];
//    $project_title = $recs['title'];

    if ($is_active) {
        // get the markers data for this student
        //$query = "SELECT * FROM $project_allocated WHERE sid=$sid";
        $query = "SELECT * FROM $project_allocated WHERE sid=?";
        try {
            //$results = $db->query($query);
            $results = $db->prepare($query);
            $exe = array($sid);
            $results->execute($exe);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }
        $recs = $results->fetch();

        $project_id = $recs['projectid'];
        $project_master_id = $recs['masterid'];
        //$sid = $recs['sid'];
        //$inactive = $recs['inactive'];
        $supervisor_id = $recs['supervisor_id'];
        $second_marker_id = $recs['second_marker_id'];
        $project_title = $recs['title'];

        $count_this_staff_marking = 0;
        $markers = array();
        $markers_id = array();
        for ($i = 1; $i <= $num_parts; $i++) {
            $markers[$i] = array();
            $markers_id[$i] = array();
            for ($j = 1; $j <= 3; $j++) {
                $marker_entry = "marker_p$i" . "m$j" . "_id";
                $marker_id = $recs[$marker_entry];
                $markers_id[$i][$j] = $marker_id;
                $markers[$i][$j] = GetStaffNameFromID($db, $marker_id, $staff2, NULL);
                $name = $markers_id[$i][$j];
                //echo("markers_id = ($i,$j) $name $marker_id<BR>");
                // Does this marker_id match the current logged in staff
                //print_r2($number_of_cols);
                if (($this_staff_id == $marker_id) && ($j < $number_of_cols[$i])) {
                    $count_this_staff_marking++;
                }
            }
        }

        //echo("Num Projects  to mark for $this_staff_full_name = $count_this_staff_marking<BR>");
        if ($count_this_staff_marking > $num_limit) {
            // Modify option
            $modify_link = "";
            if ($modify == 1) {
                $modify_link = "<a href=modify_student_project_details.php?pm_id=$pm_id&sid=$sid><img width=\"26\" src=\"./icons/0032_screw_driver.png\"  border=\"0\" alt=\"Modify this student's project details\" title=\"Modify this student's project details\"></a>";
            }


            $display_block .= "<tr>\n";
            $display_block .= "<td>$modify_link$student_name </td>\n";
            $display_block .= "<td>$sid</td>\n";
            $display_block .= "<td>$username</td>\n";
            $display_block .= "<td>$project_id</td>\n";
            $display_block .= "<td>$project_title</td>\n";
            $supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff2, NULL);
            $display_block .= "<td>$supervisor_name</td>\n";
            $second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff2, NULL);
            $display_block .= "<td>$second_marker_name</td>\n";

            // Loop through the parts
            for ($i = 1; $i <= $num_parts; $i++) {
                $num_cols = $number_of_cols[$i];
                $is_numeric = $p_numeric[$i];
                $show_overall_feedback_flag = $p_show_feedback[$i];
                for ($j = 1; $j < $num_cols; $j++) {
                    $mymarker = $markers[$i][$j];
                    //if($i == 4){echo("($i $j) $sid $project_id $mymarker<BR>");}
                    $m_id = $markers_id[$i][$j];
                    $is_marked = IsMarked($db, $sid, $markstores[$i][$j]);
                    if ($is_marked) {
                        $icon = "\"./icons/_0007_Tick.png\"";
                        $remark = "review";
                    } else {//if (IsThirdMarkerRequired($sid, $i, $markstore_totals)){
                        $icon = "\"./icons/_0008_Minus.png\"";
                        $remark = "assess";
                    }
                    $this_mark = GetThisMark($db, $sid, $i, $markstores[$i][$j]);
                    $this_mark_s = "";
                    if ($this_mark > 0) {
                        //$this_mark_s = sprintf("(%5.1f%%)", (float)$this_mark * 100.0 / (float)$p_max[$i]);
                    }
                    if (IsAdmin($db, $this_staff_username) || (IsModuleAdmin($db,$this_staff_username,$pm_id,null,null)) || ($m_id == $this_staff_id)) {
                        $link = "<a href=assess_minor_part_questions_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=$j&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag><img width=\"26\" src=$icon  border=\"0\" alt=\"$this_mark_s click to $remark\" title=\"$this_mark_s click to $remark\"></a><BR>";
                    } else {
                        $link = "";
                        $link = "<img id=\"grey_image\" width=\"26\" src=$icon  border=\"0\" alt=\"$this_mark_s click to $remark\" title=\"$this_mark_s click to $remark\"><BR>";
                    }
                    $name = $markers[$i][$j];
                    if ($markers[$i][$j] == "") {
                        $link = "";
                        $modify_link = "<a href=modify_student_project_details.php?pm_id=$pm_id&sid=$sid>";
                        $name = $modify_link . "Name needs setting";
                        $diff = GetDifference($db, $sid, $i, $markstores);
                        $diff_s = sprintf("%5.1f%%", (float)$diff);
                        $agreed_total = "diff=" . $diff_s;
                        $name .= "</A><BR>" . $agreed_total;
                    }
                    // If the third marker is required for some then DON'T show  one when not needed
                    if (($j == 3) && (!IsThirdMarkerRequired($db, $sid, $i, $markstore_totals))) {
                        $link = "";
                        $name = "";
                    }
                    $display_block .= "<td style=\"font-size: smaller;\">$link$name</td>";
                    if ($j == $num_cols - 1) {
                        // Add the agreed total mark on if agreed
                        if (HaveAllMarkersMarked($db, $sid, $i, $p_num_markers[$i], $markstores, $markstore_totals)) {
                            if ($p_num_markers[$i] == 1) {
                                // Just write the mark
                                $total_mark = GetTotalForPart($db, $sid, $i, $markstore_totals);
                                //echo("Total for part $i = $total_mark<BR>");
                                if ($total_mark > 0) {
                                    $max = $p_max[$i];
                                    $total_mark_s = sprintf("%5.1f%%", (float)$total_mark * 100.0 / (float)$max);
                                    $display_block .= "<td>$total_mark_s</td>";
                                } else {
                                    $display_block .= "<td>$total_mark (^._.^)</td>";
                                }
                            } elseif ($p_num_markers[$i] == 3) {
                                // Check that the mark is recorded in the totals table
                                // If not then put it there
                                $total_mark = GetTotalForPart($db, $sid, $i, $markstore_totals);
                                if ($total_mark < 0) {
                                    $total_mark = GetTotalFromAllMarkers($db, $sid, $i, $p_num_markers[$i], $markstores);
                                    $total_mark /= $p_num_markers[$i];
                                }
                                $max = $p_max[$i];
                                $total_mark_s = sprintf("%5.1f%%", (float)$total_mark * 100.0 / (float)$max);
                                $display_block .= "<td>$total_mark_s</td>";

                            } else {
                                // Two markers so needed to check whether a third marker has been requested
                                $total_mark = GetTotalForPart($db, $sid, $i, $markstore_totals);
                                //echo("$sid : Total: $total_mark<BR>");
                                if ($total_mark > 0) {
                                    // Mark had been agreed. Show the appropriate icon (green tick) and prompt
                                    $max = $p_max[$i];
                                    $total_mark_s = sprintf("%5.1f%%", (float)$total_mark * 100.0 / (float)$max);
                                    //$print_r($total_mark);
                                    $this_mark_s = $total_mark_s;
                                    $icon = "\"./icons/_0007_Tick.png\"";
                                    $remark = "review";
                                    $agreed_total = $this_mark_s;
                                    if (IsThirdMarkerRequired($db, $sid, $i, $markstore_totals)) {
                                        $link = "<a href=assess_minor_part_questions_3_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=4&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag><img width=\"26\" src=$icon  border=\"0\" alt=\"$this_mark_s click to $remark\" title=\"$this_mark_s click to $remark\"></a><BR>";
                                    } else {
                                        $link = "<a href=assess_minor_part_questions_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=$j&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag><img width=\"26\" src=$icon  border=\"0\" alt=\"$this_mark_s click to $remark\" title=\"$this_mark_s click to $remark\"></a><BR>";
                                    }
                                    $display_block .= "<td style=\"font-size: smaller;\"> $link$agreed_total</td>";
                                } else {
                                    if (IsThirdMarkerRequired($db, $sid, $i, $markstore_totals)) {
                                    }
                                    $icon = "\"./icons/_0008_Minus.png\"";
                                    $this_mark_s = "Not yet agreed,";
                                    $remark = "compare";
                                    $diff = GetDifference($db, $sid, $i, $markstores);
                                    $diff_s = sprintf("%5.1f%%", (float)$diff);
                                    $agreed_total = "diff=" . $diff_s;
                                    if (IsThirdMarkerRequired($db, $sid, $i, $markstore_totals)) {
                                        $link = "<a href=assess_minor_part_questions_3_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=4&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag><img width=\"26\" src=$icon  border=\"0\" alt=\"$this_mark_s click to $remark\" title=\"$this_mark_s click to $remark\"></a><BR>";
                                    } else {
                                        $link = "<a href=assess_minor_part_questions_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=$j&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag><img width=\"26\" src=$icon  border=\"0\" alt=\"$this_mark_s click to $remark\" title=\"$this_mark_s click to $remark\"></a><BR>";
                                    }
                                    $display_block .= "<td style=\"font-size: smaller;\">$link$agreed_total</td>";
                                }
                            }
                        } else {
                            // Agreed total column
                            // No total agreed yet
                            $agreed_total = "";
                            $display_block .= "<td style=\"font-size: smaller;\"></td>";
                        }
                    }

                }

            }
            $overall_total = SetRecalculatedOverallTotal($db, $sid, $num_parts, $p_percent, $markstore_totals);
            if ($overall_total < 0.0) {
                $overall_total_s = "<td>--</td>";
            } else {
                $overall_total_s = sprintf("%6.1f%%", (float)$overall_total);
                // Put in a link
                $icon = "'./icons/pdf-icon_gg.png'";
                $link = "<a href=show_overall_mark_feedback_pdf.php?sid=$sid&pid=$project_id&pm_id=$pm_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag><img width='24' src=$icon  border='0' alt='View feedback to student' title='View feedback to student'></a><BR>";
                $overall_total_s = "<td style='font-size: smaller;'> $link $overall_total_s </td>"; //"<A href='./show_overall_mark_feedback_pdf.php' target='_blank'>$overall_total_s</A>";
            }
            $display_block .= "$overall_total_s";

            $display_block .= "</tr>\n";
        }
    }else{// End of if $is_active = true
        if($is_allocated){
            // Allocated but not active
            // Probably withdrawn
            $status_text = "Not active";
        }else{
            // Not yet active
            $status_text = "Not allocated";
        }
        $empty_cell = "-";
        $modify_link = "";
        if ($modify == 1) {
            $modify_link = "<a href=modify_student_project_details.php?pm_id=$pm_id&sid=$sid><img width=\"26\" src=\"./icons/0032_screw_driver.png\"  border=\"0\" alt=\"Modify this student's project details\" title=\"Modify this student's project details\"></a>";
        }
        $display_block .= "<tr>\n";
        $display_block .= "<td>$modify_link$student_name </td>\n"; // name
        $display_block .= "<td>$sid</td>\n";         // sid
        $display_block .= "<td>$username</td>\n";    // usermame
        $display_block .= "<td>$empty_cell</td>\n";  // PID
        $display_block .= "<td>$status_text</td>\n"; // project Title
        $display_block .= "<td>$empty_cell</td>\n";  // Supervisor
        $display_block .= "<td>$empty_cell</td>\n";  // Second Marker
        // Parts
        // Loop through the parts
        //print_r2($number_of_cols);
        for ($i = 1; $i <= $num_parts; $i++) {
            $num_cols = $number_of_cols[$i];
            for ($j = 1; $j <= $num_cols; $j++) {
                $display_block .= "<td>$empty_cell</td>\n";
            }
        }
        // final total
        $display_block .= "<td>$empty_cell</td>";
        $display_block .= "</tr>\n";
    }
}
$display_block .= "</tbody>\n</table>\n";

//mysql_close();
?>
<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php
(@include_once("$desktop_head")) OR die("Cannot find this file to include: $desktop_head<BR>");
?>
<script>
    $(function () {
        $("#geninfo").dataTable(
            {
                "bSort": false,
                "paging": false,
                "ordering": false,
                "info": false,
                "searching": false
            }
        );
    })
    $(function () {
        $("#studentlist").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false
            }
        );
    })
</script>
<body>
<div id="wrapper">

    <div id="header">
        <span
            class="header">Marking table for <?PHP echo("$top_bar_prefix $module_short_description  $module_prefix$module1$module_postfix ($short_code) $year_begin/$year_end") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo("$display_block"); ?>


    </div><!-- /content -->

    <div id="footer">
        <span><BR><a target="_self" href="./projectmarking_select_bs.php<?PHP echo("?pm_id=$pm_id"); ?>"
                     style="color: dimgray;"><?PHP echo("$module_prefix$module1$module_postfix index"); ?></a></span>
    </div><!-- /footer -->


</div><!-- /wrapper -->
</body>

</html>
