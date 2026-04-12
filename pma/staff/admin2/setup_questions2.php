<?php
// Turn off all error reporting
// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// connect to the datbase
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot find this file to include: markstore_functions.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../show_feedback_form.php")) OR die("Cannot get show_feedback_form.php<BR>");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
//$access = false;
//$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
//$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

// A hack to reduce the number of POST variables to < 1000
// (while the server has PHP 5.3.3 (it must be 5.3.9 + to change max_input_vars to = 1500)
// This means that only "$max_num_responses_editable" questions can be edited
$max_num_responses_editable = 8;
$g_max_num_responses_per_questionXX = $max_num_responses_editable;

$assess_part_description_text = "";

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

    if (array_key_exists('part', $_GET) == false) {
        $_GET['part'] = null;
    }
    $assesspart = $_GET['part'];




// if the form was submitted then enter it all into the database

    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }


    // Start of functions
    function show_section_questions2($cat, $cat_description, $weight_pc, $factor, $num_questions, $questions, $num_responses, $q_weights, $response_texts, $response_marks, $max_num_questions, $max_num_responses, $is_numeric, $show_cat_feedback_flag)
    {
        $display_block = "\n\n<div name='section' id='section_block$cat'>";
        $display_block .= "<table id=table$cat class='tableSection'>";
        $display_block .= "<tr>\n";

        // Display the category title
        $sectionid = "sec" . $cat;
        $display_block .= "<td><div class='feedbackSection'>Section $cat:
        \n<input  class='feedbackSectionQ' type='text' id='$sectionid' name='$sectionid' value='$cat_description' size='60'>
        \n<input type='hidden' id='numqs$cat' name='numqs$cat' value='$num_questions'>
        </div>\n";
        $display_block .= "<div class='feedbackWeights'>Proportion of assessment: <span id=weightpc$cat>
        \n<input type='number' id='weightpc_id$cat' name='weightpc_name$cat' value='$weight_pc' min='0' max='100' size='3'>%. </span>";
        $display_block .= "Weight factor <span id=factor$cat>
        \n<input type='number' id='factor_id$cat' name='factor_name$cat' value='$factor' min='0' max='1' step='0.0001' size='4'></span></div>";
        $display_block .= "</td>\n";
        $display_block .= "</tr>";
        $num_qs = $num_questions - 1;
        $display_block .= "\n<tr><td>Number of questions in this section <input type='number' id='new_numqs$cat' name='new_numqs$cat' value='$num_qs' min='1' max='10' size=6 maxlength=5> (note that questions will be deleted if this number is less than already exists.)</td></tr>\n";
        $display_block .= "<tr>\n<td>";
        for ($q_count = 1; $q_count <= $max_num_questions; $q_count++) {
            $question = $questions[$q_count];

            $question_block_id = "q_block_c$cat" . "_q$q_count";
            $block_visibility = "";
            if ($q_count > $num_questions-1){
                $block_visibility = "style='display: none'";
            }
            $display_block .= "<div  id='$question_block_id' $block_visibility>\n";

            $display_block .= "<span class='feedbackQ'>Question $q_count:\n";
            $qid = "q" . $cat . "-" . $q_count;
            $rid = "res" . $cat . "-" . $q_count;
            $qwid = "qw" . $cat . "-" . $q_count;
            $display_block .= "<span class='feedbackQem'>
           <input class='feedbackQem' type='text' id='$qid' name='$qid' value='$question' size='120'>
           </span></span><br />\n";
            $display_block .= "<div class='options'>\n";
            $display_block .= "Number of responses <input type='number' id='$rid' name='$rid' value='$num_responses[$q_count]'  min='1' max='11' size='2'><BR>";
            // Display this for both types of questions
            //if($is_numeric) {
            $remark = "";
            if($is_numeric){
                $remark = "";
            }else{
                $remark = "(As your marking response is of phrasal selection type, this value should <em>always</em> be 100.)";
            }
                $display_block .= "Fraction of mark awarded (%)<input type='number' id='$qwid' name='$qwid' value='$q_weights[$q_count]'  min='0' max='100' step='1' size='4'> $remark<BR>";
            //}else{
            //    // same but hidden
            //    $display_block .= "<input type='hidden' id='$qwid' name='$qwid' value='$q_weights[$q_count]'  min='0' max='100' step='1' size='4'>";
            //}
            for ($r_count = 1; $r_count <= $num_responses[$q_count]; $r_count++) {
                $pchoice = "q" . $cat . "-" . $q_count;
                $pchoice_id = "id" . $pchoice . $r_count;
                $mchoice = "m" . $cat . "-" . $q_count . $r_count;
                $mchoice_id = "id" . $mchoice;
                $tchoice = "t" . $cat . "-" . $q_count . $r_count;
                $tchoice_id = "id" . $tchoice;
                $rchoice = "r" . $cat . "-" . $q_count;
                $response_text = $response_texts[$q_count][$r_count];
                $response_mark = $response_marks[$q_count][$r_count];
                $ischecked = "";
                if ($r_count == $num_responses[$q_count]) {
                    //$ischecked = "checked";
                }
                $q_block_id = "qb_id_" . $tchoice;
                $display_block .= "<span id='$q_block_id'>";
                $display_block .= "\n$r_count: <input type='text' id='$tchoice_id' name='$tchoice' value='$response_text' size='60'>";
                $display_block .= "\n<input type='number' id='$mchoice_id' name='$mchoice' value='$response_mark'  min='-1000' max='1000' size='5'>";
                $display_block .= "\n<input type='radio' id='$pchoice_id' name='$rchoice' value='$response_mark' $ischecked> <BR>\n";
                $display_block .= "</span>";
            }
            // AND THE HIDDEN ONES
            for ($r_count = $num_responses[$q_count]+1; $r_count <= $max_num_responses; $r_count++) {
                $pchoice = "q" . $cat . "-" . $q_count;
                $pchoice_id = "id" . $pchoice . $r_count;
                $mchoice = "m" . $cat . "-" . $q_count . $r_count;
                $mchoice_id = "id" . $mchoice;
                $tchoice = "t" . $cat . "-" . $q_count . $r_count;
                $tchoice_id = "id" . $tchoice;
                $rchoice = "r" . $cat . "-" . $q_count;
                $response_text = $response_texts[$q_count][$r_count];
                $response_mark = $response_marks[$q_count][$r_count];
                $ischecked = "";
                if ($r_count == $num_responses[$q_count]) {
                    //$ischecked = "checked";
                }
                $q_block_id = "qb_id_" . $tchoice;
                $block_visibility = "";
                if ($r_count > $num_responses[$q_count]){
                    $block_visibility = "style='display: none'";
                }
                $display_block .= "<span id='$q_block_id' $block_visibility>";
                $display_block .= "\n$r_count: <input type='text' id='$tchoice_id' name='$tchoice' value='$response_text' size='60'>";
                $display_block .= "\n<input type='number' id='$mchoice_id' name='$mchoice' value='$response_mark'  min='-1000' max='1000' size='5'>";
                $display_block .= "\n<input type='radio' id='$pchoice_id' name='$rchoice' value='$response_mark' $ischecked> <BR>\n";
                $display_block .= "</span>";
            }
            $display_block .= "<BR>";
            $display_block .= "</DIV>\n";// end of options
            $display_block .= "</DIV>\n";// end of questionblock
        }
        $display_block .= "</td>\n</tr>\n";

        $display_block .= "<tr><td>Would you like this section to require written feedback from the marker? Check the box for feedback.";
        $section_feedback = "";
        if($show_cat_feedback_flag == 1){
            $section_feedback = "checked";
        }
        $display_block .= "<input type='checkbox' id='show_cat_feedback$cat' name='show_cat_feedback$cat' value='show_cat_feedback$cat' $section_feedback> <br><br>\n";

        $display_block .= "<tr><td><span class='feedbackSectionMarkText'>Mark for section $cat:
                          <span id=sectionmarktext$cat> </span></span>";
        $display_block .= "        <span class='feedbackSectionMark' id=sectionmark$cat> </span></td></tr>";

        $display_block .= "</table></div>\n";

        return $display_block;
    }// end of show_section_questions2()

    // definitions for each part
    // Questions table
    $assessqs_year = $assessqs[$assesspart];
    // Questions categories table
    $assess_cats = $assessqs_cats[$assesspart];
    // description
    $assesstype_text = $p_description[$assesspart];
    // markbook
    $mark_max = $p_max[$assesspart];
    $mark_pc = $p_percent[$assesspart];
    $is_numeric = $p_numeric[$assesspart];


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

    if ($POST_OP == "commit") {
        $display_block .= "<h2>Submitting the changes to the database ...</H2>";

        $num_categories = $_POST["new_num_cats"];
        // the insert to the database code goes here

        // Get the  data from the form

        for ($cat = 1; $cat <= $num_categories; $cat++) {
            $id = "sec" . $cat;
            $a_section_q[$cat] = addslashes($_POST[$id]);
            $id = "weightpc_name" . $cat;
            $a_section_weight[$cat] = $_POST[$id];
            $id = "factor_name" . $cat;
            $a_section_factor[$cat] = $_POST[$id];

            $id = "numqs" . $cat;
            $numqs = $_POST[$id];

            $id = "new_numqs$cat";
            $new_numqs = $_POST[$id];
            $new_numqs = $new_numqs + 1;

            $a_num_questions[$cat]=$new_numqs-1;

            for ($i = 1; $i < $new_numqs; $i++) {
                $id = "q$cat-$i";
                $question = addslashes($_POST[$id]);
                $a_questions[$cat][$i] = $question;

                $id = "res$cat-$i";
                $num_responses = $_POST[$id];

                $a_num_responses[$cat][$i] = $num_responses;

                $id = "qw$cat-$i";
                $q_weight = $_POST[$id];
                $a_q_weights[$cat][$i] = $q_weight;

                $query_entry = "";
                unset($mark);
                $mark = array();
                unset($text);
                $text = array();

                for ($r = 1; $r <= $num_responses; $r++) {
                    $id = "t$cat-$i$r";
                    $text[$r] = addslashes($_POST[$id]);
                    $id = "m$cat-$i$r";
                    $mark[$r] = $_POST[$id];
                    $query_entry .= ", text$r='" . $text[$r] . "', mark$r" . "='" . $mark[$r] . "'\n";
                }
                $a_question_texts[$cat][$i] = $text;
                $a_question_marks[$cat][$i] = $mark;
            }
            // Show feedback
            $show_feedback_flag = 1;
            $show_feedback_id = "show_cat_feedback$cat";
            if (array_key_exists($show_feedback_id, $_POST) == false) {
                // then it is not checked so feedback is NOT wanted
                $show_feedback_flag = 0;
            }
            $a_section_feedback[$cat] = $show_feedback_flag;
        }



        // Delete the data in the two table and then add the new data
        $query = "TRUNCATE $assess_cats";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $query = "TRUNCATE $assessqs_year";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        // Now put all the data in
        for($cat = 1;  $cat <= $num_categories ; $cat++){
            // write the cat index , lable , weight, factor to table
            $query = "ALTER TABLE $assess_cats ADD `get_feedback` int DEFAULT 1";
            if(!DoesColumnExistInTable($db,$assess_cats,'get_feedback')) {
                $db->query($query);
            } else {
            }
            $query = "INSERT $assess_cats SET
                         cat='$cat',
                         description='$a_section_q[$cat]',
                         weight_percent='$a_section_weight[$cat]',
                         factor='$a_section_factor[$cat]',
                         get_feedback='$a_section_feedback[$cat]'
                         ;";

            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }

            // Now the questions and responses
            for($q = 1 ; $q <= $a_num_questions[$cat] ; $q++) {
                $query = "INSERT $assessqs_year SET";
                $query .= "  cat='$cat'";
                $query .= ", qid='$q'";
                $query .= ", question='" . $a_questions[$cat][$q] . "'";
                $query .= ", entries='". $a_num_responses[$cat][$q] . "'";
                $query .= ", weight='". $a_q_weights[$cat][$q] . "'";
                for ($r = 1; $r <= $a_num_responses[$cat][$q]; $r++) {
                    $question_text = $a_question_texts[$cat][$q][$r];
                    $question_mark = $a_question_marks[$cat][$q][$r];
                    $query .= ", text$r='$question_text', mark$r='$question_mark' ";
                }
                $query .= ";";

                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
            }

            // the feed back row
            $query = "INSERT $assessqs_year SET";
            $query .= "  cat='$cat'";
            $last_num = $a_num_questions[$cat] + 1;
            $query .= ", qid='$last_num'";
            $query .= ", question='Feedback Comments:'";
            $query .= ", entries='0'";

            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
        }


        $display_block .= "<h2>Done ... carry on making changes if you like</H2>";

    }// end of if ($POST_OP == "commit")

// No need to have an else as the rest is always done.

    $display_block .= "<h1>This form allows you to view and edit questions, marks and section weightings.</h1>";
    $display_block .= "<h2>$assesstype_text section of $module_short_description  $module_prefix$module1$module_postfix ($short_code) $year_begin/$year_end</h2>";
    $display_block .= "<p>Questions table: $assessqs_year,  Question Categories: $assess_cats</p>";
    $display_block .= "<div class='feedbackInstructions'>You can use this form <em>play</em> with setting before finally commiting them to the marking system.<BR> \nYou can use it to: \n
<OL>\n
<LI>Change the text of questions (just edit the text in the boxes)</LI>\n
<LI>Change the text of responses</LI>\n
<LI>Change the mark for a response (just change the number)</LI>\n
<LI>Dynamically see the effect of the mark change, both on the section total and the overall total.</LI>\n
<LI>Change proportion of each section, change the <em>weight factor</em> for each section (see below) </LI>\n
<LI>Change the number of responses (max 8). Change the number then commit the form. A new blank row will appear. </LI>\n
<LI>Change the number of Sections (max 5). Change the number then commit the form. The new number of sections will be presented. </LI>\n
<LI>Change the number of Questions in corresponding section (max 10). Change the number then commit the form. The new number of questions will be presented. </LI>\n
</OL>\n

The  maximum 'Mark for Section' should be equal to or less than the 'Proportion of assessment'<BR>
the 'Weight factor' is used achieve this while giving <em>sensible</em> numbers to each question.</div><BR>\n";

    //$display_block .= "<h1>^^^^^ WARNING: Currently this form will only allow editing of a maximum of $max_num_responses_editable responses to questions. ^^^^^</h1>";

    if( $is_numeric == 1){
        $display_block .= "<div style='border: thin solid black; padding: 5px; background - color: #fceda7 '>";
        $display_block .= "<p>This question is defined as <strong>numeric</strong>. <BR>You should define only <strong>one response</strong> to each question and make that text be an indication to the marker of what to <strong>Look for</strong> when marking.</p>";
        $display_block .= "<p>If you don't want numeric questions then go back to <em>Modify module description split and date</em> link from the <a href='./edit_module_settings.php?pm_id=$pm_id'>Module's admin page</a> to change it.</p>";
        $display_block .= "</div>";
    }

    $display_block .= "
    <div id='sticker'>0.0</div>";


    $display_block .= "<form name='qs' method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id&part=$assesspart'>\n";
//This one has the validation
//$display_block .="<form method='post' onsubmit='return validate_form();' action='$_SERVER[PHP_SELF]?module1=$module1&year_begin=$year_begin' '>\n";

    $display_block .= "\n<P>Number of sections (categories) <input type='number' name='new_num_cats'  id='new_num_cats' value='$num_categories' min='1' max='5' size=6 maxlength=5> (Note that questions will be deleted should this number be less that those shown on this form.)</P>\n";

// Get number of categories by counting the rows in $assess_cats
    $query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_categories = $results->rowCount();
//$display_block .="<input type='hidden' name='numcats' value='$num_categories' />";

// enter the max mark and percent of this part for use on the form
    $display_block .= "<span id='markmax_span'>
\n<input type='hidden' id='markmax' name='markmax' value='$mark_max' size='3'></span>";
    $display_block .= "<span id=markpc>
\n<input type='hidden' id='markpc_id' name='markpc' value='$mark_pc' size='3'></span>";
//$display_block .= "\n<span id=min_feedback_words>
//<input type='hidden' id='min_feedback_words_id' name='min_feedback_words' value='$min_feedback_words' size='3'></span>"; 

    $max_num_categories = $g_max_num_cats;
    if($max_num_categories > 5){$max_num_categories = 5;}
    for ($cat = 1; $cat <= $max_num_categories; $cat++) {

        // Get the category titles
        $query = "select * from $assess_cats where cat=$cat";
        //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. <BR>Query: $get_cat_details");
        //$cat_data = mysql_fetch_array($get_cat_details_list);
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $cat_data = $results->fetch();
        $description = $weight_pc = $factor = "";
        if($cat_data){
            $description = $cat_data['description'];
            $weight_pc = $cat_data['weight_percent'];
            $factor = $cat_data['factor'];
            if(array_key_exists('get_feedback',$cat_data)==false){
                $show_cat_feedback = 1;
            }else{
                $show_cat_feedback = $cat_data['get_feedback'];
            }
        }

        if($description == ""){$description = "Enter a new section description";}
        if($weight_pc == ""){$weight_pc = "20";}
        if($factor == ""){$factor = "0.1";}

        // Get the questions for this category
        $query = "select * from $assessqs_year where cat=$cat";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        $num_questions = $results->rowCount();

        for ($q_count = 1; $q_count <= $g_max_num_questions_per_cat; $q_count++) {
            if($q_count < $num_questions) {
                $query = "select * from $assessqs_year where cat=$cat and qid=$q_count";
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

                $questions[$q_count] = $qs_list['question'];
                $num_entries[$q_count] = $qs_list['entries'];
                if($is_numeric){
                    $num_entries[$q_count] = 1;
                }

                $q_weight = $qs_list['weight'];
                if(!is_numeric($q_weight)){
                    $q_weight = 100;
                }
                $q_weights[$q_count] = $q_weight;

                for ($r_count = 1; $r_count <= $num_entries[$q_count]; $r_count++) {
                    $r_text = "text" . $r_count;
                    $response_texts[$q_count][$r_count] = $qs_list[$r_text];
                    $value = $response_texts[$q_count][$r_count];
                    $r_mark = "mark" . $r_count;
                    $response_marks[$q_count][$r_count] = $qs_list[$r_mark];
                    if($is_numeric){
                        $response_marks[$q_count][$r_count] = 100;
                    }
                }
                // and the extra ones to fill the array
                for ($r_count = $num_entries[$q_count]+1; $r_count <= $g_max_num_responses_per_questionXX; $r_count++) {
                    $r_text = "text" . $r_count;
                    $response_texts[$q_count][$r_count] = 'enter a response';
                    $value = $response_texts[$q_count][$r_count];
                    $r_mark = "mark" . $r_count;
                    $response_marks[$q_count][$r_count] = 0;
                }
            }else{
                // Put in some dummy data
                $questions[$q_count] = 'Enter a new question';
                $num_entries[$q_count] = $g_max_num_responses_per_questionXX;
                $q_weights[$q_count] = 100;

                for ($r_count = 1; $r_count <= $num_entries[$q_count]; $r_count++) {
                    $r_text = "text" . $r_count;
                    $r_text = "text" . $r_count;
                    $response_texts[$q_count][$r_count] = 'enter a response';
                    $value = $response_texts[$q_count][$r_count];
                    $r_mark = "mark" . $r_count;
                    $response_marks[$q_count][$r_count] = ($r_count-1)*10;
                }
            }

        }
        //$show_cat_feedback = 1;
        if($num_questions < 1){$num_questions = 2;}
        $display_block .= show_section_questions2($cat, $description, $weight_pc, $factor, $num_questions, $questions, $num_entries, $q_weights, $response_texts, $response_marks, $g_max_num_questions_per_cat,$g_max_num_responses_per_questionXX, $is_numeric, $show_cat_feedback);
        $display_block .= "<BR>\n";
    }

    $display_block .= "\n<div class='feedbackSection'>Total marks = <span class='feedbackTotalMark' id=totalmarks> </span></div>";
    //$display_block .= "<BR>\n<input type='button' id='validate' name='btn_validate' value='Check all selected' >";
    $display_block .= "\n<input type='hidden' name='op' value='commit'>";
    $display_block .= "\n<input type='hidden' name='nc' value='$num_categories'>";
    $display_block .= "<p><input type='submit' id='submitbutton' name='submit' value='Commit the question changes to database table'></p>
</form>";

// End of main

} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href='../index.php'>Go back here</A><BR>";
}
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Edit questions and test marks scheme</span>
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
