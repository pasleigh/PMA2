<?php

$argv=$_SERVER['argv'];

// connect to the datbase
require('../connect.php');

$get_master = "select concat_ws(' - ', f_supervisor, f_title) as display_name from
   master_name where id = $argv[0]";
$get_master_res = mysql_query($get_master);
$display_name = stripslashes(mysql_result($get_master_res, 0, 'display_name'));
$get_details = "select date_added, date_modified, f_assocstaff, f_outline, f_relmods, f_relsites, f_skills,
   f_programmes, f_progs, f_UGprojects, f_PGprojects, f_furtherinfo from project_details where master_id = $argv[0]";
$get_details_res = mysql_query($get_details);

if (mysql_num_rows($get_details_res) > 0) {
   while ($add_info = mysql_fetch_array($get_details_res)) {
      $date_added = $add_info['date_added']; 
      $date_modified = $add_info['date_modified']; 
      $f_assocstaff = $add_info['f_assocstaff'];
      $f_outline = stripslashes($add_info['f_outline']);
      $f_relmods = stripslashes($add_info['f_relmods']);
      $f_relsites = stripslashes($add_info['f_relsites']);
      $f_skills = stripslashes($add_info['f_skills']);
      $f_programmes = stripslashes($add_info['f_programmes']);
      $f_progs = $add_info['f_progs'];
      $f_UGprojects = $add_info['f_UGprojects'];
      $f_PGprojects = $add_info['f_PGprojects'];
      $f_furtherinfo = stripslashes($add_info['f_furtherinfo']);

      $progs = split(" ", $f_progs);
      $tik_aero = "";
      $tik_auto = "";
      $tik_mech = "";
      $tik_medi = "";
      $tik_oilc = "";
      $tik_othr = "";
			$tik_mtcr = "";
      foreach ($progs as $prog) {
          $p_aero = $prog[0];
          $p_auto = $prog[1];
          $p_mech = $prog[2];
          $p_medi = $prog[3];
          $p_oilc = $prog[4];
          $p_othr = $prog[5];
          $p_mtcr = $prog[6];
      }
      if ($p_aero == 1) {
         $tik_aero = "<![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]-->";
      }
      if ($p_auto == 1) {
         $tik_auto = "<![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]-->";
      }
      if ($p_mech == 1) {
         $tik_mech = "<![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]-->";
      }
      if ($p_medi == 1) {
         $tik_medi = "<![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]-->";
      }
      if ($p_oilc == 1) {
         $tik_oilc = "<![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]-->";
      }
      if ($p_othr == 1) {
         $tik_othr = "<![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]-->";
      }
      if ($p_mtcr == 1) {
         $tik_mtcr = "<![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]-->";
      }			
      $new_info = "";
      if ($f_progs == "0") {
         $new_info = "<font color=red><b>NEW INFORMATION NEEDS TO BE ADDED ON MODIFY FORM A <![if !IE]>&#10003;<![endif]><!--[if gt IE 6]>&#10003;<![endif]--><!--[if lt IE 7]><div style=\"font:bold 1.2em 'wingdings 2'; text-align: center;\">P</div><![endif]--> WILL APPEAR NEXT TO PROGRAMME.</b></font><br>";
      }

      $display_block = "<font face=\"Arial\">Level 3 Projects (CIVE370X) and
      Level 5 Projects (CIVE5708)</font><br><br>
      <div align=\"left\">
      <table border=\"1\" width=\"650\" height=\"752\">
      <tr>
        <td width=\"200\" height=\"25\" bgcolor=\"#C0C0C0\"><b><font face=\"Arial\" size=\"2\">Project
          ID</font></b></td>
        <td width=\"446\" height=\"25\" bgcolor=\"#C0C0C0\"><font face=\"Arial\" size=\"2\"><b>$argv[0]</b> 
          (<em>Date entry added: $date_added; modified: $date_modified</em>)</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"32\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Principal
          Supervisor and title:</font></b></td>
        <td width=\"446\" height=\"32\"><font face=\"Arial\" size=\"2\">$display_name</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"35\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Co-Supervisor(s) (Academic staff only)</font></b></td>
        <td width=\"446\" height=\"35\"><font face=\"Arial\" size=\"2\">$f_assocstaff</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"65\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Project
          Outline</font></b>
          <p>&nbsp;</p>
          <p>&nbsp;</td>
        <td width=\"446\" height=\"65\"><font face=\"Arial\" size=\"2\">$f_outline</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"33\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Relevant
          Modules:</font></b></td>
        <td width=\"446\" height=\"33\"><font face=\"Arial\" size=\"2\">$f_relmods</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"40\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Relevant
          Web Sites:</font></b></td>
        <td width=\"446\" height=\"40\"><font face=\"Arial\" size=\"2\">$f_relsites</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"44\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Project Type:</font></b></td>
        <td width=\"446\" height=\"44\"><font face=\"Arial\" size=\"2\">$f_skills</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"12\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Appropriate
          to the following programmes of study:</font></b></td>
        <td width=\"446\" height=\"12\"><font color=red><b>NOT USED ON FORMS ANY MORE</b></font><br><font face=\"Arial\" size=\"2\">$f_programmes</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"12\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Appropriate
          to the following programmes of study:</font></b></td>
        <td width=\"446\" height=\"12\"><font face=\"Arial\" size=\"2\"> $new_info <b>Civ+Struct:</b> $tik_aero &nbsp;&nbsp;<b>Con-Man:</b> $tik_auto &nbsp;&nbsp;<b>Env Eng:</b> $tik_mech &nbsp;&nbsp;<b>Other:</b> $tik_medi &nbsp;&nbsp;</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"20\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Number
          of Level 3 (CIVE370X) projects available:</font></b></td>
        <td width=\"446\" height=\"20\"><font face=\"Arial\" size=\"2\">$f_UGprojects</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"19\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Number
          of Level 5 (CVE5708) projects available:</font></b></td>
        <td width=\"446\" height=\"19\"><font face=\"Arial\" size=\"2\">$f_PGprojects</font></td>
      </tr>
      <tr>
        <td width=\"200\" height=\"94\" bgcolor=\"#F2F5A9\"><b><font face=\"Arial\" size=\"2\">Further
          Information:</font></b>
          <p>&nbsp;</p>
          <p>&nbsp;
          </td>
        <td width=\"446\" height=\"94\"><font face=\"Arial\" size=\"2\">$f_furtherinfo</font></td>
      </tr>
      </table>
      </div>";
   }
$display_block .= "<br>";
}
mysql_close();
?>
<HTML>
<HEAD>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="../database.css" />

<TITLE>Project Details</TITLE>
<SCRIPT TYPE="text/javascript">
<!--
window.focus();
function targetopener(mylink, closeme, closeonly)
{
if (! (window.focus && window.opener))return true;
if (! closeonly)window.opener.location.href=mylink.href;
if (closeme)window.close();
return false;
}
//-->
</SCRIPT>
<SCRIPT TYPE="text/javascript">
<!--
window.focus();
//-->
</SCRIPT>
</HEAD>
<BODY link="#0000FF" vlink="#800080">
<A HREF="" onClick="return targetopener(this,true,true)">Close</A> | <A href="javascript:window.print()">HTML Print</A><br><br>
<?php echo $display_block; ?>
</BODY>
</HTML>
