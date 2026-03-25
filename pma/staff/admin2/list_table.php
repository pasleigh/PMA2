<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) OR die("Cannot find this file to include: connect_pdo.php<BR>");
(@include_once("$STAFF_DIR_VAL/table_functions.php")) OR die("Cannot include table_functions.php<BR>");

if (array_key_exists('op', $_POST) == false) {
    $_POST['op'] = null;
}
// if the form was submitted then enter it all into the database
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}
if(array_key_exists('table_name',$_POST)){
    $table_name = $_POST['table_name'];
}else{
    $table_name = "";
    $table_name = "definition";
}

// see all the POST values
foreach ($_POST as $key => $value){
}

$display_block = "";

$display_block .= "<h1>This page lists the contents of a table</h1>";

//$display_block = "<P>Database definitions table: $definition_table_name</P>";

$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">";

$display_block .= "<table>\n";
$display_block .= "<TR>\n";
$display_block .= "<TD>Table name </TD>\n";
$display_block .= "<TD><input type=\"text\" name=\"table_name\"  value=\"$table_name\" size=30 maxlength=30></TD>\n";
$display_block .= "</TR>\n";
$display_block .= "</table>\n";

$display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_new\">";

$display_block .= "<p><input type=\"submit\" id=\"submit_list\" name=\"submit_list\" value=\"List table\"></p>";
$display_block .= "<p><input type=\"submit\" id=\"submit_list_all\" name=\"submit_list_all\" value=\"List all tables\"></p>";

$display_block .= "</form>";

$display_block .= "<hr>\n";

if(array_key_exists('submit_list',$_POST)) {
    $query = "DESCRIBE `$table_name`";
    try {
        $res = $db->query($query);
        // my_table exists
        $display_block .= "<h1>The table \"$table_name\" exists in the database</h1>";
        $display_block .= "<TABLE>";
        $display_block .= "<TR>";
        foreach ($res as $rec){
            $col_name = $rec['Field'];
            $col_names[] = $col_name;
            $display_block .= "<TD>$col_name</TD>";
            //$display_block .= "<p>$col_name</P>";
        }
        $display_block .= "</TR>";
        // get the data
        $query = "SELECT * FROM $table_name";
        $res = $db->query($query);
        foreach($res as $rec){
            $display_block .= "<TR>";
            for($i = 0; $i < count($col_names); $i++){
                //$display_block .= $rec[$col_names[$i]] . "  ";
                $display_block .= "<TD>" . $rec[$col_names[$i]] . "</TD>";
            }
            $display_block .= "</TR>";
        }
        $display_block .= "</TABLE>";
    }catch (PDOException $ex) {
        $display_block .= "<h1>The table \"$table_name\" DOES NOT exist in the database</h1>";
    }
}

if(array_key_exists('submit_list_all',$_POST)) {
    $query = "SHOW TABLES";
    $res = $db->query($query);
    foreach($res as $rec) {
        $display_block .= $rec[0]."<BR>";
    }
}
?>

<HTML>

<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<style>
    table, th, td {
        border: 1px solid black;
        border-collapse: collapse;
    }
</style>
<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">Table contents listing</span>
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
