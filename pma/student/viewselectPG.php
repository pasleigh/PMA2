<?php

$argv=$_SERVER['argv'];

// connect to the datbase
require('connect.php');

$get_setup = "select year_begin, dayname(sel_open), dayofmonth(sel_open), monthname(sel_open), month(sel_open), year(sel_open), dayname(sel_close), dayofmonth(sel_close), monthname(sel_close), month(sel_close), year(sel_close), dayofmonth(alloc_date), month(alloc_date), year(alloc_date) from projects_setup where module=5825";
$get_setup_list = mysql_query($get_setup) or die(" Unable to get database configuration.");
$setup_data = mysql_fetch_array($get_setup_list);

$year = $setup_data['year_begin'];
if ($year == "") {
   header("Location: problem.php");
   exit;
}

$choice = "PGselect" . $year;
$students = "PGstudents" . $year;

$nowarray = getdate();
$open = mktime(9, 0, 0, $setup_data['month(sel_open)'], $setup_data['dayofmonth(sel_open)'], $setup_data['year(sel_open)']);
// to delete
// $open = mktime(9, 0, 0, 9, 15, 2009);
//
$opdate = "9am, " . $setup_data['dayname(sel_open)'] . " " . $setup_data['dayofmonth(sel_open)'] . " " . $setup_data['monthname(sel_open)'] . " " . $setup_data['year(sel_open)'];
$close = mktime(17, 0, 0, $setup_data['month(sel_close)'], $setup_data['dayofmonth(sel_close)'], $setup_data['year(sel_close)']);
$cldate = "5pm, " . $setup_data['dayname(sel_close)'] . " " . $setup_data['dayofmonth(sel_close)'] . " " . $setup_data['monthname(sel_close)'] . " " . $setup_data['year(sel_close)'];
$alloc = mktime(17, 0, 0, $setup_data['month(alloc_date)'], $setup_data['dayofmonth(alloc_date)'], $setup_data['year(alloc_date)']);
if ($nowarray[0] > $close && $nowarray[0] > $alloc) {
   header("Location: selectclosed.php?5825");
   exit;
} else if ($nowarray[0] > $close && $nowarray[0] <= $alloc) {
   header("Location: selectclosed_noalloc.php?5825");
   exit;
}

require('ownerPG.php');
$keeper = $owner;

if ($_POST['op'] == "") {
   $display_block = "<h1>View and update my selection of Projects</h1>";
   if ($nowarray[0] > $open) {
       $display_block .= "<font face=\"Arial\">Enter your details below.</font><br><br>";
   }
   if ($argv[0] == "error1"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: entry fields are incomplete or there is a mismatch in the data - please try again.</b></font><br>";
   }
   if ($argv[0] == "error2") {
       $display_block .="<font color=\"red\" face=\"Arial\">You have not yet made a selection. <a href=\"selectionPG.php\">Make
                         your initial selection</a></font>";
   }
   if ($argv[0] == "error3"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: username does not match SID - please try again.</b></font><br>";
   }
   if ($argv[0] == "error4"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: incorrect memorable 5 character key - please try again.</b></font><br>";
   }
   if ($argv[0] == "error5"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: choices must be unique - reconnect and try again</b></font><br>";
   }
   if ($argv[0] == "error6"){
       $display_block .="<font face=\"Arial\"><b>You have already made a selection - connect here to  view and modify your choice</b></font><br>";
   }
   if ($argv[0] == "error7"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: the username and student ID do not appear in the database - please try again.<br> If the problem continues then contact $owner</b></font><br>";
   }
   if ($nowarray[0] < $open) {
       $display_block .="<font face=\"Arial\">Note that the selection procedure opens at <b><font color=\"red\">$opdate</font></b></font><br><br>";
   } else {
     $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">
             <p><strong>Username:</strong>&nbsp;<input type=\"text\" name=\"username\" size=12 maxlength=12>&nbsp;&nbsp;
             <strong>Student ID:</strong>&nbsp;<input type=\"text\" name=\"sid\" size=10 maxlength=9>&nbsp;&nbsp;
             <input type=\"hidden\" name=\"op\" value=\"view\">

             <p><input type=\"submit\" name=\"submit\" value=\"View Selection\"></p>
             </form>";
   }
} else if ($_POST['op'] == "view") {
   //check for required fields
   if (($_POST['sid'] == "") || ($_POST['username'] == "")) {
//      header("Location: viewselect_new.php?error1");
      header("Location: viewselectPG.php?error1");
      exit;
   }
   $check_sid = "select sid from $students where username = '$_POST[username]'";
   $get_sid = mysql_query($check_sid);
   $get_check_sid = mysql_fetch_array($get_sid);
   $checksid = $get_check_sid['sid'];
   $check_username = "select username from $students where sid = '$_POST[sid]'";
   $get_username = mysql_query($check_username);
   $get_check_username = mysql_fetch_array($get_username);
   $checkusername = $get_check_username['username'];
   if (($checksid == "") && ($checkusername == "")) {
//      header("Location: viewselect_new.php?error7");
      header("Location: viewselectPG.php?error7");
      exit;
   }
   if (($_POST['sid'] != $checksid) && ($_POST['username'] != $checkusername)) {
//      header("Location: viewselect_new.php?error1");
      header("Location: viewselectPG.php?error1");
      exit;
   }
   $check_exist = "select count(sid) from $choice where sid = '$_POST[sid]'";
   $get_check = mysql_query($check_exist);
   $get_check_res = mysql_fetch_array($get_check,MYSQL_NUM);
   if ($get_check_res[0] == 0) {
//      header("Location: viewselect_new.php?error2");
      header("Location: viewselectPG.php?error2");
      exit;
   }
   $details = "select sel_date, sid, username, name, choice1, choice2, choice3, choice4, choice5 from $choice where sid = '$_POST[sid]'";
   $get_details = mysql_query($details) or die(" Unable to get data from selection table");
   $get_details_res = mysql_fetch_array($get_details);
   $username = $get_details_res['username'];
   if ($_POST['username'] != "$username") {
//      header("Location: viewselect_new.php?error3");
      header("Location: viewselectPG.php?error3");
      exit;
   }
   $sel_date = $get_details_res['sel_date'];
   $name = $get_details_res['name'];
   $choice1 = $get_details_res['choice1'];
   $choice2 = $get_details_res['choice2'];
   $choice3 = $get_details_res['choice3'];
   $choice4 = $get_details_res['choice4'];
   $choice5 = $get_details_res['choice5'];

   $get_choice1 = "select f_title from master_name where id = $choice1";
   $get_choice1_res = mysql_query($get_choice1) or die(" Unable to get data from database");
   $info_choice1 = mysql_fetch_array($get_choice1_res);
   $title1 = $info_choice1['f_title'];
   if ($title1 == "") { $chk1 = "withdrawn"; $title1 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select f_UGprojects from project_details where master_id = $choice1";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   $f_UGprojects = $nums['f_UGprojects']; 
   $get_choice1 = "select count(choice1) from $choice where choice1 = $choice1";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $choice where choice2 = $choice1";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $choice where choice3 = $choice1";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $choice where choice4 = $choice1";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $choice where choice5 = $choice1";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_UGprojects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail1 = round($avail*100/$f_UGprojects);

   $get_choice2 = "select f_title from master_name where id = $choice2";
   $get_choice2_res = mysql_query($get_choice2) or die(" Unable to get data from database");
   $info_choice2 = mysql_fetch_array($get_choice2_res);
   $title2 = $info_choice2['f_title'];
   if ($title2 == "") { $chk2 = "withdrawn"; $title2 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select f_UGprojects from project_details where master_id = $choice2";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   $f_UGprojects = $nums['f_UGprojects']; 
   $get_choice1 = "select count(choice1) from $choice where choice1 = $choice2";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $choice where choice2 = $choice2";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $choice where choice3 = $choice2";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $choice where choice4 = $choice2";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $choice where choice5 = $choice2";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_UGprojects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail2 = round($avail*100/$f_UGprojects);

   $get_choice3 = "select f_title from master_name where id = $choice3";
   $get_choice3_res = mysql_query($get_choice3) or die(" Unable to get data from database");
   $info_choice3 = mysql_fetch_array($get_choice3_res);
   $title3 = $info_choice3['f_title'];
   if ($title3 == "") { $chk3 = "withdrawn"; $title3 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select f_UGprojects from project_details where master_id = $choice3";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   $f_UGprojects = $nums['f_UGprojects']; 
   $get_choice1 = "select count(choice1) from $choice where choice1 = $choice3";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $choice where choice2 = $choice3";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $choice where choice3 = $choice3";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $choice where choice4 = $choice3";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $choice where choice5 = $choice3";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_UGprojects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail3 = round($avail*100/$f_UGprojects);

   $get_choice4 = "select f_title from master_name where id = $choice4";
   $get_choice4_res = mysql_query($get_choice4) or die(" Unable to get data from database");
   $info_choice4 = mysql_fetch_array($get_choice4_res);
   $title4 = $info_choice4['f_title'];
   if ($title4 == "") { $chk4 = "withdrawn"; $title4 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select f_UGprojects from project_details where master_id = $choice4";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   $f_UGprojects = $nums['f_UGprojects']; 
   $get_choice1 = "select count(choice1) from $choice where choice1 = $choice4";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $choice where choice2 = $choice4";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $choice where choice3 = $choice4";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $choice where choice4 = $choice4";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $choice where choice5 = $choice4";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_UGprojects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail4 = round($avail*100/$f_UGprojects);

   $get_choice5 = "select f_title from master_name where id = $choice5";
   $get_choice5_res = mysql_query($get_choice5) or die(" Unable to get data from database");
   $info_choice5 = mysql_fetch_array($get_choice5_res);
   $title5 = $info_choice5['f_title'];
   if ($title5 == "") { $chk5 = "withdrawn"; $title5 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select f_UGprojects from project_details where master_id = $choice5";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   $f_UGprojects = $nums['f_UGprojects']; 
   $get_choice1 = "select count(choice1) from $choice where choice1 = $choice5";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $choice where choice2 = $choice5";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $choice where choice3 = $choice5";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $choice where choice4 = $choice5";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $choice where choice5 = $choice5";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_UGprojects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail5 = round($avail*100/$f_UGprojects);

   $display_block .= "<div align=\"left\">
             <table border=\"1\" width=\"700\">
             <tr>
             <td width=\"150\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"></font></td>
             <td width=\"150\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"></font></td>
             <td width=\"300\" bgcolor=\"#C0C0C0\"><font face=\"Arial\" color=\"red\"><b>Choice made on &nbsp;$sel_date</b></font></td>
             </tr>
             <tr>
             <td width=\"150\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Student ID</b></font></td>
             <td width=\"150\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Username</b></font></td>
             <td width=\"300\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Student Name</b></font></td>
             </tr>
             <tr>
             <td width=\"150\"><font face=\"Arial\">$_POST[sid]</font></td>
             <td width=\"150\"><font face=\"Arial\">$username</font></td>
             <td width=\"450\"><font face=\"Arial\">$name</font></td>
             </tr>
             <tr>
             <td width=\"150\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Choice</b></font></td>
             <td width=\"150\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Project ID</b></font></td>
             <td width=\"300\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Project (Percentage availability)</b></font></td>
             </tr>
             <tr>
             <td width=\"150\"><font face=\"Arial\">1st choice</font></td>";
             if ($chk1 == "withdrawn") {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"></font></td>
                                   <td width=\"300\"><font face=\"Arial\">$title1</font></td>";
             }
             else {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"><b>$choice1</b></font></td>
                                   <td width=\"300\"><font face=\"Arial\"><a href=\"viewproject.php?$choice1\" onClick=\"return popup(this, 'projinfo')\">$title1</a> <b>($pcavail1%)</b></font></td>";
             }
             $display_block .= "</tr>
             <tr>
             <td width=\"150\"><font face=\"Arial\">2nd choice</font></td>";
             if ($chk2 == "withdrawn") {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"></font></td>
                                   <td width=\"300\"><font face=\"Arial\">$title2</font></td>";
             }
             else {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"><b>$choice2</b></font></td>
                                   <td width=\"300\"><font face=\"Arial\"><a href=\"viewproject.php?$choice2\" onClick=\"return popup(this, 'projinfo')\">$title2</a> <b>($pcavail2%)</b></font></td>";
             }
             $display_block .= "</tr>
             <tr>
             <td width=\"150\"><font face=\"Arial\">3rd choice</font></td>";
             if ($chk3 == "withdrawn") {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"></font></td>
                                   <td width=\"300\"><font face=\"Arial\">$title3</font></td>";
             }
             else {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"><b>$choice3</b></font></td>
                                   <td width=\"300\"><font face=\"Arial\"><a href=\"viewproject.php?$choice3\" onClick=\"return popup(this, 'projinfo')\">$title3</a> <b>($pcavail3%)</b></font></td>";
             }
             $display_block .= "</tr>
             <tr>
             <td width=\"150\"><font face=\"Arial\">4th choice</font></td>";
             if ($chk4 == "withdrawn") {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"></font></td>
                                   <td width=\"300\"><font face=\"Arial\">$title4</font></td>";
             }
             else {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"><b>$choice4</b></font></td>
                                   <td width=\"300\"><font face=\"Arial\"><a href=\"viewproject.php?$choice4\" onClick=\"return popup(this, 'projinfo')\">$title4</a> <b>($pcavail4%)</b></font></td>";
             }
             $display_block .= "</tr>
             <tr>
             <td width=\"150\"><font face=\"Arial\">5th choice</font></td>";
             if ($chk5 == "withdrawn") {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"></font></td>
                                   <td width=\"300\"><font face=\"Arial\">$title5</font></td>";
             }
             else {
                $display_block .= "<td width=\"150\"><font face=\"Arial\"><b>$choice5</b></font></td>
                                   <td width=\"300\"><font face=\"Arial\"><a href=\"viewproject.php?$choice5\" onClick=\"return popup(this, 'projinfo')\">$title5</a> <b>($pcavail5%)</b></font></td>";
             }
             $display_block .= "</tr>
             </table>
             </div>";
        $errormsg = "<h3><font color=red>Update your selections below.</font></h3>";
        $check_exist = "select count(sid) from $choice where sid = '$_POST[sid]'";
        $get_check = mysql_query($check_exist);
        $get_check_res = mysql_fetch_array($get_check,MYSQL_NUM);
        if ($get_check_res[0] == 0) {
//           header("Location: viewselect_new.php?error2");
           header("Location: viewselectPG.php?error2");
           exit;
        }
        require('projectlistPG.php');
} else if ($_POST['op'] == "select") {
   //check for required fields
   if (($_POST['choice1'] == $_POST['choice2']) || ($_POST['choice1'] == $_POST['choice3']) || ($_POST['choice1'] == $_POST['choice4']) || ($_POST['choice1'] == $_POST['choice5']) || ($_POST['choice2'] == $_POST['choice3']) || ($_POST['choice2'] == $_POST['choice4']) || ($_POST['choice2'] == $_POST['choice5']) || ($_POST['choice3'] == $_POST['choice4']) || ($_POST['choice3'] == $_POST['choice5']) || ($_POST['choice4'] == $_POST['choice5'])) {
        $errormsg = "<h3><font color=red><font size=+2>YOUR SELECTION HAS NOT BEEN SAVED YET.</font> 
        You can only make one choice for each project - please rectify your five choices.
        </font></h3>";
        $check_exist = "select count(sid) from $choice where sid = '$_POST[sid]'";
        $get_check = mysql_query($check_exist);
        $get_check_res = mysql_fetch_array($get_check,MYSQL_NUM);
        if ($get_check_res[0] == 0) {
//           header("Location: viewselect_new.php?error2");
           header("Location: viewselectPG.php?error2");
           exit;
        }
        $choice1 = $_POST['choice1'];
        $choice2 = $_POST['choice2'];
        $choice3 = $_POST['choice3'];
        $choice4 = $_POST['choice4'];
        $choice5 = $_POST['choice5'];
        require('projectlistPG.php');
   } else if ($_POST['confirm'] != 1) {
        $errormsg = "<h3><font color=red><font size=+2>YOUR SELECTION HAS NOT BEEN SAVED YET.</font> 
        Please confirm that you have read the agreement to meet with your individual project supervisor once per week.
        </font></h3>";
        $check_exist = "select count(sid) from $choice where sid = '$_POST[sid]'";
        $get_check = mysql_query($check_exist);
        $get_check_res = mysql_fetch_array($get_check,MYSQL_NUM);
        if ($get_check_res[0] == 0) {
//           header("Location: viewselect_new.php?error2");
           header("Location: viewselectPG.php?error2");
           exit;
        }
        $choice1 = $_POST['choice1'];
        $choice2 = $_POST['choice2'];
        $choice3 = $_POST['choice3'];
        $choice4 = $_POST['choice4'];
        $choice5 = $_POST['choice5'];
        require('projectlistPG.php');
   } else {
   $update_choice = "update $choice set sel_date=now(), choice1='$_POST[choice1]',
                 choice2='$_POST[choice2]', choice3='$_POST[choice3]',  choice4='$_POST[choice4]', 
                 choice5='$_POST[choice5]' where sid = '$_POST[sid]'";
   mysql_query($update_choice) or die(" Unable to update choices to the database");
   $details = "select username, name from $choice where sid = '$_POST[sid]'";
   $get_details = mysql_query($details);
   $get_details_res = mysql_fetch_array($get_details);
   $username = $get_details_res['username'];
   $name = $get_details_res['name'];
   $get_date = "select sel_date from $choice where sid = '$_POST[sid]'";
   $res_date = mysql_query($get_date);
   $choicedate = mysql_fetch_array($res_date);

   $update = "YOUR CHOICES HAVE BEEN UPDATED AS OF $choicedate[sel_date]\n";
   require('emailstudent.php');
   }
}
mysql_close();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" type="text/css" href="./database.css" />

    <title>CIVE370X - Research Projects</title>
			<SCRIPT TYPE="text/javascript">
			function popup(mylink, windowname)
			{
			if (! window.focus)return true;
			var href;
			if (typeof(mylink) == 'string')
				 href=mylink;
			else
				 href=mylink.href;
			window.open(href, windowname, 'width=780,height=800,scrollbars=yes');
			return false;
			}
			</SCRIPT>
  </head>
  <body>

<!-- Start of main container -->
    <div id="container">
<!-- Start of main content -->
      <div id="content"><a name="pagetop"></a>
     	 <?php echo $display_block; ?>
      </div>
<!-- End of main content -->
    </div>
  </body>
</html>
