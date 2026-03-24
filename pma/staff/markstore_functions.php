<?PHP
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

function IsMarked($db, $sid, $markstore)
{
    $isMarked = false;
    $query = "select * from $markstore where sid=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();
    if ($num_rows > 0) {
        //$recs = $results->fetch();
        $isMarked = true;
    }

    return $isMarked;
}

function GetThisMark($db, $sid, $part, $markstore)
{
    $thisMark = -1;
    $query = "select * from $markstore where sid=$sid";
    //$get_list_res = mysql_query($get_list) or die("Unable to marks list from table $markstore.<BR>Query: $get_list\n");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();
    if ($num_rows > 0) {
        $recs = $results->fetch();
        $thisMark = $recs['total'];
    }

    return $thisMark;
}

function GetTotalForPart($db, $sid, $part, $markstore_totals)
{
    $total = -1;
    $query = "select * from $markstore_totals where sid=$sid";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();
    if ($num_rows > 0) {
        $recs = $results->fetch();
        $entry = "retmark$part";
        $total = $recs[$entry];
    } else {
        //echo("$sid: $get_list<BR>");
    }

    return $total;
}

function CountThirdMarkersRequired($db, $part, $markstore_totals)
{
    $count = 0;

    $entry = "third_marker_req" . $part;
    $query = "select * from $markstore_totals where $entry=1";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();

    return $num_rows;
}

function IsThirdMarkerRequired($db, $sid, $part, $markstore_totals)
{

    $query = "select * from $markstore_totals where SID='$sid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        $recs = $results->fetch();
        $entry = "third_marker_req$part";
        $third_marker_req = $recs[$entry];
        if ($third_marker_req == 1) {
            return true;
        }
    }

    return false;
}

function SetThirdMarkerRequired($db, $sid, $part, $markstore_totals)
{
    $third_marker_entry = "third_marker_req" . $part;

    // Need to check if the SID already has an entry or not
    $query = "select count(SID) from $markstore_totals where SID=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $get_marks_res = $results->fetch();

    if ($get_marks_res[0] == 0) {
        $query = "insert $markstore_totals set SID='$sid', $third_marker_entry=1";
    } else {
        $query = "update $markstore_totals set $third_marker_entry=1 where SID='$sid'";
    }

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }

    return "<p>Done</p>";
}

function HaveAllMarkersMarked($db, $sid, $part, $num_markers, $markstores, $markstore_totals)
{
    //echo("part = $part, num_markers = $num_markers<BR>");
    // $num_markers is the default number of markers.
    // If it is two and the third marker has been requested then there needs to be completion of three markers checked.
    for ($j = 1; $j <= $num_markers; $j++) {
        if (IsMarked($db, $sid, $markstores[$part][$j]) == false) {
            // found one that has not been marked
            return false;
        }
    }

    if ($num_markers == 2) {
        $query = "select * from $markstore_totals where sid=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");

            exit();
        }
        $num_rows = $results->rowCount();

        if ($num_rows > 0) {
            $recs = $results->fetch();
            $entry = "third_marker_req$part";
            $third_marker_req = $recs[$entry];
            if ($third_marker_req == 1) {
                if (IsMarked($db, $sid, $markstores[$part][$num_markers + 1]) == false) {
                    // not been marked
                    return false;
                }
            }
        }
    }

    return true;
}

function HaveAllPartsBeenMarked($db, $sid, $num_parts, $markstore_totals)
{
    for ($i = 1; $i <= $num_parts; $i++) {
        $part_mark = GetTotalForPart($db, $sid, $i, $markstore_totals);
        if ($part_mark < 0.01) {
            // No marks recorded
            return false;
        }
    }
    return true;
}

function SetRecalculatedOverallTotal($db, $sid, $num_parts, $part_percents, $markstore_totals)
{
    $total = 0.0;
    for ($i = 1; $i <= $num_parts; $i++) {
        $part_mark = GetTotalForPart($db, $sid, $i, $markstore_totals);
        if ($part_mark < 0.01) {
            // No marks recorded
            return -1;
        }
        $total += $part_mark * $part_percents[$i] * 0.01;
    }

    $entry = "total";
    $query = "update $markstore_totals set $entry=$total where SID='$sid'";

    try {
        $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }

    return $total;
}

function GetTotalFromAllMarkers($db, $sid, $part, $num_markers, $markstores)
{
    $total = 0.0;
    for ($j = 1; $j <= $num_markers; $j++) {
        if (IsMarked($db, $sid, $markstores[$part][$j]) == false) {
            // found one that has not been marked
            return -2;
        }
        $total += GetThisMark($db, $sid, $part, $markstores[$part][$j]);
    }
    return $total;
}

function GetDifference($db, $sid, $part, $markstores)
{
    // return the difference between the first and second marker
    // Assumes there are two markers
    $mark1 = GetThisMark($db, $sid, $part, $markstores[$part][1]);
    $mark2 = GetThisMark($db, $sid, $part, $markstores[$part][2]);
    $diff = abs($mark1 - $mark2);
    return $diff;
}

function GetMarkStoreListFromMarkMethodString($mark_method_string, $markstore1, $markstore2, $markstore3)
{
    $mark_method_array = GetMarkMethodAsArray($mark_method_string);

    $markstore_list = array();
    $count = 0;

    $markstore_list[0] = "";
    $count++;

    if ($mark_method_array[4] == 1) {
        $markstore_list[$count] = $markstore1;
        // This means that only the Supervisor (marker 1) will be displayed for an overwritten mark
        return $markstore_list;
    }

    if ($mark_method_array[1] == 1) {
        $markstore_list[$count] = $markstore1;
        $count++;
    }
    if ($mark_method_array[2] == 1) {
        $markstore_list[$count] = $markstore2;
        $count++;
    }
    if ($mark_method_array[3] == 1) {
        $markstore_list[$count] = $markstore3;
    }

    return $markstore_list;
}

function GetMarkerListFromMarkMethodString($mark_method_string, $marker1, $marker2, $marker3)
{
    $mark_method_array = GetMarkMethodAsArray($mark_method_string);

    $marker_list = array();
    $count = 0;

    $marker_list[0] = "";
    $count++;

    if ($mark_method_array[4] == 1) {
        $marker_list[$count] = $marker1;
        // This means that only the Supervisor (marker 1) will be displayed for an overwritten mark
        return $marker_list;
    }

    if ($mark_method_array[1] == 1) {
        $marker_list[$count] = $marker1;
        $count++;
    }
    if ($mark_method_array[2] == 1) {
        $marker_list[$count] = $marker2;
        $count++;
    }
    if ($mark_method_array[3] == 1) {
        $marker_list[$count] = $marker3;
    }

    return $marker_list;
}

function GetMarkMethodString($db, $sid, $part, $markstore_totals)
{
    $mark_method_string = "9999";

    $query = "select * from $markstore_totals where sid=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        $recs = $results->fetch();
        $entry = "mark_method$part";
        if($recs){
            if($recs[$entry] !== null){
                $mark_method_string = $recs[$entry];
            }
        }
    }
    if(strlen($mark_method_string) != 4){
        $mark_method_string = "0001";
    }
    //print_r3("mark method len ",strlen($mark_method_string));
    return $mark_method_string;
}

function GetMarkReasonString($db, $sid, $part, $markstore_totals)
{
    $mark_reason_string = "";

    $query = "select * from $markstore_totals where sid=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        $recs = $results->fetch();
        $entry = "mark_reason$part";
        $mark_reason_string = $recs[$entry];
    }

    return $mark_reason_string;
}

function GetReturnedMark($db, $sid, $part, $markstore_totals)
{
    $mark = -1;

    $query = "select * from $markstore_totals where sid=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        $recs = $results->fetch();
        $entry = "retmark$part";
        $mark = $recs[$entry];
    }

    return $mark;
}
function GetMarkMethodAsArray($mark_method)
{
    // mark_method is a string of 4 1s and 0s
    // Position  corresponds to the market (Overwrite, Third, Second, First)
    // This function returns an array with:
    // position 0 = empty
    // position 1 = first marker
    // position 2 = second marker
    // position 1 = third marker
    // position 3 = indicates overwritten mark (not based on the marks recorded by the markers)
    $raw_array = str_split($mark_method);
    $mark_method_array[1] = $raw_array[3];
    $mark_method_array[2] = $raw_array[2];
    $mark_method_array[3] = $raw_array[1];
    $mark_method_array[4] = $raw_array[0];

    return $mark_method_array;
}

function GetMarkerNamesArray($db, $sid, $part, $project_allocated_table, $staff_names)
{
    $query = "select * from $project_allocated_table where sid=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }

    $marker_names = array_fill(0, 4, "");

    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        $recs = $results->fetch();

        $who = 0;
        $marker_entry = "supervisor_id";
        $marker_id = $recs[$marker_entry];
        $marker_names[$who] = GetStaffNameFromID($db, $marker_id, $staff_names, NULL);

        $who = 1;
        $marker_entry = "marker_p$part" . "m$who" . "_id";
        $marker_id = $recs[$marker_entry];
        $marker_names[$who] = GetStaffNameFromID($db, $marker_id, $staff_names, NULL);

        $who = 2;
        $marker_entry = "marker_p$part" . "m$who" . "_id";
        $marker_id = $recs[$marker_entry];
        $marker_names[$who] = GetStaffNameFromID($db, $marker_id, $staff_names, NULL);

        $who = 3;
        $marker_entry = "marker_p$part" . "m$who" . "_id";
        $marker_id = $recs[$marker_entry];
        $marker_names[$who] = GetStaffNameFromID($db, $marker_id, $staff_names, NULL);
    }

    return $marker_names;
}

function IsSIDAllocated($db, $sid, $project_allocated_table)
{
    $query = "select * from $project_allocated_table where sid=$sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }

    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        return true;
    }

    return false;
}

function IsSIDActive($db, $sid, $project_allocated_table)
{
    if (IsSIDAllocated($db, $sid, $project_allocated_table)) {
        $query = "select * from $project_allocated_table where sid=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");

            exit();
        }

        $rec = $results->fetch();

        $inactive = $rec['inactive'];
        //echo(" inactive=$inactive ");
        if ($inactive == 0) {
            // IS active
            return true;
        }
    }
    # If not allocated then IS NOT active i.e. is inactive
    return false;
}

function GetIsNumeric($db, $pm_id, $part, $project_setup_table)
{
    $isnumeric = 0;

    $query = "select * from $project_setup_table where id=$pm_id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        $recs = $results->fetch();
        //print_r3("project_setup",$recs);
        $entry = "p$part" . "_numeric";

        $isnumeric = $recs[$entry];
    }

    return $isnumeric;
}


function CountAllocationTable($db, $allocation_table)
{
    $num = 0;
    try {
        $query = "DESCRIBE $allocation_table";
        $result = $db->query($query);
        if ($result) {
            $query = "select * from $allocation_table";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                exit();
            }
            $num = $results->rowCount();
        } else {
            $num = 0;
        }
    } catch (PDOException $ex) {
        //$this_function = __FUNCTION__;
        //echo "An Error occured accessing the database in function: $this_function <BR>\n";
        //echo(" Query = $query<BR> \n");
        //echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        //exit();
        $num = 0;
    }

    return $num;
}

function DoesTableExist($db, $table)
{
    try {
        $query = "DESCRIBE $table";
        $db->query($query);

        $result = true;
    } catch (\PDOException $ex) {
        //$this_function = __FUNCTION__;
        //echo "An Error occured accessing the database in function: $this_function <BR>\n";
        //echo(" Query = $query<BR> \n");
        //echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        //var_dump($db);
        //exit();
        $result = false;
    }

    //echo($result . '<BR>');
    return $result;
}

function DoesColumnExistInTable($db, $table, $column)
{
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $res = $db->query($query);
    $count = $res->rowCount();

    //echo("count = $count<BR>");
    $result = true;

    if($count == 0){
        $result = false;
    }

    //echo($result . '<BR>');
    return $result;
}

function print_r2($val)
{
    echo '<pre>';
    print_r($val);
    echo '</pre>';
}

function print_r3($name, $val)
{
    echo '<pre>';
    echo("$name\n");
    print_r($val);
    echo '</pre>';
}

?>
