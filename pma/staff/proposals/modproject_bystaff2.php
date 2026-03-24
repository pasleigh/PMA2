<?php

// connect to the datbase
(@include_once("./set_dir_props.php")) OR die("Cannot find this file to include: set_dir_props.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("../staff_functions.php")) OR die("Cannot setup database tables");

(@include_once("../admin2/utility_functions.php")) OR die("Cannot include utility_functions.php");
(@include_once("../admin2/adminusers.php")) OR die("Cannot setup admin users");

if (array_key_exists('stid', $_REQUEST)==false){ $_REQUEST['stid'] = null; }
//if (array_key_exists('proj_id', $_POST)==false){ $_POST['proj_id'] = null; }
if (array_key_exists('op', $_POST)==false){ $_POST['op']=null; }
if (array_key_exists('sel_id', $_POST)==false){ $_POST['sel_id'] = null; }

$stid = $_REQUEST['stid'];
$proj_id = $_POST['sel_id'];

//
// Check the date for editiing has not passed
// 
$query = "select year_begin, dayname(staff_close), dayofmonth(staff_close), monthname(staff_close), month(staff_close), year(staff_close) from $projects_setup where module=$module1 and year_begin=$year_begin";
//$get_setup_list = mysql_query($get_setup) or die(" Unable to get database configuration.");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$setup_data = $results->fetch();;

$staff_close_date = $staff_close;
$str_staff_close_date = date("j F Y", strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$now_datetime = date("Y-m-d H:i:s");
$date_diff = day_diff($now_datetime, $staff_close_date);

//if (IsAdmin($db, $this_staff_username) or IsProjectAdmin($db, $this_staff_username, $staff_names)) {
if($date_diff > 1.0 && !IsAdmin($db, $this_staff_username) && !IsProjectAdmin($db, $this_staff_username, $staff_names))
{
    $display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
}else {
//
// Display the list of staff
//
    $display_block = "<H3>Display and edit project proposals by all staff</H3>";
    $display_block .= "<h3>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

    $display_block .= "<a href='#' data-role='button' data-inline='true' onclick='toggle_zero_project_links();'>Toggle showing staff with zero projects</a>";


    $query = "select * from $staff_names2 order by familyname, firstname ";
//$get_list_res = mysql_query($get_list) or die(" Unable to create staff list from database:<BR> query = $get_list <BR>");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $rows = $results->fetchAll();


    if ($results->rowCount() < 1) {
        //no records
        $display_block .= "<p><em>Sorry, there are no staff names in the database!</em></p>";
    } else {
        //has records
        $display_block .= "<div data-role='controlgroup'>";
        foreach ($rows as $recs) {
            $initials = $recs['initials'];
            $staffname = stripslashes($recs['fullname']);
            $staffid = $recs['staffid'];
            $inactive = $recs['inactive'];
            $staffid_anchor = "sid" . $staffid;

            $num_supervised = CountSupervisedProjectsByID($db, $master_name, $staffid);
            $num_second_marked = CountSecondMarkedProjectsByID($db, $project_details, $staffid);
            if ($inactive == 0) {
                $display_block .= "<a id='$staffid_anchor' href='list_staff_projects.php?pm_id=$pm_id&stid=$staffid' rel='external' data-role='button' data-theme=\"b\">$staffname [$num_supervised]</a>\n";
            }
        }
        $display_block .= "</div>\n";
    }


}
?>
<!DOCTYPE HTML>
<html>

<?php require("./$mobile_head"); ?>

<body>
<script type="text/javascript">
    //$(document).ready(

    $(document).ready(function () {
        toggle_zero_project_links();
    });

    function toggle_zero_project_links()
    {
        $("a:contains('[0]')").each(function () {
            $(this).toggle();
        });
    }


    function showUser(str) {
        if (str == "") {
            document.getElementById("txtHint").innerHTML = "";
            return;
        } else {
            if (window.XMLHttpRequest) {
                // code for IE7+, Firefox, Chrome, Opera, Safari
                xmlhttp = new XMLHttpRequest();
            } else {
                // code for IE6, IE5
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
                    document.getElementById("txtHint").innerHTML = xmlhttp.responseText;
                }
            };
            xmlhttp.open("GET","getuser.php?q="+str,true);
            xmlhttp.send();
        }
    }

    function submitForm(myValue)
    {
        //action=\"modproject.php?module1=$module1&year_begin=$year_begin\"
      var elem = document.getElementById("myop");
      elem.value = myValue;
      var mysel_id_elem = document.getElementById("mysel_id");
      mySelID = mysel_id_elem.value;
      //alert("Submitted" + myValue);
      //document.projectlist.submit();
      if(myValue=="mod")
      {
        document.projectlist.action = 'modproject.php?<?php echo("pm_id=$pm_id&sel_id=") ?>'+mySelID;
      }else if(myValue=="predel"){
        document.projectlist.action = 'delproject.php?<?php echo("pm_id=$pm_id&sel_id=") ?>'+mySelID;
      }else{// view
        document.projectlist.action = 'viewproject.php?<?php echo("pm_id=$pm_id&op=view&sel_id=") ?>'+mySelID;
      }
      document.projectlist.submit();
    }

    //var myDiv = document.getElementById("myDiv");
    //document.getElementById("stid").onchange = function(){
    //    alert("changed");
    //    myDiv.style.display = (this.selectedIndex == 0) ? "block" : "none";
    //}
    //)
</script>
<!--home page --> 
<div data-role="page"  data-theme="a" id="home"> 
<div id="jqm-homeheader" data-role="header"> 
	<!--<h1 id="jqm-logo"><img src="../images/<?php echo($header_image) ?>" alt="Leeds Eng Logo" width="320" height="87" /></h1>-->
    <h1 id="jqm-logo"><img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>
    <p><?php echo("$school_title - $running_title ") ?> <?php echo ("$year_begin/$year_end") ?></p>

</div>
<!---------------------END of HEADER FILE------------------------------------->
<!--++++Main page+++++++-->
<div data-role="content">
	
       <?php echo $display_block; ?>
       
 </div> <!-- content -->
 
 <div data-role="footer" data-position="fixed">
 	<H1>
 		<a href="./projects_indexm2.php<?php echo("?pm_id=$pm_id") ?>" data-role="button">Back</a>
 		</H1>
  </div><!-- /footer -->

</div> <!-- page -->
<!--++++----------End of page-----------+++++++-->

</body>
</html>
