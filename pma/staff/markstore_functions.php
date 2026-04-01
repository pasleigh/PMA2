<?PHP

/**
 * Mark-store read/write helpers.
 *
 * The mark-store is the set of per-part, per-marker database tables that hold
 * individual marks and question-level feedback, plus a totals table that holds
 * the reconciled marks, mark method, and lock status.
 *
 * Naming conventions used throughout:
 *   $markstore          — a single marker's mark table for one part (e.g. markstore_p1m1)
 *   $markstores         — 2-D array indexed [part][marker_number]
 *   $markstore_totals   — the totals/reconciliation table (e.g. markstore_totals)
 *   $sid                — student ID (integer primary key from the students table)
 *   $part               — assessment part number (1-based integer)
 */

/**
 * Return true if a mark record exists for the student in the given mark store.
 *
 * @param PDO    $db         Active PDO connection.
 * @param int    $sid        Student ID.
 * @param string $markstore  Mark-store table name (single marker, single part).
 * @return bool
 */
function IsMarked($db, $sid, $markstore)
{
    $isMarked = false;
    $query = "select * from $markstore where sid=:sid";
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }
    $num_rows = $results->rowCount();
    if ($num_rows > 0) {
        //$recs = $results->fetch();
        $isMarked = true;
    }

    return $isMarked;
}

/**
 * Return the total mark from a single marker's store, or -1 if no record.
 *
 * @param PDO    $db        Active PDO connection.
 * @param int    $sid       Student ID.
 * @param int    $part      Assessment part number (unused in query, kept for signature clarity).
 * @param string $markstore Mark-store table name.
 * @return float|int Mark total, or -1 if no record found.
 */
function GetThisMark($db, $sid, $part, $markstore)
{
    $thisMark = -1;
    $query = "select * from $markstore where sid=:sid";
    //$get_list_res = mysql_query($get_list) or die("Unable to marks list from table $markstore.<BR>Query: $get_list\n");
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }
    $num_rows = $results->rowCount();
    if ($num_rows > 0) {
        $recs = $results->fetch();
        $thisMark = $recs['total'];
    }

    return $thisMark;
}

/**
 * Return the reconciled total mark for a student's assessment part, or -1 if not set.
 *
 * Reads the `retmark{$part}` column from the totals table.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $part             Assessment part number.
 * @param string $markstore_totals Totals table name.
 * @return float|int Reconciled mark, or -1 if no record.
 */
function GetTotalForPart($db, $sid, $part, $markstore_totals)
{
    $total = -1;
    $query = "select * from $markstore_totals where sid=:sid";

    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }
    $num_rows = $results->rowCount();
    if ($num_rows > 0) {
        $recs = $results->fetch();
        $entry = "retmark$part";
        $total = $recs[$entry];
    } else {
    }

    return $total;
}

/**
 * Count how many students in a part have been flagged as requiring a third marker.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $part             Assessment part number.
 * @param string $markstore_totals Totals table name.
 * @return int Number of students requiring a third marker for this part.
 */
function CountThirdMarkersRequired($db, $part, $markstore_totals)
{
    $count = 0;

    $entry = "third_marker_req" . $part;
    $query = "select * from $markstore_totals where $entry=1";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }
    $num_rows = $results->rowCount();

    return $num_rows;
}

/**
 * Return true if a third marker has been requested for this student's part.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $part             Assessment part number.
 * @param string $markstore_totals Totals table name.
 * @return bool
 */
function IsThirdMarkerRequired($db, $sid, $part, $markstore_totals)
{

    $query = "select * from $markstore_totals where SID=:sid";
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
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

/**
 * Flag that a third marker is required for a student's part.
 *
 * Inserts or updates the `third_marker_req{$part}` column in the totals table.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $part             Assessment part number.
 * @param string $markstore_totals Totals table name.
 * @return string HTML confirmation string "<p>Done</p>".
 */
function SetThirdMarkerRequired($db, $sid, $part, $markstore_totals)
{
    $third_marker_entry = "third_marker_req" . $part;

    // Need to check if the SID already has an entry or not
    $query = "select count(SID) from $markstore_totals where SID=:sid";
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }
    $get_marks_res = $results->fetch();

    if ($get_marks_res[0] == 0) {
        $query = "insert $markstore_totals set SID=:sid, $third_marker_entry=1";
    } else {
        $query = "update $markstore_totals set $third_marker_entry=1 where SID=:sid";
    }

    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }

    return "<p>Done</p>";
}

/**
 * Return true if all required markers (including optional third) have submitted marks.
 *
 * Checks all default markers; if only two markers are configured and a third
 * has been requested, also checks the third marker's store.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $part             Assessment part number.
 * @param int    $num_markers      Number of default markers configured for this part.
 * @param array  $markstores       2-D array of mark-store table names indexed [part][marker_number].
 * @param string $markstore_totals Totals table name.
 * @return bool
 */
function HaveAllMarkersMarked($db, $sid, $part, $num_markers, $markstores, $markstore_totals)
{
    // $num_markers is the default number of markers.
    // If it is two and the third marker has been requested then there needs to be completion of three markers checked.
    for ($j = 1; $j <= $num_markers; $j++) {
        if (IsMarked($db, $sid, $markstores[$part][$j]) == false) {
            // found one that has not been marked
            return false;
        }
    }

    if ($num_markers == 2) {
        $query = "select * from $markstore_totals where sid=:sid";
        try {
            $results = $db->prepare($query);
            $results->execute([':sid' => $sid]);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
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

/**
 * Return true if all assessment parts have a non-zero reconciled total for the student.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $num_parts        Total number of assessment parts.
 * @param string $markstore_totals Totals table name.
 * @return bool
 */
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

/**
 * Compute the weighted overall total across all parts and write it to the totals table.
 *
 * Returns -1 if any part has no marks yet (total is not written).
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $num_parts        Total number of assessment parts.
 * @param array  $part_percents    Array indexed by part number, each value is the percentage weight (0–100).
 * @param string $markstore_totals Totals table name.
 * @return float|int Computed weighted total, or -1 if incomplete.
 */
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
    $query = "update $markstore_totals set $entry=:total where SID=:sid";

    try {
        $stmt = $db->prepare($query);
        $stmt->execute([':total' => $total, ':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }

    return $total;
}

/**
 * Return the sum of marks from all markers for a part, or -2 if any marker hasn't marked yet.
 *
 * @param PDO    $db          Active PDO connection.
 * @param int    $sid         Student ID.
 * @param int    $part        Assessment part number.
 * @param int    $num_markers Number of markers for this part.
 * @param array  $markstores  2-D array of mark-store table names indexed [part][marker_number].
 * @return float|int Sum of marks, or -2 if any marker has not yet submitted.
 */
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

/**
 * Return the absolute difference between marker 1 and marker 2's totals for a part.
 *
 * Assumes exactly two markers exist. Used to decide whether a third marker is needed.
 *
 * @param PDO   $db        Active PDO connection.
 * @param int   $sid       Student ID.
 * @param int   $part      Assessment part number.
 * @param array $markstores 2-D array of mark-store table names indexed [part][marker_number].
 * @return float Absolute difference between the two markers' totals.
 */
function GetDifference($db, $sid, $part, $markstores)
{
    // return the difference between the first and second marker
    // Assumes there are two markers
    $mark1 = GetThisMark($db, $sid, $part, $markstores[$part][1]);
    $mark2 = GetThisMark($db, $sid, $part, $markstores[$part][2]);
    $diff = abs($mark1 - $mark2);
    return $diff;
}

/**
 * Return an array of mark-store table names relevant to the given mark method.
 *
 * The mark method string is a 4-char bitmask (see GetMarkMethodAsArray()).
 * Position 4 (overwrite flag) short-circuits to return only markstore1.
 *
 * @param string $mark_method_string 4-character bitmask string.
 * @param string $markstore1         Marker 1 table name.
 * @param string $markstore2         Marker 2 table name.
 * @param string $markstore3         Marker 3 table name.
 * @return array Mark-store table names indexed from 1 (index 0 is always empty string).
 */
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

/**
 * Return an array of marker name strings relevant to the given mark method.
 *
 * Mirror of GetMarkStoreListFromMarkMethodString() but for human-readable names.
 *
 * @param string $mark_method_string 4-character bitmask string.
 * @param string $marker1            Marker 1 full name.
 * @param string $marker2            Marker 2 full name.
 * @param string $marker3            Marker 3 full name.
 * @return array Marker name strings indexed from 1 (index 0 is always empty string).
 */
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

/**
 * Return the 4-character mark method bitmask string for a student's part.
 *
 * Returns '0001' (supervisor-only) if no record exists or string is malformed.
 * Decode with GetMarkMethodAsArray().
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $part             Assessment part number.
 * @param string $markstore_totals Totals table name.
 * @return string 4-character bitmask, e.g. '0011' = markers 1 & 2, '1000' = overwritten.
 */
function GetMarkMethodString($db, $sid, $part, $markstore_totals)
{
    $mark_method_string = "9999";

    $query = "select * from $markstore_totals where sid=:sid";
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
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
    return $mark_method_string;
}

/**
 * Return the free-text reason string explaining how the mark was determined.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $part             Assessment part number.
 * @param string $markstore_totals Totals table name.
 * @return string Reason string, or empty string if not set.
 */
function GetMarkReasonString($db, $sid, $part, $markstore_totals)
{
    $mark_reason_string = "";

    $query = "select * from $markstore_totals where sid=:sid";
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
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

/**
 * Return the official returned mark (`retmark{$part}`) for a student's part.
 *
 * This is the mark that has been formally awarded after reconciliation,
 * as opposed to individual marker totals.
 *
 * @param PDO    $db               Active PDO connection.
 * @param int    $sid              Student ID.
 * @param int    $part             Assessment part number.
 * @param string $markstore_totals Totals table name.
 * @return float|int Returned mark, or -1 if no record.
 */
function GetReturnedMark($db, $sid, $part, $markstore_totals)
{
    $mark = -1;

    $query = "select * from $markstore_totals where sid=:sid";
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
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
/**
 * Decode a 4-character mark method bitmask string into an indexed array.
 *
 * The string encodes which markers contributed to the final mark:
 *   Position (char index): 0=Overwrite, 1=Third marker, 2=Second marker, 3=First marker
 *
 * Returns array indexed 1–4:
 *   [1] = first marker active, [2] = second marker active,
 *   [3] = third marker active, [4] = overwrite active (ignores individual marker marks).
 *
 * @param string $mark_method 4-character bitmask string (e.g. '0011').
 * @return array<int, string> Indexed 1–4, each value '0' or '1'.
 */
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

/**
 * Return an array of marker names for a student's part (supervisor + up to 3 markers).
 *
 * Index 0 = supervisor, index 1–3 = markers in order. Empty string if ID is absent.
 *
 * @param PDO    $db                      Active PDO connection.
 * @param int    $sid                     Student ID.
 * @param int    $part                    Assessment part number.
 * @param string $project_allocated_table Allocation table name.
 * @param string $staff_names             Staff table name.
 * @return array<int, string> Array of 4 name strings indexed 0–3.
 */
function GetMarkerNamesArray($db, $sid, $part, $project_allocated_table, $staff_names)
{
    $query = "select * from $project_allocated_table where sid=:sid";
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
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

/**
 * Return true if the student has a row in the project allocation table.
 *
 * @param PDO    $db                      Active PDO connection.
 * @param int    $sid                     Student ID.
 * @param string $project_allocated_table Allocation table name.
 * @return bool
 */
function IsSIDAllocated($db, $sid, $project_allocated_table)
{
    $query = "select * from $project_allocated_table where sid=:sid";
    try {
        $results = $db->prepare($query);
        $results->execute([':sid' => $sid]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }

    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        return true;
    }

    return false;
}

/**
 * Return true if the student is allocated and their record is not marked inactive.
 *
 * A student can be allocated but set inactive (e.g. withdrawn). Both conditions
 * must be true for this to return true.
 *
 * @param PDO    $db                      Active PDO connection.
 * @param int    $sid                     Student ID.
 * @param string $project_allocated_table Allocation table name.
 * @return bool
 */
function IsSIDActive($db, $sid, $project_allocated_table)
{
    if (IsSIDAllocated($db, $sid, $project_allocated_table)) {
        $query = "select * from $project_allocated_table where sid=:sid";
        try {
            $results = $db->prepare($query);
            $results->execute([':sid' => $sid]);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            exit();
        }

        $rec = $results->fetch();

        $inactive = $rec['inactive'];
        if ($inactive == 0) {
            // IS active
            return true;
        }
    }
    # If not allocated then IS NOT active i.e. is inactive
    return false;
}

/**
 * Return whether the marking scheme for a part uses numeric scores (1) or grade bands (0).
 *
 * Reads the `p{$part}_numeric` column from the project setup table.
 *
 * @param PDO    $db                  Active PDO connection.
 * @param int    $pm_id               Project-module ID.
 * @param int    $part                Assessment part number.
 * @param string $project_setup_table Project setup table name.
 * @return int 1 = numeric scoring, 0 = grade band scoring.
 */
function GetIsNumeric($db, $pm_id, $part, $project_setup_table)
{
    $isnumeric = 0;

    $query = "select * from $project_setup_table where id=:pm_id";
    try {
        $results = $db->prepare($query);
        $results->execute([':pm_id' => $pm_id]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows > 0) {
        $recs = $results->fetch();
        $entry = "p$part" . "_numeric";

        $isnumeric = $recs[$entry];
    }

    return $isnumeric;
}

/**
 * Return the number of rows in the allocation table, or 0 if the table doesn't exist.
 *
 * Safe to call before setup is complete — catches the DESCRIBE exception silently.
 *
 * @param PDO    $db               Active PDO connection.
 * @param string $allocation_table Allocation table name.
 * @return int Row count, or 0 if table is missing.
 */
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
                error_log($ex->getMessage());
                exit();
            }
            $num = $results->rowCount();
        } else {
            $num = 0;
        }
    } catch (PDOException $ex) {
        //$this_function = __FUNCTION__;
        //exit();
        $num = 0;
    }

    return $num;
}

/**
 * Return true if a table exists in the current database.
 *
 * Uses DESCRIBE rather than information_schema for compatibility.
 *
 * @param PDO    $db    Active PDO connection.
 * @param string $table Table name to test.
 * @return bool
 */
function DoesTableExist($db, $table)
{
    try {
        $query = "DESCRIBE $table";
        $db->query($query);

        $result = true;
    } catch (\PDOException $ex) {
        //$this_function = __FUNCTION__;
        //exit();
        $result = false;
    }

    return $result;
}

/**
 * Return true if a specific column exists in a table.
 *
 * @param PDO    $db     Active PDO connection.
 * @param string $table  Table name.
 * @param string $column Column name to check for.
 * @return bool
 */
function DoesColumnExistInTable($db, $table, $column)
{
    $query = "SHOW COLUMNS FROM $table LIKE '$column'";
    $res = $db->query($query);
    $count = $res->rowCount();

    $result = true;

    if($count == 0){
        $result = false;
    }

    return $result;
}

/**
 * Debug helper: print a value wrapped in `<pre>` tags.
 *
 * @param mixed $val Value to dump.
 * @return void
 */
function print_r2($val)
{
    echo '<pre>';
    print_r($val);
    echo '</pre>';
}

/**
 * Debug helper: print a labelled value wrapped in `<pre>` tags.
 *
 * @param string $name Label to display above the value.
 * @param mixed  $val  Value to dump.
 * @return void
 */
function print_r3($name, $val)
{
    echo '<pre>';
    echo("$name\n");
    print_r($val);
    echo '</pre>';
}

?>
