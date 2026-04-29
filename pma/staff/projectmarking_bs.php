<?php
(@include_once('./set_dir_staff.php')) or die('Cannot find this file to include: set_dir_staff.php<BR>');
(@include_once('./config.php')) or die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once('./setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('./connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('./setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('./staff_functions.php')) or die('Cannot include staff_function.php<BR>');
(@include_once('./markstore_functions.php')) or die('Cannot include markstore_function.php<BR>');
(@include_once('./admin2/adminusers.php')) or die('Cannot setup admin users<BR>');

$staff_username = get_server_username();
if (IsStaffB($db, $staff_username, $staff_names) == false) {
    echo 'You do not have access to this page. If you think this is an error please contact the system administrator.<BR>';
    echo "<p><A target='self' href='..'>Return to the home page</a>.</P>";
    exit();
}

if (array_key_exists('modify', $_REQUEST) == false) {
    $_REQUEST['modify'] = null;
}
$modify = $_REQUEST['modify'];

$num_limit = -1;
$top_bar_prefix = '';
if (array_key_exists('user', $_REQUEST) == false) {
    $_REQUEST['user'] = null;
}
if ($_REQUEST['user'] == 1) {
    $num_limit = 0;
    $top_bar_prefix = "$this_staff_full_name:";
}

$table_body = '';
//$table_body .= $connect_msg;

// Draw the main table

// Loop through the parts to get the number of columns required for each
for ($i = 1; $i <= $num_parts; $i++) {
    if ($p_num_markers[$i] == 1) {
        $number_of_cols[$i] = 2;
    } elseif ($p_num_markers[$i] == 2) {
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
//$table_body .= "<table id='studentlist' class='hover compact cell-border'>\n";
$table_head = '';
$table_head .= "
    <thead>
      <tr>
        <th rowspan='2'>Student name</th>
        <th rowspan='2'>SID</th>
        <th rowspan='2'>Username</th>
        <th rowspan='2'>Project ID</th>
        <th rowspan='2'>Project Title</th>
        <th rowspan='2'>Supervisor</th>
        <th rowspan='2'>Second marker</th>
        ";

// Loop through the parts
for ($i = 1; $i <= $num_parts; $i++) {
    $query = "SELECT * FROM $project_allocated ORDER BY projectid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
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

    $num_cols = $number_of_cols[$i];

    if ($i % 2 == 1) {
        $cell_style = "style='background-color: linen;'";
    } else {
        $cell_style = '';
    }
    $table_head .= "<th colspan='$num_cols' $cell_style>Part $i: $p_description[$i] ($p_percent[$i]%)<BR>($num_marked/$num_students)</th>";
}

$table_head .= "<th rowspan='2'>Final Total</th>";
$table_head .= "\n</tr>\n";

// Loop through the parts - second row
$table_head .= "\n<tr>\n";
for ($i = 1; $i <= $num_parts; $i++) {
    $num_cols = $number_of_cols[$i];

    for ($j = 1; $j <= $num_cols; $j++) {
        if ($num_cols == 2) {
            if ($j == 1) {
                $table_head .= "<th>Marker $j</th>";
            } else {
                $table_head .= '<th>Total</th>';
            }
        } else {
            if ($j < $num_cols) {
                $table_head .= "<th>Marker $j</th>";
            } else {
                $table_head .= '<th>Agreed Total</th>';
            }
        }
    }
}
$table_head .= "\n</tr>\n";

$table_head .= "\n</thead>\n";

// Now do the table body
$table_body .= "<tbody>\n";
// list all the allocated students
$query = "select * from $project_allocated order by projectid";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$allocated_students_rows = $results->fetchAll();

// list all the students
$query = "select * from $students order by name";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$all_students_rows = $results->fetchAll();

$table_rows = [];
$table_cells = [];
foreach ($all_students_rows as $student_recs) {
    $sid = $student_recs['sid'];
    $student_name = $student_recs['name'];
    $username = $student_recs['username'];
    $programme = $student_recs['programme'];
    $sel = $student_recs['sel'];

    $is_allocated = IsSIDAllocated($db, $sid, $project_allocated);
    $is_active = IsSIDActive($db, $sid, $project_allocated);

    $table_cells = [];
    if ($is_active) {
        // get the markers data for this student
        $query = "SELECT * FROM $project_allocated WHERE sid=?";
        try {
            $results = $db->prepare($query);
            $exe = [$sid];
            $results->execute($exe);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $recs = $results->fetch();

        $project_id = $recs['projectid'];
        $project_master_id = $recs['masterid'];
        $supervisor_id = $recs['supervisor_id'];
        $second_marker_id = $recs['second_marker_id'];
        $project_title = $recs['title'];

        $count_this_staff_marking = 0;
        $markers = [];
        $markers_id = [];
        for ($i = 1; $i <= $num_parts; $i++) {
            $markers[$i] = [];
            $markers_id[$i] = [];
            for ($j = 1; $j <= 3; $j++) {
                $marker_entry = "marker_p$i" . "m$j" . '_id';
                $marker_id = $recs[$marker_entry];
                $markers_id[$i][$j] = $marker_id;
                $markers[$i][$j] = GetStaffNameFromID($db, $marker_id, $staff2, null);
                $name = $markers_id[$i][$j];
                // Does this marker_id match the current logged in staff
                if (($this_staff_id == $marker_id) && ($j < $number_of_cols[$i])) {
                    $count_this_staff_marking++;
                }
            }
        }
        if ($count_this_staff_marking > $num_limit) {
            // Modify option
            $modify_link = '';
            if ($modify == 1) {
                $modify_link = "<a href='modify_student_project_details.php?pm_id=$pm_id&sid=$sid'><img width='26' src='./icons/0032_screw_driver.png'  alt='Modify this student's project details' title='Modify this student's project details'></a>";
            }

            $table_cells = [];

            $table_row = "<tr>\n";
            $table_body .= $table_row;
            $table_cell = "<td>$modify_link$student_name </td>\n";
            $table_body .= $table_cell;
            $table_cells[] = $table_cell;
            $table_cell = "<td>$sid</td>\n";
            $table_body .= $table_cell;
            $table_cells[] = $table_cell;
            $table_cell = "<td>$username</td>\n";
            $table_body .= $table_cell;
            $table_cells[] = $table_cell;
            $table_cell = "<td>$project_id</td>\n";
            $table_body .= $table_cell;
            $table_cells[] = $table_cell;
            $table_cell = "<td>$project_title</td>\n";
            $table_body .= $table_cell;
            $table_cells[] = $table_cell;
            $supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff2, null);
            $table_cell = "<td>$supervisor_name</td>\n";
            $table_body .= $table_cell;
            $table_cells[] = $table_cell;
            $second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff2, null);
            $table_cell = "<td>$second_marker_name</td>\n";
            $table_body .= $table_cell;
            $table_cells[] = $table_cell;

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
                        $icon = "'./icons/_0007_Tick.png'";
                        $remark = 'review';
                    } else {//if (IsThirdMarkerRequired($sid, $i, $markstore_totals)){
                        $icon = "'./icons/_0008_Minus.png'";
                        $remark = 'assess';
                    }
                    $this_mark = GetThisMark($db, $sid, $i, $markstores[$i][$j]);
                    $this_mark_s = '';
                    if ($this_mark > 0) {
                        //$this_mark_s = sprintf("(%5.1f%%)", (float)$this_mark * 100.0 / (float)$p_max[$i]);
                    }
                    if (IsAdmin($db, $this_staff_username) || (IsModuleAdmin($db, $this_staff_username, $pm_id, null, null)) || ($m_id == $this_staff_id)) {
                        $link = "<a href='assess_minor_part_questions_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=$j&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag'><img width='26' src=$icon  alt='$this_mark_s click to $remark' title='$this_mark_s click to $remark'></a><BR>";
                    } else {
                        $link = '';
                        $link = "<img id='grey_image' width='26' src=$icon  alt='$this_mark_s click to $remark' title='$this_mark_s click to $remark'><BR>";
                    }
                    $name = $markers[$i][$j];
                    if ($markers[$i][$j] == '') {
                        $link = '';
                        $modify_link = "<a href='modify_student_project_details.php?pm_id=$pm_id&sid=$sid'>";
                        $name = $modify_link . 'Name needs setting';
                        $diff = GetDifference($db, $sid, $i, $markstores);
                        $diff_s = sprintf('%5.1f%%', (float) $diff);
                        $agreed_total = 'diff=' . $diff_s;
                        $name .= '</A><BR>' . $agreed_total;
                    }
                    // If the third marker is required for some then DON'T show  one when not needed
                    if (($j == 3) && (!IsThirdMarkerRequired($db, $sid, $i, $markstore_totals))) {
                        $link = '';
                        $name = '';
                    }
                    $table_cell = "<td style='font-size: smaller;'>$link$name</td>";
                    $table_body .= $table_cell;
                    $table_cells[] = $table_cell;
                    if ($j == $num_cols - 1) {
                        // Add the agreed total mark on if agreed
                        if (HaveAllMarkersMarked($db, $sid, $i, $p_num_markers[$i], $markstores, $markstore_totals)) {
                            if ($p_num_markers[$i] == 1) {
                                // Just write the mark
                                $total_mark = GetTotalForPart($db, $sid, $i, $markstore_totals);
                                if ($total_mark > 0) {
                                    $max = $p_max[$i];
                                    $total_mark_s = sprintf('%5.1f%%', (float) $total_mark * 100.0 / (float) $max);
                                    $table_cell = "<td>$total_mark_s</td>";
                                } else {
                                    $table_cell = "<td>$total_mark (^._.^)</td>";
                                }
                                $table_body .= $table_cell;
                                $table_cells[] = $table_cell;
                            } elseif ($p_num_markers[$i] == 3) {
                                // Check that the mark is recorded in the totals table
                                // If not then put it there
                                $total_mark = GetTotalForPart($db, $sid, $i, $markstore_totals);
                                if ($total_mark < 0) {
                                    $total_mark = GetTotalFromAllMarkers($db, $sid, $i, $p_num_markers[$i], $markstores);
                                    $total_mark /= $p_num_markers[$i];
                                }
                                $max = $p_max[$i];
                                $total_mark_s = sprintf('%5.1f%%', (float) $total_mark * 100.0 / (float) $max);
                                $table_cell = "<td>$total_mark_s</td>";
                                $table_body .= $table_cell;
                                $table_cells[] = $table_cell;
                            } else {
                                // Two markers so needed to check whether a third marker has been requested
                                $total_mark = GetTotalForPart($db, $sid, $i, $markstore_totals);
                                if ($total_mark > 0) {
                                    // Mark had been agreed. Show the appropriate icon (green tick) and prompt
                                    $max = $p_max[$i];
                                    $total_mark_s = sprintf('%5.1f%%', (float) $total_mark * 100.0 / (float) $max);
                                    //$print_r($total_mark);
                                    $this_mark_s = $total_mark_s;
                                    $icon = "'./icons/_0007_Tick.png'";
                                    $remark = 'review';
                                    $agreed_total = $this_mark_s;
                                    if (IsThirdMarkerRequired($db, $sid, $i, $markstore_totals)) {
                                        $link = "<a href='assess_minor_part_questions_3_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=4&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag'><img width='26' src=$icon  alt='$this_mark_s click to $remark' title='$this_mark_s click to $remark'></a><BR>";
                                    } else {
                                        $link = "<a href='assess_minor_part_questions_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=$j&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag'><img width='26' src=$icon  alt='$this_mark_s click to $remark' title='$this_mark_s click to $remark'></a><BR>";
                                    }
                                    $table_cell = "<td style='font-size: smaller;'> $link$agreed_total</td>";
                                    $table_body .= $table_cell;
                                    $table_cells[] = $table_cell;
                                } else {
                                    if (IsThirdMarkerRequired($db, $sid, $i, $markstore_totals)) {
                                    }
                                    $icon = "'./icons/_0008_Minus.png'";
                                    $this_mark_s = 'Not yet agreed,';
                                    $remark = 'compare';
                                    $diff = GetDifference($db, $sid, $i, $markstores);
                                    $diff_s = sprintf('%5.1f%%', (float) $diff);
                                    $agreed_total = 'diff=' . $diff_s;
                                    if (IsThirdMarkerRequired($db, $sid, $i, $markstore_totals)) {
                                        $link = "<a href='assess_minor_part_questions_3_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=4&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag'><img width='26' src=$icon  alt='$this_mark_s click to $remark' title='$this_mark_s click to $remark'></a><BR>";
                                    } else {
                                        $link = "<a href='assess_minor_part_questions_side_by_side_bs.php?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$i&who=$j&marker_id=$m_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag'><img width='26' src=$icon  alt='$this_mark_s click to $remark' title='$this_mark_s click to $remark'></a><BR>";
                                    }
                                    $table_cell = "<td style='font-size: smaller;'>$link$agreed_total</td>";
                                    $table_body .= $table_cell;
                                    $table_cells[] = $table_cell;
                                }
                            }
                        } else {
                            // Agreed total column
                            // No total agreed yet
                            $agreed_total = '';
                            $table_cell = "<td style='font-size: smaller;'></td>";
                            $table_body .= $table_cell;
                            $table_cells[] = $table_cell;
                        }
                    }
                }
            }
            $overall_total = SetRecalculatedOverallTotal($db, $sid, $num_parts, $p_percent, $markstore_totals);
            if ($overall_total < 0.0) {
                $table_cell = '<td>--</td>';
                $overall_total_s = $table_cell;
            } else {
                $overall_total_s = sprintf('%6.1f%%', (float) $overall_total);
                // Put in a link
                $icon = "'./icons/pdf-icon_gg.png'";
                $link = "<a href='show_overall_mark_feedback_pdf.php?sid=$sid&pid=$project_id&pm_id=$pm_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag'><img width='24' src=$icon  border='0' alt='View feedback to student' title='View feedback to student'></a><BR>";
                $table_cell = "<td style='font-size: smaller;'> $link $overall_total_s </td>"; //"<A href='./show_overall_mark_feedback_pdf.php' target='_blank'>$overall_total_s</A>";
                $overall_total_s = $table_cell;
            }
            $table_body .= $table_cell;
            $table_cells[] = $table_cell;

            $table_row = "</tr>\n";
            $table_body .= $table_row;
        }
    } else {// End of if $is_active = true
        if ($is_allocated) {
            // Allocated but not active
            // Probably withdrawn
            $status_text = 'Not active';
        } else {
            // Not yet active
            $status_text = 'Not allocated';
        }
        $empty_cell = '-';
        $modify_link = '';
        if ($modify == 1) {
            $modify_link = "<a href='modify_student_project_details.php?pm_id=$pm_id&sid=$sid'><img width='26' src='./icons/0032_screw_driver.png'  alt='Modify this student's project details' title='Modify this student's project details'></a>";
        }
        $table_cells = [];
        $table_row = "<tr>\n";
        $table_body .= $table_row;
        $table_cell = "<td>$modify_link$student_name </td>\n"; // name
        $table_body .= $table_cell;
        $table_cells[] = $table_cell;
        $table_cell = "<td>$sid</td>\n";         // sid
        $table_body .= $table_cell;
        $table_cells[] = $table_cell;
        $table_cell = "<td>$username</td>\n";    // usermame
        $table_body .= $table_cell;
        $table_cells[] = $table_cell;
        $table_cell = "<td>$empty_cell</td>\n";  // PID
        $table_body .= $table_cell;
        $table_cells[] = $table_cell;
        $table_cell = "<td>$status_text</td>\n"; // project Title
        $table_body .= $table_cell;
        $table_cells[] = $table_cell;
        $table_cell = "<td>$empty_cell</td>\n";  // Supervisor
        $table_body .= $table_cell;
        $table_cells[] = $table_cell;
        $table_cell = "<td>$empty_cell</td>\n";  // Second Marker
        $table_body .= $table_cell;
        $table_cells[] = $table_cell;
        // Parts
        // Loop through the parts
        for ($i = 1; $i <= $num_parts; $i++) {
            $num_cols = $number_of_cols[$i];
            for ($j = 1; $j <= $num_cols; $j++) {
                $table_cell = "<td>$empty_cell</td>\n";
                $table_body .= $table_cell;
                $table_cells[] = $table_cell;
            }
        }
        // final total
        $table_cell = "<td>$empty_cell</td>";
        $table_body .= $table_cell;
        $table_cells[] = $table_cell;

        $table_row = "</tr>\n";
        $table_body .= $table_row;
    }

    if (count($table_cells) > 0) {
        // remove the <td> and </td> from the cells of this row
        for ($i = 0; $i < count($table_cells); $i++) {
            $table_cell = str_replace('<td>', '', $table_cells[$i]);
            $table_cell = str_replace("<td style='font-size: smaller;'>", '', $table_cell);
            $table_cell = str_replace('</td>', '', $table_cell);
            $table_cell = str_replace("\n", '', $table_cell);
            $table_cells[$i] = $table_cell;
        }
        $table_rows[] = $table_cells;
    }
}
$table_body .= '</tbody>';

//mysql_close();
$admin_link_text = '';
$show_admin_link = 'none';
$admin_link = '';
if (IsAdmin($db, $this_staff_username)) {
    $admin_link = './admin2/';
    $admin_link_text = 'Super Admin';
    $show_admin_link = 'block';
} elseif (IsModuleAdmin($db, $this_staff_username, 'all', null, null)) {
    $admin_link = './admin2/';
    $admin_link_text = 'Admin';
    $show_admin_link = 'block';
}
?>
<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html lang="en">
<?php
(@include_once("$mobile_head_bs")) or die("Cannot find this file to include: $mobile_head_bs<BR>");
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
        $("#studentlistXXX").dataTable(
            {
                //"bSort":    false,
                //"paging": false,
                //"ordering": false,
                //"info":     false,
                //"searching": false
                "lengthMenu": [[10, 20, 30, 100, -1], [10, 20, 30, 100, "All"]]
            }
        );
    })
</script>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Marking table for <?php echo("$top_bar_prefix $module_short_description  $module_prefix$module1$module_postfix ($short_code) $year_begin/$year_end") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./projectmarking_select_bs.php<?php echo("?pm_id=$pm_id"); ?>" class="nav-link px-2 text-white-50"><?php echo("$module_prefix$module1$module_postfix index"); ?></a></li>
        </ul>
        <div class="navbar-brand ms-auto" style="display: <?php echo($show_admin_link);?>">
            <a target='_top' class="navbar-brand" href='<?php echo("$admin_link"); ?>'>
                <i class="bi bi-gear"></i>
                <?php echo("$admin_link_text"); ?>
            </a>
        </div>
    </div>
</nav>
<div id="wrapper">

    <div class="container-fluid" id="content">
        <?php echo("$connect_msg"); ?>
        <table id='studentlist' class='hover compact cell-border'>
            <?php echo("$table_head"); ?>
            <!--<?php echo("$table_body"); ?>-->
        </table>

    </div><!-- /content -->


</div><!-- /wrapper -->
</body>
<script>
    $(document).ready(function () {
        let project_marking_data = {
            markers: [
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17],
                [17, 16, 15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1]
            ]
        }
        let mydata = <?php echo json_encode($table_rows); ?>;
        //console.log(mydata);
        $('#studentlist').DataTable({
            //data: project_marking_data.markers,
            data: mydata,
            "lengthMenu": [[10, 20, 30, 100, -1], [10, 20, 30, 100, "All"]]
        })
    });
</script>
</html>
