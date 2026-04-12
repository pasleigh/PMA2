<?php

function show_minor_part_feedback_form($db, $sid, $markstore, $assessqs, $student_name, $username, $title, $supervisor_name, $second_marker_name, $marker_name, $project_id, $assesstype, $assess_cats, $max_mark, $mark_pc, $programme, $assesspart, $assesstype_desc, $module_desc, $is_numeric) {
    //$display_block = "<h1>Part $assesspart: Assessment of $assesstype_desc by $student_name</h1>";
    $display_block = "<h1>Assessment of $module_desc : $assesstype_desc, by $student_name</h1>";

    $display_block .= "<div align=\"left\">\n";
    $display_block .= "
              <table class=\"tableGradeTop\" > 
              <tr>
              <td><b>Student:</b>&nbsp; $student_name&nbsp;&nbsp;</td>
              </tr>
              <tr>
              <td><b>Supervisor:</b>&nbsp; $supervisor_name</td>
              </tr>
              <tr>
              <td><b>Marker:</b>&nbsp; $marker_name
              </td>\n
              </tr>
              <tr>
              <td><b>Programme:</b>&nbsp; $programme</td>
              </tr>
              <tr>
              <td><b>Title:</b>&nbsp; <i>$title</i></td>
              </tr>
              <tr>
              <td><b>Project ID:</b>&nbsp; <i>$project_id</i></td>
              </tr>
              <tr>
              <td><em>This assessment contributes $mark_pc% to the module mark</em></td>
              </tr>              
              ";
    $display_block .= "</div><BR>\n";
    // get the returned mark
    $query = "select * from $markstore where SID=$sid";
    //$get_list_marks = mysql_query($get_marks) or die(" Unable to create list from database table $markstore. Query: $get_marks");
    //$get_marks_res = mysql_fetch_array($get_list_marks);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $get_marks_res = $results->fetch();
    if ($get_marks_res) {
        $final_total = $get_marks_res['total'];
        $rettotal = $get_marks_res['total'];
        $locked = $get_marks_res['locked'];
    } else {
        $final_total = 0;
        $rettotal = 0;
        $locked = 0;
    }
    $final_total_s = sprintf('%5.1f', $final_total * 100.0 / $max_mark);

    $display_block .= "
             <tr>
             <td><b>$assesstype mark:</b>&nbsp;<span class=\"grade\"> $final_total_s/$max_mark </span></td>
             </tr>
             </table>
             </div><BR />";

    //Get the marks from the markstore
    $query = "SELECT * FROM $markstore WHERE SID='$sid'";
    //$marks_res = mysql_query($marks_sql);
    //$marks_stored1 = mysql_fetch_array($marks_res);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $marks_stored1 = $results->fetch();

    // Get number of categories by counting the rows in $assess_cats
    $query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_categories = $results->rowCount();
    $cat_count = $num_categories;

    for ($cat = 1; $cat <= $cat_count; $cat++) {
        //$display_block .= "<tr>";
        // Get the category titles
        $query = "select * from $assess_cats where cat=$cat";
        //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. <BR>Query: $get_cat_details");
        //$cat_data = mysql_fetch_array($get_cat_details_list);
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $cat_data = $results->fetch();

        $description = $cat_data['description'];
        //$weight_pc = $cat_data['weight_percent'];
        //$factor = $cat_data['factor'];
        if (array_key_exists('get_feedback', $cat_data) == false) {
            $show_cat_feedback = 1;
        } else {
            $show_cat_feedback = $cat_data['get_feedback'];
        }

        $display_block .= "<div class=\"feedbackSection\">($cat) $description </div>";
        // Display the header
        $display_block .= '<table width="100%" class="tableSimple" style="border:1px solid black;border-collapse:collapse;">';
        $display_block .= "<tr></tr>\n";

        if ($is_numeric == 1) {
            $title1 = '';
            $title2 = 'Mark awarded';
            //$display_block .= "<tr><td class=\"tablehead\" width=\"50%\">$title2</td><td class=\"tablehead\" width=\"50%\">$title2</td></tr>\n";
        } else {
            $title1 = 'Question';
            $title2 = 'Feedback from marker';
            $display_block .= "<tr><td class=\"tablehead\" width=\"50%\">$title1</td><td class=\"tablehead\" width=\"50%\">$title2</td></tr>\n";
        }

        $query = "select * from $assessqs where cat=$cat";
        //$get_list_qstot = mysql_query($get_qstot) or die(" Unable to create list from database $assessqs. Query: $get_qstot");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $num = $results->rowCount();
        //$display_block .= "Number of questions = $num<br>";
        if ($num < 1) {
            //no questions
            $display_block .= '<em>Sorry, there are no questions in this category select!</em>';
        } else {
            //has questions
            for ($count = 1; $count <= $num; $count++) {
                $query = "select * from $assessqs where cat=$cat and qid=$count";
                //$display_block .= "Query: $get_qs <BR>";
                //$get_qs_res = mysql_query($get_qs);
                //$qs_list = mysql_fetch_array($get_qs_res);
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
                $qs_list = $results->fetch();
                $question = $qs_list['question'];
                $entries = $qs_list['entries'];

                $q_weight = $qs_list['weight'];
                if (!is_numeric($q_weight)) {
                    $q_weight = 100;
                }
                $q_weight *= 0.01;

                $choice = $cat . '-' . $count;
                $pchoice = 'q' . $choice;
                if (array_key_exists('mark1', $qs_list) == false) {
                    $qs_list['mark1'] = null;
                }
                if (array_key_exists('mark2', $qs_list) == false) {
                    $qs_list['mark2'] = null;
                }
                if (array_key_exists('mark3', $qs_list) == false) {
                    $qs_list['mark3'] = null;
                }
                if (array_key_exists('mark4', $qs_list) == false) {
                    $qs_list['mark4'] = null;
                }
                if (array_key_exists('mark5', $qs_list) == false) {
                    $qs_list['mark5'] = null;
                }
                if (array_key_exists('mark6', $qs_list) == false) {
                    $qs_list['mark6'] = null;
                }
                if (array_key_exists('mark7', $qs_list) == false) {
                    $qs_list['mark7'] = null;
                }
                if (array_key_exists('mark8', $qs_list) == false) {
                    $qs_list['mark8'] = null;
                }
                if (array_key_exists('mark9', $qs_list) == false) {
                    $qs_list['mark9'] = null;
                }
                if (array_key_exists('mark10', $qs_list) == false) {
                    $qs_list['mark10'] = null;
                }
                if (array_key_exists('mark11', $qs_list) == false) {
                    $qs_list['mark11'] = null;
                }
                if (array_key_exists('text1', $qs_list) == false) {
                    $qs_list['text1'] = null;
                }
                if (array_key_exists('text2', $qs_list) == false) {
                    $qs_list['text2'] = null;
                }
                if (array_key_exists('text3', $qs_list) == false) {
                    $qs_list['text3'] = null;
                }
                if (array_key_exists('text4', $qs_list) == false) {
                    $qs_list['text4'] = null;
                }
                if (array_key_exists('text5', $qs_list) == false) {
                    $qs_list['text5'] = null;
                }
                if (array_key_exists('text6', $qs_list) == false) {
                    $qs_list['text6'] = null;
                }
                if (array_key_exists('text7', $qs_list) == false) {
                    $qs_list['text7'] = null;
                }
                if (array_key_exists('text8', $qs_list) == false) {
                    $qs_list['text8'] = null;
                }
                if (array_key_exists('text9', $qs_list) == false) {
                    $qs_list['text9'] = null;
                }
                if (array_key_exists('text10', $qs_list) == false) {
                    $qs_list['text10'] = null;
                }
                if (array_key_exists('text11', $qs_list) == false) {
                    $qs_list['text11'] = null;
                }
                $text1 = $qs_list['text1'];
                $mark1 = $qs_list['mark1'];
                $text2 = $qs_list['text2'];
                $mark2 = $qs_list['mark2'];
                $text3 = $qs_list['text3'];
                $mark3 = $qs_list['mark3'];
                $text4 = $qs_list['text4'];
                $mark4 = $qs_list['mark4'];
                $text5 = $qs_list['text5'];
                $mark5 = $qs_list['mark5'];
                $text6 = $qs_list['text6'];
                $mark6 = $qs_list['mark6'];
                $text7 = $qs_list['text7'];
                $mark7 = $qs_list['mark7'];
                $text8 = $qs_list['text8'];
                $mark8 = $qs_list['mark8'];
                $text9 = $qs_list['text9'];
                $mark9 = $qs_list['mark9'];
                $text10 = $qs_list['text10'];
                $mark10 = $qs_list['mark10'];
                $text11 = $qs_list['text11'];
                $mark11 = $qs_list['mark11'];
                $feedback_heading = $question;

                $check_marks[1] = $mark1;
                $check_marks[2] = $mark2;
                $check_marks[3] = $mark3;
                $check_marks[4] = $mark4;
                $check_marks[5] = $mark5;
                $check_marks[6] = $mark6;
                $check_marks[7] = $mark7;
                $check_marks[8] = $mark8;
                $check_marks[9] = $mark9;
                $check_marks[10] = $mark10;
                $check_marks[11] = $mark11;
                $check_texts[1] = $text1;
                $check_texts[2] = $text2;
                $check_texts[3] = $text3;
                $check_texts[4] = $text4;
                $check_texts[5] = $text5;
                $check_texts[6] = $text6;
                $check_texts[7] = $text7;
                $check_texts[8] = $text8;
                $check_texts[9] = $text9;
                $check_texts[10] = $text10;
                $check_texts[11] = $text11;

                //$display_block .= "Question : $count, Entries: $entries<BR>";
                if ($count == $num) {
                    $db_col = 'C' . $cat . 'Feedback';
                } else {
                    $db_col = 'C' . $cat . 'Q' . $count;
                }

                if ($marks_stored1) {
                    $mark1_stored = $marks_stored1[$db_col];
                    $mark2_stored = $marks_stored1[$db_col];
                } else {
                    $mark1_stored = 0;
                    $mark2_stored = 0;
                }

                // If it is a numeric question then un weight it
                if ($is_numeric == 1) {
                    // Only do if it is not feedback
                    if (is_numeric($mark1_stored) && abs($q_weight) > 0.0001) {
                        $mark1_stored /= $q_weight;
                    }
                }

                $checked1 = [];
                $checked2 = [];
                // set which is selected
                for ($i = 1; $i <= 11; $i++) {
                    $checked1[$i] = '';
                    $checked2[$i] = '';

                    //$display_block .= "Mark stored " . $mark1_stored . " check_mark " . $check_marks[$i] . "<BR>";
                    if ($mark1_stored == $check_marks[$i]) {
                        $checked1[$i] = 'checked';
                        //$display_block .= $i . "<BR>";
                    }
                    if ($mark2_stored == $check_marks[$i]) {
                        $checked2[$i] = 'checked';
                    }
                    //$display_block .= $checked1[$i] . "<BR>";
                }

                // Display the question
                $display_block .= '<tr>';

                //$display_block .= "choice " . $choice;
                // Supervisor
                if ($entries == 0) {// the feedback comments
                    if ($show_cat_feedback) {
                        $mark1_stored = htmlentities($mark1_stored);
                        $mark1_stored = addslashes($mark1_stored);
                        $mark1_stored = str_replace('’', "'", $mark1_stored);
                        $entry = nl2br($mark1_stored);

                        //$display_block .= "<textarea class=\"feedback\" placeholder=\"Remember, this feedback is for the student's benefit!\" name=A$choice >$entry</textarea><br /><br />";
                        $display_block .= "<td class=\"tablehead\" width=\"50%\">$question</td>";
                        $display_block .= '<td width="50%">';
                        $display_block .= "$entry";
                        $display_block .= '</td>';
                    }
                } else {
                    if ($is_numeric == 1) {
                        $display_block .= "<td class=\"tablehead\" width=\"50%\">$title2</td>";
                        $display_block .= '<td width="50%">';
                        $display_block .= nl2br($mark1_stored);
                        $display_block .= '</td>';
                    } else {
                        //$display_block .= "$count: ";
                        for ($i = 1; $i <= $entries; $i++) {
                            if ($checked1[$i] == 'checked') {
                                $display_block .= "<td width=\"50%\">$count) $question</td>";
                                $display_block .= '<td width="50%">';
                                $display_block .= "$check_texts[$i]";
                                $display_block .= '</td>';
                            }
                        }
                    }
                }

                //          // Second marker

                $display_block .= "</tr>\n";
            }
        }
        $display_block .= '</table><BR />';
    } // $cat

    //$display_block .= "<a href=\"javascript:window.print()\">Print this page</a>";

    return $display_block;
}// end of show_feedback_form()

function show_minor_part_overall_feedback_form($db, $part_label, $markstore, $sid, $min_feedback_words) {
    $overall_feedback_visibility = 'block';

    // Display the overall feedback box
    $query = "ALTER TABLE $markstore ADD `OverallFeedback` text DEFAULT null";
    if (!DoesColumnExistInTable($db, $markstore, 'OverallFeedback')) {
        $db->query($query);
    } else {
    }

    $query = "SELECT OverallFeedback FROM $markstore WHERE SID=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $overall_feedback_res = $results->fetch();
    if ($overall_feedback_res) {
        $overall_feedback_text = $overall_feedback_res['OverallFeedback'];
    } else {
        $overall_feedback_text = null;
    }
    $overall_feedback_heading = "Overall feedback on this $part_label part";
    $overall_feedback_id = 'overall_feedback';

    $display_block = '';
    //$display_block .= "<div id='assess_criteria' style='border: thin solid black; padding-left: 5px; background-color: #ECEBE6; display: $overall_feedback_visibility;' >\n";
    //$display_block .= "<span class=\"feedbackQ\"><P>Overall Feedback: &nbsp;<span class=\"feedbackQem\">$overall_feedback_heading</span></p></span><br />";
    //$display_block .= "\n<textarea name='$overall_feedback_id'>$overall_feedback_text</textarea><br /><br />";
    //$display_block .= "</div><BR>\n";

    $display_block .= "<table class='tableSimple' style='width: 100%; border:1px solid black;border-collapse:collapse;'>";
    $display_block .= "<tr></tr>\n";
    $display_block .= "<tr><td class='tablehead_overall'>$overall_feedback_heading</td></tr>\n";
    $display_block .= "<tr><td>$overall_feedback_text</td></tr>";
    $display_block .= '</table>';
    return $display_block;
}
