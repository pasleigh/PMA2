<?php

// connect to the database
(@include_once('./set_dir_props.php')) or die('Cannot find this file to include: set_dir_props.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables');
(@include_once('../staff_functions.php')) or die('Cannot setup database tables');
(@include_once('../markstore_functions.php')) or die('Cannot open markstore_functions.php');

(@include_once('../admin2/adminusers.php')) or die('Cannot setup admin users');
(@include_once('../staff_student_functions.php')) or die('Cannot find staff_student_functions.php');

$staff_username = get_server_username();
if (IsStaff($db, $staff_username, $staff_names) == false) {
    echo 'You do not have access to this page. If you think this is an error please contact the system administrator.<BR>';
    echo "<p><A target='self' href='../..'>Return to the home page</a>.</P>";
    exit();
}

$query = "select * from $projects_setup where id=$pm_id";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$res = $results->fetch();

$module1 = $res['module'];
$year_begin = $res['year_begin'];
$year_end = $res['year_end'];
$module_prefix = $res['module_prefix'];
$module_short_description = $res['module_short_desc'];
$short_code = $res['short_code'];

$html_filename = $module_prefix . $module1 . '_' . $year_begin . '_' . $year_end . '.xls';

header("Content-Disposition: attachment; filename=$html_filename");
header('Content-Type: application/vnd.ms-excel');

$html_page = '';

$tmpcsvfile = tempnam('dummy', '');
$path = dirname($tmpcsvfile);

$sep = "\t";
$html_page .= "Project ID $sep Supervisor $sep Email $sep Second Marker $sep Title $sep Num projects $sep Description ";
$project_option_titles = '';
for ($i = 0; $i < sizeof($option); $i++) {
    if ($option_flag[$i] == 1) {
        $option_title = $option_text[$i];
        cleanData($option_title);
        $project_option_titles .= $option_title . $sep;
    }
}
if ($project_option_titles == '') {
    $html_page .= " \n";
} else {
    $html_page .= "$sep$project_option_titles \n";
}

$query = "select * from $staff_names2 order by familyname";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$rows = $results->fetchAll();

foreach ($rows as $recs) {
    $initials = $recs['initials'];
    $fullname = $recs['fullname'];
    $email = $recs['email'];
    $email_link = "<a href='mailto:$email'>$fullname. </A>";
    $query = "select id, f_title from $master_name where f_supervisor = '$fullname' order by id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $m_rows = $results->fetchAll();

    $num_projects = 0;
    $num_places = 0;
    $csv_block = '';
    foreach ($m_rows as $m_recs) {
        $project_id = $m_recs['id'];
        $title = $m_recs['f_title'];

        $query = "select * from $project_details where master_id = $project_id and f_PGprojects > 0";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $p_rows = $results->fetchAll();

        $num = $results->rowCount();

        if ($num > 0) {
            $num_projects += 1;
            // count the total number of places
            foreach ($p_rows as $p_recs) {
                $f_assocstaff = $p_recs['f_assocstaff'];
                //$f_UGprojects = $p_recs['f_UGprojects'];
                $f_PGprojects = $p_recs['f_PGprojects'];
                $f_outline = $p_recs['f_outline'];
                //$f_progs = $p_recs['f_progs'];
                //cleanData($f_UGprojects);
                cleanData($f_outline);
                //cleanData($f_relsites);

                $num_places += $f_PGprojects;
                $csv_block .= "$project_id$sep$fullname$sep$email$sep$f_assocstaff$sep$title$sep$f_PGprojects$sep$f_outline";

                $date_added = $p_recs['date_added'];
                $date_modified = $p_recs['date_modified'];

                $f_relsites = $p_recs['f_relsites'];
                $f_relmods = $p_recs['f_relmods'];
                $f_backread = $p_recs['f_backread'];
                $f_skills = $p_recs['f_skills'];
                $f_software = $p_recs['f_software'];
                $f_softsupport = $p_recs['f_softsupport'];
                $f_labs = $p_recs['f_labs'];
                $f_works = $p_recs['f_works'];
                $f_furtherinfo = $p_recs['f_furtherinfo'];

                $option_value[0] = $f_relsites;
                $option_value[1] = $f_relmods;
                $option_value[2] = $f_backread;
                $option_value[3] = $f_skills;
                $option_value[4] = $f_software;
                $option_value[5] = $f_softsupport;
                $option_value[6] = $f_labs;
                $option_value[7] = $f_works;
                $option_value[8] = $f_furtherinfo;

                $project_options = '';
                for ($i = 0; $i < sizeof($option); $i++) {
                    if ($option_flag[$i] == 1) {
                        $value = $option_value[$i];
                        cleanData($value);
                        $project_options .= $value . $sep;
                    }
                }

                if ($project_options == '') {
                    $csv_block .= " \n";
                } else {
                    $csv_block .= "$sep$project_options \n";
                }
            }
        }
    }

    $html_page .= $csv_block;
}

echo $html_page;

function cleanData(&$str) {
    $str = preg_replace("/\t/", '\\t', $str);
    $str = preg_replace("/\r?\n/", '\\n', $str);
    if (strstr($str, '"')) {
        $str = '"' . str_replace('"', '""', $str) . '"';
    }
}
