<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
(@include_once("./set_dir_staff.php")) or die("Cannot find this file to include: set_dir_staff.php<BR>");


$num_cookie_days = 30;
if (isset($_SERVER['REMOTE_USER'])) {
    $user = get_server_username();
    setcookie('USERNAME', $user, [
        'expires'  => time() + $num_cookie_days * 24 * 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_SESSION['USERNAME'] = $user;
}elseif(isset($_SERVER['LOCAL_USER'])){
    $user = $_SERVER['LOCAL_USER'];
    setcookie('USERNAME', $user, [
        'expires'  => time() + $num_cookie_days * 24 * 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_SESSION['USERNAME'] = $user;
}
(@include_once("./config.php")) or die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) or die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./markstore_functions.php")) or die("Cannot include markstore_functions.php<BR>");
(@include_once("./connect_pdo.php")) or die("Cannot connect to the database<BR>");
(@include_once("./setup_tables.php")) or die("Cannot setup database tables<BR>");
(@include_once("./staff_functions.php")) or die("Cannot open staff_functions.php<BR>");
(@include_once("./admin2/adminusers.php")) or die("Cannot include adminusers<BR>");
(@include_once("./markstore_functions.php")) or die("Cannot include markstore_functions.php<BR>");
(@include_once("./table_functions.php")) or die("Cannot include table_functions.php<BR>");
// Select the years available in the projects setup table
// Check the table exists
if (DoesTableExist($db, $projects_setup)) {
} else {
    echo("Need to create the $projects_setup  table<BR>\n");
    exit();
}

$staff_username = "unknown";
if(array_key_exists('REMOTE_USER',$_SERVER)){
    $staff_username = get_server_username();
}else{
    if(isset($_SESSION['USERNAME'])){
        $staff_username = $_SESSION['USERNAME'];
    }
}
$staff_full_name = GetStaffNameFromUsername($db, $staff_username, $staff_names_default, false);
$staff_full_name = trim($staff_full_name);

// Check access permission
$access = false;
$display_block = "";
$display_pma_block = "block";
$display_feedback_block = "block";

if (empty($staff_full_name) || strtolower($staff_full_name) === 'unknown') {
    $safe_username  = htmlspecialchars($staff_username, ENT_QUOTES, 'UTF-8');
    $greeting_block = "<H4>Hello {$safe_username}, you do not seem to have a staff username on this system.</H4>";
    $greeting_block .= "<P>Please contact the manager of this system if you think there is a mistake.</P>";
    $greeting_block .= "<p><A target='self' href='..'>Return to the home page</a>.</P>";
    $display_pma_block = "none";
} else {
    $safe_fullname  = htmlspecialchars($staff_full_name, ENT_QUOTES, 'UTF-8');
    $safe_username  = htmlspecialchars($staff_username,  ENT_QUOTES, 'UTF-8');
    $greeting_block = "<h4>Hello {$safe_fullname} ({$safe_username})</h4>";
    $access = true;
}



$display_block .= $greeting_block;

$session_buttons = "";
$session_buttons2 = "";
$dash_buttons = "";

// The main block
if ($access) {
    // Get the list of years and modules from the project_setup table
    $get_data_query = "select DISTINCT(`year_begin`) from $projects_setup where 1 AND visible=1 order by year_begin desc";

    try {
        $setup = $db->query($get_data_query);
        $results = $setup->fetchAll();
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $projects_setup table<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    foreach ($results as $res) {
        $year_begin = $res['year_begin'];
        $year_end = $year_begin + 1;
        $display_block .= "<A href=\"./session_modules_bs.php?year_begin=$year_begin\" rel=\"external\" data-role=\"button\" data-theme=\"e\">Session $year_begin/$year_end</A>\n";
        $session_buttons .= "<A href='./session_modules_bs.php?year_begin=$year_begin' class='btn btn-lg btn-warning' target='_top'>Academic session $year_begin/$year_end  &raquo;</A>\n";

    }

}

$admin_link = date('l jS \of F Y, h:i A');
$admin_link = "<a href='#'> $admin_link</a>";
if (IsAdmin($db, $staff_username)) {
    $admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Super Admin</a>";
    $admin_link = "./admin2/";
    $admin_link_text = "Super Admin";
} else if (IsModuleAdmin($db, $staff_username, 'all', null, null)) {
    $admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Admin</a>";
    $admin_link = "./admin2/";
    $admin_link_text = "Admin";
} else {
    // Not admin
    $admin_link = "../..";
    $admin_link_text = "Back";
}

?>
<!DOCTYPE HTML>
<html>
<?php (@include_once("./$mobile_head_bs")) or die("Cannot find this file to include: $mobile_head_bs<BR>"); ?>

<body class="bg-dark text-white">
<!-- A grey horizontal navbar that becomes vertical on small screens -->
<nav class="navbar navbar-expand-sm fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <A class="navbar-brand" href="index.php"><?php echo("$school_title $running_title"); ?></A>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../.." class="nav-link px-2 text-white-50">Root options</a>
            </li>
        </ul>
        <div class="navbar-brand ms-auto">
            <a target='_top' class="navbar-brand" href='<?php echo("$admin_link"); ?>'>
                <i class="bi bi-gear"></i>
                <?php echo("$admin_link_text"); ?>
            </a>
        </div>

    </div>
</nav>

<div class="container bg-dark text-white">
    <div class="jumbotron jumbotron-header bg-dark text-white">
        <img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>

    <hr class="style1">
    <div class="container-fluid text-center">
        <?php echo("$greeting_block"); ?>
        <div id="pma_div" style="display: <?php echo("$display_pma_block"); ?>">
            <div class="well wellfb text-center bg-dark text-white">
                <H4>Project Module Marking</H4>
                <h5>Select year/session:</h5>
                <div class="btn-group-vertical btn-group-lg mx-auto">
                    <?php echo("$session_buttons"); ?>
                </div>
            </div>
        </div>
    </div>

</div>


<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
<script type="text/javascript" src="./frameworks/bootstrap-5.2.3-dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
