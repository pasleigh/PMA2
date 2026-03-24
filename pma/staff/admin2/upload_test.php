<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 28/09/2015
 * Time: 18:26
 */
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once ("./utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once ("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");

   if(isset($_FILES['image'])){
       $errors= array();
       $file_name = $_FILES['image']['name'];
       $file_size =$_FILES['image']['size'];
       $file_tmp =$_FILES['image']['tmp_name'];
       $file_type=$_FILES['image']['type'];
       $file_ext=strtolower(end(explode('.',$_FILES['image']['name'])));

       $extensions= array("jpeg","jpg","png","csv");

       if(in_array($file_ext,$extensions)=== false){
           $errors[]="extension not allowed, please choose a JPEG or PNG or csv file.";
       }

       if($file_size > 2097152){
           $errors[]='File size must be < 2 MB';
       }

       if(empty($errors)==true){
           echo("File: " . htmlspecialchars($file_tmp, ENT_QUOTES, 'UTF-8') . "<BR>");
           $importer = new CSVparse();
           $CSV_DATA = $importer->parse_file($file_tmp);
           print_r3("CSV_DATA",$CSV_DATA);
       }
       else{
           print_r($errors);
       }
   }
?>
<html>
<body>

<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?pm_id=<?php echo htmlspecialchars($pm_id ?? '', ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data">
    <input type="file" name="image" />
    <input type="submit"/>

    <?php if (!empty($_FILES['image']['name'])): ?>
    <ul>
        <li>Sent file: <?php echo htmlspecialchars($_FILES['image']['name'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li>File size: <?php echo (int)$_FILES['image']['size']; ?></li>
        <li>File type: <?php echo htmlspecialchars($_FILES['image']['type'], ENT_QUOTES, 'UTF-8'); ?></li>
    </ul>
    <?php endif; ?>

</form>

</body>
</html>