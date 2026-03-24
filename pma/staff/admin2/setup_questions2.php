<?php
// Turn off all error reporting
//error_reporting(0);
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

    $debug = false;
    if (array_key_exists('d', $_GET) == true || array_key_exists('debug', $_GET) == true ) {
        $debug = true;
    }
    //echo("debug =$debug.<BR>");

    // double debug - shows all ofthe _REQUEST array and new table dump
    $ddebug = false;
    if (array_key_exists('dd', $_GET) == true || array_key_exists('ddebug', $_GET) == true ) {
        $ddebug = true;
        $debug = true;
    }

    if($debug){
        //echo("<pre> \n _Request \n");
        //var_dump($_REQUEST);
        //echo("</pre>");
        echo("Assessment part = $assesspart<BR>\n");
    }


// if the form was submitted then enter it all into the database

    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }

    if($ddebug) {
        echo("All of the _REQUEST variables<BR>");
        echo("The number of _REQUEST variables is: " . sizeof($_REQUEST) . "<BR>\n");
        foreach ($_REQUEST as $key => $value) {
            echo "{$key} = {$value}<BR>\r\n";
        }
        echo("End of the _REQUEST variables<BR><BR>");
    }

    // Start of functions
    function show_section_questions2($cat, $cat_description, $weight_pc, $factor, $num_questions, $questions, $num_responses, $q_weights, $response_texts, $response_marks, $max_num_questions, $max_num_responses, $is_numeric, $show_cat_feedback_flag)
    {
        $display_block = "\n\n<div name='section' id=\"section_block$cat\">";
        $display_block .= "<table id=table$cat class=\"tableSection\">";
        $display_block .= "<tr>\n";

        // Display the category title
        $sectionid = "sec" . $cat;
        $display_block .= "<td><div class=\"feedbackSection\">Section $cat:
        \n<input  class=\"feedbackSectionQ\" type=\"text\" id=\"$sectionid\" name=\"$sectionid\" value=\"$cat_description\" size=\"60\">
        \n<input type=\"hidden\" id=\"numqs$cat\" name=\"numqs$cat\" value=\"$num_questions\">
        </div>\n";
        $display_block .= "<div class=\"feedbackWeights\">Proportion of assessment: <span id=weightpc$cat>
        \n<input type=\"number\" id=\"weightpc_id$cat\" name=\"weightpc_name$cat\" value=\"$weight_pc\" min=\"0\" max=\"100\" size=\"3\">%. </span>";
        $display_block .= "Weight factor <span id=factor$cat>
        \n<input type=\"number\" id=\"factor_id$cat\" name=\"factor_name$cat\" value=\"$factor\" min=\"0\" max=\"1\" step=\"0.0001\" size=\"4\"></span></div>";
        $display_block .= "</td>\n";
        $display_block .= "</tr>";
        $num_qs = $num_questions - 1;
        $display_block .= "\n<tr><td>Number of questions in this section <input type=\"number\" id=\"new_numqs$cat\" name=\"new_numqs$cat\" value=\"$num_qs\" min=\"1\" max=\"10\" size=6 maxlength=5> (note that questions will be deleted if this number is less than already exists.)</td></tr>\n";
        $display_block .= "<tr>\n<td>";
        //print_r($questions);
        for ($q_count = 1; $q_count <= $max_num_questions; $q_count++) {
            $question = $questions[$q_count];

            $question_block_id = "q_block_c$cat" . "_q$q_count";
            $block_visibility = "";
            if ($q_count > $num_questions-1){
                $block_visibility = "style=\"display: none\"";
            }
            $display_block .= "<div  id=\"$question_block_id\" $block_visibility>\n";

            $display_block .= "<span class=\"feedbackQ\">Question $q_count:\n";
            $qid = "q" . $cat . "-" . $q_count;
            $rid = "res" . $cat . "-" . $q_count;
            $qwid = "qw" . $cat . "-" . $q_count;
            $display_block .= "<span class=\"feedbackQem\">
           <input class=\"feedbackQem\" type=\"text\" id=\"$qid\" name=\"$qid\" value=\"$question\" size=\"120\">
           </span></span><br />\n";
            $display_block .= "<div class=\"options\">\n";
            $display_block .= "Number of responses <input type=\"number\" id=\"$rid\" name=\"$rid\" value=\"$num_responses[$q_count]\"  min=\"1\" max=\"11\" size=\"2\"><BR>";
            // Display this for both types of questions
            //if($is_numeric) {
            $remark = "";
            if($is_numeric){
                $remark = "";
            }else{
                $remark = "(As your marking response is of phrasal selection type, this value should <em>always</em> be 100.)";
            }
                $display_block .= "Fraction of mark awarded (%)<input type=\"number\" id=\"$qwid\" name=\"$qwid\" value=\"$q_weights[$q_count]\"  min=\"0\" max=\"100\" step=\"1\" size=\"4\"> $remark<BR>";
            //}else{
            //    // same but hidden
            //    $display_block .= "<input type=\"hidden\" id=\"$qwid\" name=\"$qwid\" value=\"$q_weights[$q_count]\"  min=\"0\" max=\"100\" step=\"1\" size=\"4\">";
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
                $display_block .= "<span id=\"$q_block_id\">";
                $display_block .= "\n$r_count: <input type=\"text\" id=\"$tchoice_id\" name=\"$tchoice\" value=\"$response_text\" size=\"60\">";
                $display_block .= "\n<input type=\"number\" id=\"$mchoice_id\" name=\"$mchoice\" value=\"$response_mark\"  min=\"-1000\" max=\"1000\" size=\"5\">";
                $display_block .= "\n<input type=\"radio\" id=\"$pchoice_id\" name=\"$rchoice\" value=\"$response_mark\" $ischecked> <BR>\n";
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
                    $block_visibility = "style=\"display: none\"";
                }
                $display_block .= "<span id=\"$q_block_id\" $block_visibility>";
                $display_block .= "\n$r_count: <input type=\"text\" id=\"$tchoice_id\" name=\"$tchoice\" value=\"$response_text\" size=\"60\">";
                $display_block .= "\n<input type=\"number\" id=\"$mchoice_id\" name=\"$mchoice\" value=\"$response_mark\"  min=\"-1000\" max=\"1000\" size=\"5\">";
                $display_block .= "\n<input type=\"radio\" id=\"$pchoice_id\" name=\"$rchoice\" value=\"$response_mark\" $ischecked> <BR>\n";
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
        $display_block .= "<input type=\"checkbox\" id=\"show_cat_feedback$cat\" name=\"show_cat_feedback$cat\" value=\"show_cat_feedback$cat\" $section_feedback> <br><br>\n";

        $display_block .= "<tr><td><span class=\"feedbackSectionMarkText\">Mark for section $cat:
                          <span id=sectionmarktext$cat> </span></span>";
        $display_block .= "        <span class=\"feedbackSectionMark\" id=sectionmark$cat> </span></td></tr>";

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

    if($debug) {
        echo("Assess_cats table = $assess_cats<BR>\n");
        echo("Assessqs_year table = $assessqs_year<BR>\n");
        echo("Assesstype_text table = $assesstype_text<BR>\n");
        echo("max mark for this part = $mark_max<BR>");
        echo("percent of final assesment = $mark_pc<BR>");
    }

    // Get number of categories by counting the rows in $assess_cats
    $query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_categories = $results->rowCount();


    if ($POST_OP == "commit") {
        $display_block .= "<h2>Submitting the changes to the database ...</H2>";

        $num_categories = $_POST["new_num_cats"];
        // the insert to the database code goes here
        if($debug) {
            echo("num cats = $num_categories<BR>");
        }
        // Get the  data from the form

        for ($cat = 1; $cat <= $num_categories; $cat++) {
            $id = "sec" . $cat;
            $a_section_q[$cat] = addslashes($_POST[$id]);
            $id = "weightpc_name" . $cat;
            $a_section_weight[$cat] = $_POST[$id];
            $id = "factor_name" . $cat;
            $a_section_factor[$cat] = $_POST[$id];

            if($debug) {
                echo("Section: $cat : $a_section_q[$cat]<BR>\n");
                echo("  weight: $a_section_weight[$cat]<BR>\n");
                echo("  factor: $a_section_factor[$cat]<BR>\n");
            }
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
            //print_r3('$show_feedback_id',$show_feedback_id);
            //print_r3('$_POST[$show_feedback_id]',$_POST[$show_feedback_id]);
            if (array_key_exists($show_feedback_id, $_POST) == false) {
                // then it is not checked so feedback is NOT wanted
                $show_feedback_flag = 0;
            }
            $a_section_feedback[$cat] = $show_feedback_flag;
        }

        if($debug) {
            print_r3("a_section_q", $a_section_q);
            print_r3("a_questions", $a_questions);
            print_r3("a_section_weight", $a_section_weight);
            print_r3("a_section_factor", $a_section_factor);
            print_r3("a_section_feedback", $a_section_feedback);
            print_r3("a_num_questions", $a_num_questions);
            print_r3("a_num_responses", $a_num_responses);
            print_r3("a_q_weights", $a_q_weights);
            print_r3("a_question_texts", $a_question_texts);
            print_r3("a_question_marks", $a_question_marks);

        }

        // Delete the data in the two table and then add the new data
        $query = "TRUNCATE $assess_cats";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");

            exit();
        }
        $query = "TRUNCATE $assessqs_year";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");

            exit();
        }
        // Now put all the data in
        for($cat = 1;  $cat <= $num_categories ; $cat++){
            // write the cat index , lable , weight, factor to table
            $query = "ALTER TABLE $assess_cats ADD `get_feedback` int DEFAULT 1";
            if(!DoesColumnExistInTable($db,$assess_cats,'get_feedback')) {
                $db->query($query);
            } else {
                //echo("\nWarning $assess_cats 'get_feedback' already exists\n");
            }
            $query = "INSERT $assess_cats SET
                         cat='$cat',
                         description='$a_section_q[$cat]',
                         weight_percent='$a_section_weight[$cat]',
                         factor='$a_section_factor[$cat]',
                         get_feedback='$a_section_feedback[$cat]'
                         ;";
            if($debug){
                echo("$query <BR>");
            };
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");

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
                if($debug){
                    echo("$query <BR>");
                };
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    echo(" Query = $query<BR> \n");
                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");

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
            if($debug){
                echo("$query <BR>");
            };
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");

                exit();
            }
        }

        if($ddebug){
            echo("<BR><b>Dump of the questions table $assessqs_year</B><BR>");
            echo("<PRE>");
            $query = "SELECT * FROM $assessqs_year";
            $results = $db->query($query)->fetchAll();
            var_export($results);
            echo("</PRE>");

            echo("<BR><B>Dump of the question sections (cats) table $assess_cats</B><BR>");
            echo("<PRE>");
            $query = "SELECT * FROM $assess_cats";
            $results = $db->query($query)->fetchAll();
            var_export($results);
            echo("</PRE>");

        }
        $display_block .= "<h2>Done ... carry on making changes if you like</H2>";

    }// end of if ($POST_OP == "commit")

// No need to have an else as the rest is always done.


    $display_block .= "<h1>This form allows you to view and edit questions, marks and section weightings.</h1>";
    $display_block .= "<h2>$assesstype_text section of $module_short_description  $module_prefix$module1$module_postfix ($short_code) $year_begin/$year_end</h2>";
    $display_block .= "<p>Questions table: $assessqs_year,  Question Categories: $assess_cats</p>";
    $display_block .= "<div class=\"feedbackInstructions\">You can use this form <em>play</em> with setting before finally commiting them to the marking system.<BR> \nYou can use it to: \n
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

The  maximum \"Mark for Section\" should be equal to or less than the \"Proportion of assessment\"<BR>
the \"Weight factor\" is used achieve this while giving <em>sensible</em> numbers to each question.</div><BR>\n";

    //$display_block .= "<h1>^^^^^ WARNING: Currently this form will only allow editing of a maximum of $max_num_responses_editable responses to questions. ^^^^^</h1>";

    if( $is_numeric == 1){
        $display_block .= "<div style=\"border: thin solid black; padding: 5px; background - color: #fceda7 \">";
        $display_block .= "<p>This question is defined as <strong>numeric</strong>. <BR>You should define only <strong>one response</strong> to each question and make that text be an indication to the marker of what to <strong>Look for</strong> when marking.</p>";
        $display_block .= "<p>If you don't want numeric questions then go back to <em>Modify module description split and date</em> link from the <a href='./edit_module_settings.php?pm_id=$pm_id'>Module's admin page</a> to change it.</p>";
        $display_block .= "</div>";
    }

    $display_block .= "
    <div id=\"sticker\">0.0</div>";

    if($debug){
        $debug_call = '&d';
    }else{
        $debug_call = '';
    }
    if($ddebug){
        // double debug - shows all ofthe _REQUEST array
        $debug_call = '&dd';
    }
    $display_block .= "<form name='qs' method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id&part=$assesspart$debug_call'>\n";
//This one has the validation
//$display_block .="<form method=\"post\" onsubmit=\"return validate_form();\" action=\"$_SERVER[PHP_SELF]?module1=$module1&year_begin=$year_begin\" \">\n";

    $display_block .= "\n<P>Number of sections (categories) <input type=\"number\" name=\"new_num_cats\"  id=\"new_num_cats\" value=\"$num_categories\" min=\"1\" max=\"5\" size=6 maxlength=5> (Note that questions will be deleted should this number be less that those shown on this form.)</P>\n";

// Get number of categories by counting the rows in $assess_cats
    $query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_categories = $results->rowCount();
//$display_block .="<input type=\"hidden\" name=\"numcats\" value=\"$num_categories\" />";

// enter the max mark and percent of this part for use on the form
    $display_block .= "<span id=\"markmax_span\">
\n<input type=\"hidden\" id=\"markmax\" name=\"markmax\" value=\"$mark_max\" size=\"3\"></span>";
    $display_block .= "<span id=markpc>
\n<input type=\"hidden\" id=\"markpc_id\" name=\"markpc\" value=\"$mark_pc\" size=\"3\"></span>";
//$display_block .= "\n<span id=min_feedback_words>
//<input type=\"hidden\" id=\"min_feedback_words_id\" name=\"min_feedback_words\" value=\"$min_feedback_words\" size=\"3\"></span>"; 

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
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");

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
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");

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
                    echo(" Query = $query<BR> \n");
                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");

                    exit();
                }

                $qs_list = $results->fetch();

                $questions[$q_count] = $qs_list['question'];
                $num_entries[$q_count] = $qs_list['entries'];
                if($is_numeric){
                    $num_entries[$q_count] = 1;
                }

                $q_weight = $qs_list['weight'];
                //echo("q weight $qweight<BR>");
                if(!is_numeric($q_weight)){
                    $q_weight = 100;
                }
                //echo("q weight $qweight<BR>");
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
        //echo("<pre>");
        //print_r($response_texts);
        //echo("</pre>");
        //$show_cat_feedback = 1;
        if($num_questions < 1){$num_questions = 2;}
        $display_block .= show_section_questions2($cat, $description, $weight_pc, $factor, $num_questions, $questions, $num_entries, $q_weights, $response_texts, $response_marks, $g_max_num_questions_per_cat,$g_max_num_responses_per_questionXX, $is_numeric, $show_cat_feedback);
        $display_block .= "<BR>\n";
    }


    $display_block .= "\n<div class=\"feedbackSection\">Total marks = <span class=\"feedbackTotalMark\" id=totalmarks> </span></div>";
    //$display_block .= "<BR>\n<input type=\"button\" id=\"validate\" name=\"btn_validate\" value=\"Check all selected\" >";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"commit\">";
    $display_block .= "\n<input type=\"hidden\" name=\"nc\" value=\"$num_categories\">";
    $display_block .= "<p><input type='submit' id='submitbutton' name=\"submit\" value=\"Commit the question changes to database table\"></p>
</form>";


// End of main


} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <?php
    (@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
    ?>

    <script>
        $(document).ready(function () {
            DisplaySections();
            SumSections();
            var s = $("#sticker");
            var pos = s.position();
            $(window).scroll(function () {
                var windowpos = $(window).scrollTop();
                //s.html("Distance from top:" + pos.top + "<br />Scroll position: " + windowpos);
                if (windowpos >= pos.top) {
                    s.addClass("stick");
                } else {
                    s.removeClass("stick");
                }
            });
            $("[name='section'] input[type='radio']").click(function () {
                var section = Number($(this).attr('name').substring(1, 2));
                var question_num = Number($(this).attr('name').substring(3));
                //alert("Section: " + section);
                //alert("Question num: " + question_num);
                var q_weight_id = "qw" + String(section) + "-" + String(question_num);
                var q_weight = $("#" + q_weight_id).val();
                q_weight *= 0.01;
                //alert("Q: " + question_num + " weight: " + q_weight);

                var mark_id = "idm" + ((this).id).substring(3);
                var mark_id_val = $("#" + mark_id).val();
                var mark_val = mark_id_val * q_weight;
                //alert("markid: " + mark_id + " val: " + mark_id_val);
                //$("#sectionmark" + section).html(mark_id_val);

                var section_total = 0.0;
                $("#table" + section + " input[type='radio']:checked").each(function () {
                    //alert($(this).val());
                    var question_val = $(this).val();
                    question_num = Number($(this).attr('name').substring(3));
                    q_weight_id = "qw" + String(section) + "-" + String(question_num);
                    q_weight = $("#" + q_weight_id).val();
                    question_val = question_val * q_weight * 0.01;
                    section_total += Number(question_val);

                    //alert(section_total);
                });

                var factor = $("#factor_id" + section).val();
                $("#sectionmarktext" + section).html("(" + factor + " x " + section_total + " = )");
                $("#sectionmark" + section).html((factor * section_total).toFixed(2));

                SumSections();
            });

            function DisplaySections(){
                var i;
                var max_num_cats = 5;
                var num_cats = $("#new_num_cats").val();
                for( i = 1 ; i <= max_num_cats; i++){
                    var my_id = "#section_block" + Number(i).toString();
                    if(i > num_cats){
                        // hide
                        $(my_id).hide();
                        // uncheck the associated radio buttons
                    }else{
                        // show
                        $(my_id).show();
                    }
                }
            }
            function SumSections() {
                var markpc = $("#markpc_id").val();
                var markmax = Number($("#markmax").val());

                var mark1 = Number($("#sectionmark1").text());
                var mark2 = Number($("#sectionmark2").text());
                var mark3 = Number($("#sectionmark3").text());
                var mark4 = Number($("#sectionmark4").text());
                var mark5 = Number($("#sectionmark5").text());
                //$("#totalmarks").html((mark1+mark2+mark3+mark4+mark5).toFixed(2));
                $("#totalmarks").html(((mark1 + mark2 + mark3 + mark4 + mark5) * 100 / markmax).toFixed(1) + "%");
                $("#sticker").html(((mark1 + mark2 + mark3 + mark4 + mark5) * 100 / markmax).toFixed(1) + "%");
            }

            $("#validate").click(function () {
                var num = CheckAllSelected();
                if (num > 0) {
                    alert(num + " questions have not been given responses. Fix this.");
                }
                //validate_form();
            });

            $("#new_num_cats").change(function () {
                DisplaySections()
            });

            $("input[id^=new_numqs").change(function () {
                // Hide / show the question block
                var i;
                var max_num_qs = 10;
                //alert(this.name + "value " + this.value)
                //q_block_c$cat" . "_q$q_count
                var num_qs = this.value;
                var section = this.name.substring(9, this.name.length);
                for( i = 1; i <= max_num_qs; i++)
                {
                    var my_id = "#q_block_c" + Number(section).toString() + "_q" + Number(i).toString();
                    if(i > num_qs){
                        // hide
                        $(my_id).hide();
                        // Need to uncheck all of the radio buttons.
                        // radio group name
                        var radio_group_name  = "r" + section + "-"  + i;
                        var selector = "input:radio[name=" + radio_group_name + "]";
                        $(selector).each(function(){
                            this.checked = false;
                        })
                    }else{
                        // show
                        $(my_id).show();
                    }
                }
                //var section = Number(this.name).substring(9, this.name.length));
                //alert(section);
            });

            $("input[id^=res").change(function () {
                //alert(this.name + "value " + this.value)
                var max_num_responses = 11;
                var section = this.name.substring(3, 4);
                var q_number = this.name.substring(5, this.name.length);
                var q_count = this.value;
                //alert("name: " + this.name + " value: " + this.value + " section: " + section + " question no.: " + q_number)
                //$tchoice = "t" . $cat . "-" . $q_count . $r_count;
                var q_block_id = "#qb_id_t" + section + "-" + q_number;
                for(var i = 1; i <= max_num_responses; i++)
                {
                    var my_id = q_block_id + Number(i).toString();
                    if(i > q_count){
                        // hide
                        $(my_id).hide();
                    }else{
                        // show
                        $(my_id).show();
                    }
                }
            })
            function SubmitChangesToDatabase() {

                return true;
            }
        });
        function CheckAllSelected() {
            //alert("Checking for unclicked radio buttons");
            var groups = {};

            $(":radio").each(function () {
                groups[this.name] = true;
                //alert("Group:" + this.name);
            });
            //alert("Num Groups: " + Object.keys(groups).length);

            var num_not_selected = 0;

            for (group in groups) {
                var radioButttons = $(":radio[name=" + group + "]:checked");
                isChecked = !!radioButttons.length;
                if (!isChecked) {
                    // change the color of the question. It happens to have the same name
                    //alert(group);
                    $(":text[name=q" + group.substring(1) + "]").css("background-color", "pink");
                    num_not_selected++;
                }
                else {
                    $(":text[name=q" + group.substring(1) + "]").css("background-color", "white");
                }
            }

            return num_not_selected;
        }
        function validate_form() {
            //alert("validating form");
            var num_not_selected = Number(CheckAllSelected());
            //alert("Num not selected = " + num_not_selected );
            //return false;
            if (num_not_selected == 0) {
                alert("All done.");
                return true;
            } else {
                alert(num_not_selected + " questions still need to have responses ticked.");
            }
            return false;

        }
    </script>
</HEAD>
<BODY link="#0000FF" vlink="#800080">
<div id="wrapper">
    <div id="header">
        <span class="header">Edit questions and test marks scheme</span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
        <P>Return to the
            <a href="../projectmarking_select.php?pm_id=<?php echo("$pm_id") ?>">Module page</a> -
            <a href="../session_modules.php?year_begin= <?php echo("$year_begin") ?>">Year index page</a> -
            <a href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>">Module admin page</a> -
            <a href="./index.php">Main admin page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
