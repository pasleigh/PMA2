<?PHP
// connect to the database
(@include_once("./set_dir_props.php")) OR die("Cannot find this file to include: set_dir_props.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../staff_student_functions.php")) OR die("Cannot find this file to include: staff_student_functions.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");

(@include_once("../admin2/utility_functions.php")) OR die("Cannot setup utility functions");
(@include_once("../admin2/adminusers.php")) OR die("Cannot setup admin users");

(@include_once("./project_display_functions_staff_bs.php")) OR die("Cannot open project_display_functions_staff_bs.php");

$display_block = "";

$is_staff = IsStaff($db,$this_staff_username,$staff_names);

if ($is_staff) {
    $main_body_visible = "block";
    $warning_visible = "none";
    $staff_full_name = $this_staff_full_name;

    if (!isset($_REQUEST['pm_id'])) {
        $_REQUEST['pm_id'] = null;
    }
    $pm_id = $_REQUEST['pm_id'];

    if (!isset($_REQUEST['p_id'])) {
        $_REQUEST['p_id'] = null;
    }
    $master_id = $_REQUEST['p_id'];

    if (!isset($_REQUEST['stid'])) {
        $_REQUEST['stid'] = null;
    }
    $staffid = $_REQUEST['stid'];

    if (!isset($_REQUEST['op'])) {
        $_REQUEST['op'] = null;
    }
    $OP = $_REQUEST['op'];

    if (!isset($_REQUEST['f_title'])) {
        $_REQUEST['f_title'] = null;
    }
    $f_title = $_REQUEST['f_title'];

    if (!isset($_REQUEST['f_supervisor_id'])) {
        $_REQUEST['f_supervisor_id'] = null;
    }
    $f_supervisor_id = $_REQUEST['f_supervisor_id'];

    if (!isset($_REQUEST['f_assocstaff_id'])) {
        $_REQUEST['f_assocstaff_id'] = null;
    }
    $f_assocstaff_id = $_REQUEST['f_assocstaff_id'];

    if (!isset($_REQUEST['f_outline'])) {
        $_REQUEST['f_outline'] = null;
    }
    $f_outline = $_REQUEST['f_outline'];

    if (!isset($_REQUEST['f_UGprojects'])) {
        $_REQUEST['f_UGprojects'] = null;
    }
    $f_UGprojects = $_REQUEST['f_UGprojects'];

    if (!isset($_REQUEST['f_PGprojects'])) {
        $_REQUEST['f_PGprojects'] = null;
    }
    $f_PGprojects = $_REQUEST['f_PGprojects'];

    for($i = 0 ; $i < sizeof($option) ; $i++){
        $fname = "f" . $option[$i];
        if (!isset($_REQUEST[$fname])) {
            $_REQUEST[$fname] = null;
        }
    }

    $query = "select * from $projects_setup where id=$pm_id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $res = $results->fetch();

    $module1 = $res['module'];
    $year_begin = $res['year_begin'];
    $year_end = $res['year_end'];
    $year_end_short = $year_end-2000;
    $module_prefix = $res['module_prefix'];
    $module_short_description = $res['module_short_desc'];
    $module_short_code = $res['short_code'];

    $staff_close_date = $res['staff_close'] . " 23:59";
    $str_staff_close_date = date("j F Y, H:i", strtotime($staff_close_date));
    $student_allocation_date = $res['alloc_date'];
    $str_student_allocation_date = date("j F Y", strtotime($student_allocation_date));
    $today = date("Y-m-d");

    $now_datetime = date("Y-m-d H:i:s");

    $display_block = "";
    $display_block_edit_buttons = "";

    $date_diff = day_diff($now_datetime, $staff_close_date);

    $project_heading = "$module_short_description $year_begin/$year_end_short - $module_prefix$module1$module_postfix - $module_short_code";

    // Check the dates
    $date_passed_display = "none";
    $date_passed_display_admin = "none";
    $active_button = "";
    if($date_diff > 1.0 ){
        $date_passed_display = "block";
        $active_button = "disabled";
        //$display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
        if (IsAdmin($db, $this_staff_username) or IsModuleAdmin($db, $this_staff_username, $pm_id, $module_short_code, $year_begin)) {
            $date_passed_display_admin = "block";
            $active_button = "";
        }
    }

    if ($_REQUEST['op'] != "add") {
        // present a new form
        $display_form_head = "<form method='post' onsubmit='return validate_form();' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>";

        for($i = 0 ; $i < sizeof($option) ; $i++){
            $option_value[$i] = "";
        }

        if(is_null($f_supervisor_id)){
            $f_supervisor_id = $this_staff_id;
        }

        if (is_null($f_PGprojects)) {
            $num_projects = 1;
        } else {
            $num_projects = $f_PGprojects;
        }

        $supervisor_select = GetStaffSelectID($db, $staff_names, 'f_supervisor_id', $f_supervisor_id);
        $secondmarker_select = GetStaffSelectID($db, $staff_names, 'f_assocstaff_id', $f_assocstaff_id);

        $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
        $program_variant_flags = GetProgramVariantFlagsStaff($db, $project_details, null);

        $display_form_tail = DisplayProjectSelectBlockStaff($db, null, $staff_names2, null, $staffid,
            null, null, null, $option_value, $option_text,
            $option, $option_flag,$option_flag_v,$option_flag_m, $program_variants, $program_variant_flags);

        $display_form_tail .= "<input type='hidden' name='op' value='add'>";
        //$display_form_tail .= "<p><input type='submit' name='submit' value='Add project proposal'></p>\n";
        $display_form_tail .= "<button type='submit' class='btn btn-success'>Submit this Project Proposal</button>";
        $display_form_tail .= "</form>";

    }else if ($_REQUEST['op'] == "add") {
        // add it to the data base
    }
/*

    */

}else{
    $main_body_visible = "none";
    $warning_visible = "block";
}

//$school_title = "School of Civil Engineering";
//$running_title = "Project Database";
//$year_begin = 2018;
//$year_end = 19;
//$module_short_code = "MENG";

//$warning_visible = "none";
//$main_body_visible = "block";

//$staff_full_name = "Andy";

$text_area_placement_text = "placeholder='Type your project description here...'";

// If editing existing entry then this should have the text from the database
$html_text_area_text = "";//"<H2>this is a header</H2> <UL><li>first</li><li>second</li></UL><P>And a new parahraph</P>";

// Used if html text from DB is blank
$text_project_title = "Is Concrete is King?";
$text_area_text = <<< END
So you want to discovery the secret of concrete's success? This is the project for you.

You will:
*  Do experiments in the lab to test various mixes and rebar arrangements
*  Undertake finite element simulations to help design the experiments and predict strength

Conclusions will be drawn on the ability of simulation to predict prototype structural properties.
END;
//"";//"* first\n* second\n* third row";

$header_image = "leeds_civil_header.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View/Edit Project Proposal</title>

    <?php (@include_once("./mobile_head_props.php")) OR die("Cannot find this file to include: mobile_head_props.php<BR>"); ?>

    <style type="text/css">
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 30px 0 0 0;
            font-family: Geneva, Verdana, sans-serif, helvetica;
        }

        .navbar-fixed-top {
            min-height: 10px;
        }

        .navbar-custom {
            color: #FFFFFF;
            background-color: #006600;
        }

        #tips {
            font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
        }

        #tips td, #tips th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        #tips tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        #tips tr:hover {
            background-color: #ddd;
        }

        #tips th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #4CAF50;
            color: white;
            font-size: larger;
        }

        .output {
            padding: 10px;
            min-height: 50px;
            border: 2px solid darkgreen;
            width: 100%;
            height: 100%;
        }

        #info02_div {
            border: 1px solid #ddd;
            padding: 8px;
            background: mintcream;
        }

        .border-blue {
            color: black;
            border: 2px solid #00d;
            background-color: lightskyblue;/*rgba(0, 114, 153, 0.31);*/
        }
        .maroon_heading{
                     color: maroon;
                     font-weight: bold;
                 }
        .required{
            color: deeppink;
        }
        .notice{
            color: yellow;
        }
        .form-control{
            width: 100%;
        }
        .btn.active[data-active-class="primary"] {
            color: #fff;
            background-color: #286090;
            border-color: #204d74;
        }
        .btn.active[data-active-class="success"] {
            color: #fff;
            background-color: #449d44;
            border-color: #398439;
        }
        .btn.active[data-active-class="info"] {
            color: #fff;
            background-color: #31b0d5;
            border-color: #269abc;
        }
        .btn.active[data-active-class="warning"] {
            color: #fff;
            background-color: #ec971f;
            border-color: #d58512;
        }
        .btn.active[data-active-class="danger"] {
            color: #fff;
            background-color: #c9302c;
            border-color: #ac2925;
        }

    </style>
    <script type="text/javascript">
        $(document).ready(function () {
            // Put the html retrieved from the database in the hidden text area
            $("#myHTMLTextarea").html('<?php echo("$html_text_area_text") ?>');

            var converter = new showdown.Converter();

            $("#myTextarea").keyup(function () {
                run_showdown();
            });
            $("#project_title").keyup(function () {
                run_showdown();
            });
            $('#info02_btn').on('click', function () {
                $("#info02").toggle();
            });

            function run_showdown() {
                var projectTitle = $("#project_title").val();
                // Getting the current value of textarea
                var currentText = $("#myTextarea").val();
                //var converter = new showdown.Converter(),
                var myhtml = converter.makeHtml('# ' + projectTitle + '\n\n' + currentText);
                // Setting the Div content
                $(".output").html(myhtml);
            }

            function run_turndown() {
                // Getting the html in the hidden textarea

                var currentHTML = $("#myHTMLTextarea").val();
                if (currentHTML != '') {
                    var turndownService = new TurndownService();
                    var mymarkdown = turndownService.turndown(currentHTML);
                    // Setting the Div content
                    $("#myTextarea").html(mymarkdown);

                    run_showdown();
                }
            }

            run_showdown();
            run_turndown();
        });
    </script>
</head>
<body>
<nav id="myNavbar" class="navbar navbar-default navbar-custom  navbar-inverse navbar-fixed-top"
     role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <A class="navbar-brand"
               href="#"><?php echo("$school_title - $running_title ") ?><?php echo("$year_begin/$year_end") ?></A>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" style="background-color: #006600;">
            <ul class="nav navbar-nav">
                <li><a href="#" target="_top"><?php echo("$module_short_code") ?></a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a id="id_admin" href="../" target="_top"> back</a></li>

            </ul>
        </div>
        <!-- dropdown -->

    </div>
</nav>

<div class="container-fluid ">
    <div class="row">
        <div class="span4"></div>
        <div class="span4">
            <!--<img class="center-block" src='<?php echo("../images/$header_image") ?>'/>-->
            <img class="center-block" src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
        </div>
        <div class="span4"></div>
    </div>
    <HR/>
</div>

<div class="container-fluid" style='display: <?php echo("$warning_visible"); ?>'>
    <H2>You do not have a staff username on this system.</H2>

    <p>Please <a href="../index.php">go back to the main home page</a></p>

</div>

<!------------------------ Main body of page begins here ---------------------------------------------->
<div class="container-fluid" style='display: <?php echo("$main_body_visible"); ?>' id="main_body">

    <div class="container">
        <h3>Editing Project proposals: <?php echo("$project_heading"); ?></h3>

        <h3><strong>Hello <?php echo("$staff_full_name"); ?></strong></h3>

        <p>This page allows you to edit existing, or enter new, project proposals.
            You enter text in the <span style="color: deepskyblue; font-weight: bold;">blue</span> boxes.<BR/>
            Type a title in the <em>Project Title</em> box and then the text of the project description in the
            large left hand blue box and what will appear to students will be shown in the right, green bordered,
            box.</p>

        <p>You can format the text by using <em>markdown</em> which a quick and simple way to get nice looking
            formmatting.
            i.e. headings, new-paragraphs, numbering, emphasis (bold, italic) and adding of links to web pages and
            images.
            Click the "Show formatting tips" button to the right for more details of formatting your proposal - to make
            it more appealing(?)</P>

        <H4>You will not be able to edit projects after this date: <span class="emph"><?php echo("$str_staff_close_date")?></span></H4>

        <H4 class="maroon_heading" style='display: <?php echo("$date_passed_display")?>'>
            The deadline for entering project proposals has passed.</H4>
        <H4 style='color: #6bc43d; font-weight: bold; display: <?php echo("$date_passed_display_admin")?>'>
            As module level administrator you have access to modifying projects past the deadline date. <span style='color: red;'>Do this sparingly.</span></H4>

    </div>

    <div class="container">
    <div class="row">
        <div class="col-sm-2">
        </div>
        <div class="col-sm-8">
        </div>
        <div class="col-sm-2">
            <label class="btn btn-info btn-sm" id="info02_btn">
                Show formatting tips
            </label>
        </div>
    </div>
    </div>

    <BR/>

    <div class='container'>

        <div id="info02" style="display: none; padding-left: 20px">

            <div class="container" id="info02_div" style="display: block; font-size: small ">
                <h3>Formatting the text is done with <em>markdown</em></h3>

                <P>Here is a table showing how to achieve the desired style using markdown examples.</P>

                <P>Much more markdown can be used see a detailed list here: <A
                        href="https://github.com/adam-p/markdown-here/wiki/Markdown-Cheatsheet" target="_blank">Markdown-Cheatsheet</A>
                </P>

                <table id="tips">
                    <thead>
                    <tr>
                        <th>The style you want to see</th>
                        <th>What you need to type in</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td><H1>This is a level 1 heading</H1></td>
                        <td># This is a level 1 heading<br>or<br>This is a level 1 heading<BR>===========</td>
                    </tr>
                    <tr>
                        <td><H2>This is a level 2 heading</H2></td>
                        <td>## This is a level 2 heading<br>or<br>This is a level 2 heading<BR>-----------</td>
                    </tr>
                    <tr>
                        <td><H3>This is a level 3 heading</H3></td>
                        <td>### This is a level 3 heading</td>
                    </tr>
                    <tr>
                        <td><p>First paragraph</p>

                            <p>Second paragraph</p></td>
                        <td><p>First paragraph (leave a blank line)</p>

                            <p>Second paragraph</p></td>
                    </tr>
                    <tr>
                        <td><p>This is <strong>bold text</strong></p>

                            <p>This is <em>italic text</em></p>

                            <p>This is <strong><em>bold and italic</em></strong></p></td>
                        <td><p>Italics are done with asterisks: *italic text* or underscores: _italic text_</p>

                            <p>Bold is done with double-asterisks: **bold text** or double-underscores: __bold text__</p>

                            <p>Bold & italic is a combination with double-asterisks and underscore: _**bold italic
                                text**_</p></td>
                    </tr>
                    <tr>
                        <td>Link to a web page: <A href="https://www.leeds.ac.uk">University of Leeds</A></td>
                        <td>[University of Leeds](https://www.leeds.ac.uk)</td>
                    </tr>
                    <tr>
                        <td>Link to an image: <img src="http://www.sleigh-munoz.co.uk/img/lago_pangipulli_s.png"
                                                   width="200px" alt="Lago Panguipulli and Volcan Choshquenco"
                                                   title="Lago Panguipulli and Volcan Choshquenco"></td>
                        <td><P>![alt text](url to image "Image Title Text")</p>e.g.
                            <p>![Lago Panguipulli and Volcan
                                Choshquenco](http://www.sleigh-munoz.co.uk/img/lago_pangipulli_s.png "Lago Panguipulli and
                                Volcan Choshquenco")</p></td>
                    </tr>
                    <tr>
                        <td>Youtube: Not directly, but put an image and a link to the page.
                            <p><a href="http://www.youtube.com/watch?feature=player_embedded&v=ub73Ar1kTd4
" target="_blank"><img src="http://img.youtube.com/vi/ub73Ar1kTd4/1.jpg"
                       alt="How to get to the fluids lab" width="240" height="180" border="10"/></a></p>
                        </td>
                        <td>
                            <P>[![IMAGE ALT TEXT
                                HERE](http://img.youtube.com/vi/YOUTUBE_VIDEO_ID_HERE/0.jpg)](http://www.youtube.com/watch?v=YOUTUBE_VIDEO_ID_HERE)</p>
                            e.g.
                            <p>[![How to get to the fluids
                                lab](http://img.youtube.com/vi/ub73Ar1kTd4/0.jpg)](http://www.youtube.com/watch?v=ub73Ar1kTd4)</p>
                        </td>
                    </tr>

                    <tr>
                        <td>Ordered list
                            <OL>
                                <LI>First list item</LI>
                                <LI>Second list item</LI>
                                <LI>Third list item</LI>
                            </OL>
                        </td>
                        <td>Just number them followed with a "."<BR>1. First list item<BR> 2. Second list item<BR>3. Third list item
                        </td>
                    </tr>
                    <tr>
                        <td>Bulleted (un-ordered) list
                            <uL>
                                <LI>First list item</LI>
                                <LI>Second list item</LI>
                                <LI>Third list item</LI>
                            </uL>
                        </td>
                        <td>Just pur an asterix in the first column<BR>* First list item<BR> * next list item<BR>* and the
                            next list item
                        </td>
                    </tr>
                    </tbody>

                </table>
            </div>

        </div>

        <?php echo("$display_form_head")?>

        <h4>Project Title</h4>
        <input type="text" class="form-control border-blue" id="project_title" value="<?php echo("$text_project_title") ?>">

        <BR/>
        <div class="row">
            <div class="col-sm-3">
                <h4>Select a principal supervisor</h4>
            </div>
            <div class="col-sm-3">
                <?php echo("$supervisor_select")?>
            </div>
            <div class="col-sm-3 required">
                Required
            </div>
            <div class="col-sm-3">
            </div>
        </div>
        <div class="row">
            <div class="col-sm-3">
                <h4>Suggest a second marker</h4>
            </div>
            <div class="col-sm-3">
                <?php echo("$secondmarker_select")?>
            </div>
            <div class="col-sm-3 required">
                Required
            </div>
            <div class="col-sm-3">
            </div>
        </div>
        <div class="row">
            <div class="col-sm-3">
                <h4>Number of projects offered</h4>
            </div>
            <div class="col-sm-3">
                <input class='form-control' type='number' min='0' max='10' name='f_PGprojects' id='f_PGprojects' value='<?php echo($num_projects)?>' size=4 maxlength=4>
            </div>
            <div class="col-sm-3 required">
                Required
            </div>
            <div class="col-sm-3">
            </div>
        </div>

        <BR/>
        <div class="row">
            <div class="col-sm-6">
                <h4>Type / Edit your proposal here</h4>
            </div>
            <div class="col-sm-6">
                <h4>This is how it will appear to students</h4>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6 ">
                <textarea class="border-blue" id="myTextarea" rows="15" cols="60"
                          placeholder='Type yout project description here...'><?php echo("$text_area_text") ?></textarea>
            </div>
            <div class="col-sm-6">
                <div class="output"></div>

            </div>
        </div>

        <div class="row">
            <h3>Level 1 Selectors</h3>
        <div class="btn-group" data-toggle="buttons-checkbox">
            <div class="btn btn-small btn-default" data-active-class="info">btn-group 1</div>
            <div class="btn btn-small btn-default" data-active-class="info">btn-group 2</div>
            <div class="btn btn-small btn-default" data-active-class="info">btn-group 3</div>
            <div class="btn btn-small btn-default" data-active-class="info">btn-group 4</div>
        </div>
            </div>

        <div class="row">
            <h3>Level 2 Selectors</h3>
            <div class="btn-group" data-toggle="buttons-checkbox">
                <div class="btn btn-small btn-default" data-active-class="success">btn-group 1</div>
                <div class="btn btn-small btn-default" data-active-class="success">btn-group 2</div>
                <div class="btn btn-small btn-default" data-active-class="success">btn-group 3</div>
                <div class="btn btn-small btn-default" data-active-class="success">btn-group 4</div>
            </div>
        </div>

        <div class="row">
            <h3>Level 3 Selectors</h3>
            <div class="btn-group" data-toggle="buttons-checkbox">
                <div class="btn btn-small btn-default" data-active-class="warning">btn-group 1</div>
                <div class="btn btn-small btn-default" data-active-class="warning">btn-group 2</div>
                <div class="btn btn-small btn-default" data-active-class="warning">btn-group 3</div>
                <div class="btn btn-small btn-default" data-active-class="warning">btn-group 4</div>
            </div>
        </div>

        <?php echo("$display_form_tail")?>

    </div>

    <div class='container' style="visibility: hidden">
        <textarea id="myHTMLTextarea" rows="15" cols="60" title="myHTML"></textarea>
    </div>
</body>
</html>
