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
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}

if ($access) {
// Check image table exists
    create_header_images_table_structure($db);

    if (isset($_FILES['image'])) {
        require_valid_csrf();
        $errors = array();
        $file_tmp  = $_FILES['image']['tmp_name'];
        $file_size = $_FILES['image']['size'];
        // Use finfo to verify actual MIME type — do not trust client-supplied type
        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file_tmp);
        $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

        $image_info   = getimagesize($file_tmp);
        $image_width  = $image_info ? $image_info[0] : 0;
        $image_height = $image_info ? $image_info[1] : 0;

        if (!array_key_exists($mime_type, $allowed_mimes)) {
            $errors[] = "File type not allowed. Please upload a JPEG or PNG image.";
        }

        if ($image_width > 320 || $image_height > 87) {
            $errors[] = "Image is too big ({$image_width} x {$image_height}). Maximum size is 320x87 px.";
        }

        if ($file_size > 2097152) {
            $errors[] = 'File size must be under 2 MB.';
        }

        if (empty($errors)) {
            // Generate a safe random filename — never use the original user-supplied name for storage
            $safe_ext      = $allowed_mimes[$mime_type];
            $safe_filename = bin2hex(random_bytes(16)) . '.' . $safe_ext;

            $fp             = fopen($file_tmp, 'r');
            $content        = fread($fp, filesize($file_tmp));
            $img_file_base64 = base64_encode($content);
            fclose($fp);

            try {
                $result = $db->prepare("INSERT INTO header_images (header_image, filename) VALUES (?, ?)");
                $result->execute([$img_file_base64, $safe_filename]);
            } catch (PDOException $ex) {
                echo "An Error occured!<BR>";
                echo htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8');
                exit();
            }
            $id = $db->lastInsertId();

            /*
            echo "<br>File uploaded $file_name uploaded<br><br>";

            $query = "SELECT header_image FROM header_images WHERE id=$id";
            $result = $db->query($query);
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
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Upload a new header image</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Admin index page</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
        <?php echo $display_block; ?>

    <div>
        <BR>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_token_field(); ?>
        <input type="file" name="image" />
        <input type="submit" value="Upload this file"/>

        <?php if (!empty($_FILES['image']['name'])): ?>
        <ul>
            <li>Sent file: <?php echo htmlspecialchars($_FILES['image']['name'], ENT_QUOTES, 'UTF-8'); ?></li>
            <li>File size: <?php echo (int)$_FILES['image']['size']; ?> bytes
                <?php if (!empty($image_width)): ?>(<?php echo (int)$image_width; ?> x <?php echo (int)$image_height; ?> px)<?php endif; ?>
            </li>
            <?php if (!empty($img_file_base64)): ?>
            <li><img src="data:image/png;base64,<?php echo $img_file_base64; ?>" style="border-style: solid;" /></li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

    </form>
    </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>