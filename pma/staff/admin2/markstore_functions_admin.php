<?php

function SetLockedStatusForMarker($db, $sid, $is_part_locked, $part_index, $marker_index, $markstores) {
    $this_markstore = $markstores[$part_index][$marker_index];
    // Check exists
    $query = "select * from $this_markstore where sid='$sid'";
    //$get_list_res = mysql_query($my_query) or die(" Unable to get the locked value from $this_markstore table. <BR>Query: $my_query");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num = $results->rowCount();
    if ($num > 0) {
        $query = "update $this_markstore set locked='$is_part_locked' where sid = $sid";
        //mysql_query($mod_details) or die(" Unable to modify the locked in the $this_markstore table. <BR>Query: $mod_details");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
    } else {
    }
}

function SetLockedStatusForPart($db, $sid, $is_locked, $part_index, $markstores) {
    for ($j = 1; $j <= 3 ; $j++) {
        SetLockedStatusForMarker($db, $sid, $is_locked, $part_index, $j, $markstores);
    }
}

function SetLockedStatusForAllParts($db, $sid, $is_locked, $part_index, $markstores) {
    for ($i = 1; $i <= 5 ; $i++) {
        for ($j = 1; $j <= 3; $j++) {
            SetLockedStatusForMarker($db, $sid, $is_locked, $i, $j, $markstores);
        }
    }
}

function SetLockedStatusOverall($db, $sid, $is_part_locked, $markstore_totals) {
    // Check exists
    $this_markstore = $markstore_totals;
    $query = "select * from $this_markstore where sid='$sid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num = $results->rowCount();
    if ($num > 0) {
        $query = "update $this_markstore set locked='$is_part_locked' where sid = $sid";
        //mysql_query($mod_details) or die(" Unable to modify the locked in the $this_markstore table. <BR>Query: $mod_details");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }        //echo("Set = $is_part_locked<BR>\n");
    } else {
    }
}

function SetThirdMarkerStatusForPart($db, $sid, $is_third_marker_required, $part_index, $markstore_totals) {
    // Check exists
    $this_markstore = $markstore_totals;
    $query = "select * from $this_markstore where sid='$sid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num = $results->rowCount();
    if ($num > 0) {
        $field = "third_marker_req$part_index";
        $query = "update $this_markstore set $field='$is_third_marker_required' where sid = $sid";
        //mysql_query($mod_details) or die(" Unable to modify the $field in the $this_markstore table. <BR>Query: $mod_details");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
    } else {
    }
}

function GetLockedStatusForMarker($db, $sid, $part_index, $marker_index, $markstores) {
    $is_locked = 0;
    $this_markstore = $markstores[$part_index][$marker_index];
    $query = "select * from $this_markstore where sid='$sid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num = $results->rowCount();
    if ($num > 0) {
        //$recs = mysql_fetch_array($get_list_res);
        $recs = $results->fetch();
        $entry = 'locked';
        $is_locked = $recs[$entry];
    }
    return $is_locked;
}

function GetLockedStatusOverall($db, $sid, $markstore_totals) {
    $is_locked = 0;
    $this_markstore = $markstore_totals;
    $query = "select * from $this_markstore where sid='$sid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num = $results->rowCount();
    if ($num > 0) {
        //$recs = mysql_fetch_array($get_list_res);
        $recs = $results->fetch();
        $entry = 'locked';
        $is_locked = $recs[$entry];
    }

    return $is_locked;
}
