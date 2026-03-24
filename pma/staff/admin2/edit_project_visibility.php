<?php

(@include_once("ADMINconnect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot read markstore_functions file<BR>");


if (array_key_exists('op', $_POST) == false) {
    $_POST['op'] = null;
}
// if the form was submitted then enter it all into the database
//echo("OP=$_POST['op'] . <BR>\n");
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}
//echo("POST_OP=$POST_OP <BR>\n");

// see all the POST values
//foreach ($_POST as $key => $value){
//  echo "{$key} = {$value}<BR>\r\n";
//}
$display_block = "";
$display_block .= $connect_msg;
$display_block .= "<h1>Edit which projects are visible to staff users</h1>";


$display_block .= "<h2>Projects in the database</h2>";

if ($POST_OP == "submit_edited") {
    $display_block .= "Making these change to the visibility<BR>";
    $project_count = $_POST['project_count'];
    for ($i = 1; $i <= $project_count; $i++)
    {
        $postid = "p_id_$i";
        $pm_id = $_POST[$postid];
        $postid = "visibility_$i";
        if (array_key_exists($postid, $_POST) == false) {
            $_POST[$postid] = 0;
        }
        $visibility = $_POST[$postid];

        $display_block .= "Project  $i: pid=$pm_id visibility=$visibility<BR>\n";

        $query = "UPDATE $projects_setup SET ";
        $query .= "visible='" . $visibility . "' WHERE id=$pm_id ";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
    }
}
$display_block .= "<HR>";

$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">\n";

$query = "select * from $projects_setup where 1 order by year_begin desc";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}


//$display_block = "<TABLE  class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">\n";
$display_block .= "<TABLE id=\"projectinfo\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
$display_block .= "
    <thead>
      <tr>
        <th>Project ID</th>
        <th>Year</th>
        <th>Module code</th>
        <th>Short code</th>
        <th>Module description</th>
        <th>Visibility</th>
      </tr>
    </thead>";
$display_block .= "<tbody>\n";
$project_count = 0;
$rows = $results->fetchAll();
foreach ($rows as $recs) {

    $year = $recs['year_begin'];
    $module_number = $recs['module'];
    $module_short_code_s = $recs['short_code'];
    $module_prefix = $recs['module_prefix'];
    $module_short_desc = $recs['module_short_desc'];
    $pm_id_s = $recs['id'];
    $visibility = $recs['visible'];

    $project_count++;
    $display_block .= "<TR>\n";
    $display_block .= "<TD><input type=\"text\" name=\"p_id_$project_count\" value=\"$pm_id_s\"  size=5 maxlength=5 readonly></TD>";
    $display_block .= "<TD>$year</TD>";
    $display_block .= "<TD>$module_prefix$module_number</TD>";
    $display_block .= "<TD>$module_short_code_s</TD>";
    $display_block .= "<TD>$module_short_desc</TD>";
    $checked = "";
    if ($visibility == 1) {
        $checked = "checked";
    }
    $display_block .= "<TD><input type=\"checkbox\" name=\"visibility_$project_count\" value=\"1\" $checked></TD>\n";
    $display_block .= "</TR>\n";

}
$display_block .= "</tbody>\n";
$display_block .= "</TABLE>\n";

$display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_edited\">";
$display_block .= "\n<input type=\"hidden\" name=\"project_count\" value=\"$project_count\">";

$display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit changes to visibility of projects.\"></p>";

$display_block .= "</form>";
//---------------------------------------------------------------------------------

?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>
    $(function () {
        $("#projectinfo").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false,
                "columnDefs": [
                    {"className": "dt-center", "targets": "_all"}
                ]
            }
        );
    })
</script>
<BODY>
<div id="wrapper">
    <div id="header">
        <span
            class="header">Edit visibility of projects details - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div class="footer_link" id="footer">
        <P>Return to the
            <a href="./index.php">Admin index page</a>.
            or go to the
            <a href="../index.php">Year index page</a>.</P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
