<?php
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./connect_pdo.php")) OR die("Cannot find this file to include: connect_pdo.php<BR>");

//$TABLE_NAME = "staff_names";

if (array_key_exists('table_name', $_POST) == false) {
    $_POST['table_name'] = null;
}
$TABLE_NAME = $_POST['table_name'];

echo("Let's see if the table: \"$TABLE_NAME\" exists<BR>");


try {
    if ($connection->query("DESCRIBE $TABLE_NAME")) {
        echo "$TABLE_NAME exists in database $dbName";
    } else {
        echo "$TABLE_NAME doesn't exist in database $dbName";
    }
} catch (PDOException $ex) {
    echo "An Error occured!<BR>";
    $err_msg = $ex->getMessage();
    echo("$err_msg <BR>");
}

$display_block = "";
$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">";
$display_block .= "<table>\n";


$display_block .= "<TR>\n";
$display_block .= "<TD>Table name</TD>\n";
$display_block .= "<TD><input type=\"text\" name=\"table_name\"  size=10 maxlength=30></TD>\n";
$display_block .= "</TR>\n";
$display_block .= "<table>\n";

$display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Check if this table exists.\"></p>";

$display_block .= "</form>";


?>


<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body>

<?php echo $display_block; ?>

</body>

</html>