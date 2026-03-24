<?php

// connect to the datbase
require('../connect.php');

$get_setup = "select year_begin, year_end, dayname(alloc_date), dayofmonth(alloc_date), monthname(alloc_date), month(alloc_date), year(alloc_date) from projects_setup where module=5825";
$get_setup_list = mysql_query($get_setup) or die(" Unable to get database configuration.");
$setup_data = mysql_fetch_array($get_setup_list);

$year = $setup_data['year_begin'];
if ($year == "") {
   header("Location: problem.php");
   exit;
}
// loading factors
$level3fac = 1.0;
$level4fac = 0.75;
$level5fac = 1.5;

// $projectload = "projectload" . $year;
// $projectload = "projectload" . $year;
$projectload = "projectload";
$UGselection = "selection" . $year;
$UGallocation = "allocation" . $year;
$lev4load = "lev4load";
$iclp = "iclp";
$special = "special" . $year;
$selection = "PGselect" . $year;
$allocation = "PGalloc" . $year;

$nowarray = getdate();
$open = mktime(9, 0, 0, $setup_data['month(alloc_date)'], $setup_data['dayofmonth(alloc_date)'], $setup_data['year(alloc_date)']);
$opdate = "9am, " . $setup_data['dayname(alloc_date)'] . " " . $setup_data['dayofmonth(alloc_date)'] . " " . $setup_data['monthname(alloc_date)'] . " " . $setup_data['year(alloc_date)'];
$year_range = $year . "-" . $setup_data['year_end'];

require('../ownerPG.php');

require('../viewpronumcorePG.php');

mysql_close();
?>
<HTML>
<HEAD>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" type="text/css" href="../database.css" />
<TITLE>View Selection statistices per supervisor</TITLE>
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
 
</HEAD>
<BODY link="#0000FF" vlink="#800080">
<?php echo $display_block; ?>
<a href="viewpronumPG.php">Level 5 CIVE5708, Project Allocation page</a>
</BODY>
</HTML>
