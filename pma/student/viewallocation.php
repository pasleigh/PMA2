<?php

// connect to the datbase
require('connect.php');

if (array_key_exists('module1', $_GET)==false){ $_GET['module1']=null; }
if (array_key_exists('year_begin', $_GET)==false){ $_GET['year_begin']=null; }
if (array_key_exists('error', $_GET)==false){ $_GET['error']=null; }
$module1 = $_GET['module1'];
$year_begin = $_GET['year_begin'];
$error = $_GET['error'];

// setup the names of tables to extract data from
include('setup_tables.php');

if($module1 == "" || $year_begin == "")
{
	echo("Module ($module1) and Year_begin ($year_begin) must be specified.<BR>)");
	exit;
}

$get_setup = "select year_begin, year_end, dayname(alloc_date), dayofmonth(alloc_date), monthname(alloc_date), month(alloc_date), year(alloc_date), dayname(date1), dayofmonth(date1), monthname(date1), month(date1), year(date1), dayname(date2), dayofmonth(date2), monthname(date2), month(date2), year(date2) from $projects_setup where module=$module1 and year_begin=$year_begin";
$get_setup_list = mysql_query($get_setup) or die(" Unable to get database configuration. Query: $get_setup");
$setup_data = mysql_fetch_array($get_setup_list);


$projectload = "projectload";
$choice = $selection;
$allocation = $project_alloc;

$nowarray = getdate();
$open = mktime(12, 0, 0, $setup_data['month(alloc_date)'], $setup_data['dayofmonth(alloc_date)'], $setup_data['year(alloc_date)']);
// remove later
// $open = mktime(9, 0, 0, 9, 17, 2009);
//
$opdate = "12 noon, " . $setup_data['dayname(alloc_date)'] . " " . $setup_data['dayofmonth(alloc_date)'] . " " . $setup_data['monthname(alloc_date)'] . " " . $setup_data['year(alloc_date)'];
$pp_date = "12 noon, " . $setup_data['dayname(date1)'] . " " . $setup_data['dayofmonth(date1)'] . " " . $setup_data['monthname(date1)'] . " " . $setup_data['year(date1)'];
$pdp = $setup_data['dayname(date2)'] . " " . $setup_data['dayofmonth(date2)'] . " " . $setup_data['monthname(date2)'] . " " . $setup_data['year(date2)'];
$year_range = $year . "-" . $setup_data['year_end'];

require('owner.php');

if ($nowarray[0] < $open) {
    $display_block = "<h1>View Allocation of Level $level Projects CIVE$module1</h1>
       <h2>Allocation for session $setup_data[year_begin] - $setup_data[year_end]</h2>";
       $display_block .="<p>Note that project allocation will be available from <b><font color=\"red\">$opdate</font></b></font><br><br> Contact $owner for further details.<br><br>";
} else {

if ($_POST['op'] == "") {
    $display_block = "<h1>View Allocation of Level $level Projects CIVE$module1</h1>
       <h2>Allocation for session $setup_data[year_begin] - $setup_data[year_end]</h2>";
       
//   $display_block = "<h1>View my allocated Project Area</h1>";
   $display_block .= "<font face=\"Arial\">Enter your details below.</font><br><br>";
   if ($error == "error1"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: entry fields are incomplete or there is a mismatch in the data - please try again.</b></font><br>";
   }
   if ($error == "error2") {
       $display_block .="<font color=\"red\" face=\"Arial\">It appears that you have not yet been allocated a project - please
                         try again later</font>";
   }
   if ($error == "error3"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: username does not match SID - please try again.</b></font><br>";
   }
   if ($error == "error4"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: incorrect memorable 5 character key - please try again.</b></font><br>";
   }
   if ($error == "error5"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: choices must be unique - reconnect and try again</b></font><br>";
   }
   if ($error == "error6"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: you have already made a selection - connect here and view your choice</b></font><br>";
   }
   if ($error == "error7"){
       $display_block .="<font color=\"red\" face=\"Arial\"><b>Error: the username and student ID do not appear in the database - please try again.<br> If the problem continues then contact $owner</b></font><br>";
   }
   $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?module1=$module1&year_begin=$year_begin\">
             <p><strong>Username:</strong>&nbsp;<input type=\"text\" name=\"username\" size=12 maxlength=12>&nbsp;&nbsp;
             <strong>Student ID:</strong>&nbsp;<input type=\"text\" name=\"sid\" size=10 maxlength=9>&nbsp;&nbsp;
             <input type=\"hidden\" name=\"op\" value=\"view\">

             <p><input type=\"submit\" name=\"submit\" value=\"View Allocation\"></p>
             </form>";
  $display_block .= "<br><br>";
} else if ($_POST['op'] == "view") {
   //check for required fields
   if (($_POST['sid'] == "") || ($_POST['username'] == "")) {
//      header("Location: viewallocation_new.php?error1");
      header("Location: viewallocation.php?module1=$module1&year_begin=$year_begin&error=error1");
      exit;
   }
   $check_sid = "select sid from $students where username = '$_POST[username]'";
   $get_sid = mysql_query($check_sid) or die(" Unable to get data from database. Query: $check_sid");
   $get_check_sid = mysql_fetch_array($get_sid);
   $checksid = $get_check_sid['sid'];
   $check_username = "select username from $students where sid = '$_POST[sid]'";
   $get_username = mysql_query($check_username) or die(" Unable to get data from database. Query: $check_username");
   $get_check_username = mysql_fetch_array($get_username);
   $checkusername = $get_check_username['username'];
   if (($checksid == "") && ($checkusername == "")) {
//      header("Location: viewallocation_new.php?error7");
      header("Location: viewallocation.php?module1=$module1&year_begin=$year_begin&error=error7");
      exit;
   }
   if (($_POST['sid'] != $checksid) && ($_POST['username'] != $checkusername)) {
//      header("Location: viewallocation_new.php?error1");
      header("Location: viewallocation.php?module1=$module1&year_begin=$year_begin&error=error1");
      exit;
   }
   $check_exist = "select count(sid) from $allocation where sid = '$_POST[sid]'";
   $get_check = mysql_query($check_exist) or die(" Unable to get data from database. Query: $check_exist");
   $get_check_res = mysql_fetch_array($get_check,MYSQL_NUM);
   if ($get_check_res[0] == 0) {
//      header("Location: viewallocation_new.php?error2");
      header("Location: viewallocation.php?module1=$module1&year_begin=$year_begin&error=error2");
      exit;
   }
   $details = "select sel_date, sid, username, name, choice1, choice2, choice3, choice4, choice5 from $selection where sid = '$_POST[sid]'";
   $get_details = mysql_query($details) or die(" Unable to get data from selection table");
   $get_details_res = mysql_fetch_array($get_details);
   $username = $get_details_res['username'];
   if ($_POST['username'] != "$username") {
//      header("Location: viewallocation_new.php?error3");
      header("Location: viewallocation.php?module1=$module1&year_begin=$year_begin&error=error3");
      exit;
   }
   $display_block = "<h1>Details of your Original Project Selection</h1>";

   $sel_date = $get_details_res['sel_date'];
   $name = $get_details_res['name'];
   $choice1 = $get_details_res['choice1'];
   $choice2 = $get_details_res['choice2'];
   $choice3 = $get_details_res['choice3'];
   $choice4 = $get_details_res['choice4'];
   $choice5 = $get_details_res['choice5'];

   $get_choice1 = "select f_title from $master_name where id = $choice1";
   $get_choice1_res = mysql_query($get_choice1) or die(" Unable to get data from database. Query: $get_choice1");
   $info_choice1 = mysql_fetch_array($get_choice1_res);
   $title1 = $info_choice1['f_title'];
   if ($title1 == "") { $chk1 = "withdrawn"; $title1 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select * from $project_details where master_id = $choice1";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   if( $level == 3 ){
     $f_NumProjects = $nums['f_UGprojects']; 
   }else{
     $f_NumProjects = $nums['f_PGprojects']; 
   }
   $get_choice1 = "select count(choice1) from $selection where choice1 = $choice1";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $selection where choice2 = $choice1";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $selection where choice3 = $choice1";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $selection where choice4 = $choice1";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $selection where choice5 = $choice1";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_NumProjects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail1 = round($avail*100/$f_NumProjects);

   $get_choice2 = "select f_title from $master_name where id = $choice2";
   $get_choice2_res = mysql_query($get_choice2) or die(" Unable to get data from database. Query: $get_choice2");
   $info_choice2 = mysql_fetch_array($get_choice2_res);
   $title2 = $info_choice2['f_title'];
   if ($title2 == "") { $chk2 = "withdrawn"; $title2 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select * from $project_details where master_id = $choice2";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   if( $level == 3 ){
     $f_NumProjects = $nums['f_UGprojects']; 
   }else{
     $f_NumProjects = $nums['f_PGprojects']; 
   }
   $get_choice1 = "select count(choice1) from $selection where choice1 = $choice2";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $selection where choice2 = $choice2";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $selection where choice3 = $choice2";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $selection where choice4 = $choice2";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $selection where choice5 = $choice2";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_NumProjects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail2 = round($avail*100/$f_NumProjects);

   $get_choice3 = "select f_title from $master_name where id = $choice3";
   $get_choice3_res = mysql_query($get_choice3) or die(" Unable to get data from database. Query: $get_choice2");
   $info_choice3 = mysql_fetch_array($get_choice3_res);
   $title3 = $info_choice3['f_title'];
   if ($title3 == "") { $chk3 = "withdrawn"; $title3 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select * from $project_details where master_id = $choice3";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   if( $level == 3 ){
     $f_NumProjects = $nums['f_UGprojects']; 
   }else{
     $f_NumProjects = $nums['f_PGprojects']; 
   }
   $get_choice1 = "select count(choice1) from $selection where choice1 = $choice3";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $selection where choice2 = $choice3";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $selection where choice3 = $choice3";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $selection where choice4 = $choice3";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $selection where choice5 = $choice3";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_NumProjects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail3 = round($avail*100/$f_NumProjects);

   $get_choice4 = "select f_title from $master_name where id = $choice4";
   $get_choice4_res = mysql_query($get_choice4) or die(" Unable to get data from database. Query: $get_choice2");
   $info_choice4 = mysql_fetch_array($get_choice4_res);
   $title4 = $info_choice4['f_title'];
   if ($title4 == "") { $chk4 = "withdrawn"; $title4 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select * from $project_details where master_id = $choice4";
   $get_num_res = mysql_query($get_num);
   $nums = mysql_fetch_array($get_num_res);
   if( $level == 3 ){
     $f_NumProjects = $nums['f_UGprojects']; 
   }else{
     $f_NumProjects = $nums['f_PGprojects']; 
   }
   $get_choice1 = "select count(choice1) from $selection where choice1 = $choice4";
   $get_choice1_res = mysql_query($get_choice1);
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $selection where choice2 = $choice4";
   $get_choice2_res = mysql_query($get_choice2);
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $selection where choice3 = $choice4";
   $get_choice3_res = mysql_query($get_choice3);
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $selection where choice4 = $choice4";
   $get_choice4_res = mysql_query($get_choice4);
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $selection where choice5 = $choice4";
   $get_choice5_res = mysql_query($get_choice5);
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_NumProjects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail4 = round($avail*100/$f_NumProjects);

   $get_choice5 = "select f_title from $master_name where id = $choice5";
   $get_choice5_res = mysql_query($get_choice5) or die(" Unable to get data from database. Query: $get_choice2");
   $info_choice5 = mysql_fetch_array($get_choice5_res);
   $title5 = $info_choice5['f_title'];
   if ($title5 == "") { $chk5 = "withdrawn"; $title5 = "<i>Project has been withdrawn</i>"; }
   $get_num = "select * from $project_details where master_id = $choice5";
   $get_num_res = mysql_query($get_num)  or die(" Unable to get data from database. Query: $get_num");
   $nums = mysql_fetch_array($get_num_res);
   if( $level == 3 ){
     $f_NumProjects = $nums['f_UGprojects']; 
   }else{
     $f_NumProjects = $nums['f_PGprojects']; 
   }
   $get_choice1 = "select count(choice1) from $selection where choice1 = $choice5";
   $get_choice1_res = mysql_query($get_choice1) or die(" Unable to get data from database. Query: $get_choice1");
   $nchoice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
   $get_choice2 = "select count(choice2) from $selection where choice2 = $choice5";
   $get_choice2_res = mysql_query($get_choice2) or die(" Unable to get data from database. Query: $get_choice2");
   $nchoice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
   $get_choice3 = "select count(choice3) from $selection where choice3 = $choice5";
   $get_choice3_res = mysql_query($get_choice3) or die(" Unable to get data from database. Query: $get_choice3");
   $nchoice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
   $get_choice4 = "select count(choice4) from $selection where choice4 = $choice5";
   $get_choice4_res = mysql_query($get_choice4) or die(" Unable to get data from database. Query: $get_choice4");
   $nchoice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
   $get_choice5 = "select count(choice5) from $selection where choice5 = $choice5";
   $get_choice5_res = mysql_query($get_choice5) or die(" Unable to get data from database. Query: $get_choice5");
   $nchoice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
   $avail = $f_NumProjects - $nchoice1[0] - $nchoice2[0]/2 - $nchoice3[0]/3 - $nchoice4[0]/4 - $nchoice5[0]/5;
   $pcavail5 = round($avail*100/$f_NumProjects);

   $display_block .= "<div align=\"left\">
             <table border=\"1\">
             <tr>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"></font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"></font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\" color=\"red\"><b>Choice made on &nbsp;$sel_date</b></font></td>
             </tr>
             <tr>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Student ID</b></font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Username</b></font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Student Name</b></font></td>
             </tr>
             <tr>
             <td><font face=\"Arial\">$_POST[sid]</font></td>
             <td><font face=\"Arial\">$username</font></td>
             <td><font face=\"Arial\">$name</font></td>
             </tr>
             <tr>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Choice</b></font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Project ID</b></font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Project Area (Percentage availability)</b></font></td>
             </tr>
             <tr>
             <td><font face=\"Arial\">1st choice</font></td>";
             if ($chk1 == "withdrawn") {
                $display_block .= "<td><font face=\"Arial\"></font></td>
                                   <td><font face=\"Arial\">$title1</font></td>";
             }
             else {
                $display_block .= "<td><font face=\"Arial\"><b>$choice1</b></font></td>
                                   <td><font face=\"Arial\"><a href=\"viewproject.php?module1=$module1&year_begin=$year_begin&id=$choice1\" onClick=\"return popup(this, 'projinfo')\">$title1</a> <b>($pcavail1%)</b></font></td>";
             }
             $display_block .= "</tr>
             <tr>
             <td><font face=\"Arial\">2nd choice</font></td>";
             if ($chk2 == "withdrawn") {
                $display_block .= "<td><font face=\"Arial\"></font></td>
                                   <td><font face=\"Arial\">$title2</font></td>";
             }
             else {
                $display_block .= "<td><font face=\"Arial\"><b>$choice2</b></font></td>
                                   <td><font face=\"Arial\"><a href=\"viewproject.php?module1=$module1&year_begin=$year_begin&id=$choice2\" onClick=\"return popup(this, 'projinfo')\">$title2</a> <b>($pcavail2%)</b></font></td>";
             }
             $display_block .= "</tr>
             <tr>
             <td><font face=\"Arial\">3rd choice</font></td>";
             if ($chk3 == "withdrawn") {
                $display_block .= "<td><font face=\"Arial\"></font></td>
                                   <td><font face=\"Arial\">$title3</font></td>";
             }
             else {
                $display_block .= "<td><font face=\"Arial\"><b>$choice3</b></font></td>
                                   <td><font face=\"Arial\"><a href=\"viewproject.php?module1=$module1&year_begin=$year_begin&id=$choice3\" onClick=\"return popup(this, 'projinfo')\">$title3</a> <b>($pcavail3%)</b></font></td>";
             }
             $display_block .= "</tr>
             <tr>
             <td><font face=\"Arial\">4th choice</font></td>";
             if ($chk4 == "withdrawn") {
                $display_block .= "<td><font face=\"Arial\"></font></td>
                                   <td><font face=\"Arial\">$title4</font></td>";
             }
             else {
                $display_block .= "<td><font face=\"Arial\"><b>$choice4</b></font></td>
                                   <td><font face=\"Arial\"><a href=\"viewproject.php?module1=$module1&year_begin=$year_begin&id=$choice4\" onClick=\"return popup(this, 'projinfo')\">$title4</a> <b>($pcavail4%)</b></font></td>";
             }
             $display_block .= "</tr>
             <tr>
             <td><font face=\"Arial\">5th choice</font></td>";
             if ($chk5 == "withdrawn") {
                $display_block .= "<td><font face=\"Arial\"></font></td>
                                   <td><font face=\"Arial\">$title5</font></td>";
             }
             else {
                $display_block .= "<td><font face=\"Arial\"><b>$choice5</b></font></td>
                                   <td><font face=\"Arial\"><a href=\"viewproject.php?module1=$module1&year_begin=$year_begin&id=$choice5\" onClick=\"return popup(this, 'projinfo')\">$title5</a> <b>($pcavail5%)</b></font></td>";
             }
             $display_block .= "</tr>
             </table>
             </div><br>";
   $display_block .= "<h1>Details of your Project Allocation</h1>";
   // get allocation
   $get_alloc = "select projectid from $allocation where sid = '$_POST[sid]'";
   $get_alloc_res = mysql_query($get_alloc) or die(" Unable to get data from database. Query: $get_alloc");
   $get_alloc_det = mysql_fetch_array($get_alloc_res);
   $projectid = $get_alloc_det['projectid'];
   if ($projectid == 0) {
      $supervisors = "select supinitals from $special where sid = '$_POST[sid]'";
   } else {
      $supervisors = "select supinitals from $projectload where id = $projectid";
      $supervisors = "select f_supervisor from $master_name where id = $projectid";
   }
   $get_supers = mysql_query($supervisors) or die(" Unable to get supervisor data from database. Query: $supervisors");
   $super_block = "";
   while ($super_info = mysql_fetch_array($get_supers)) {
         $f_supervisor = $super_info['f_supervisor'];
         $sup_details = "select initials, room, email from $staff_names where fullname = '$f_supervisor'";
         $sup_details_res = mysql_query($sup_details) or die(" Unable to get data from database. Query: $sup_details");
         $sup_details_det = mysql_fetch_array($sup_details_res);
         $sup_initials = $sup_details_det['initials'];
         $sup_room = $sup_details_det['room'];
         $sup_email = $sup_details_det['email'];
         
         $super_block .= "<b>$f_supervisor</b> - Email <a href=\"mailto:$sup_email\">$sup_email</a><br>"; 
   }
   if ($projectid > 0) {
     $choice1 = "select count(choice1) from $selection where sid = '$_POST[sid]' and choice1 = $projectid";
     $get_choice1 = mysql_query($choice1) or die(" Unable to get data from database. Query: $choice1");
     $nchoice1 = mysql_fetch_array($get_choice1,MYSQL_NUM);
     if ($nchoice1[0] == 1) {
        $choice = "first";
     }
     $choice2 = "select count(choice2) from $selection where sid = '$_POST[sid]' and choice2 = $projectid";
     $get_choice2 = mysql_query($choice2) or die(" Unable to get data from database. Query: $choice2");
     $nchoice2 = mysql_fetch_array($get_choice2,MYSQL_NUM);
     if ($nchoice2[0] == 1) {
        $choice = "second";
     }
     $choice3 = "select count(choice3) from $selection where sid = '$_POST[sid]' and choice3 = $projectid";
     $get_choice3 = mysql_query($choice3) or die(" Unable to get data from database. Query: $choice3");
     $nchoice3 = mysql_fetch_array($get_choice3,MYSQL_NUM);
     if ($nchoice3[0] == 1) {
        $choice = "third";
     }
     $choice4 = "select count(choice4) from $selection where sid = '$_POST[sid]' and choice4 = $projectid";
     $get_choice4 = mysql_query($choice4) or die(" Unable to get data from database. Query: $choice4");
     $nchoice4 = mysql_fetch_array($get_choice4,MYSQL_NUM);
     if ($nchoice4[0] == 1) {
        $choice = "forth";
     }
     $choice5 = "select count(choice5) from $selection where sid = '$_POST[sid]' and choice5 = $projectid";
     $get_choice5 = mysql_query($choice5) or die(" Unable to get data from database. Query: $choice5");
     $nchoice5 = mysql_fetch_array($get_choice5,MYSQL_NUM);
     if ($nchoice5[0] == 1) {
        $choice = "fifth";
     }
     $get_prodet1 = "select f_title from $master_name where id = $projectid";
     $get_prodet1_res = mysql_query($get_prodet1) or die(" Unable to get data from database. Query: $get_prodet1");
     $get_prodet1_det = mysql_fetch_array($get_prodet1_res);
     $title = $get_prodet1_det['f_title'];
     $get_prodet2 = "select * from $project_details where master_id = $projectid";
     $get_prodet2_res = mysql_query($get_prodet2) or die(" Unable to get data from database. Query: $get_prodet2");
     $get_prodet2_det = mysql_fetch_array($get_prodet2_res);
     //$projnums = $get_prodet2_det['f_UGprojects'];
     if( $level == 3 ){
       $projnums = $get_prodet2_det['f_UGprojects']; 
     }else{
       $projnums = $get_prodet2_det['f_PGprojects']; 
     }     
     $allocated = "select count(sid) from $allocation where projectid = $projectid";
     $get_alloc = mysql_query($allocated) or die(" Unable to get data from database. Query: $allocated");
     $get_alloc_num = mysql_fetch_array($get_alloc,MYSQL_NUM);
     $numalloc = $get_alloc_num[0];
   } else {
      $get_title = "select title from $special where sid = '$_POST[sid]'";
      $get_title_res = mysql_query($get_title) or die(" Unable to get data from database. Query: $get_title");
      $get_title_det = mysql_fetch_array($get_title_res);
      $title = $get_title_det['title']; 
   }
   $display_block .= "<div align=\"left\">
             <table border=\"1\" >
             <tr>
             <td bgcolor=\"#C0C0C0\" colspan=\"3\"><font face=\"Arial\">Name: <b>$name</b> Username: <b>$username</b> SID: <b>$_POST[sid]</b></font></td>
             </tr>
             <tr>
             <td><font face=\"Arial\"><b>Allocated choice</b></td>
             <td><font face=\"Arial\"><b>Project (current allocation)</b></td>
             <td><font face=\"Arial\"><b>Supervisor(s) details</b></td>
             </tr>";
   if ($projectid > 0) {
      $display_block .= "<tr>";
      if ($choice != "") {
         $display_block .= "<td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>You have been allocated your <font color=\"red\">$choice</font> choice.</b></font></td>";
      } else {
         $display_block .= "<td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b><font color=\"red\">You could not be allocated to any of your choices.</font></b></font></td>";
      }
      $display_block .= "<td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>$projectid</b> - 
             <a href=\"viewproject.php?module1=$module1&year_begin=$year_begin&id=$projectid\" onClick=\"return popup(this, 'projinfo')\"><b>$title</b></a><br>(<b>$numalloc</b> 
             out of <b>$projnums</b> projects allocated)</font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\">$super_block</font></td>
             </tr>";
   } else {
      $display_block .= "<tr>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>You have been allocated a pre-agreed or ICLP project</b></font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>$title</b></font></td>
             <td bgcolor=\"#C0C0C0\"><font face=\"Arial\">$super_block</font></td>
             </tr>";
   }
	 
	 $display_block .= "</table>
             </div><br>";
	 
// commented out for 2010 as requested by PGW, replaced with above display block
/*   $display_block .= "</table>
             </div><br>
             <i>You now need to make contact with your allocated supervisor and arrange a time to discuss the project.<br> 		
						 Remember you will need to create a project plan, countersigned by your supervisor and submitted to the Student Support Office on level 4 by
             <b>$pp_date</b></i>
             <br><br>";*/

// check personal tutor

// commented out for 2009 as tutor information not uploaded in project database
/*
   $display_block .= "<h3>REMINDER: PDP tutorials</h3> <b>Your PDP tutorial is timetabled for $pdp and will take place at this time<br>
                     unless your personal tutor (see below) contacts you to rearrange.</b><br><br>";
   $get_tutor = "select tutinitials from $tutors where sid = '$_POST[sid]'";
   $get_tutor_res = mysql_query($get_tutor);
   $get_tutor_det = mysql_fetch_array($get_tutor_res);
   if ("$get_tutor_det[tutinitials]" == "") {
      $display_block .= "<font color=\"red\"><b>It appears you do not have a personal tutor - please<br> contact $owner as you will need to see someone for your PDP tutorial.</font><br><br>";
   } else {
      $sup_details = "select fullname, room, email from staff_names where initials = '$get_tutor_det[tutinitials]'";
      $sup_details_res = mysql_query($sup_details);
      $sup_details_det = mysql_fetch_array($sup_details_res);
      $display_block .= "Your personal tutor is <b>$sup_details_det[fullname]</b> (Room $sup_details_det[room]) - Email <a href=\"mailto:$sup_details_det[email]\">$sup_details_det[email]</a><br><br>"; 
   }
*/	 

} 
}
mysql_close();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />
<link media="Screen" href="database.css" type="text/css" rel="stylesheet" />
<link media="handheld, only screen and (max-width: 480px), only screen and (max-device-width: 480px)" href="mobile.css" type="text/css" rel="stylesheet" />

    <title>View Allocation of Projects</title>
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
        <link rel="stylesheet" type="text/css" href="database.css" />
  </head>
  <body>

<!-- Start of main container -->
    <div id="container">
<!-- Start of main content -->
      <div id="content"><a name="pagetop"></a>
      	<?php echo $display_block; ?>
      </div>
 			<div>
				<A href="./projects_indexm_all.php?<?php echo("module1=$module1&year_begin=$year_begin"); ?>">Return to main project selection page </A>
			</div>

<!-- End of main content -->
    </div>
  </body>
</html>
