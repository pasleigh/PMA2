<?php
error_reporting(E_STRICT);
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 28/09/2015
 * Time: 18:26
 */
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) or die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) or die("Cannot setup database tables<BR>");
(@include_once ("./utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once ("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");
(@include_once ("../table_functions.php")) OR die("Cannot read table_functions file<BR>");

(@include_once("./admin_functions.php")) or die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

$display_block .= "<h1>Upload a new header image</h1>";
$display_block .= "<div>The image should be a png of size 320x87px</div>";
//$display_block .= "<h2>Database table to be updated \"$assessment_criteria_table\"</h2>";

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

if ($access) {
// Check image table exists
    create_header_images_table_structure($db);

    if (isset($_FILES['image'])) {
        $errors = array();
        $file_name = $_FILES['image']['name'];
        $file_size = $_FILES['image']['size'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_type = $_FILES['image']['type'];
        $file = $files = $_FILES['image']['name'];
        $ext = explode('.', $file);
        $ext = end($ext);
        $file_ext = strtolower($ext);

        $extensions = array("jpeg", "jpg", "png");

        $image_info = getimagesize($_FILES["image"]["tmp_name"]);
        $image_width = $image_info[0];
        $image_height = $image_info[1];

        //echo("Image size $image_width x $image_height<BR><BR>");

        if (in_array($file_ext, $extensions) === false) {
            $errors[] = "extension not allowed, please choose a JPEG or PNG file of dimensions <= 320x90.";
        }

        if ($image_width > 320 || $image_height > 87) {
            $errors[] = "Image is too big ($image_width x $image_height), please choose a JPEG or PNG file of dimensions <= 320x87.";
        }

        if ($file_size > 2097152) {
            $errors[] = 'File size must be < 2 MB';
            //exit();
        }

        if (empty($errors) == true) {
            //move_uploaded_file($file_tmp,"uploads/".$file_name);
            //$csv_file_string = file_get_contents($file_tmp);
            //echo $csv_file_string;
            //echo "<BR>Success";
            //echo("Temp Filename: $file_tmp<BR>");
            $fp = fopen($file_tmp, 'r');
            $content = fread($fp, filesize($file_tmp));
            //$content = addslashes($content);
            $img_file_base64 = base64_encode($content);
            fclose($fp);

            $query = "INSERT INTO header_images (header_image, filename) VALUES ('$img_file_base64', '$file_name')";

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

            /*
            echo "<br>File uploaded $file_name uploaded<br><br>";

            $query = "SELECT header_image FROM header_images WHERE id=$id";
            $result = $db->query($query);
            //print_r($result);
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                echo('<img src="data:image/png;base64,' . $row['header_image'] . '" style="border-style: solid;" /><BR><BR>');
            }
            */
        } else {
            print_r($errors);
        }
    }
}else{
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}
?>


<!DOCTYPE HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>

<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">Upload a new header image</span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>


    <div>
        <BR>
    <form action="<?php echo("$_SERVER[PHP_SELF]"); ?>" method="POST" enctype="multipart/form-data">
        <input type="file" name="image" />
        <input type="submit" value="Upload this file"/>

        <ul>
            <li>Sent file: <?php echo $_FILES['image']['name'];  ?></li>
            <li>File size: <?php echo $_FILES['image']['size'];  ?> bytes, (<?php echo("$image_width x $image_height px")  ?>)</li>
            <li>File type: <?php echo $_FILES['image']['type'] ?></li>

            <li><img src="data:image/png;base64,<?php echo($img_file_base64); ?>" style="border-style: solid;" /></li>
        </ul>

    </form>
    </div>

    </div><!-- /content -->

    <div id="footer">
        <P>Return to the
            <a href="./index.php">Admin index page</a>.
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>