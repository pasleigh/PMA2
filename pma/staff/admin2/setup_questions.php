<?php
// Turn off all error reporting
//error_reporting(0);

// connect to the datbase
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../show_feedback_form.php")) OR die("Cannot get show_feedback_form.php<BR>");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($access) {


    if (array_key_exists('part', $_GET) == false) {
        $_GET['part'] = null;
    }
    $assesspart = $_GET['part'];
//echo("Assessment part = $assesspart<BR>\n");


//if (array_key_exists('module1', $_GET)==false){ $_GET['module1']=null; }
//if (array_key_exists('year_begin', $_GET)==false){ $_GET['year_begin']=null; }
//$module1 = $_GET['module1'];
//$year_begin = $_GET['year_begin'];


// if the form was submitted then enter it all into the database
//echo($_POST['op'] . "<BR>\n");
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }
//echo($POST_OP . "<BR>\n");
// see all the POST values

//foreach ($_POST as $key => $value){
//  echo "{$key} = {$value}<BR>\r\n";
//}
    // Start of functions
    function show_section_questions($cat, $cat_description, $weight_pc, $factor, $num_questions, $questions, $num_responses, $response_texts, $response_marks)
    {
        $display_block = "\n\n<span id=section>";
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
        \n<input type=\"number\" id=\"factor_id$cat\" name=\"factor_name$cat\" value=\"$factor\" min=\"0\" max=\"1\" step=\"0.001\" size=\"4\"></span></div>";
        $display_block .= "</td>\n";
        $display_block .= "</tr>";
        $num_qs = $num_questions - 1;
        $display_block .= "\n<tr><td>Number of questions in this section <input type=\"number\" name=\"new_numqs$cat\" value=\"$num_qs\" min=\"1\" max=\"10\" size=6 maxlength=5> (note that questions will be deleted if this number is less than already exists.)</td></tr>\n";
        $display_block .= "<tr>\n<td>";
        for ($q_count = 1; $q_count < $num_questions; $q_count++) {
            $question = $questions[$q_count];

            $display_block .= "<span class=\"feedbackQ\">Question $q_count:\n";
            $qid = "q" . $cat . "-" . $q_count;
            $rid = "res" . $cat . "-" . $q_count;
            $display_block .= "<span class=\"feedbackQem\">
           <input class=\"feedbackQem\" type=\"text\" id=\"$qid\" name=\"$qid\" value=\"$question\" size=\"120\">
           </span></span><br />\n";
            $display_block .= "<div class=\"options\">\n";
            $display_block .= "Number of responses <input type=\"number\" id=\"$rid\" name=\"$rid\" value=\"$num_responses[$q_count]\"  min=\"3\" max=\"11\" size=\"2\"><BR>";
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
                    $ischecked = "checked";
                }
                $display_block .= "\n<input type=\"text\" id=\"$tchoice_id\" name=\"$tchoice\" value=\"$response_text\" size=\"60\">";
                $display_block .= "\n<input type=\"number\" id=\"$mchoice_id\" name=\"$mchoice\" value=\"$response_mark\"  min=\"-1000\" max=\"1000\" size=\"5\">";
                $display_block .= "\n<input type=\"radio\" id=\"$pchoice_id\" name=\"$rchoice\" value=\"$response_mark\" $ischecked> <BR>\n";
            }
            $display_block .= "<BR>";
            $display_block .= "</DIV>\n";
        }
        $display_block .= "</td>\n</tr>\n";
        $display_block .= "<tr><td><span class=\"feedbackSectionMarkText\">Mark for section $cat:
                          <span id=sectionmarktext$cat> </span></span>";
        $display_block .= "        <span class=\"feedbackSectionMark\" id=sectionmark$cat> </span></td></tr>";

        $display_block .= "</table></span>\n";

        return $display_block;
    }// end of show_section_questions()


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

//echo("num cats = $num_categories <BR>\n");

    if ($POST_OP == "commit") {
        $display_block .= "<h2>Submitting the changes to the database ...</H2>";

        $num_categories = $_POST["new_num_cats"];
        // the insert to the database code goes here
        // write the categories
        // get the categories

        // Get the  data from the form
        for ($cat = 1; $cat <= $num_categories; $cat++) {
            $id = "sec" . $cat;
            $section_q[$cat] = addslashes($_POST[$id]);
            $id = "weightpc_name" . $cat;
            $section_weight[$cat] = $_POST[$id];
            $id = "factor_name" . $cat;
            $section_factor[$cat] = $_POST[$id];
            //echo("Section: $cat : $section_q[$cat]<BR>\n");
            //echo("  weight: $section_weight[$cat]<BR>\n");
            //echo("  factor: $section_factor[$cat]<BR>\n");

            $id = "numqs" . $cat;
            $numqs = $_POST[$id];

            $id = "new_numqs$cat";
            $new_numqs = $_POST[$id];
            $new_numqs = $new_numqs + 1;

            for ($i = 1; $i < $numqs; $i++) {
                $id = "q$cat-$i";
                $question = addslashes($_POST[$id]);
                //echo("$id : $question<BR>");
                //$id = "r$cat-$i";
                //$rmark = $_POST[$id];
                //echo("$id : $rmark<BR>");
                $id = "res$cat-$i";
                $num_entries = $_POST[$id];
                $query_entry = "";
                for ($r = 1; $r <= $num_entries; $r++) {
                    $id = "t$cat-$i$r";
                    $text[$r] = addslashes($_POST[$id]);
                    //echo("$id : $text[$r]<BR>");
                    $id = "m$cat-$i$r";
                    $mark[$r] = $_POST[$id];
                    //echo("$id : $mark[$r]<BR>");
                    $query_entry .= ", text$r='" . $text[$r] . "', mark$r" . "='" . $mark[$r] . "'\n";
                }

                // Input text and marks fo responses into the database
                $query = "update $assessqs_year set
                         question='$question', 
                         entries='$num_entries'
                         $query_entry
                         where qid='$i' and cat='$cat'";
                //echo ("$action <BR>");
                //mysql_query($action) or die(" Unable to add mark data to the table: $assessqs_year. <BR /> Query = $action");
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
                // Input Section title and weight and factor into the database
                $query = "update $assess_cats set
                         description='$section_q[$cat]', 
                         weight_percent='$section_weight[$cat]',
                         factor='$section_factor[$cat]'
                         where cat='$cat'";
                //echo ("$action <BR>");
                //mysql_query($action) or die(" Unable to add mark data to the table: $assess_cats. <BR /> Query = $action");
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

            if ($new_numqs != $numqs) {
                // Need to update the questions
                // This is a bit fiddly
                if ($new_numqs > 11) {
                    $new_numqs = 11;
                }

                $new_numqs1 = $new_numqs + 1;
                $numqs1 = $numqs + 1;

                if ($new_numqs > $numqs) {
                    // Change the "Feedback" entry to this number of Qs plus 1
                    $query = "update $assessqs_year set qid='$new_numqs' where qid='$numqs'";
                    //echo ("$action <BR>");
                    //mysql_query($action) or die(" Unable to change the Feedback qid table : $assessqs_year. <BR /> Query = $action");
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo "An Error occured accessing the database in function: $this_function <BR>\n";
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";

                        exit();
                    }
                    for ($i = $numqs; $i < $new_numqs; $i++) {
                        $query = "insert $assessqs_year set qid='$i', cat='$cat', question='--New question--CHANGE ME--', entries='6',
                     text1='answer 1',mark1='0', text2='answer 2',mark2='10',text3='answer 3',mark3='25',
                     text4='answer 4',mark4='50',text5='answer 5',mark5='60',text6='answer 6',mark6='90'";
                        //echo ("$action <BR>");
                        //mysql_query($action) or die(" Unable to add rows to table : $assessqs_year. <BR /> Query = $action");
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
                }
                if ($new_numqs < $numqs) {
                    // Need to delete some questions
                    for ($i = $new_numqs; $i <= $numqs; $i++) {
                        $query = "DELETE FROM $assessqs_year WHERE qid='$i'";
                        //echo ("$action <BR>");
                        //mysql_query($action) or die(" Unable to delete rows from table : $assessqs_year. <BR /> Query = $action");
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
                    // Insert a feedback row
                    $query = "insert $assessqs_year set qid='$new_numqs', cat='$cat', question='Feedback Comments:', entries='0'";
                    //echo ("$action <BR>");
                    // mysql_query($action) or die(" Unable to change the Feedback qid table : $assessqs_year. <BR /> Query = $action");
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
            }

        }

        $display_block .= "<h2>Done ... carry on making changes if you like</H2>";

    }// end of if ($POST_OP == "commit")

// No need to have an else as the rest is always done.


    $display_block .= "<h1>This form allows you to view and edit questions, marks and section weightings.</h1>";
    $display_block .= "<h2>$assesstype_text section of module $module1, beginning year $year_begin</h2>";
    $display_block .= "<p>Questions table: $assessqs_year,  Question Categories: $assess_cats</p>";
    $display_block .= "<div class=\"feedbackInstructions\">You can use this form <em>play</em> with setting before finally commiting them to the marking system.<BR> \nYou can use it to: \n
<OL>\n
<LI>Change the text of questions (just edit the text in the boxes)</LI>\n
<LI>Change the text of responses</LI>\n
<LI>Change the mark for a response (just change the number)</LI>\n
<LI>Dynamically see the effect of the mark change, both on the section total and the overall total.</LI>\n
<LI>Change proportion of each section, change the <em>weight factor</em> for each section (see below) </LI>\n
<LI>Change the number of responses (max 6). Change the number then commit the form. A new blank row will appear. </LI>\n
<LI>Change the number of Sections (max 5). Change the number then commit the form. The new number of sections will be presented. </LI>\n
<LI>Change the number of Questions in corresponding section (max 10). Change the number then commit the form. The new number of questions will be presented. </LI>\n
</OL>\n

The  maximum \"Mark for Section\" should be equal to or less than the \"Proportion of assessment\"<BR>
the \"Weight factor\" is used achieve this while giving <em>sensible</em> numbers to each question.</div><BR>\n";

    $display_block .= "
    <div id=\"sticker\">0.0</div>";

    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id&part=$assesspart\" \">\n";
//This one has the validation
//$display_block .="<form method=\"post\" onsubmit=\"return validate_form();\" action=\"$_SERVER[PHP_SELF]?module1=$module1&year_begin=$year_begin\" \">\n";

    $display_block .= "\n<P>Number of sections (categories) <input type=\"number\" id=\"new_num_cats\" name=\"new_num_cats\" value=\"$num_categories\" min=\"1\" max=\"5\" size=6 maxlength=5> (Note that questions will be deleted should this number be less that those shown on this form.)</P>\n";

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
//$display_block .="<input type=\"hidden\" name=\"numcats\" value=\"$num_categories\" />";

// enter the max mark and percent of this part for use on the form
    $display_block .= "<span id=\"markmax_span\">
\n<input type=\"hidden\" id=\"markmax\" name=\"markmax\" value=\"$mark_max\" size=\"3\"></span>";
    $display_block .= "<span id=markpc>
\n<input type=\"hidden\" id=\"markpc_id\" name=\"markpc\" value=\"$mark_pc\" size=\"3\"></span>";
//$display_block .= "\n<span id=min_feedback_words>
//<input type=\"hidden\" id=\"min_feedback_words_id\" name=\"min_feedback_words\" value=\"$min_feedback_words\" size=\"3\"></span>"; 

    for ($cat = 1; $cat <= $num_categories; $cat++) {

        // Get the category titles
        $query = "select description, weight_percent, factor from $assess_cats where cat=$cat";
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
        $weight_pc = $cat_data['weight_percent'];
        $factor = $cat_data['factor'];

        // Get the questions for this category
        $query = "select * from $assessqs_year where cat=$cat";
        //$get_list_qstot = mysql_query($get_qstot) or die(" Unable to create list from $assessqs_year table. <BR>Query: $get_qstot");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        $num_questions = $results->rowCount();//$get_list_qstot);

        for ($q_count = 1; $q_count < $num_questions; $q_count++) {
            $query = "select * from $assessqs_year where cat=$cat and qid=$q_count";
            //echo("$get_qs<BR>");
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

            $questions[$q_count] = $qs_list['question'];
            $num_entries[$q_count] = $qs_list['entries'];

            for ($r_count = 1; $r_count <= $num_entries[$q_count]; $r_count++) {
                $r_text = "text" . $r_count;
                $response_texts[$q_count][$r_count] = $qs_list[$r_text];
                $value = $response_texts[$q_count][$r_count];
                //echo("$q_count-$r_count $value<br>");
                $r_mark = "mark" . $r_count;
                $response_marks[$q_count][$r_count] = $qs_list[$r_mark];
            }

        }
        //print_r($questions);
        $display_block .= show_section_questions($cat, $description, $weight_pc, $factor, $num_questions, $questions, $num_entries, $response_texts, $response_marks);
        $display_block .= "<BR>\n";
    }


    $display_block .= "\n<div class=\"feedbackSection\">Total marks = <span class=\"feedbackTotalMark\" id=totalmarks> </span></div>";
    $display_block .= "<BR>\n<input type=\"button\" id=\"validate\" name=\"btn_validate\" value=\"Check all selected\" >";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"commit\">";
    $display_block .= "\n<input type=\"hidden\" name=\"nc\" value=\"$num_categories\">";
    $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Commit the changes to the database\"></p></form>";


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
            $("#section input[type='radio']").click(function () {
                var section = Number($(this).attr('name').substring(1, 2));
                //alert("Section: " + section);

                var mark_id = "idm" + ((this).id).substring(3);
                //alert("markid: " + mark_id);
                $("#sectionmark" + section).html($("#" + mark_id).val());

                var section_total = 0.0;
                $("#table" + section + " input[type='radio']:checked").each(function () {
                    //alert($(this).val());
                    section_total += Number($(this).val());
                    //alert(section_total);
                });

                var factor = $("#factor_id" + section).val();
                $("#sectionmarktext" + section).html("(" + factor + " x " + section_total + " = )");
                $("#sectionmark" + section).html((factor * section_total).toFixed(2));

                SumSections();
            });


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
            //alert("Num not seleectd = " + num_not_selected );
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
