<?php
/**
 * Generate the HTML feedback report for a single student's assessed part.
 *
 * Builds a complete feedback display including the student/marker header table,
 * per-category question-level marks and comments, and the overall mark summary.
 * The returned HTML string is used both for on-screen display and as input to
 * mPDF for PDF generation (the same HTML is rendered in both contexts).
 *
 * @param PDO    $db                 Active PDO connection.
 * @param int    $sid                Student ID.
 * @param string $markstore1         Marker 1 mark-store table name.
 * @param string $markstore2         Marker 2 mark-store table name.
 * @param string $markstore3         Marker 3 mark-store table name.
 * @param string $markstore_totals   Totals/reconciliation table name.
 * @param string $assessqs           Assessment questions table name.
 * @param string $student_name       Student's full display name.
 * @param string $username           Student's login username.
 * @param string $title              Project title.
 * @param string $supervisor_name    Supervisor's full name.
 * @param string $second_marker_name Second marker's full name.
 * @param string $marker_name        Primary marker's full name.
 * @param int    $project_id         Project ID.
 * @param string $assesstype         Assessment type code.
 * @param string $assess_cats        Assessment categories (DB value).
 * @param float  $max_mark           Maximum mark for this part.
 * @param float  $mark_pc            Percentage contribution of this part to the module mark.
 * @param string $programme          Student's degree programme.
 * @param int    $assesspart         Assessment part number.
 * @param string $assesstype_desc    Human-readable assessment type description.
 * @param string $module_desc        Human-readable module description.
 * @param int    $is_numeric         1 = numeric scoring, 0 = grade band scoring.
 * @return string HTML feedback block ready to echo or pass to mPDF.
 */
function show_feedback_form1($db,$sid,$markstore1,$markstore2,$markstore3,$markstore_totals,$assessqs,$student_name,$username,$title,$supervisor_name,$second_marker_name,$marker_name, $project_id,$assesstype, $assess_cats, $max_mark, $mark_pc,$programme, $assesspart,$assesstype_desc, $module_desc, $is_numeric)
{
//function show_feedback_form($sid,$markstore1,$markstore2,$markstore3,$markstore_totals,$assessqs,$student_name,$username,$title,$supervisor_name,$second_marker_name,$marker_name, $project_id,$assesstype, $assess_cats, $max_mark, $mark_pc,$programme, $assesspart,$assesstype_desc, $module_desc, $is_numeric)
    //echo "assess category db = $assess_cats </BR>";

    $display_block = "<div><h1>$module_desc, $assesstype_desc Feedback </h1></div>";
    $display_block .= "
             <div align=\"left\">
              <table class=\"tableGradeTop\" >
              <tr>
              <td><b>Student:</b>&nbsp; $student_name&nbsp;&nbsp;</td>
              </tr>
              <tr>
              <td><b>Supervisor:</b>&nbsp; $supervisor_name</td>
              </tr>
              <tr>
              <td><b>Marker:</b>&nbsp; $marker_name
              </td>
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

    // get the returned mark
    $markstore = $markstore_totals;
    //echo("show_feedback_form1<BR>");
    //echo("$markstore $sid<BR>");
    //echo("$assesspart <BR>");
    $query = "select * from $markstore where SID=$sid";
    //$get_list_marks = mysql_query($get_marks) or die(" Unable to create list from database table $markstore. <BR>Query: $get_marks");
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

    $final_total = $get_marks_res['total'];
    $final_total_s = sprintf("%5.1f",$final_total);
    $rettotal = $get_marks_res["retmark$assesspart"];
    $rettotal_s = sprintf("%5.1f",$rettotal);
    //echo $get_marks_res[rettotal];
    $locked = $get_marks_res['locked'];
    //echo("finale total = $final_total_s<BR>");
    //echo("retmark$assesspart = $rettotal_s<BR>");
    //echo("locked = $locked<BR>");
    $display_block .= "
             <tr>
             <td><b>$assesstype mark:</b>&nbsp;<span class=\"grade\"> $rettotal_s/$max_mark</span></td>
             </tr>
             </table>
             </div><BR />";

    //$display_block .= "<a href=\"javascript:window.print()\">Print this page</a><BR /><BR />";

    //$display_block = "";

    //Get the marks from the markstore
    $query = "select * from $markstore1 where SID='$sid'";
    //$marks_res = mysql_query($marks_sql);
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

    //Get the marks from the markstore
    $query = "select * from $markstore2 where SID='$sid'";
    //$marks_res = mysql_query($marks_sql);
    //$marks_stored2 = mysql_fetch_array($marks_res);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $marks_stored2 = $results->fetch();

    $query = "select * from $assess_cats where 1";
    //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. query: $get_cat_details");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }

    $cat_count=$results->rowCount();
    //echo("Cat count = $cat_count<BR>");
    if ($cat_count == null){
        $cat_count = 3;
    }


    for ($cat=1;$cat<=$cat_count;$cat++) {
        //$display_block .= "<tr>";

        // Get the category titles
        $query = "select * from $assess_cats where cat=$cat";
        //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. query: $get_cat_details");
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
        //$factor = $cat_data['factor'];
        if(array_key_exists('get_feedback',$cat_data)==false){
            $show_cat_feedback = 1;
        }else{
            $show_cat_feedback = $cat_data['get_feedback'];
        }

        $display_block .= "<div class=\"feedbackSection\">Section $cat: $description ($weight_pc%)</div>";
        // Display the header
        $display_block .= "<table width=\"100%\" class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">";
        $display_block .= "<tr class=\"tablehead\"><td>Question</td><td>Mark / Comment</td></tr>\n";

        $query = "select * from $assessqs where cat=$cat";
        //$display_block .= "$get_qstot<BR>";
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

        // Store these for later use by the java script
        //$display_block .="<input type=\"hidden\" id=\"factor$cat\" value=\"$factor\">";

        $num=$results->rowCount();
        if ($num < 1) {
            //no questions
            $display_block .= "<em>Sorry, there are no questions in this catagory select!</em>";
        } else {
            //has questions
            for ($count=1;$count<=$num;$count++) {
                $query = "select * from $assessqs where cat=$cat and qid=$count";
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

                $question=$qs_list['question'];
                $entries =$qs_list['entries'];

                $q_weight = $qs_list['weight'];
                //echo("q weight $qweight<BR>");
                if(!is_numeric($q_weight)){
                    $q_weight = 100;
                }
                $q_weight *= 0.01;
                //echo("q weight $qweight<BR>");

                $choice  = $cat . "-" . $count;
                $pchoice = "q" . $choice;
                if (array_key_exists('mark1', $qs_list)==false){ $qs_list['mark1']=null; }
                if (array_key_exists('mark2', $qs_list)==false){ $qs_list['mark2']=null; }
                if (array_key_exists('mark3', $qs_list)==false){ $qs_list['mark3']=null; }
                if (array_key_exists('mark4', $qs_list)==false){ $qs_list['mark4']=null; }
                if (array_key_exists('mark5', $qs_list)==false){ $qs_list['mark5']=null; }
                if (array_key_exists('mark6', $qs_list)==false){ $qs_list['mark6']=null; }
                if (array_key_exists('mark7', $qs_list)==false){ $qs_list['mark7']=null; }
                if (array_key_exists('mark8', $qs_list)==false){ $qs_list['mark8']=null; }
                if (array_key_exists('mark9', $qs_list)==false){ $qs_list['mark9']=null; }
                if (array_key_exists('mark10', $qs_list)==false){ $qs_list['mark10']=null; }
                if (array_key_exists('mark11', $qs_list)==false){ $qs_list['mark11']=null; }
                if (array_key_exists('text1', $qs_list)==false){ $qs_list['text1']=null; }
                if (array_key_exists('text2', $qs_list)==false){ $qs_list['text2']=null; }
                if (array_key_exists('text3', $qs_list)==false){ $qs_list['text3']=null; }
                if (array_key_exists('text4', $qs_list)==false){ $qs_list['text4']=null; }
                if (array_key_exists('text5', $qs_list)==false){ $qs_list['text5']=null; }
                if (array_key_exists('text6', $qs_list)==false){ $qs_list['text6']=null; }
                if (array_key_exists('text7', $qs_list)==false){ $qs_list['text7']=null; }
                if (array_key_exists('text8', $qs_list)==false){ $qs_list['text8']=null; }
                if (array_key_exists('text9', $qs_list)==false){ $qs_list['text9']=null; }
                if (array_key_exists('text10', $qs_list)==false){ $qs_list['text10']=null; }
                if (array_key_exists('text11', $qs_list)==false){ $qs_list['text11']=null; }
                $text1   =$qs_list['text1'];
                $mark1   =$qs_list['mark1'];
                $text2   =$qs_list['text2'];
                $mark2   =$qs_list['mark2'];
                $text3   =$qs_list['text3'];
                $mark3   =$qs_list['mark3'];
                $text4   =$qs_list['text4'];
                $mark4   =$qs_list['mark4'];
                $text5   =$qs_list['text5'];
                $mark5   =$qs_list['mark5'];
                $text6   =$qs_list['text6'];
                $mark6   =$qs_list['mark6'];
                $text7   =$qs_list['text7'];
                $mark7   =$qs_list['mark7'];
                $text8   =$qs_list['text8'];
                $mark8   =$qs_list['mark8'];
                $text9   =$qs_list['text9'];
                $mark9   =$qs_list['mark9'];
                $text10   =$qs_list['text10'];
                $mark10   =$qs_list['mark10'];
                $text11   =$qs_list['text11'];
                $mark11   =$qs_list['mark11'];
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

                if($count == $num){
                    $db_col ="C" . $cat . "Feedback";
                }else{
                    $db_col ="C" . $cat . "Q" . $count;
                }

                $mark1_stored = $marks_stored1[$db_col];
                $mark2_stored = $marks_stored2[$db_col];

                // If it is a numeric question then un weight it
                if($is_numeric == 1){
                    // Only do if it is not feedback
                    if(is_numeric($mark1_stored)){
                        $mark1_stored /= $q_weight;
                        $mark2_stored /= $q_weight;

                        $mark1_stored = round($mark1_stored);
                        $mark2_stored = round($mark2_stored);
                    }
                }

                //$display_block .= "Mark stored 1 $mark1_stored, Mark stored 2 $mark2_stored.<BR>";
                /*
                $checked1 = "";
                $checked2 = "";
                $checked3 = "";
                $checked4 = "";
                $checked5 = "";
                $checked6 = "";
                $checked7 = "";
                $checked8 = "";
                $checked9 = "";
                $checked10 = "";
                $checked11 = "";
                */
                //echo("Checked changed<BR>");
                $checked1 = array();
                $checked2 = array();

                // set which is selected
                for ($i = 1 ; $i <= 11 ; $i++ ){
                    $checked1[$i] = "";
                    $checked2[$i] = "";

                    //$display_block .= "Mark stored " . $mark1_stored . " check_mark " . $check_marks[$i] . "<BR>";
                    if ($mark1_stored == $check_marks[$i])
                    {
                        $checked1[$i] = "checked";
                        //$display_block .= $i . "<BR>";
                    }
                    if ($mark2_stored == $check_marks[$i])
                    {
                        $checked2[$i] = "checked";
                    }
                    //$display_block .= $checked1[$i] . "<BR>";
                }


                // Display the question
                $display_block .= "<tr>";
                $display_block .= "<td width=\"50%\">$question</td>";

                //$display_block .= "choice " . $choice;
                // Supervisor
                $display_block .= "<td>";
                if ($entries == 0) {
                    $entry = $mark1_stored;
                    //$display_block .= "<textarea class=\"feedback\" placeholder=\"Remember, this feedback is for the student's benefit!\" name=A$choice >$entry</textarea><br /><br />";
                    $display_block .= "$entry";
                }else{
                    if( $is_numeric == 1) {
                        $display_block .= nl2br($mark1_stored);
                    }else{
                        for ( $i = 1 ; $i <= $entries ; $i++) {
                            if($checked1[$i] == "checked"){
                                $display_block .= "$check_texts[$i]";
                            }
                        }
                    }
                }
                $display_block .= "</td>";

                // Second marker
                /*

                 $display_block .= "<td>";
                //if ( $cat <> 1 ){
                if ($entries == 0) {
                    $entry = $mark2_stored;
                    //$display_block .= "<textarea class=\"feedback\" placeholder=\"Remember, this feedback is for the student's benefit!\" name=B$choice >$entry</textarea><br /><br />";
                    $display_block .= "$entry<br /><br />";
                }else{
                    if( $is_numeric == 1) {
                        $display_block .= nl2br($mark2_stored);
                    }else{
                        for ( $i = 1 ; $i <= $entries ; $i++) {
                            if($checked2[$i] == "checked"){
                                $display_block .= "$check_texts[$i] &nbsp;<br>";
                            }
                        }
                    }
                }
                //}
                $display_block .= "</td>";
                */
                $display_block .= "</tr>\n";
            }
        }
        $display_block .= "</table><BR />";

    } // $cat


    //$display_block .= "<a href=\"javascript:window.print()\">Print this page</a>";
    //echo($display_block);
    //echo("Done <BR>");
    return $display_block;
}// end of show_feedback_form1()

function show_feedback_form2($db,$sid,$markstore1,$markstore2,$markstore3,$markstore_totals,$assessqs,$student_name,$username,$title,$supervisor_name,$second_marker_name,$marker_name, $project_id,$assesstype, $assess_cats, $max_mark, $mark_pc,$programme, $assesspart,$assesstype_desc, $module_desc, $is_numeric)
{
//function show_feedback_form($sid,$markstore1,$markstore2,$markstore3,$markstore_totals,$assessqs,$student_name,$username,$title,$supervisor_name,$second_marker_name,$marker_name, $project_id,$assesstype, $assess_cats, $max_mark, $mark_pc,$programme, $assesspart,$assesstype_desc, $module_desc, $is_numeric)
	//echo "assess category db = $assess_cats </BR>";
	$display_block = "<div><h1>$module_desc, $assesstype_desc Feedback </h1></div>";
  $display_block .= "
             <div align=\"left\">
              <table class=\"tableGradeTop\" > 
              <tr>
              <td><b>Student:</b>&nbsp; $student_name&nbsp;&nbsp;</td>
              </tr>
              <tr>
              <td><b>Supervisor:</b>&nbsp; $supervisor_name</td>
              </tr>
              <tr>
              <td><b>Marker:</b>&nbsp; $marker_name
              </td>
              </tr>
              <tr>
              <td><b>Second Marker:</b>&nbsp; $second_marker_name
              </td>
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
             
  // get the returned mark
   $markstore = $markstore_totals;
   // echo("show_feedback_form2<BR>");
   //echo("$markstore $sid<BR>");
   //echo("$assesspart <BR>");
   $query = "select * from $markstore where SID=$sid";
   //$get_list_marks = mysql_query($get_marks) or die(" Unable to create list from database table $markstore. <BR>Query: $get_marks");
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

    $final_total = $get_marks_res['total'];
   $final_total_s = sprintf("%5.1f",$final_total);
   $rettotal = $get_marks_res["retmark$assesspart"];
   $rettotal_s = sprintf("%5.1f",$rettotal);
   //echo $get_marks_res[rettotal];
   $locked = $get_marks_res['locked'];
   //echo("finale total = $final_total_s<BR>");
   //echo("retmark$assesspart = $rettotal_s<BR>");
   //echo("locked = $locked<BR>");
   $display_block .= "
             <tr>
             <td><b>$assesstype mark:</b>&nbsp;<span class=\"grade\"> $rettotal_s/$max_mark</span></td>
             </tr>
             </table>
             </div><BR />";
   
   //$display_block .= "<a href=\"javascript:window.print()\">Print this page</a><BR /><BR />";

  //$display_block = "";

  //Get the marks from the markstore
  $query = "select * from $markstore1 where SID='$sid'";
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
    //print_r3("marker 1 marks", $marks_stored1);
  //Get the marks from the markstore
  $query = "select * from $markstore2 where SID='$sid'";
  //$marks_res = mysql_query($marks_sql);
  //$marks_stored2 = mysql_fetch_array($marks_res);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $marks_stored2 = $results->fetch();

    //print_r3("marker 2 marks", $marks_stored2);
  $query = "select * from $assess_cats where 1";
  //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. query: $get_cat_details");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }

    $cat_count=$results->rowCount();
  //echo("Cat count = $cat_count<BR>");
	if ($cat_count == null){
		$cat_count = 3;
	}
	
	
  for ($cat=1;$cat<=$cat_count;$cat++) {
    //$display_block .= "<tr>";

    // Get the category titles
    $query = "select * from $assess_cats where cat=$cat";
    //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. query: $get_cat_details");
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
    //$factor = $cat_data['factor'];
      if(array_key_exists('get_feedback',$cat_data)==false){
          $show_cat_feedback = 1;
      }else{
          $show_cat_feedback = $cat_data['get_feedback'];
      }
    
    $display_block .= "<div class=\"feedbackSection\">Section $cat: $description ($weight_pc%)</div>";
    // Display the header
    $display_block .= "<table width=\"100%\" class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">";
    if( $cat <> 1){
       $display_block .= "<tr><td></td><td class=\"bluetablehead\">1st Marker</td><td class=\"bluetablehead\">2nd Marker</td></tr>\n";
    }else{
       //$display_block .= "<tr class=\"feedbackQem\"><td>Question</td><td>Supervisor</td></tr>\n";
       $display_block .= "<tr><td></td><td class=\"bluetablehead\">1st Marker</td><td class=\"bluetablehead\">2nd Marker</td></tr>\n";
    }
    
    $query = "select * from $assessqs where cat=$cat";
    //$display_block .= "$get_qstot<BR>";
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

    // Store these for later use by the java script
    //$display_block .="<input type=\"hidden\" id=\"factor$cat\" value=\"$factor\">";
    
    $num=$results->rowCount();
    if ($num < 1) {
       //no questions
       $display_block .= "<em>Sorry, there are no questions in this catagory select!</em>";
    } else {
       //has questions
       for ($count=1;$count<=$num;$count++) {
          $query = "select * from $assessqs where cat=$cat and qid=$count";
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
            //print_r3("Qs list ",$qs_list);
          $question=$qs_list['question'];
          $entries =$qs_list['entries'];

           $q_weight = $qs_list['weight'];
           //echo("q weight $qweight<BR>");
           if(!is_numeric($q_weight)){
               $q_weight = 100;
           }
           $q_weight *= 0.01;
           //echo("q weight $qweight<BR>");

          $choice  = $cat . "-" . $count;
          $pchoice = "q" . $choice;
          if (array_key_exists('mark1', $qs_list)==false){ $qs_list['mark1']=null; }
          if (array_key_exists('mark2', $qs_list)==false){ $qs_list['mark2']=null; }
          if (array_key_exists('mark3', $qs_list)==false){ $qs_list['mark3']=null; }
          if (array_key_exists('mark4', $qs_list)==false){ $qs_list['mark4']=null; }
          if (array_key_exists('mark5', $qs_list)==false){ $qs_list['mark5']=null; }
          if (array_key_exists('mark6', $qs_list)==false){ $qs_list['mark6']=null; }
          if (array_key_exists('mark7', $qs_list)==false){ $qs_list['mark7']=null; }
          if (array_key_exists('mark8', $qs_list)==false){ $qs_list['mark8']=null; }
          if (array_key_exists('mark9', $qs_list)==false){ $qs_list['mark9']=null; }
          if (array_key_exists('mark10', $qs_list)==false){ $qs_list['mark10']=null; }
          if (array_key_exists('mark11', $qs_list)==false){ $qs_list['mark11']=null; }
          if (array_key_exists('text1', $qs_list)==false){ $qs_list['text1']=null; }
          if (array_key_exists('text2', $qs_list)==false){ $qs_list['text2']=null; }
          if (array_key_exists('text3', $qs_list)==false){ $qs_list['text3']=null; }
          if (array_key_exists('text4', $qs_list)==false){ $qs_list['text4']=null; }
          if (array_key_exists('text5', $qs_list)==false){ $qs_list['text5']=null; }
          if (array_key_exists('text6', $qs_list)==false){ $qs_list['text6']=null; }
          if (array_key_exists('text7', $qs_list)==false){ $qs_list['text7']=null; }
          if (array_key_exists('text8', $qs_list)==false){ $qs_list['text8']=null; }
          if (array_key_exists('text9', $qs_list)==false){ $qs_list['text9']=null; }
          if (array_key_exists('text10', $qs_list)==false){ $qs_list['text10']=null; }
          if (array_key_exists('text11', $qs_list)==false){ $qs_list['text11']=null; }
          $text1   =$qs_list['text1'];
          $mark1   =$qs_list['mark1'];
          $text2   =$qs_list['text2'];
          $mark2   =$qs_list['mark2'];
          $text3   =$qs_list['text3'];
          $mark3   =$qs_list['mark3'];
          $text4   =$qs_list['text4'];
          $mark4   =$qs_list['mark4'];
          $text5   =$qs_list['text5'];
          $mark5   =$qs_list['mark5'];
          $text6   =$qs_list['text6'];
          $mark6   =$qs_list['mark6'];
          $text7   =$qs_list['text7'];
          $mark7   =$qs_list['mark7'];
          $text8   =$qs_list['text8'];
          $mark8   =$qs_list['mark8'];
          $text9   =$qs_list['text9'];
          $mark9   =$qs_list['mark9'];
          $text10   =$qs_list['text10'];
          $mark10   =$qs_list['mark10'];
          $text11   =$qs_list['text11'];
          $mark11   =$qs_list['mark11'];
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
          
          if($count == $num){
            $db_col ="C" . $cat . "Feedback";
          }else{
            $db_col ="C" . $cat . "Q" . $count;
          }
          
          $mark1_stored = $marks_stored1[$db_col];
          $mark2_stored = $marks_stored2[$db_col];

           // If it is a numeric question then un weight it
           if($is_numeric == 1){
               // Only do if it is not feedback
               if(is_numeric($mark1_stored)){
                   $mark1_stored /= $q_weight;
                   $mark2_stored /= $q_weight;

                   $mark1_stored = round($mark1_stored);
                   $mark2_stored = round($mark2_stored);
               }
           }

          //$display_block .= "Mark stored 1 $mark1_stored, Mark stored 2 $mark2_stored.<BR>";
           /*
          $checked1 = "";
          $checked2 = "";
          $checked3 = "";
          $checked4 = "";
          $checked5 = "";
          $checked6 = "";
          $checked7 = "";
          $checked8 = "";
          $checked9 = "";
          $checked10 = "";
          $checked11 = "";
          */
           $checked1 = array();
           $checked2 = array();
          // set which is selected
          for ($i = 1 ; $i <= 11 ; $i++ ){
            $checked1[$i] = "";
            $checked2[$i] = "";
            
            //$display_block .= "Mark stored " . $mark1_stored . " check_mark " . $check_marks[$i] . "<BR>";
            if ($mark1_stored == $check_marks[$i])
            {
              $checked1[$i] = "checked";
              //$display_block .= $i . "<BR>";
            }
            if ($mark2_stored == $check_marks[$i])
            {
              $checked2[$i] = "checked";
            }
              //$display_block .= $checked1[$i] . "<BR>";
          }

          
          // Display the question
           if (($entries == 0) and (!$show_cat_feedback)){
                continue;
           }else {
               $display_block .= "<tr>";
               if($is_numeric == 1){
                   if($entries == 0){
                       $display_block .= "<td class=\"tablehead\" width=\"35%\">Feedback comments</td>";
                   }else{
                       $display_block .= "<td class=\"tablehead\" width=\"35%\">Marks awarded</td>";
                   }
               }else{
                   $display_block .= "<td width=\"35%\">$question</td>";
               }
           }
          //$display_block .= "choice " . $choice;
          // Supervisor
          $display_block .= "<td width=\"32%\">";
          if ($entries == 0) {// feedback comments
              if($show_cat_feedback) {
                  $entry = $mark1_stored;
                  //$display_block .= "<textarea class=\"feedback\" placeholder=\"Remember, this feedback is for the student's benefit!\" name=A$choice >$entry</textarea><br /><br />";
                  $display_block .= "$entry";
              }
          }else{
            if( $is_numeric == 1) {
              $display_block .= nl2br($mark1_stored);
            }else{
              for ( $i = 1 ; $i <= count($checked1); $i++) {
                 if($checked1[$i] == "checked"){
                    $display_block .= "$check_texts[$i]";
                 }
              }
            }
          }
          $display_block .= "</td>";
          
          // Second marker
          $display_block .= "<td>";
          //if ( $cat <> 1 ){
          if ($entries == 0) {// feedback comments
              if($show_cat_feedback) {
               $entry = $mark2_stored;
               //$display_block .= "<textarea class=\"feedback\" placeholder=\"Remember, this feedback is for the student's benefit!\" name=B$choice >$entry</textarea><br /><br />";
               $display_block .= "$entry<br /><br />";
               }
          }else{
            if( $is_numeric == 1) {
              $display_block .= nl2br($mark2_stored);
            }else{
              for ( $i = 1 ; $i <= count($checked2) ; $i++) {
               if($checked2[$i] == "checked"){
                  $display_block .= "$check_texts[$i] &nbsp;<br>";
               }
              }
            }
          }
          //}
           if (($entries == 0) and (!$show_cat_feedback)){
               continue;
           }else {
               $display_block .= "</td>";
               $display_block .= "</tr>\n";
           }

       }
    }
    $display_block .= "</table><BR />";
    
  } // $cat
  
  
  //$display_block .= "<a href=\"javascript:window.print()\">Print this page</a>";
  //echo($display_block);
  //echo("Done <BR>");
  return $display_block;
}// end of show_feedback_form2()

function show_feedback_form3($db, $sid,$markstore1,$markstore2,$markstore3,$markstore_totals,$assessqs,$student_name,$username,$title,$supervisor_name,$second_marker_name,$third_marker_name, $project_id,$assesstype, $assess_cats, $max_mark, $mark_pc,$programme, $assesspart,$assesstype_desc, $module_desc, $is_numeric)
{
//function show_feedback_form($sid,$markstore1,$markstore2,$markstore3,$markstore_totals,$assessqs,$student_name,$username,$title,$supervisor_name,$second_marker_name,$marker_name, $project_id,$assesstype, $assess_cats, $max_mark, $mark_pc,$programme, $assesspart,$assesstype_desc, $module_desc, $is_numeric)
    //echo "assess category db = $assess_cats </BR>";
    $display_block = "<div><h1>$module_desc, $assesstype_desc Feedback </h1></div>";
    $display_block .= "
             <div align=\"left\">
              <table class=\"tableGradeTop\" >
              <tr>
              <td><b>Student:</b>&nbsp; $student_name&nbsp;&nbsp;</td>
              </tr>
              <tr>
              <td><b>Supervisor:</b>&nbsp; $supervisor_name</td>
              </tr>
              <tr>
              <td><b>Second Marker:</b>&nbsp; $second_marker_name
              </td>
              </tr>
              <tr>
              <td><b>Third Marker:</b>&nbsp; $third_marker_name
              </td>
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

    // get the returned mark
    $markstore = $markstore_totals;
    //echo("show_feedback_form3<BR>");
    //echo("$markstore $sid<BR>");
    //echo("$assesspart <BR>");
    $query = "select * from $markstore where SID=$sid";
    //$get_list_marks = mysql_query($get_marks) or die(" Unable to create list from database table $markstore. <BR>Query: $get_marks");
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

    $final_total = $get_marks_res['total'];
    $final_total_s = sprintf("%5.1f",$final_total);
    $rettotal = $get_marks_res["retmark$assesspart"];
    $rettotal_s = sprintf("%5.1f",$rettotal);
    //echo $get_marks_res[rettotal];
    $locked = $get_marks_res['locked'];
    //echo("finale total = $final_total_s<BR>");
    //echo("retmark$assesspart = $rettotal_s<BR>");
    //echo("locked = $locked<BR>");
    $display_block .= "
             <tr>
             <td><b>$assesstype mark:</b>&nbsp;<span class=\"grade\"> $rettotal_s/$max_mark</span></td>
             </tr>
             </table>
             </div><BR />";

    //$display_block .= "<a href=\"javascript:window.print()\">Print this page</a><BR /><BR />";

    //$display_block = "";

    //Get the marks from the markstore
    $query = "select * from $markstore1 where SID='$sid'";
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

    //Get the marks from the markstore
    $query = "select * from $markstore2 where SID='$sid'";
    //$marks_res = mysql_query($marks_sql);
    //$marks_stored2 = mysql_fetch_array($marks_res);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $marks_stored2 = $results->fetch();

    //Get the marks from the markstore
    $query = "select * from $markstore3 where SID='$sid'";
    //$marks_res = mysql_query($marks_sql);
    //$marks_stored3 = mysql_fetch_array($marks_res);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $marks_stored3 = $results->fetch();

    $query = "select * from $assess_cats where 1";
    //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. query: $get_cat_details");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $cat_count=$results->rowCount();
    //echo("Cat count = $cat_count<BR>");
    if ($cat_count == null){
        $cat_count = 3;
    }


    for ($cat=1;$cat<=$cat_count;$cat++) {
        //$display_block .= "<tr>";

        // Get the category titles
        $query = "select * from $assess_cats where cat=$cat";
        //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. query: $get_cat_details");
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
        //$factor = $cat_data['factor'];
        if(array_key_exists('get_feedback',$cat_data)==false){
            $show_cat_feedback = 1;
        }else{
            $show_cat_feedback = $cat_data['get_feedback'];
        }

        $display_block .= "<div class=\"feedbackSection\">Section $cat: $description ($weight_pc%)</div>";
        // Display the header
        $display_block .= "<table width=\"100%\" class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">";
        if( $cat <> 1){
            $display_block .= "<tr><td></td><td class=\"bluetablehead\">1st Marker</td><td class=\"bluetablehead\">2nd Marker</td><td class=\"bluetablehead\">3rd Marker</td></tr>\n";
        }else{
            //$display_block .= "<tr class=\"tablehead\"><td></td><td>1st Marker</td><td>2nd Marker</td><td>3rd Marker</td></tr>\n";
            $display_block .= "<tr><td></td><td class=\"bluetablehead\">1st Marker</td><td class=\"bluetablehead\">2nd Marker</td><td class=\"bluetablehead\">3rd Marker</td></tr>\n";
        }

        $query = "select * from $assessqs where cat=$cat";
        //$display_block .= "$get_qstot<BR>";
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

        // Store these for later use by the java script
        //$display_block .="<input type=\"hidden\" id=\"factor$cat\" value=\"$factor\">";

        $num=$results->rowCount();
        if ($num < 1) {
            //no questions
            $display_block .= "<em>Sorry, there are no questions in this category select!</em>";
        } else {
            //has questions
            for ($count=1;$count<=$num;$count++) {
                $query = "select * from $assessqs where cat=$cat and qid=$count";
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

                $question=$qs_list['question'];
                $entries =$qs_list['entries'];

                $q_weight = $qs_list['weight'];
                //echo("q weight $qweight<BR>");
                if(!is_numeric($q_weight)){
                    $q_weight = 100;
                }
                $q_weight *= 0.01;
                //echo("q weight $qweight<BR>");

                $choice  = $cat . "-" . $count;
                $pchoice = "q" . $choice;
                if (array_key_exists('mark1', $qs_list)==false){ $qs_list['mark1']=null; }
                if (array_key_exists('mark2', $qs_list)==false){ $qs_list['mark2']=null; }
                if (array_key_exists('mark3', $qs_list)==false){ $qs_list['mark3']=null; }
                if (array_key_exists('mark4', $qs_list)==false){ $qs_list['mark4']=null; }
                if (array_key_exists('mark5', $qs_list)==false){ $qs_list['mark5']=null; }
                if (array_key_exists('mark6', $qs_list)==false){ $qs_list['mark6']=null; }
                if (array_key_exists('mark7', $qs_list)==false){ $qs_list['mark7']=null; }
                if (array_key_exists('mark8', $qs_list)==false){ $qs_list['mark8']=null; }
                if (array_key_exists('mark9', $qs_list)==false){ $qs_list['mark9']=null; }
                if (array_key_exists('mark10', $qs_list)==false){ $qs_list['mark10']=null; }
                if (array_key_exists('mark11', $qs_list)==false){ $qs_list['mark11']=null; }
                if (array_key_exists('text1', $qs_list)==false){ $qs_list['text1']=null; }
                if (array_key_exists('text2', $qs_list)==false){ $qs_list['text2']=null; }
                if (array_key_exists('text3', $qs_list)==false){ $qs_list['text3']=null; }
                if (array_key_exists('text4', $qs_list)==false){ $qs_list['text4']=null; }
                if (array_key_exists('text5', $qs_list)==false){ $qs_list['text5']=null; }
                if (array_key_exists('text6', $qs_list)==false){ $qs_list['text6']=null; }
                if (array_key_exists('text7', $qs_list)==false){ $qs_list['text7']=null; }
                if (array_key_exists('text8', $qs_list)==false){ $qs_list['text8']=null; }
                if (array_key_exists('text9', $qs_list)==false){ $qs_list['text9']=null; }
                if (array_key_exists('text10', $qs_list)==false){ $qs_list['text10']=null; }
                if (array_key_exists('text11', $qs_list)==false){ $qs_list['text11']=null; }
                $text1   =$qs_list['text1'];
                $mark1   =$qs_list['mark1'];
                $text2   =$qs_list['text2'];
                $mark2   =$qs_list['mark2'];
                $text3   =$qs_list['text3'];
                $mark3   =$qs_list['mark3'];
                $text4   =$qs_list['text4'];
                $mark4   =$qs_list['mark4'];
                $text5   =$qs_list['text5'];
                $mark5   =$qs_list['mark5'];
                $text6   =$qs_list['text6'];
                $mark6   =$qs_list['mark6'];
                $text7   =$qs_list['text7'];
                $mark7   =$qs_list['mark7'];
                $text8   =$qs_list['text8'];
                $mark8   =$qs_list['mark8'];
                $text9   =$qs_list['text9'];
                $mark9   =$qs_list['mark9'];
                $text10   =$qs_list['text10'];
                $mark10   =$qs_list['mark10'];
                $text11   =$qs_list['text11'];
                $mark11   =$qs_list['mark11'];
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

                if($count == $num){
                    $db_col ="C" . $cat . "Feedback";
                }else{
                    $db_col ="C" . $cat . "Q" . $count;
                }

                $mark1_stored = $marks_stored1[$db_col];
                $mark2_stored = $marks_stored2[$db_col];
                $mark3_stored = $marks_stored3[$db_col];

                // If it is a numeric question then un weight it
                if($is_numeric == 1){
                    // Only do if it is not feedback
                    if(is_numeric($mark1_stored)){
                        $mark1_stored /= $q_weight;
                        $mark2_stored /= $q_weight;
                        $mark3_stored /= $q_weight;

                        $mark1_stored = round($mark1_stored);
                        $mark2_stored = round($mark2_stored);
                        $mark3_stored = round($mark3_stored);
                    }
                }

                //$display_block .= "Mark stored 1 $mark1_stored, Mark stored 2 $mark2_stored.<BR>";
                /*
                $checked1 = "";
                $checked2 = "";
                $checked3 = "";
                $checked4 = "";
                $checked5 = "";
                $checked6 = "";
                $checked7 = "";
                $checked8 = "";
                $checked9 = "";
                $checked10 = "";
                $checked11 = "";
                */
                $checked1 = array();
                $checked2 = array();
                $checked3 = array();
                // set which is selected
                for ($i = 1 ; $i <= 11 ; $i++ ){
                    $checked1[$i] = "";
                    $checked2[$i] = "";
                    $checked3[$i] = "";

                    //$display_block .= "Mark stored " . $mark1_stored . " check_mark " . $check_marks[$i] . "<BR>";
                    if ($mark1_stored == $check_marks[$i])
                    {
                        $checked1[$i] = "checked";
                        //$display_block .= $i . "<BR>";
                    }
                    if ($mark2_stored == $check_marks[$i])
                    {
                        $checked2[$i] = "checked";
                    }
                    if ($mark3_stored == $check_marks[$i])
                    {
                        $checked3[$i] = "checked";
                    }
                    //$display_block .= $checked1[$i] . "<BR>";
                }


                // Display the question
                //$display_block .= "<tr>";
                //$display_block .= "<td width=\"30%\">$question</td>";

                //$display_block .= "choice " . $choice;
                // Supervisor
                //$display_block .= "<td width=\"23%\">";
                if ($entries == 0 && !$show_cat_feedback) {
                    continue;
                }else {
                    $display_block .= "<tr>";
                    if($is_numeric == 1){
                        if($entries == 0){
                            $display_block .= "<td class='tablehead'  width='30%'>Feedback comment</td>";
                        }else{
                            $display_block .= "<td class='tablehead'  width='30%'>Marks awarded</td>";
                        }
                    }else{
                        $display_block .= "<td width='30%'>$question</td>";
                    }
                }
                // Supervisor
                $display_block .= "<td width='30%'>";
                if($entries == 0) {
                    if($show_cat_feedback) {
                        $entry = $mark1_stored;
                        $display_block .= "$entry";
                    }
                }else {
                    if( $is_numeric == 1) {
                        $display_block .= nl2br($mark1_stored);
                    }else{
                        for ( $i = 1 ; $i <= count($checked1) ; $i++) {
                            if($checked1[$i] == "checked"){
                                $display_block .= "$check_texts[$i]";
                            }
                        }
                    }
                }
                $display_block .= "</td>";

                // Second marker
                $display_block .= "<td width=\"24%\">";
                if ($entries == 0) {
                    $entry = $mark2_stored;
                    $display_block .= "$entry<br /><br />";
                }else{
                    if($entries == 0) {
                        if($show_cat_feedback){
                            $entry = $mark2_stored;
                            $display_block .= "$entry";
                        }
                    }else{
                        if( $is_numeric == 1) {
                            $display_block .= nl2br($mark2_stored);
                        }else{
                            for ( $i = 1 ; $i <= count($checked2) ; $i++) {
                                if($checked2[$i] == "checked"){
                                    $display_block .= "$check_texts[$i] &nbsp;<br>";
                                }
                            }
                        }
                    }
                }
                $display_block .= "</td>";

                // Third marker
                $display_block .= "<td>";
                if ($entries == 0) {
                    $entry = $mark3_stored;
                    $display_block .= "$entry<br /><br />";
                }else{
                    if($entries == 0){
                        $entry = $mark3_stored;
                        $display_block .= "$entry";
                    }else{
                        if( $is_numeric == 1) {
                            $display_block .= nl2br($mark3_stored);
                        }else{
                            for ( $i = 1 ; $i <= count($checked3)  ; $i++) {
                                if($checked3[$i] == "checked"){
                                    $display_block .= "$check_texts[$i] &nbsp;<br>";
                                }
                            }
                        }
                    }
                }
                $display_block .= "</td>";

                $display_block .= "</tr>\n";
            }
        }
        $display_block .= "</table><BR />";

    } // $cat


    //$display_block .= "<a href=\"javascript:window.print()\">Print this page</a>";
    //echo($display_block);
    //echo("Done <BR>");

    //echo($display_block);
    //exit;
    return $display_block;
}// end of show_feedback_form3()

function show_overall_feedback_form1($db, $sid, $markstore1, $markstore2, $markstore3, $title, $min_feedback_words){
    return "To do: show_overall_feedback_form1";
}
function show_overall_feedback_form2($db, $sid, $markstore1, $markstore2, $markstore3, $part_label, $min_feedback_words){
    $overall_feedback_visibility = "block";

    // Display the overall feedback box
    $query = "ALTER TABLE $markstore1 ADD `OverallFeedback` text DEFAULT null";
    if(!DoesColumnExistInTable($db,$markstore1,'OverallFeedback')) {
        $db->query($query);
    }else{
        //echo("\nWarning $markstore1 OverallFeedback already exists\n");
    }
    $query = "ALTER TABLE $markstore2 ADD `OverallFeedback` text DEFAULT null";
    if(!DoesColumnExistInTable($db,$markstore2,'OverallFeedback')) {
        $db->query($query);
    } else {
        //echo("\nWarning $markstore2 OverallFeedback already exists\n");
    }

    $query = "SELECT OverallFeedback FROM $markstore1 WHERE SID=$sid";
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
        $overall_feedback_text1 = $overall_feedback_res['OverallFeedback'];
    } else {
        $overall_feedback_text1 = null;
    }

    $query = "SELECT OverallFeedback FROM $markstore2 WHERE SID=$sid";
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
        $overall_feedback_text2 = $overall_feedback_res['OverallFeedback'];
    } else {
        $overall_feedback_text2 = null;
    }

    $overall_feedback_heading = "Overall feedback on this $part_label part";
    $overall_feedback_id = "overall_feedback";

    $display_block = "";
    //$display_block .= "<div id='assess_criteria' style='border: thin solid black; padding-left: 5px; background-color: #ECEBE6; display: $overall_feedback_visibility;' >\n";
    //$display_block .= "<span class=\"feedbackQ\"><P>Overall Feedback: &nbsp;<span class=\"feedbackQem\">$overall_feedback_heading</span></p></span><br />";
    //$display_block .= "\n<textarea name='$overall_feedback_id'>$overall_feedback_text</textarea><br /><br />";
    //$display_block .= "</div><BR>\n";

    $display_block .= "<table class='tableSimple' style='width: 100%; border:1px solid black;border-collapse:collapse;'>";
    $display_block .= "<tr></tr>\n";
    $display_block .= "<tr><td colspan='2' class='tablehead_overall'>$overall_feedback_heading</td></tr>";
    $display_block .= "<tr style='background-color: lightcyan'><td style='width: 50%;'>1st Marker</td><td>2nd Marker</td></tr>\n";
    $display_block .= "<tr><td>$overall_feedback_text1</td>";
    $display_block .= "<td>$overall_feedback_text2</td></tr>";
    $display_block .= "</table>";
    return $display_block;
}
function show_overall_feedback_form3($db, $sid, $markstore1, $markstore2, $markstore3, $title, $min_feedback_words){
    $overall_feedback_visibility = "block";

    // Display the overall feedback box
    $query = "ALTER TABLE $markstore1 ADD `OverallFeedback` text DEFAULT null";
    if(!DoesColumnExistInTable($db,$markstore1,'OverallFeedback')) {
        $db->query($query);
    }else{
        //echo("\nWarning $markstore1 OverallFeedback already exists\n");
    }
    $query = "ALTER TABLE $markstore2 ADD `OverallFeedback` text DEFAULT null";
    if(!DoesColumnExistInTable($db,$markstore2,'OverallFeedback')) {
        $db->query($query);
    } else {
        //echo("\nWarning $markstore2 OverallFeedback already exists\n");
    }
    $query = "SELECT OverallFeedback FROM $markstore1 WHERE SID=$sid";
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
        $overall_feedback_text1 = $overall_feedback_res['OverallFeedback'];
    } else {
        $overall_feedback_text1 = null;
    }

    $query = "SELECT OverallFeedback FROM $markstore2 WHERE SID=$sid";
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
        $overall_feedback_text2 = $overall_feedback_res['OverallFeedback'];
    } else {
        $overall_feedback_text2 = null;
    }

    $query = "SELECT OverallFeedback FROM $markstore3 WHERE SID=$sid";
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
        $overall_feedback_text3 = $overall_feedback_res['OverallFeedback'];
    } else {
        $overall_feedback_text3 = null;
    }

    $overall_feedback_heading = "Overall feedback on this $part_label part";
    $overall_feedback_id = "overall_feedback";

    $display_block = "";
    //$display_block .= "<div id='assess_criteria' style='border: thin solid black; padding-left: 5px; background-color: #ECEBE6; display: $overall_feedback_visibility;' >\n";
    //$display_block .= "<span class=\"feedbackQ\"><P>Overall Feedback: &nbsp;<span class=\"feedbackQem\">$overall_feedback_heading</span></p></span><br />";
    //$display_block .= "\n<textarea name='$overall_feedback_id'>$overall_feedback_text</textarea><br /><br />";
    //$display_block .= "</div><BR>\n";

    $display_block .= "<table class='tableSimple' style='width: 100%; border:1px solid black;border-collapse:collapse;'>";
    $display_block .= "<tr></tr>\n";
    $display_block .= "<tr><td colspan='3' class='tablehead_overall'>$overall_feedback_heading</td></tr>";
    $display_block .= "<tr style='background-color: lightcyan'><td style='width: 33%;'>1st Marker</td><td style='width: 33%;'>2nd Marker</td><td style='width: 34%;'>3rd Marker</td></tr>\n";
    $display_block .= "<tr><td>$overall_feedback_text1</td>";
    $display_block .= "<td>$overall_feedback_text2</td>";
    $display_block .= "<td>$overall_feedback_text3</td>";
    $display_block .= "</tr>";
    $display_block .= "</table>";
    return $display_block;
}

?>