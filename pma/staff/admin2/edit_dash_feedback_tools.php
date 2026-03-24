<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot read markstore_functions file<BR>");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h2>\n";

if ($access) {

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
    $display_block .= "<h1>Edit dash feedback tools and visibility</h1>";

    $display_block .= "<h2>Database table $dash_projects_setup</h2>";

    if ($POST_OP == "submit_edited") {
        $postid = "dash_visible_staff";
        if (array_key_exists($postid, $_POST) == false) {
            $_POST[$postid] = 0;
        }
        $dash_visible_staff_chk = $_POST[$postid];

        $postid = "dash_visible_student";
        if (array_key_exists($postid, $_POST) == false) {
            $_POST[$postid] = 0;
        }
        $dash_visible_student_chk = $_POST[$postid];

        $id = 0;
        $query = "UPDATE $dash_projects_setup SET staff='$dash_visible_staff_chk', student='$dash_visible_student_chk' WHERE id=$id";

        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }

        $dash_count = $_POST['dash_count'];
        for ($i = 1; $i < $dash_count; $i++) {

            //$display_block .= "Submitting: <BR>\n";
            $id = $i;
            $postid = "description$i";
            $description = addslashes($_POST[$postid]);
            $postid = "title$i";
            $title = addslashes($_POST[$postid]);
            $postid = "subtitle$i";
            $subtitle = addslashes($_POST[$postid]);

            $postid = "visible$i";
            if (array_key_exists($postid, $_POST) == false) {
                $_POST[$postid] = 0;
            }
            $visible_chk = $_POST[$postid];

            $postid = "active$i";
            if (array_key_exists($postid, $_POST) == false) {
                $_POST[$postid] = 0;
            }
            $active_chk = $_POST[$postid];

            $postid = "staff$i";
            if (array_key_exists($postid, $_POST) == false) {
                $_POST[$postid] = 0;
            }
            $staff_chk = $_POST[$postid];

            $postid = "student$i";
            if (array_key_exists($postid, $_POST) == false) {
                $_POST[$postid] = 0;
            }
            $student_chk = $_POST[$postid];

            $query = "UPDATE $dash_projects_setup SET description='$description',title='$title',subtitle='$subtitle',visible='$visible_chk',active='$active_chk',staff='$staff_chk',student='$student_chk' WHERE id=$id";

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
        $display_block .= "<B>All updated</B><BR><hr>\n";
    }

    // Always display the page below here

    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>";

    // Get the list of dash tools from the dash_project_setup
    $query = "SELECT * FROM $dash_projects_setup ORDER BY id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $dash_visible_block= "";
    $table_block = "<TABLE id=\"dashinfo\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $table_block .= "
    <thead>
      <tr>
        <th>ID</th>
        <th>Table prefix</th>
        <th>Description</th>
        <th>Title</th>
        <th>Sub-title</th>
        <th>Visible</th>
        <th>Active</th>
        <th>Visible to Staff</th>
        <th>Visible to Students</th>
      </tr>
    </thead>\n";
    $table_block .= "<tbody>\n";
    $dash_count = 0;
    $rows = $results->fetchAll();
    foreach ($rows as $recs) {
        $id = $dash_count;
        if($id == 0) {
            $dash_visible_staff_chk = $recs['staff'];
            $checked = "";
            if ($dash_visible_staff_chk == 1) {
                $checked = "checked";
            }
            $dash_visible_block .= "<P>Make dash options visible to STAFF <input type='checkbox' name='dash_visible_staff' value='1' $checked></P>\n";
            $dash_visible_student_chk = $recs['student'];
            $checked = "";
            if ($dash_visible_student_chk == 1) {
                $checked = "checked";
            }
            $dash_visible_block .= "<P>Make dash options visible to STUDENTS <input type='checkbox' name='dash_visible_student' value='1' $checked></P>\n";
        }else{
            $table_prefix = $recs['prefix'];
            $description = stripslashes($recs['description']);
            $title = stripslashes($recs['title']);
            $subtitle = stripslashes($recs['subtitle']);
            $visible = $recs['visible'];
            $active = $recs['active'];
            $staff = $recs['staff'];
            $student = $recs['student'];

            $table_block .= "<TR>\n";
            $table_block .= "<TD><input type='hidden' name='id$id' value='$id'> $id</TD>\n";
            $table_block .= "<TD><input type='hidden' name='prefix$id' value='$table_prefix'>$table_prefix</TD>\n";
            $table_block .= "<TD><input type='text' name='description$id' value='$description' size='30'></TD>\n";
            $table_block .= "<TD><input type='text' name='title$id' value='$title' size='10'></TD>\n";
            $table_block .= "<TD><input type='text' name='subtitle$id' value='$subtitle' size='35'></TD>\n";
            $checked = "";
            if ($visible == 1) {
                $checked = "checked";
            }
            $table_block .= "<TD><input type='checkbox' name='visible$id' value='1' $checked></TD>\n";
            $checked = "";
            if ($active == 1) {
                $checked = "checked";
            }
            $table_block .= "<TD><input type='checkbox' name='active$id' value='1' $checked></TD>\n";
            $checked = "";
            if ($staff == 1) {
                $checked = "checked";
            }
            $table_block .= "<TD><input type='checkbox' name='staff$id' value='1' $checked></TD>\n";
            $checked = "";
            if ($student == 1) {
                $checked = "checked";
            }
            $table_block .= "<TD><input type='checkbox' name='student$id' value='1' $checked></TD>\n";
            $table_block .= "</TR>\n";
        }
        $dash_count++;
    }
    $table_block .= "</tbody>\n";
    $table_block .= "</TABLE>\n";

    $display_block .= $dash_visible_block;
    $display_block .= $table_block;

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_edited\">";
    $display_block .= "\n<input type=\"hidden\" name=\"dash_count\" value=\"$dash_count\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit changes to feedback tools.\"></p>";

    $display_block .= "</form>";
}



?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>
    $(function () {
        $("#dashinfo").dataTable(
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
            class="header">Edit Dash Feedbact Configuration ></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div class="footer_link" id="footer">
        <P>Return to the
            <a href="../index.php">Year index</a> -
            <a href="./index.php">Main admin page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
