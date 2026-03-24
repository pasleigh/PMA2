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
           //move_uploaded_file($file_tmp,"uploads/".$file_name);
           //$csv_file_string = file_get_contents($file_tmp);
           //echo $csv_file_string;
           //echo "<BR>Success";
           echo("File: $file_tmp<BR>");
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

<form action="<?php echo("$_SERVER[PHP_SELF]?pm_id=$pm_id"); ?>" method="POST" enctype="multipart/form-data">
    <input type="file" name="image" />
    <input type="submit"/>

    <ul>
        <li>Sent file: <?php echo $_FILES['image']['name'];  ?>
        <li>File size: <?php echo $_FILES['image']['size'];  ?>
        <li>File type: <?php echo $_FILES['image']['type'] ?>
        <li>File tmp name: <?php echo $_FILES['image']['tmp_name'] ?>
    </ul>

</form>

</body>
</html>