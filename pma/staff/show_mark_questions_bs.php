<?php

function show_mark_questions_bs($db, $cat, $cat_description, $weight_pc, $factor, $show_cat_feedback, $num_questions, $questions, $num_responses, $response_texts, $response_marks, $markstore, $sid, $min_feedback_words) {
    // Get the student marks
    $query = "select * from $markstore where SID=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }

    $get_marks_res = $results->fetch();
    if ($get_marks_res) {
        for ($i = 1; $i <= 10; $i++) {
            $id = "C$cat" . "Q$i";
            $marks[$i] = $get_marks_res[$id];
        }
        $id = "C$cat" . 'Feedback';
        $feedback = $get_marks_res[$id];
    } else {
        // Student has not been marked
        for ($i = 1; $i <= 10; $i++) {
            $marks[$i] = -1;
        }
        $feedback = '';
    }
    $display_block = '';
    $display_block .= "\n\n<span id='section$cat'>";
    $display_block .= "<div class='card mb-3'>";
    $display_block .= "<div class='card-header'><h5>($cat) Assessment of: $cat_description</h5></div>";
    $display_block .= "<div id='table$cat' class='card-body'>\n";
    //$display_block .= "<table id='table$cat'>\n";
    //$display_block .= "<tbody>\n";
    //$display_block .= "<tr>\n";

    // Display the category title
    $sectionid = 'sec' . $cat;
    //$display_block .= "<td>\n";
    //$display_block .= "<div class=\"feedbackSection\">($cat) Assessment of: <span name=\"$sectionid\">$cat_description</span></div>\n";
    $display_block .= "<input type=\"hidden\" id=\"numqs$cat\" name=\"numqs$cat\" value=\"$num_questions\">\n";
    $display_block .= "<span id='factor$cat'><input type=\"hidden\" id=\"factor_id$cat\" name=\"factor_name$cat\" value=\"$factor\" size=\"3\"></span>\n";
    //$display_block .= "</td>\n";
    //$display_block .= "</tr>\n";
    //$display_block .= "<tr>\n<td>";
    $marktot = 0;
    for ($q_count = 1; $q_count < $num_questions; $q_count++) {
        $question = $questions[$q_count];

        $display_block .= "<span class=\"feedbackQ\">Question $q_count:\n";
        $qid = 'q' . $cat . '-' . $q_count;
        $rid = 'res' . $cat . '-' . $q_count;
        $qwid = 'qw' . $cat . '-' . $q_count;
        $q_weight = 100;
        //$display_block .= "<span class=\"feedbackQem\">
        //<input class=\"feedbackQem\" type=\"text\" id=\"$qid\" name=\"$qid\" value=\"$question\" size=\"120\">
        //</span></span><br />\n";
        $display_block .= "<span class=\"feedbackQem\" id=\"$qid\" name=\"$qid\">$question
    </span></span><br />\n";
        $display_block .= "<div class=\"options\">\n";
        $display_block .= "<input type=\"hidden\" id=\"$rid\" name=\"$rid\" value=\"$num_responses[$q_count]\" size=\"2\">";
        $display_block .= "<input type=\"hidden\" id=\"$qwid\" name=\"$qwid\" value=\"$q_weight\" size=\"2\">";

        for ($r_count = 1; $r_count <= $num_responses[$q_count]; $r_count++) {
            $pchoice = 'q' . $cat . '-' . $q_count;
            $pchoice_id = 'id' . $pchoice . $r_count;
            $mchoice = 'm' . $cat . '-' . $q_count . $r_count;
            $mchoice_id = 'id' . $mchoice;
            $tchoice = 't' . $cat . '-' . $q_count . $r_count;
            $tchoice_id = 'id' . $tchoice;
            $rchoice = 'r' . $cat . '-' . $q_count;
            $response_text = $response_texts[$q_count][$r_count];
            $response_mark = $response_marks[$q_count][$r_count];
            $display_block .= "\n<span id=\"$tchoice_id\" name=\"$tchoice\">$response_text</span>";
            $display_block .= "\n<input type=\"hidden\" id=\"$mchoice_id\" name=\"$mchoice\" value=\"$response_mark\" size=\"5\">";
            $is_checked = '';
            if ($response_mark == $marks[$q_count]) {
                $is_checked = 'checked';
                $marktot = $marktot + $marks[$q_count];
            }
            $display_block .= "\n<input type=\"radio\" id=\"$pchoice_id\" name=\"$rchoice\" value=\"$response_mark\" $is_checked> <BR>\n";
        }
        $display_block .= '<BR>';
        $display_block .= "</DIV>\n";
    }
    $marktot *= $factor;
    if ($show_cat_feedback == 1) {
        // put the feedback question in
        $pchoice = 'feedback' . $cat;
        $feedback_heading = "Would you give written feedback on the $cat_description ?";
        //$display_block .= "<span class=\"feedbackQ\">Feedback: &nbsp;<span class=\"feedbackQem\">$feedback_heading</span></span><br />";
        //$display_block .= "\n<textarea class=\"feedback_bs\" placeholder=\"Remember, this feedback is for the student's benefit! It should help them learn and improve next time. You will need to write at least $min_feedback_words words.\"";
        //$display_block .= "name=\"$pchoice\">$feedback</textarea><br /><br />";

        $display_block .= "<label for='$pchoice' class='form-label'>Feedback: <strong><em>$feedback_heading</em></strong></label>\n";
        $display_block .= "<textarea class='form-control' id='$pchoice' name='$pchoice' rows='5' placeholder='Feedback  given here is for the benefit of the student. It should be aimed at helping them improve next time. \nYou will need to write at least $min_feedback_words words.'>$feedback</textarea>\n";
    }
    //$display_block .= "</td>\n</tr>\n";

    // marks for this section - Display is not needed as only on section so the total will cover this.
    // But the total sums this value so it is hidden
    //$display_block .= "<tr><td>\n";
    $display_block .= "<span class=\"feedbackSectionMarkText\" style=\"display: none;\">Mark for $cat_description\n
                     <span id='sectionmarktext$cat'> </span>\n</span>\n";
    $display_block .= "        <span class=\"feedbackSectionMark\" id=sectionmark$cat style=\"display: none;\"> $marktot</span>";

    //$display_block .= "</tbody>\n";
    //$display_block .= "</table>";
    $display_block .= "</div></div></span>\n";// Close the car and card-body

    return $display_block;
}// end of show_mark_questions()

function show_mark_questions_numeric_bs($db, $cat, $cat_description, $weight_pc, $factor, $show_cat_feedback, $num_questions, $questions, $num_responses, $q_weights, $response_texts, $response_marks, $markstore, $sid, $min_feedback_words) {
    // Get the student marks
    $query = "select * from $markstore where SID=$sid";
    //$get_list_marks = mysql_query($get_marks) or die(" Unable to get marks from database table $markstore. Query: $get_marks");
    //$get_marks_res = mysql_fetch_array($get_list_marks);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $get_marks_res = $results->fetch();
    //$total = $get_marks_res['total'];
    if ($get_marks_res) {
        for ($i = 1; $i <= 10; $i++) {
            $id = "C$cat" . "Q$i";
            $marks[$i] = $get_marks_res[$id];
        }
        $id = "C$cat" . 'Feedback';
        $feedback = $get_marks_res[$id];
    } else {
        for ($i = 1; $i <= 10; $i++) {
            $marks[$i] = -1;
        }
        $feedback = '';
    }

    $display_block = '';
    $display_block = "\n\n<span id='section$cat'>";
    $display_block .= "<div class='card mb-3'>";
    $display_block .= "<div class='card-header'><h5>($cat) Assessment of: $cat_description</h5></div>";
    $display_block .= "<div id='table$cat' class='card-body'>\n";
    //$display_block .= "<div>";
    //$display_block .= "<table id='table$cat' class=\"tableSection\">";
    //$display_block .= "<tr>\n";

    // Display the category title
    $sectionid = 'sec' . $cat;
    //$display_block .= "<td><div class=\"feedbackSection\">($cat) Assessment of: <span name=\"$sectionid\">$cat_description </span></div>";
    $display_block .= "<input type=\"hidden\" id=\"numqs$cat\" name=\"numqs$cat\" value=\"$num_questions\">\n";
    $display_block .= "<span id='factor$cat'><input type=\"hidden\" id=\"factor_id$cat\" name=\"factor_name$cat\" value=\"$factor\" size=\"3\"></span>";
    //$display_block .= "</td>\n";
    //$display_block .= "</tr>";
    //$display_block .= "<tr>\n<td>";
    $marktot = 0;
    for ($q_count = 1; $q_count < $num_questions; $q_count++) {
        $question = $questions[$q_count];
        $question_purpose = $response_texts[$q_count][1];
        $display_block .= "<span class=\"feedbackQ\">Question $q_count:\n";
        $qid = 'q' . $cat . '-' . $q_count;
        $rid = 'res' . $cat . '-' . $q_count;
        $nrid = 'r' . $cat . '-' . $q_count;
        $qwid = 'qw' . $cat . '-' . $q_count;
        //$display_block .= "<span class=\"feedbackQem\">
        //<input class=\"feedbackQem\" type=\"text\" id=\"$qid\" name=\"$qid\" value=\"$question\" size=\"120\">
        //</span></span><br />\n";
        $display_block .= "<span class='feedbackQem' id='$qid' name='$qid'>$question </span>\n";
        $display_block .= "<BR><span style='color:darkslategray;margin-left:30px;'>Look for: $question_purpose</span>\n";
        $display_block .= "</span><br />\n";
        $display_block .= "<div class='col-3 mt-2'>\n";
        $display_block .= "<input type='hidden' id='$rid' name='$rid' value='$num_responses[$q_count]' size='2'>";
        $display_block .= "<input type='hidden' id='$qwid' name='$qwid' value='$q_weights[$q_count]' size='2'>";

        $q_weight = $q_weights[$q_count] * 0.01;
        $mark = $marks[$q_count];
        $mark_w = round($mark / $q_weight);
        $display_block .= "\n<input class='form-control' type='number' id='$nrid' name='$nrid' value='$mark_w' min='0' max='100'> \n";
        $marktot = $marktot + $mark;

        $display_block .= '<BR>';
        $display_block .= "</DIV>\n";
    }
    $marktot *= $factor;
    if ($show_cat_feedback == 1) {
        // put the feed back question in
        $pchoice = 'feedback' . $cat;
        $feedback_heading = "Would you give written feedback on the $cat_description ?";
        //$display_block .= "<span class=\"feedbackQ\">Feedback: &nbsp;<span class=\"feedbackQem\">$feedback_heading</span></span><br />";
        //$display_block .= "\n<textarea class=\"feedback\" placeholder=\"Remember, this feedback is for the student's benefit! It should help them learn and improve next time. You will need to write at least $min_feedback_words words.\" name=\"$pchoice\">$feedback</textarea><br /><br />";
        //$display_block .= "</td>\n</tr>\n";

        $display_block .= "<label for='$pchoice' class='form-label'>Feedback: <strong><em>$feedback_heading</em></strong></label>\n";
        $display_block .= "<textarea class='form-control' id='$pchoice' name='$pchoice' rows='5' placeholder='Feedback  given here is for the benefit of the student. It should be aimed at helping them improve next time. \nYou will need to write at least $min_feedback_words words.'>$feedback</textarea>\n";
    }
    // marks for this section - Display is not needed as only on section so the total will cover this.
    // But the total sums this value so it is hidden
    $display_block .= "\n<span class=\"feedbackSectionMarkText\" style=\"display: none;\">Mark for $cat_description\n
                     <span id=sectionmarktext$cat> </span>\n</span>\n";
    $display_block .= "        <span class=\"feedbackSectionMark\" id=sectionmark$cat style=\"display: none;\"> $marktot</span>";

    //$display_block .= "</tbody>\n";
    //$display_block .= "</table>";
    $display_block .= "</div></div></span>\n";// Close the card and card-body

    return $display_block;
}// end of show_mark_questions_numeric()
function show_overall_feedback_bs($db, $part_label, $markstore, $sid, $min_feedback_words) {
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
    $overall_feedback_heading = "Please give <strong>overall</strong> feedback on this $part_label part";
    $overall_feedback_id = 'overall_feedback';

    $display_block = '';
    //$display_block .= "<div id='assess_criteria' style='border: thin solid black; padding-left: 5px; background-color: #ECEBE6; display: $overall_feedback_visibility;' >\n";
    //$display_block .= "<span class=\"feedbackQ\"><P>Overall feedback: &nbsp;<span class=\"feedbackQem\">$overall_feedback_heading</span></p></span><br />";
    //$display_block .= "\n<textarea class=\"feedback\"";
    //$display_block .= "placeholder=\"Remember, this feedback is for the student's benefit! It should help them learn and improve next time. You will need to write at least $min_feedback_words words.\"";
    //$display_block .= "name=\"$overall_feedback_id\">$overall_feedback_text</textarea><br /><br />";
    //$display_block .= "</div><BR>\n";

    $display_block .= "<div class='card mb-3 border-info text-bg-light style='display: $overall_feedback_visibility;'>\n";
    $display_block .= "<div class='card-header'><h5>Overall Feedback: $overall_feedback_heading</h5></div>\n";
    $display_block .= "<div class='card-body'>\n";
    //$display_block .= "\n<textarea class=\"feedback\"
    //                  placeholder=\"Remember, this feedback is for the student's benefit! It should help them learn and improve next time. You will need to write at least $min_feedback_words words.\"
    //                 name=\"$overall_feedback_id\">$overall_feedback_text</textarea><br /><br />";
    $display_block .= "<label for='$overall_feedback_id' class='form-label'>Feedback given here is for the benefit of the student. It should be aimed at helping them improve next time.</label>\n";
    $display_block .= "<textarea class='form-control' id='$overall_feedback_id' name='$overall_feedback_id' rows='5' placeholder='You will need to write at least $min_feedback_words words.'>$overall_feedback_text</textarea>\n";
    $display_block .= "</div>\n";
    $display_block .= "</div>\n";

    return $display_block;
}
