<?php

// connect to the datbase
require('../connect.php');

$get_setup = "select year_begin from projects_setup where module=3810";
$get_setup_list = mysql_query($get_setup) or die(" Unable to get database configuration.");
$setup_data = mysql_fetch_array($get_setup_list);

$year = $setup_data['year_begin'];
if ($year == "") {
   header("Location: problem.php");
   exit;
}

$choice = "selection" . $year;
$students = "students" . $year;

if ($_POST['op'] != "select") {
   $display_block = "<h1>List of Level 3 Projects for CIVE370X</h1>";
   $display_block .= "<p>This is what the students will see.</p>";
   //$get_list = "select id, f_title from master_name order by f_title";
   $get_list = "select id, f_title from master_name order by id";
   $get_list_res = mysql_query($get_list) or die(" Unable to create list from database");

   if (mysql_num_rows($get_list_res) < 1) {
      //no records
      $display_block .= "<p><em>Sorry, there are no records to select!</em></p>";
   } else {
      //has records
      //$display_block .= "<h2><font color=red>Students will see only projects available to their programme of study</font></h2>";

      $selaero = "";
      $selauto = "";
      $selmech = "";
      $selmedi = "";
      $selmtcr = "";				
      $selall = "";
		
      if ($_POST['sel_id'] == "aero") {
         $selaero = "SELECTED";
         $get_tot = "select count(*) from $students where sel = 1 and programme like 'civil & struct%'";
         $selauto = "SELECTED";
         $get_tot = "select count(*) from $students where sel = 1 and programme like 'civil engg w con%'";
      } else if ($_POST['sel_id'] == "mech") {
         $selmech = "SELECTED";
         $get_tot = "select count(*) from $students where sel = 1 and programme like 'civil & env%'";
      } else if ($_POST['sel_id'] == "medi") {
         $selmedi = "SELECTED";
         $get_tot = "select count(*) from $students where sel = 1 and programme like 'medical%'";
      } else if ($_POST['sel_id'] == "mtcr") {
         $selmtcr = "SELECTED";
         $get_tot = "select count(*) from $students where sel = 1 and programme like 'mechatronic%'";	 
      } else {
         $selall = "SELECTED";
         $get_tot = "select count(*) from $students where sel = 1";
      }
      $get_tot_com = mysql_query($get_tot);
      $tot = mysql_fetch_array($get_tot_com,MYSQL_NUM);

/*
      $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">
                         <input type=\"hidden\" name=\"disp\" value=\"select\">
                         <P><strong>Select programme:</strong> <select name=\"sel_id\">
                         <option value=all $selall>All Programmes</option>
                         <option value=aero $selaero>Civil & Structural</option>
                         <option value=auto $selauto>Construction Management</option>
                         <option value=mech $selmech>Environmental Engineering</option>
                         <!--
                         <option value=medi $selmedi>Medical Engineering</option>
			 <option value=mtcr $selmtcr>Mechatronics</option>
                         -->
                         </select>
                         <p><input type=\"submit\" name=\"submit\" value=\"Choose Programme Filter\"></p>
                         </form>";
*/

      $display_block .= "
             <div align=\"left\">
             <table  width=\"750\">
             <tr class=\"yellow\">
             <td width=\"50\" >Project ID</td>
             <td width=\"330\"> Project </td>
             <td width=\"30\">Civ Str</td>
             <td width=\"30\">Con Man</td>
             <td width=\"30\">Env Eng</td>
             <td width=\"60\" >Number of projects available</td>
             <td width=\"60\" >Current availability</td>
             <td width=\"50\" >1st choice</td>
             <td width=\"50\" >2nd choice</td>
             <td width=\"50\" >3rd choice</td>
             <td width=\"50\" >4th choice</td>
             <td width=\"50\" >5th choice</td>
             </tr>";

      $total = 0;
      $tik = "<![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]-->";

      while ($recs = mysql_fetch_array($get_list_res)) {
            $id = $recs['id'];
            $display_name = stripslashes($recs['f_title']);
            $get_num = "select f_progs, f_UGprojects from project_details where master_id = $id";
            $get_num_res = mysql_query($get_num);
            $nums = mysql_fetch_array($get_num_res);
            $f_UGprojects = $nums['f_UGprojects']; 
            $f_progs = $nums['f_progs'];
            $progs = split(" ", $f_progs);
            foreach ($progs as $prog) {
                $p_aero = $prog[0];
                $p_auto = $prog[1];
                $p_mech = $prog[2];
                $p_medi = $prog[3];
                $p_oilc = $prog[4];
                $p_othr = $prog[5];
                $p_mtcr = $prog[6];								
            }
            $showproj = 0;
            if ($_POST['sel_id'] == "aero") {
               if ($p_aero == 1) {
                  $showproj = 1;
                  $tik_aero = $tik;
               }else{
                  $tik_aero = "";
               }
            } else if ($_POST['sel_id'] == "auto") {
               if ($p_auto == 1) {
                  $showproj = 1;
                  $tik_auto = $tik;
               }else{
                  $tik_auto = "";
               }
            } else if ($_POST['sel_id'] == "mech") {
               if ($p_mech == 1) {
                  $showproj = 1;
                  $tik_mech = $tik;
               }else{
                  $tik_mech = "";
               }
            } else if ($_POST['sel_id'] == "medi") {
               if ($p_medi == 1) {
                  $showproj = 1;
               }
            } else if ($_POST['sel_id'] == "mtcr") {
               if ($p_mtcr == 1) {
                  $showproj = 1;
               }							 
            }
            // Show ALL programmes and set the tik for each
            $showproj = 1;
            if ($p_aero == 1) {
               $tik_aero = $tik;
            }else{
               $tik_aero = "";
            }
            if ($p_auto == 1) {
               $tik_auto = $tik;
            }else{
               $tik_auto = "";
            }
            if ($p_mech == 1) {
               $tik_mech = $tik;
            }else{
               $tik_mech = "";
            }

            if (($f_UGprojects > 0) && ($f_progs != "0000010") && (($_POST['disp'] == "") || ($_POST['sel_id'] == "all") || ($showproj == 1))) {
               $get_choice1 = "select count(choice1) from $choice where choice1 = $id";
               $get_choice1_res = mysql_query($get_choice1);
               $choice1 = mysql_fetch_array($get_choice1_res,MYSQL_NUM);
               $get_choice2 = "select count(choice2) from $choice where choice2 = $id";
               $get_choice2_res = mysql_query($get_choice2);
               $choice2 = mysql_fetch_array($get_choice2_res,MYSQL_NUM);
               $get_choice3 = "select count(choice3) from $choice where choice3 = $id";
               $get_choice3_res = mysql_query($get_choice3);
               $choice3 = mysql_fetch_array($get_choice3_res,MYSQL_NUM);
               $get_choice4 = "select count(choice4) from $choice where choice4 = $id";
               $get_choice4_res = mysql_query($get_choice4);
               $choice4 = mysql_fetch_array($get_choice4_res,MYSQL_NUM);
               $get_choice5 = "select count(choice5) from $choice where choice5 = $id";
               $get_choice5_res = mysql_query($get_choice5);
               $choice5 = mysql_fetch_array($get_choice5_res,MYSQL_NUM);
               $avail = $f_UGprojects - $choice1[0] - $choice2[0]/2 - $choice3[0]/3 - $choice4[0]/4 - $choice5[0]/5;
               $pcavail = round($avail*100/$f_UGprojects);
               $bgcol="";
               if ($pcavail < 100) {
                  $bgcol = "yellow";
               } 
               if ($pcavail < 70) {
                  $bgcol = "green";
               } 
               if ($pcavail < 40) {
                  $bgcol = "blue";
               } 
               if ($pcavail < 10) {
                  $bgcol = "red";
               } 
               $display_block .= "<tr>
                  <td width=\"50\" align=center><font face=\"Arial\"><b>$id</b></font></td>
                  <td width=\"330\"><font face=\"Arial\"><a href=\"staffviewproject.php?$id\" onClick=\"return popup(this, 'projinfo')\">$display_name</a></font></td>";
                  $display_block .= "<td align=center>$tik_aero</td>";
                  $display_block .= "<td align=center>$tik_auto</td>";
                  $display_block .= "<td align=center>$tik_mech</td>";
                  $display_block .= "<td width=\"60\" align=center><font face=\"Arial\"><b>$f_UGprojects</b></font></td>";
                  if ($pcavail < 0) {
                     $display_block .= "<td width=\"60\" align=center bgcolor=\"$bgcol\"><font face=\"Arial\"><b><blink>$pcavail%</blink></b></font></td>";
                  } else {
                     $display_block .= "<td width=\"60\" align=center bgcolor=\"$bgcol\"><font face=\"Arial\"><b>$pcavail%</b></font></td>";
                  }
                  $display_block .= "<td width=\"50\" align=center><input type=\"radio\" name=\"choice1\" value=$id></td>
                  <td width=\"50\" align=center><input type=\"radio\" name=\"choice2\" value=$id></td>
                  <td width=\"50\" align=center><input type=\"radio\" name=\"choice3\" value=$id></td>
                  <td width=\"50\" align=center><input type=\"radio\" name=\"choice4\" value=$id></td>
                  <td width=\"50\" align=center><input type=\"radio\" name=\"choice5\" value=$id></td>
                  </tr>";
                  $total = $total + $f_UGprojects;
            }
      }
      $display_block .= "<tr>
             <td width=\"50\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"></font></td>
             <td width=\"300\" align=\"right\" colspan=\"4\" bgcolor=\"#C0C0C0\"><font face=\"Arial\"><b>Total number</b></font></td>
             <td width=\"100\" align=center bgcolor=\"#C0C0C0\"><b><font face=\"Arial\">$total</font></b></td>
             <td width=\"245\" align=center colspan=\"6\" bgcolor=\"#C0C0C0\"><b><font face=\"Arial\">$tot[0] choices made</font></b></td>
             </tr>
             </table> </div><br><br>";

   }
} 
mysql_close();
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    <title>List of Level 3 CIVE370X Projects</title>

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
    <link rel="stylesheet" type="text/css" href="../database.css" />
   </head>
<body>
<a href="index.php">Staff Access to the Project Database</a> | <a href="listproject_staff.php">List of Level 3 CIVE370X Projects</a>
<div id="navcontainer"> 

</div> 

  <div>
       <?php echo $display_block; ?>
  </div>
<!-- End of main content -->
    </div>
  </body>
</html>

