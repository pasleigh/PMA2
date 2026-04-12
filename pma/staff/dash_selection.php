<?php
//session_start();
(@include_once('./set_dir_staff.php')) or die('Cannot find this file to include: set_dir_staff.php<BR>');
(@include_once('./config.php')) or die('Cannot find this file to include: config.php<BR>');
(@include_once('./setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('./markstore_functions.php')) or die('Cannot include markstore_functions.php<BR>');
(@include_once('./connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('./setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('./staff_functions.php')) or die('Cannot open staff_functions.php<BR>');
(@include_once('./admin2/adminusers.php')) or die('Cannot include adminusers<BR>');
(@include_once('./markstore_functions.php')) or die('Cannot include markstore_functions.php<BR>');
(@include_once('./table_functions.php')) or die('Cannot include table_functions.php<BR>');

$staff_username = 'unknown';
if (array_key_exists('REMOTE_USER', $_SERVER)) {
    $staff_username = get_server_username();
} else {
    if (isset($_SESSION['USERNAME'])) {
        $staff_username = $_SESSION['USERNAME'];
    }
}
if (IsStaffB($db, $staff_username, $staff_names) == false) {
    echo 'You do not have access to this page. If you think this is an error please contact the system administrator.<BR>';
    echo "<p><A target='self' href='..'>Return to the home page</a>.</P>";
    exit();
}

$staff_username = get_server_username();
$staff_full_name = GetStaffNameFromUsername($db, $staff_username, $staff_names_default, false);
$staff_full_name = trim($staff_full_name);
$access = false;
if (empty($staff_full_name)) {
    $display_block = "<P>This user name: $staff_username . does not have an associated staff name. </P>";
    $display_block .= '<P>Please contact the manager of this system if you think there is a mistake.</P>';
    //$display_block .= "<p><A href='..'>Return to the home page</a>.</P>";
} else {
    $display_block = "<P>Hello $staff_full_name ($this_staff_username), select the feedback tool:</P>";
    $access = true;
}
if ($access) {
    // Which dash feedback is available?
    $query = "SELECT * FROM $dash_projects_setup";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $dash_projects_setup table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $recs = $results->fetchAll();

    // Check that it is visible to staff
    $dash_visible_staff = $recs[0]['staff'];

    if ($dash_visible_staff == 1) {
        $display_block .= '<BR>';
        $display_block .= '<P>These feedback tools are available:</P>';

        // ... TODO
        // the list
        /*
        for($i = 1 ; $i < $results->rowCount() ; $i++){
            $description = $recs[$i]['description'];
            $title = $recs[$i]['title'];
            $subtitle = $recs[$i]['description'];
            $staff_visible = $recs[$i]['staff'];
            if($staff_visible == 1) {
                $display_block .= "<P><em>$title</em>: $description - $subtitle</P>\n";
            }else{
                $display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
            }
        }
        */
        // PEGA
        $id = $pega_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            $display_block .= "<A href='./pega/index.php' rel='external' data-role='button' data-theme='e'>$title - $subtitle</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }
        // HAMID
        $id = $hamid_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            $display_block .= "<A href='./hamid/index.php' rel='external' data-role='button' data-theme='e'>$title - $subtitle</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }
        // DIST
        $id = $dist_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            $display_block .= "<A href='./dist/index.php' rel='external' data-role='button' data-theme='e'>$title - $subtitle</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }
        // CWT
        $id = $cwt_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            $display_block .= "<A href='./cwt/index.php' rel='external' data-role='button' data-theme='e'>$title - $subtitle</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }
    } else {
        $display_block .= '<BR>';
        $display_block .= "<P>The feedback tools are not yet available. <a href='./index.php'>Go back to the main index.</a></P>";
    }
}
$footer_link = date('l jS \of F Y, h:i A');
if (IsAdmin($db, $staff_username)) {
    $footer_link = '<a target="_self" href="./admin2/" style="color: dimgray;">You have <I>super</I> admin access</a> - use these powers VERY carefully!';
}
$footer_link .= " <A target='self' href='..' style='color: dimgray;'>Root Options</A>";
?>

<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<!-- Minified Cookie Consent served from our CDN -->
<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/1.0.9/cookieconsent.min.js"></script>
<script type="text/javascript">
    window.cookieconsent_options = {"message":"Be aware that this website uses cookies.","dismiss":"Got it!","learnMore":"More info","link":null,"theme":"light-bottom"};
</script>
<html>
<?php (@include_once("./$mobile_head")) or die("Cannot find this file to include: $mobile_head<BR>"); ?>

<body>

<!--home page -->
<div data-role="page" data-theme="a" id="home">

    <!---------------------START of HEADER------------------------------------->
    <div id="jqm-homeheader" data-role="header">
        <!--<h1 id="jqm-logo"><img src="<?php echo("./images/$header_image"); ?>" alt="Logo" width="320" height="87"/></h1>-->
        <h1 id="jqm-logo"><img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>

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
