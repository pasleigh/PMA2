<?php
$first_use = true;
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) OR die("Cannot find this file to include: connect_pdo.php<BR>");
(@include_once("$STAFF_DIR_VAL/table_functions.php")) OR die("Cannot include table_functions.php<BR>");

if (array_key_exists('op', $_POST) == false) {
    $_POST['op'] = null;
}

if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}

$display_block = "";

$header_images_table = "header_images";
create_header_images_table_structure($db);

$header_images_dir = '/../images/';
$display_block .= "<h1>Upload the default header images</h1>";

if($POST_OP == ""){

    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">";

    $display_block .= "<table style='border-style: solid; border-width: 0px;'>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>These are the default header images. <BR>Confirm that you wish to upload them to the database.</TD>\n";
    $display_block .= "<TD>\n";
    // get the default images
    $handle = opendir(dirname(realpath(__FILE__)) . $header_images_dir);
    while ($file = readdir($handle)) {
        if ($file !== '.' && $file !== '..') {
            if (strpos($file, 'header.png')) {
                $checked="";
                if(strpos($file, 'default')){
                    $checked = "checked";
                }
                //$display_block .= "<div><input type='radio' name='header_image' value='$file' $checked><img src='../images/" . $file . "' border='2' /> $file<BR> </div>\n";
                $img_file = file_get_contents("../images/$file");
                $img_file_base64 = base64_encode($img_file);
                $display_block .= "<TR><td>";
                $display_block .= '<img src="data:image/png;base64,' . $img_file_base64 .'" style="border-style: solid; border-width: 1px;" /></td></TR>';

            }
        }
    }
    $display_block .= "</table>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_default_header_images\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"I confirm that I wish to upload these images.\"></p>";


    $display_block .= "</form>";
    $display_block .= "<hr>\n";

} elseif ($POST_OP === "submit_default_header_images") {

    $handle = opendir(dirname(realpath(__FILE__)) . $header_images_dir);
    while ($file_name = readdir($handle)) {
        if ($file_name !== '.' && $file_name !== '..') {
            if (strpos($file_name, 'header.png')){
                $full_file_name = ".$header_images_dir$file_name";

                $img_file = file_get_contents($full_file_name);
                $img_file_base64 = base64_encode($img_file);
                $display_block .= "<TR><td>";
                $display_block .= '<img src="data:image/png;base64,' . $img_file_base64 .'" style="border-style: solid; border-width: 1px;" /></td></TR>';

                // does this filename exist
                $query = "SELECT * FROM $header_images_table WHERE filename='$file_name'";
                try {
                    $result = $db->query($query);
                } catch (PDOException $ex) {
                    echo "An Error occured!<BR>";
                    $err_msg = $ex->getMessage();
                    echo("$err_msg <BR>");
                    exit();
                }
                $rows = $result->fetchAll();
                if(count($rows)==0) {

                    $query = "INSERT INTO $header_images_table (header_image, filename) VALUES ('$img_file_base64','$file_name')";

                    // mysql_query($query) or die("Error, query failed\n $query");
                    try {
                        $result = $db->query($query);
                    } catch (PDOException $ex) {
                        echo "An Error occured!<BR>";
                        $err_msg = $ex->getMessage();
                        echo("$err_msg <BR>");
                        exit();
                    }
                    $id = $db->lastInsertId();
                    $display_block .= "<p>File uploaded $file_name uploaded. id = $id</p>";
                }else{
                    $id = $rows[0]['id'];
                    $display_block .= "<p>File $file_name already exists in database. id=$id. So <strong>NOT Uploaded</strong></p>";
                }
            }
        }
    }

    $display_block .= "<BR>\n";

}
?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>

<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">First use details</span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
        <P>Return to the
            <a href="./index.php">Admin index page</a>.
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
