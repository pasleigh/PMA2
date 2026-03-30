<?php
(@include_once("./set_dir_student.php")) OR die("Cannot find this file to include: set_dir_student.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./student_functions.php")) OR die("Cannot include student_function.php<BR>");

(@include_once("$ADMIN_DIR_VAL/utility_functions.php")) OR die("Cannot include utility_functions.php");
// Select the years available in the projects setup table
$display_block = "";
$display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>Project Selection</div>";

$student_username = get_server_username();

// Which dash feedback is available?
$query = "SELECT * FROM $dash_projects_setup";
try{
    $results = $db->query($query);
} catch (PDOException $ex) {
    echo "An Error occured accessing the $dash_projects_setup table<BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$recs = $results->fetchAll();

// Check that it is visible to students
$dash_visible_student = $recs[0]['student'];

if($dash_visible_student == 1){
    $display_block .= "<BR>";
    $display_block .= "<P>These feedback tools are available:</P>";

    // ... TODO
    for($i = 1 ; $i < $results->rowCount() ; $i++){
        $description = $recs[$i]['description'];
        $title = $recs[$i]['title'];
        $subtitle = $recs[$i]['description'];
    }
    $display_block .= "<P><em>$title</em>: $description - $subtitle</P>\n";

}else {
    $display_block .= "<BR>";
    $display_block .= "<P>The feedback tools are not yet available. <a href='./index.php'>Go back to the main index.</a></P>";
}

$footer_link = date('l jS \of F Y, h:i A');

?>

<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<!-- Minified Cookie Consent served from our CDN -->
<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/1.0.9/cookieconsent.min.js"></script>
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Be aware that this website uses cookies.","dismiss":"Got it!","learnMore":"More info","link":null,"theme":"light-bottom"};
</script>
<html>
<?php (@include_once("$STAFF_DIR_VAL/$mobile_head")) OR die("Cannot find this file to include: $mobile_head<BR>"); ?>

<body>

<!--home page -->
<div data-role="page" data-theme="a" id="home">

    <!---------------------START of HEADER------------------------------------->
    <div id="jqm-homeheader" data-role="header">
        <!--<h1 id="jqm-logo"><img src="<?php echo("../staff/images/$header_image"); ?>" alt="Logo" width="320" height="87"/></h1>-->
        <h1 id="jqm-logo"><img src="../staff/images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>
        <p><?php echo("$school_title $running_title"); ?></p>
    </div>
    <!---------------------END of HEADER------------------------------------->

    <!--++++Main page+++++++-->
    <div data-role="content">

        <div data-role="controlgroup">
            <?php echo("$display_block"); ?>
        </div>

    </div><!-- content -->

    <div data-role="footer" data-position="fixed">
        <H1 style="color: dimgray;">
            <?php echo("$footer_link"); ?>
        </H1>
    </div><!-- /footer -->

</div> <!-- page -->
<!--++++-------End of Main page---------+++++++-->

</body>
</html>

