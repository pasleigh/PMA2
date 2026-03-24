<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot open staff_functions.php<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot open staff_functions.php<BR>");

//echo("<table border=0>");
//foreach ($_SERVER as $key=>$val )
//{
//  echo "<tr><td>".$key."</td><td>" .$val."</tr>";
//}
//echo("</table>");


$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db,$this_staff_username, $this_staff_full_name, $staff_names,$access_phrase);

$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if($access) {
    if (array_key_exists('submit', $_REQUEST) == false) {
        $_REQUEST['op'] = null;
    }
    $POST_OP = $_REQUEST['op'];

    $display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>This page will process make all of the staff_names_SHORT_CODE_YEAR_BEGIN table and make them the same</h2>\n";
    $display_block .= "<h3>This invovles adjusting the corresponding tables:<BR>allocated_SHORT_CODE_YEAR_BEGIN<BR>master_name_SHORT_CODE_YEAR_BEGIN <BR>project_details_SHORT_CODE_YEAR_BEGIN </h3>\n";
    // Get a list of all of the staff_names tables and display them
    $display_block .= "<H3> The default staff names table is <span style='font-size: x-large;'>$staff_names_default</span> (also $staff)</H3>";
    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">";
    $display_block .= "<table>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD><B>Module Staff Names Table</B></TD>\n";
    $display_block .= "<TD><B>Corresponding Module Tables</B></TD>\n";
    $display_block .= "</TR>\n";

    $table_search ="staff_names_%";
    $query = "SHOW TABLES LIKE '$table_search'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo("Unable to update data to the table $students<BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $num = $results->rowCount();
    if ($num < 1) {
        //no records

    } else {
        //has records
        $rows = $results->fetchAll();
        foreach ($rows as $rec) {
            $staff_names_table = $rec[0];
            $post_fix = substr($staff_names_table,12);
            $allocated_table = "allocated_" . $post_fix;
            $master_name_table = "master_name_" . $post_fix;
            $project_details_table = "project_details_" . $post_fix;

            $checktable = $db->query("SHOW TABLES LIKE '$allocated_table'");
            if( $checktable->rowCount() > 0 ){
                $allocated_table .= " exists";
            }else{
                $allocated_table .= " DOES NOT exist";
            }
            $checktable = $db->query("SHOW TABLES LIKE '$master_name_table'");
            if( $checktable->rowCount() > 0 ){
                $master_name_table .= " exists";
            }else{
                $master_name_table .= " DOES NOT exist";
            }
            $checktable = $db->query("SHOW TABLES LIKE '$project_details_table'");
            if( $checktable->rowCount() > 0 ){
                $project_details_table .= " exists";
            }else{
                $project_details_table .= " DOES NOT exist";
            }

            $display_block .= "<TR>\n";
            $display_block .= "<TD style='vertical-align: top;'>$staff_names_table</TD>\n";
            $display_block .= "<TD>$allocated_table<BR>$master_name_table<BR>$project_details_table</TD>\n";
            $display_block .= "</TR>\n";
        }
    }
    $display_block .= "</table>\n";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"process_staff_names\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Process the staff names tables.\"></p>";

    $display_block .= "</form>";

    if ($POST_OP == "process_staff_names") {
        // Do the processing
        $display_block .= "<h2>About to start processing the $staff_names_default table</h2>\n";

        $table_search ="staff_names_%";
        $query = "SHOW TABLES LIKE '$table_search'";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $students<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $num = $results->rowCount();
        if ($num < 1) {
            //no records
            $display_block .= "<H2>Nothing to do ... you should not see this. Probably an error.</H2>";
        } else {
            //has records
            $rows = $results->fetchAll();
            foreach ($rows as $rec) {
                $staff_names_table = $rec[0];
                $post_fix = substr($staff_names_table, 12);
                $allocated_table = "allocated_" . $post_fix;
                $master_name_table = "master_name_" . $post_fix;
                $project_details_table = "project_details_" . $post_fix;

                $display_block .= "<BR><H1>Processing: $staff_names_table</H1>";

                // Master name as this is the easiest
                // Create a copy and work on that
                $display_block .= "<H2>Processing: $master_name_table</H2>";
                $master_name_table_copy = $master_name_table . "_copy";
                try {
                    $query = "CREATE TABLE IF NOT EXISTS $master_name_table_copy LIKE $master_name_table;";
                    $results = $db->query($query);
                    $query = "TRUNCATE TABLE $master_name_table_copy";
                    $results = $db->query($query);
                    $query = "INSERT INTO $master_name_table_copy SELECT * from $master_name_table";
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo("Unable to update data to the table $students<BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }

                // loop through all of the full_names in the master_name_copy
                $query = "SELECT DISTINCT(f_supervisor) FROM $master_name_table_copy";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo("Unable to update data to the table $students<BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }
                $recs = $results->fetchAll();
                $num = $results->rowCount();
                $display_block .= " Number of staff names in $master_name_table_copy = $num<BR> \n";
                foreach ($recs as $mrec) {
                    $staff_fullname = $mrec['f_supervisor'];
                    $staff_id = GetStaffIDFromName($db, $staff_fullname, $staff_names_default);
                    $display_block .= "<BR>Name: $staff_fullname. New ID: $staff_id";
                    $query = "UPDATE $master_name_table_copy set f_supervisor_id='$staff_id' WHERE f_supervisor='$staff_fullname'";
                    //$display_block .= $query;
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
                // Finished the master_name table updating
                // rename the original to _old
                // rename the _copy to the original
                $master_name_table_old = $master_name_table . "_old";
                $checktable = $db->query("SHOW TABLES LIKE '$master_name_table_old'");
                if ($checktable->rowCount() > 0) { // exists
                    // But don;t want to delete the original data
                    // so don;t delete it
                    /*
                    $query = "DROP TABLE IF EXISTS $master_name_table_old";
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo("An Error occured accessing the database in function: $this_function <BR>\n");
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    */


                } else {
                    // Does not exist so don't try to delete but copy the original
                    $query = "RENAME TABLE $master_name_table to $master_name_table_old";
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
                $query = "DROP TABLE IF EXISTS $master_name_table";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }
                $query = "RENAME TABLE $master_name_table_copy to $master_name_table";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }

                // DONE master_name table updating
                $display_block .= "<H2 style='font-size: large; color: #ab1471'>DONE Processing: $master_name_table</H2>";

                // ############ Now do project_details #####################################
                // project_details as this is the easiest
                // Create a copy and work on that
                $display_block .= "<H2>Processing: $project_details_table</H2>";
                $project_details_table_copy = $project_details_table . "_copy";
                try {
                    $query = "CREATE TABLE IF NOT EXISTS $project_details_table_copy LIKE $project_details_table;";
                    $results = $db->query($query);
                    $query = "TRUNCATE TABLE $project_details_table_copy";
                    $results = $db->query($query);
                    $query = "INSERT INTO $project_details_table_copy SELECT * from $project_details_table";
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo("Unable to update data to the table $students<BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }

                // loop through all of the full_names in the project_details_copy
                $query = "SELECT DISTINCT(f_assocstaff) FROM $project_details_table_copy";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo("Unable to update data to the table $students<BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }
                $recs = $results->fetchAll();
                $num = $results->rowCount();
                $display_block .= " Number of staff names in $project_details_table_copy = $num<BR> \n";
                foreach ($recs as $prec) {
                    $staff_fullname = $prec['f_assocstaff'];
                    $staff_id = GetStaffIDFromName($db, $staff_fullname, $staff_names_default);
                    $display_block .= "<BR>Name: $staff_fullname. New ID: $staff_id";
                    $query = "UPDATE $project_details_table_copy set f_assocstaff_id='$staff_id' WHERE f_assocstaff='$staff_fullname'";
                    //$display_block .= $query;
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
                // Finished the project_details table updating
                // rename the original to _old
                // rename the _copy to the original
                $project_details_table_old = $project_details_table . "_old";
                $checktable = $db->query("SHOW TABLES LIKE '$project_details_table_old'");
                if ($checktable->rowCount() > 0) { // exists
                    // But don;t want to delete the original data
                    // so don;t delete it
                    /*
                    $query = "DROP TABLE IF EXISTS $project_details_table_old";
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo("An Error occured accessing the database in function: $this_function <BR>\n");
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    */
                } else {
                    // doe not exist so copy the original
                    $query = "RENAME TABLE $project_details_table to $project_details_table_old";
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
                $query = "DROP TABLE IF EXISTS $project_details_table";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }
                $query = "RENAME TABLE $project_details_table_copy to $project_details_table";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }

                // DONE project_details table updating
                $display_block .= "<H2 style='font-size: large; color: #ab1471'>DONE Processing: $project_details_table</H2>";

                // ############ Now do $allocated #####################################
                //
                // Create a copy and work on that
                $display_block .= "<H2>Processing: $allocated_table </H2>";
                $allocated_table_old = $allocated_table . "_old";
                // if allocated_old exists then the conversion has been done and we can't do it again
                //if exists allocated_old
                $checktable = $db->query("SHOW TABLES LIKE '$allocated_table_old'");
                if ($checktable->rowCount() > 0) { // exist
                    // do nothing
                    // NOT DONE allocated_ table updating
                    $display_block .= "<H2 style='font-size: large; color: #ab1471'>Processing: $allocated_table NOT DONE as done $allocated_table_old exists</H2>";
                } else {
                    $allocated_table_copy = $allocated_table . "_copy";

                    try {
                        $query = "CREATE TABLE IF NOT EXISTS $allocated_table_copy LIKE $allocated_table;";
                        //echo("query $query<BR>");
                        $results = $db->query($query);
                        $query = "TRUNCATE TABLE $allocated_table_copy";
                        //echo("query $query<BR>");
                        $results = $db->query($query);
                        $query = "INSERT INTO $allocated_table_copy SELECT * from $allocated_table";
                        //echo("query $query<BR>");
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo("An Error occured accessing the database in function: $this_function <BR>\n");
                        echo("Unable to update data to the table $students<BR>\n");
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    // loop through every entry
                    $query = "SELECT * FROM $allocated_table_copy WHERE 1";
                    //echo("query $query<BR>");
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo("An Error occured accessing the database in function: $this_function <BR>\n");
                        echo("Unable to update data to the table $students<BR>\n");
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }

                    $num = $results->rowCount();
                    $rows = $results->fetchAll();
                    if ($num > 0) {
                        foreach ($rows as $allocrec) {
                            $project_id = $allocrec['projectid'];
                            //echo("proj id $project_id<BR>");
                            $supervisor_id = $allocrec['supervisor_id'];
                            $second_marker_id = $allocrec['second_marker_id'];

                            // update teh supervisor_id
                            // get the id from staff_names_default corresponding to teh naem in the staff_names for thr module
                            $old_staff_fullname = GetStaffNameFromID($db, $supervisor_id, $staff_names_table, 0);
                            $new_supervisor_id = GetStaffIDFromName($db, $old_staff_fullname, $staff_names_default);
                            // Replace this old_supervisor_id with the new_supervisor_id
                            $query = "UPDATE $allocated_table_copy set supervisor_id='$new_supervisor_id' WHERE projectid='$project_id'";
                            try {
                                $results = $db->query($query);
                            } catch (PDOException $ex) {
                                $this_function = __FUNCTION__;
                                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                                error_log($ex->getMessage());
                                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                                exit();
                            }
                            // update the second_marker_id
                            // get the id from staff_names_default corresponding to teh naem in the staff_names for thr module
                            $old_staff_fullname = GetStaffNameFromID($db, $second_marker_id, $staff_names_table, 0);
                            $new_second_marker_id = GetStaffIDFromName($db, $old_staff_fullname, $staff_names_default);
                            // Replace this old_supervisor_id with the new_supervisor_id
                            $query = "UPDATE $allocated_table_copy set second_marker_id='$new_second_marker_id' WHERE projectid='$project_id'";
                            try {
                                $results = $db->query($query);
                            } catch (PDOException $ex) {
                                $this_function = __FUNCTION__;
                                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                                error_log($ex->getMessage());
                                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                                exit();
                            }


                            // Now do all the otehr entries
                            $display_block .= "<BR>Project id: $project_id.";
                            for ($p = 1; $p <= 5; $p++) {
                                for ($m = 1; $m <= 3; $m++) {
                                    $field = "marker_p" . $p . "m" . $m . "_id";
                                    $old_marker_id = $allocrec[$field];
                                    if ($old_marker_id == 0) {
                                        // do nothing
                                        //echo("Filed $field. old_marker_id = $old_marker_id<BR>");
                                    } else {
                                        // get the id from staff_names_default corresponding to teh naem in the staff_names for thr module
                                        $old_staff_fullname = GetStaffNameFromID($db, $old_marker_id, $staff_names_table, 0);
                                        $new_marker_id = GetStaffIDFromName($db, $old_staff_fullname, $staff_names_default);
                                        // Replace this old_marker_id with the new_marker_id
                                        $query = "UPDATE $allocated_table_copy set $field='$new_marker_id' WHERE projectid='$project_id'";
                                        //echo("query $query<BR>");
                                        //$display_block .= $query;
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
                                }
                            }
                            // done all the markers
                        }
                        // rename the original to alloced_ _old
                        $query = "RENAME TABLE $allocated_table to $allocated_table_old";
                        try {
                            $results = $db->query($query);
                        } catch (PDOException $ex) {
                            $this_function = __FUNCTION__;
                            echo("An Error occured accessing the database in function: $this_function <BR>\n");
                            error_log($ex->getMessage());
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                        // rename the _copy to the original name
                        $query = "RENAME TABLE $allocated_table_copy to $allocated_table";
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
                    // DONE allocated_ table updating
                    $display_block .= "<H2 style='font-size: large; color: #ab1471'>DONE Processing: $allocated_table</H2>";
                }


                // rename the staff_names table to _old
                // copy teh entries of the staff_names_default table into the staff_names table
                $staff_names_table_old = $staff_names_table . "_old";
                $checktable = $db->query("SHOW TABLES LIKE '$staff_names_table_old'");
                if ($checktable->rowCount() > 0) { // exist
                    // do nothing
                    $display_block .= "<p style='font-size: large; color: #ab1471'>$staff_names_table_old exists so not overwritten</p>";
                } else {
                    try {
                        $query = "CREATE TABLE IF NOT EXISTS $staff_names_table_old LIKE $staff_names_table;";
                        $results = $db->query($query);
                        $query = "TRUNCATE TABLE $staff_names_table_old";
                        $results = $db->query($query);
                        $query = "INSERT INTO $staff_names_table_old SELECT * FROM $staff_names_table";
                        $results = $db->query($query);

                        // Copy the $staff_names_default table data into the $staff_names table
                        $query = "TRUNCATE TABLE $staff_names_table";
                        $results = $db->query($query);
                        $query = "INSERT INTO $staff_names_table SELECT * FROM  $staff_names_default";
                        $results = $db->query($query);
                        $display_block .= "<p style='font-size: large; color: #ab1471'>$staff_names_table has been overwritten from $staff_names_default</p>";
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo("An Error occured accessing the database in function: $this_function <BR>\n");
                        echo("Unable to update data to the table $students<BR>\n");
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                }
            }
        }


        $display_block .= "<h2 style='font-size: large; color: red'>Done All</h2>\n";

    }// End of POST select


}
?>
<HTML>
<HEAD>
    <?php
    (@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
    ?>
    <style>
        table {
            border-collapse: collapse;
        }

        table, td, th {
            border: 1px solid black;
        }
    </style>
</HEAD>
<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">Set up and amend projects (<?php echo("$this_staff_username"); ?>)</span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
        <P class="footer_link">Go to the
        <a href="../index.php">Index page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
