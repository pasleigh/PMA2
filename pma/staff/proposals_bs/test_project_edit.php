<?PHP
$school_title = "School of Civil Engineering";
$running_title = "Project Database";
$year_begin = 2018;
$year_end = 19;
$module_short_code = "MENG";

$warning_visible = "none";
$main_body_visible = "block";

$staff_full_name = "Andy";

$text_area_placement_text = "placeholder='Type yout project description here...'";

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

<div class="container-fluid" style='display: <?php echo("$main_body_visible"); ?>' id="main_body">

    <div class="container">
        <h1>Editing of project proposals</h1>

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

        <!--
    <div>
        <p>
            Contact Andy Sleigh if you have any questions <A href="mailto:P.A.Sleigh@leeds.ac.uk?subject=Workload model comment">P.A.Sleigh@leeds.ac.uk</A>.
        </p>
    </div>
-->


    </div>

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

        <h4>Project Title</h4>
        <input type="text" class="form-control border-blue" id="project_title" value="<?php echo("$text_project_title") ?>">

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


    </div>

    <div class='container' style="visibility: hidden">
        <textarea id="myHTMLTextarea" rows="15" cols="60" title="myHTML"></textarea>
    </div>
</body>
</html>
