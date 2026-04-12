<?php

/**
 * Created by PhpStorm.
 * User: Andy
 * Date: 12-Dec-17
 * Time: 12:28 AM
 */
function create_new_module_from_old($db, $projects_setup, $projects_setup_poposals, $pm_id_old, $short_code_old, $short_code_new, $year_begin_old, $year_begin_new) {
    // Check that the short_code_new and the year_begin_new are unique
    if (IsUniqueModule($db, $projects_setup, $short_code_new, $year_begin_new)) {
        $pm_id_new = copy_module_project_setup_row($db, $projects_setup, $projects_setup_poposals, $pm_id_old, $short_code_new, $year_begin_new);
        copy_module_tables($db, $short_code_old, $short_code_new, $year_begin_old, $year_begin_new);
    } else {
        $pm_id_new = -1; // undefined
    }

    return $pm_id_new;
}

function IsUniqueModule($db, $projects_setup, $short_code_new, $year_begin_new) {
    $query = "select * from $projects_setup where short_code='$short_code_new' AND year_begin='$year_begin_new'";
    $results = $db->query($query);
    $num_entries = $results->rowCount();
    if ($num_entries > 0) {
        echo("<P>This combination of year and short code exists. Year:$year_begin_new Short code:$short_code_new Choose another.</P>");
        return false;
    }
    // is unique
    return true;
}

function copy_module_project_setup_row($db, $projects_setup_table, $projects_setup_poposals_table, $pm_id_old, $short_code_new, $year_begin_new) {
    $query = "SELECT * from $projects_setup_table WHERE id=$pm_id_old";
    try {
        $results = $db->query($query);
        $rec = $results->fetch();
    } catch (PDOException $ex) {
        echo "An Error occured getting the list from projects_setup<BR>\n";
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    // get all the variables
    $module = $rec['module'];
    $module_prefix = $rec['module_prefix'];
    $module_postfix = $rec['module_postfix'];
    $module_short_desc = $rec['module_short_desc'];
    $short_code = $rec['short_code'];
    $year_begin = $rec['year_begin'];
    $year_end = $rec['year_end'];
    $staff_close = $rec['staff_close'];
    $sel_open = $rec['sel_open'];
    $sel_close = $rec['sel_close'];
    $p1_submission_date = $rec['p1_submission_date'];
    $p1_marking_date = $rec['p1_marking_date'];
    $p2_submission_date = $rec['p2_submission_date'];
    $p2_marking_date = $rec['p2_marking_date'];
    $p3_submission_date = $rec['p3_submission_date'];
    $p3_marking_date = $rec['p3_marking_date'];
    $p4_submission_date = $rec['p4_submission_date'];
    $p4_marking_date = $rec['p4_marking_date'];
    $p5_submission_date = $rec['p5_submission_date'];
    $p5_marking_date = $rec['p5_marking_date'];
    $alloc_date = $rec['alloc_date'];
    $num_parts = $rec['num_parts'];
    $use_p1 = $rec['use_p1'];
    $p1_percent = $rec['p1_percent'];
    $p1_num_markers = $rec['p1_num_markers'];
    $p1_max = $rec['p1_max'];
    $p1_desc = $rec['p1_desc'];
    $p1_numeric = $rec['p1_numeric'];
    $use_p2 = $rec['use_p2'];
    $p2_percent = $rec['p2_percent'];
    $p2_num_markers = $rec['p2_num_markers'];
    $p2_max = $rec['p2_max'];
    $p2_desc = $rec['p2_desc'];
    $p2_numeric = $rec['p2_numeric'];
    $use_p3 = $rec['use_p3'];
    $p3_percent = $rec['p3_percent'];
    $p3_num_markers = $rec['p3_num_markers'];
    $p3_max = $rec['p3_max'];
    $p3_desc = $rec['p3_desc'];
    $p3_numeric = $rec['p3_numeric'];
    $use_p4 = $rec['use_p4'];
    $p4_percent = $rec['p4_percent'];
    $p4_num_markers = $rec['p4_num_markers'];
    $p4_max = $rec['p4_max'];
    $p4_desc = $rec['p4_desc'];
    $p4_numeric = $rec['p4_numeric'];
    $use_p5 = $rec['use_p5'];
    $p5_percent = $rec['p5_percent'];
    $p5_num_markers = $rec['p5_num_markers'];
    $p5_max = $rec['p5_max'];
    $p5_desc = $rec['p5_desc'];
    $p5_numeric = $rec['p5_numeric'];
    $accept_var = $rec['accept_var'];
    $pass_mark = $rec['pass_mark'];
    $module_leader = $rec['module_leader'];
    $min_feedback_words = $rec['min_feedback_words'];
    $visible = $rec['visible'];
    $ranked_choice = $rec['ranked_choice'];
    $choice_number = $rec['choice_number'];
    $use_project_area = $rec['use_project_area'];
    $use_project_selection = $rec['use_project_selection'];
    // Change these
    $short_code = $short_code_new;
    $year_begin = $year_begin_new;
    $year_end = $year_begin + 1;

    $query = <<<SQL
INSERT INTO projects_setup (
  module,
  module_prefix,
  module_postfix,
  module_short_desc,
  short_code,
  year_begin ,
  year_end,
  staff_close,
  sel_open,
  sel_close,
  p1_submission_date,
  p1_marking_date,
  p2_submission_date,
  p2_marking_date,
  p3_submission_date,
  p3_marking_date,
  p4_submission_date,
  p4_marking_date,
  p5_submission_date,
  p5_marking_date,
  alloc_date,
  num_parts,
  use_p1,
  p1_percent,
  p1_num_markers,
  p1_max,
  p1_desc,
  p1_numeric,
  use_p2,
  p2_percent,
  p2_num_markers,
  p2_max,
  p2_desc,
  p2_numeric,
  use_p3,
  p3_percent,
  p3_num_markers,
  p3_max,
  p3_desc,
  p3_numeric,
  use_p4,
  p4_percent,
  p4_num_markers,
  p4_max,
  p4_desc,
  p4_numeric,
  use_p5,
  p5_percent,
  p5_num_markers,
  p5_max,
  p5_desc,
  p5_numeric,
  accept_var,
  pass_mark,
  module_leader,
  min_feedback_words,
  visible,
  ranked_choice,
  choice_number,
  use_project_area,
  use_project_selection)
VALUES (
  '$module',
  '$module_prefix',
  '$module_postfix',
  '$module_short_desc',
  '$short_code',
  '$year_begin',
  '$year_end',
  '$staff_close',
  '$sel_open',
  '$sel_close',
  '$p1_submission_date',
  '$p1_marking_date',
  '$p2_submission_date',
  '$p2_marking_date',
  '$p3_submission_date',
  '$p3_marking_date',
  '$p4_submission_date',
  '$p4_marking_date',
  '$p5_submission_date',
  '$p5_marking_date',
  '$alloc_date',
  '$num_parts',
  '$use_p1',
  '$p1_percent',
  '$p1_num_markers',
  '$p1_max',
  '$p1_desc',
  '$p1_numeric',
  '$use_p2',
  '$p2_percent',
  '$p2_num_markers',
  '$p2_max',
  '$p2_desc',
  '$p2_numeric',
  '$use_p3',
  '$p3_percent',
  '$p3_num_markers',
  '$p3_max',
  '$p3_desc',
  '$p3_numeric',
  '$use_p4',
  '$p4_percent',
  '$p4_num_markers',
  '$p4_max',
  '$p4_desc',
  '$p4_numeric',
  '$use_p5',
  '$p5_percent',
  '$p5_num_markers',
  '$p5_max',
  '$p5_desc',
  '$p5_numeric',
  '$accept_var',
  '$pass_mark',
  '$module_leader',
  '$min_feedback_words',
  '$visible',
  '$ranked_choice',
  '$choice_number',
  '$use_project_area',
  '$use_project_selection'
  );
SQL;
    try {
        $db->query($query);
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

    // Get the new pm_id
    $query = "select * from $projects_setup_table where short_code='$short_code_new' AND year_begin='$year_begin_new'";
    try {
        $result = $db->query($query);
        $rec = $result->fetch();
    } catch (PDOException $ex) {
        echo "An Error occured getting the list from projects_setup for last update<BR>\n";
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    //$get_list_res = mysql_query($get_list) or die(" Unable to create list from $projects_setup table. Query: $get_list");
    //$setup_data = mysql_fetch_array($get_list_res);
    $pm_id_new = $rec['id'];

    // Put this new pm_id in the projects_setup_proposals table along with the $prog_short_names from the old one (keep defaults for everything else)
    $query = "SELECT * from $projects_setup_poposals_table WHERE proj_id=$pm_id_old";
    try {
        $results = $db->query($query);
        $rec = $results->fetch();
    } catch (PDOException $ex) {
        echo "An Error occured getting the list from projects_setup<BR>\n";
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $prog_short_names = $rec['prog_short_names'];
    $num_progs = $rec['num_progs'];

    // Save this to the database
    $query = "INSERT $projects_setup_poposals_table SET proj_id='$pm_id_new', num_progs='$num_progs', prog_short_names='$prog_short_names'";
    try {
        $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured adding the new project setup proposals<BR>\n";
        $err_msg = $ex->getMessage();
        echo("Error message: $err_msg<BR>\n");
        exit();
    }
    return $pm_id_new;
}

function copy_module_tables($db, $short_code_old, $short_code_new, $year_begin_old, $year_begin_new) {
    $old_year_begin = $year_begin_old;
    $new_year_begin = $year_begin_new;

    $old_year_end = $year_begin_old + 1;
    $new_year_end = $year_begin_new + 1;

    $new_short_name_lc = strtolower($short_code_new);
    $old_short_name_lc = strtolower($short_code_old);

    $new_table = 'master_name_' . $new_short_name_lc . '_' . $new_year_begin;
    $old_table = 'master_name_' . $old_short_name_lc . '_' . $old_year_begin;
    CopyTable($db, $old_table, $new_table);

    $new_table = 'project_details_' . $new_short_name_lc . '_' . $new_year_begin;
    $old_table = 'project_details_' . $old_short_name_lc . '_' . $old_year_begin;
    CopyTable($db, $old_table, $new_table);

    for ($part = 1; $part <= 5; $part++) {
        for ($marker = 1; $marker <= 3; $marker++) {
            // assess
            $new_table = 'assess_' . $new_short_name_lc . '_' . $new_year_end . "_p$part" . "_m$marker";
            $old_table = 'assess_' . $old_short_name_lc . '_' . $old_year_end . "_p$part" . "_m$marker";
            CopyTable($db, $old_table, $new_table, false);
        }
    }
    $new_table = 'assess_total_' . $new_short_name_lc . '_' . $new_year_end;
    $old_table = 'assess_total_' . $old_short_name_lc . '_' . $old_year_end;
    CopyTable($db, $old_table, $new_table, false);

    $new_table = 'allocated_' . $new_short_name_lc . '_' . $new_year_begin;
    $old_table = 'allocated_' . $old_short_name_lc . '_' . $old_year_begin;
    CopyTable($db, $old_table, $new_table, false);

    $new_table = 'allocated_' . $new_short_name_lc . '_' . $new_year_begin . '_staff';
    $old_table = 'allocated_' . $old_short_name_lc . '_' . $old_year_begin . '_staff';
    CopyTable($db, $old_table, $new_table, false);

    $new_table = 'students_' . $new_short_name_lc . '_' . $new_year_begin;
    //$old_table = "students_" . $old_short_name_lc . "_" . $old_year_begin;
    $query = CreateStudentsTableQuery($new_table);
    try {
        $result = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured creating the table $new_table <BR>\n";
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $new_table = 'select_' . $new_short_name_lc . '_' . $new_year_begin;
    //$old_table = "select_" . $old_short_name_lc . "_" . $old_year_begin;
    $query = CreateStudentsSelectionTableQuery($new_table);
    try {
        $result = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured creating the table $new_table <BR>\n";
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $new_table = 'students_programmes_' . $new_short_name_lc . '_' . $new_year_begin;
    $old_table = 'students_programmes_' . $old_short_name_lc . '_' . $old_year_begin;
    CopyTable($db, $old_table, $new_table);

    for ($part = 1; $part <= 5; $part++) {
        $new_table = 'assessqs_' . $new_short_name_lc . '_' . $new_year_end . "_p$part";
        $old_table = 'assessqs_' . $old_short_name_lc . '_' . $old_year_end . "_p$part";
        CopyTable($db, $old_table, $new_table);

        $new_table = 'assessqs_cats_' . $new_short_name_lc . '_' . $new_year_end . "_p$part";
        $old_table = 'assessqs_cats_' . $old_short_name_lc . '_' . $old_year_end . "_p$part";
        CopyTable($db, $old_table, $new_table);
    }

    //$new_table = "staff_names_" . $new_short_name_lc . "_" . $new_year_begin;
    //$old_table = "staff_names_" . $old_short_name_lc . "_" . $old_year_begin;
    //CopyTable($db, $old_table, $new_table);

    $new_table = 'projects_areas_' . $new_short_name_lc . '_' . $new_year_begin;
    $old_table = 'projects_areas_' . $old_short_name_lc . '_' . $old_year_begin;
    // Aas projects Areas are new they may not exist.
    // Create the table if necessary
    create_project_areas_table_structure($db, $old_table);
    CopyTable($db, $old_table, $new_table);
}

function CopyTable($db, $old_table, $new_table, $copy_data = true) {
    // Copy the data from all of the old table to the new tables
    $query1 = "CREATE TABLE IF NOT EXISTS $new_table LIKE $old_table ";
    $query2 = "INSERT $new_table SELECT * FROM $old_table";

    try {
        $result = $db->query($query1);
    } catch (PDOException $ex) {
        echo "An Error occured creating the table $new_table <BR>\n";
        $err_msg = $ex->getMessage();
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    if ($copy_data) {
        try {
            $result = $db->query($query2);
        } catch (PDOException $ex) {
            echo "An Error occured copying data from the table $old_table to $new_table<BR>\n";
            $err_msg = $ex->getMessage();
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
    }
}
