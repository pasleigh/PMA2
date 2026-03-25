<?php
/**
 * Database table creation, population, and HTML rendering helpers.
 *
 * The create_* functions are called during first-run setup and module duplication
 * to ensure the required DB tables exist. The HTML-returning functions are used
 * in the marking and admin pages to render student/staff data.
 */

/**
 * Return an HTML table listing all allocated students for a marking part.
 *
 * Includes a select-all checkbox, student name, supervisor, second marker,
 * and a flag showing whether feedback has been sent for this part.
 *
 * @param PDO    $db                     Active PDO connection.
 * @param string $project_allocated      Project allocation table name.
 * @param string $students_table         Students table name.
 * @param string $staff_table            Staff table name.
 * @param string $feedback_records_table Feedback records table name.
 * @param int    $part                   Assessment part number.
 * @return string HTML table string.
 */
function GetStudentListTable($db,$project_allocated, $students_table, $staff_table, $feedback_records_table, $part){
    $select_all_block = "<div>Select/De-select all<input type=\"checkbox\" id=\"select-all\" name=\"selectAll\" checked value=\"\"/></div>\n";
    $display_block = "\n";
    $display_block = "<div id=\"students_select_table\">\n";
    $display_block .= "<table id=\"student_email_list\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $display_block .= "
    <thead>
      <tr>
        <th>Student name</th>
        <th>SID</th>
        <th>Username</th>
        <th>Project ID</th>
        <th>Project Title</th>
        <th>Supervisor</th>
        <th>Second marker</th>
        <th>Ticked = Send email if marking is complete $select_all_block</th>
        <th>Has feedback already been sent</th>
      </tr>
    </thead>
        ";
    $display_block .= "<tbody>\n";

    // list all the students
    $query = "select * from $project_allocated order by projectid";
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
    foreach ($rows as $recs ) {
        $project_id = $recs['projectid'];
        # $project_master_id = $recs['masterid'];
        $sid = $recs['sid'];
        $inactive = $recs['inactive'];
        $supervisor_id = $recs['supervisor_id'];
        $second_marker_id = $recs['second_marker_id'];
        $project_title = $recs['title'];

        if ($inactive != 1) {

            $markers = array();
            $markers_id = array();
            for ($i = 1; $i <= 5; $i++) {
                $markers[$i] = array();
                $markers_id[$i] = array();
                for ($j = 1; $j <= 3; $j++) {
                    $marker_entry = "marker_p$i" . "m$j" . "_id";
                    $marker_id = $recs[$marker_entry];
                    $markers_id[$i][$j] = $marker_id;
                    $markers[$i][$j] = GetStaffNameFromID($db, $marker_id, $staff_table, NULL);
                    # $name = $markers_id[$i][$j];
                }
            }

            // Get the student name from SID
            $query = "select * from $students_table where sid = $sid";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $recs = $results->fetch();
            $student_name = $username = "";
            if($recs){
                $student_name = $recs['name'];
                $username = $recs['username'];
            }
            # $programme = $recs['programme'];
            # $sel = $recs['sel'];

            $feedback_sent = "";
            // Find out if feedback sent to this SID
            $query = "select * from $feedback_records_table where sid = $sid";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $num_rows = $results->rowCount();
            if($num_rows == 0){
                $feedback_sent = "Not Sent";
            }else if(is_null($part)){
                $feedback_sent = "Not Sent";
            }else{
                $recs = $results->fetch();
                $key = "sent_p" . $part;
                $val = $recs[$key];
                if($val != 0){
                    $feedback_sent = "Sent";
                }else{
                    $feedback_sent = "Not Sent";
                }
            }

            $display_block .= "<tr>\n";
            $display_block .= "  <td>$student_name </td>\n";
            $display_block .= "  <td>$sid</td>\n";
            $display_block .= "  <td>$username</td>\n";
            $display_block .= "  <td>$project_id</td>\n";
            $display_block .= "  <td>$project_title</td>\n";
            $supervisor_name = GetStaffNameFromID($db,$supervisor_id, $staff_table, NULL);
            $display_block .= "  <td>$supervisor_name</td>\n";
            $second_marker_name = GetStaffNameFromID($db,$second_marker_id, $staff_table, NULL);
            $display_block .= "  <td>$second_marker_name</td>\n";
            $display_block .= "  <td style=\"text-align: center;\"><input type=\"checkbox\" name=\"check_$sid\" checked></td>\n";
            $display_block .= "  <td>$feedback_sent</td>\n";
            $display_block .= "</tr>\n";
        }
    }
    $display_block .= "</tbody>\n";
    $display_block .= "</table>\n";
    $display_block .= "</div>\n";
    return $display_block;
}

/**
 * Record that feedback has been sent to a student for a given part.
 *
 * Inserts a new row or updates the existing one in the feedback records table.
 *
 * @param PDO    $db                     Active PDO connection.
 * @param int    $sid                    Student ID.
 * @param int    $part                   Assessment part number.
 * @param string $feedback_records_table Feedback records table name.
 * @return void
 */
function RecordFeedbackSent($db, $sid, $part, $feedback_records_table){
    $query = "select * from $feedback_records_table where sid = $sid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $num_rows = $results->rowCount();

    $part_query_string = "sent_p" . $part;
    if($num_rows == 0){
        // Insert
        $query = "insert $feedback_records_table set SID='$sid', $part_query_string=1";
    } else {
        // Update
        $query = "update $feedback_records_table set $part_query_string=1 where SID='$sid'";
    }
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

/**
 * Create the `header_images` table if it does not already exist.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function create_header_images_table_structure($db){
    $query = <<<SQL
    --
    -- Table structure for table `header_images`
    --
    CREATE TABLE IF NOT EXISTS `header_images` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
      `header_image` BLOB NOT NULL,
      `filename` char(255),
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }
}

/**
 * Create the `dash_projects_setup` (module configuration) table if it does not exist.
 *
 * This table stores per-module settings: number of parts, part names, percentages,
 * number of markers, mark method, and numeric/grade-band flag.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function create_projects_setup_table_structure($db){

    $query = <<<SQL
    --
    -- Table structure for table `projects_setup`
    --

    CREATE TABLE IF NOT EXISTS `projects_setup` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
      `module` int(4) NOT NULL,
      `module_prefix` char(5) NOT NULL,
      `module_postfix` char(1) default NULL,
      `module_short_desc` char(25) NOT NULL,
      `short_code` char(5) NOT NULL,
      `year_begin` year(4) NOT NULL,
      `year_end` year(4) NOT NULL,
      `staff_close` date DEFAULT NULL,
      `sel_open` date DEFAULT NULL,
      `sel_close` date DEFAULT NULL,
      `p1_submission_date` date DEFAULT NULL,
      `p1_marking_date` date DEFAULT NULL,
      `p2_submission_date` date DEFAULT NULL,
      `p2_marking_date` date DEFAULT NULL,
      `p3_submission_date` date DEFAULT NULL,
      `p3_marking_date` date DEFAULT NULL,
      `p4_submission_date` date DEFAULT NULL,
      `p4_marking_date` date DEFAULT NULL,
      `p5_submission_date` date DEFAULT NULL,
      `p5_marking_date` date DEFAULT NULL,
      `alloc_date` date DEFAULT NULL,
      `num_parts` int(11) DEFAULT NULL,
      `use_p1` int(11) DEFAULT NULL,
      `p1_percent` float DEFAULT NULL,
      `p1_num_markers` int(11) DEFAULT '1',
      `p1_max` float DEFAULT NULL,
      `p1_desc` char(64) DEFAULT 'Objectives',
      `p1_numeric` int(11) DEFAULT '0',
      `p1_feedback` int(11) DEFAULT '1',
      `use_p2` int(11) DEFAULT NULL,
      `p2_percent` float DEFAULT NULL,
      `p2_num_markers` int(11) DEFAULT '1',
      `p2_max` float DEFAULT NULL,
      `p2_desc` char(64) DEFAULT 'Effort',
      `p2_numeric` int(11) DEFAULT '0',
      `p2_feedback` int(11) DEFAULT '1',
      `use_p3` int(11) DEFAULT NULL,
      `p3_percent` float DEFAULT NULL,
      `p3_num_markers` int(11) DEFAULT '1',
      `p3_max` float DEFAULT NULL,
      `p3_desc` char(64) DEFAULT 'Presentation',
      `p3_numeric` int(11) DEFAULT '0',
      `p3_feedback` int(11) DEFAULT '1',
      `use_p4` int(11) DEFAULT NULL,
      `p4_percent` float DEFAULT NULL,
      `p4_num_markers` int(11) DEFAULT '1',
      `p4_max` float DEFAULT NULL,
      `p4_desc` char(64) DEFAULT 'Report',
      `p4_numeric` int(11) DEFAULT '0',
      `p4_feedback` int(11) DEFAULT '1',
      `use_p5` int(11) DEFAULT NULL,
      `p5_percent` float DEFAULT NULL,
      `p5_num_markers` int(11) DEFAULT '1',
      `p5_max` float DEFAULT NULL,
      `p5_desc` char(64) DEFAULT 'Extra task',
      `p5_numeric` int(11) DEFAULT '0',
      `p5_feedback` int(11) DEFAULT '1',
      `accept_var` float DEFAULT NULL,
      `pass_mark` float DEFAULT NULL,
      `module_leader` char(64) DEFAULT NULL,
      `min_feedback_words` int(11) DEFAULT '10',
      `visible` int(1) DEFAULT '1',
      `ranked_choice` int(1) DEFAULT '1', 
      `choice_number` int(1) DEFAULT '5',
      `choice_areas_number` int(1) DEFAULT '10',
      `use_project_area` int(1) DEFAULT '0',
      `use_project_selection` int(1) DEFAULT '1',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

    CREATE TABLE IF NOT EXISTS `projects_setup_proposals` (
        `proj_id` INT NOT NULL ,
        `num_progs` INT NOT NULL DEFAULT '0' ,
        `prog_short_names` VARCHAR(256) NULL ,
        `i_relsites` INT NOT NULL DEFAULT '1' ,
        `i_relmods` INT NOT NULL DEFAULT '1' ,
        `i_backread` INT NOT NULL DEFAULT '1' ,
        `i_skills` INT NOT NULL DEFAULT '1' ,
        `i_software` INT NOT NULL DEFAULT '1' ,
        `i_softsupport` INT NOT NULL DEFAULT '1' ,
        `i_labs` INT NOT NULL DEFAULT '1' ,
        `i_works` INT NOT NULL DEFAULT '1' ,
        `i_furtherinfo` INT NOT NULL DEFAULT '1' ,
        `v_relsites` INT NOT NULL DEFAULT '1' ,
        `v_relmods` INT NOT NULL DEFAULT '1' ,
        `v_backread` INT NOT NULL DEFAULT '1' ,
        `v_skills` INT NOT NULL DEFAULT '1' ,
        `v_software` INT NOT NULL DEFAULT '1' ,
        `v_softsupport` INT NOT NULL DEFAULT '1' ,
        `v_labs` INT NOT NULL DEFAULT '1' ,
        `v_works` INT NOT NULL DEFAULT '1' ,
        `v_furtherinfo` INT NOT NULL DEFAULT '1' ,
        `m_relsites` INT NOT NULL DEFAULT '0' ,
        `m_relmods` INT NOT NULL DEFAULT '0' ,
        `m_backread` INT NOT NULL DEFAULT '0' ,
        `m_skills` INT NOT NULL DEFAULT '0' ,
        `m_software` INT NOT NULL DEFAULT '0' ,
        `m_softsupport` INT NOT NULL DEFAULT '0' ,
        `m_labs` INT NOT NULL DEFAULT '0' ,
        `m_works` INT NOT NULL DEFAULT '0' ,
        `m_furtherinfo` INT NOT NULL DEFAULT '0' ,
        UNIQUE (`proj_id`)
    ) ENGINE = InnoDB;

SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }
}

/**
 * Create the `admin_users` table (global super-admins) if it does not exist.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function create_admin_users_table_structure($db){

    $query = <<<SQL
    --
    -- Table structure for table `admin_users`
    --

    CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
      `firstname` varchar(25) DEFAULT NULL,
      `familyname` varchar(50) DEFAULT NULL,
      `fullname` varchar(75) DEFAULT NULL,
      `email` varchar(150) DEFAULT NULL,
      `username` varchar(25) DEFAULT NULL,
      `admin_level` int(1) DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

}

/**
 * Create the `admin_users_module` table (per-module admin privileges) if it does not exist.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function create_admin_users_module_table_structure($db){

    $query = <<<SQL
    --
    -- Table structure for table `admin_users_module`
    --

    CREATE TABLE IF NOT EXISTS `admin_users_module` (
      `index` int(11) NOT NULL AUTO_INCREMENT,
      `project_id` varchar(11) NOT NULL,
      `username` varchar(25) NOT NULL,
      `staffid` int(11) NOT NULL,
      `fullname` varchar(75) NOT NULL,
      `year_begin` year(4) DEFAULT 1977,
      `short_code` char(5) NOT NULL,
      `admin_level` int(1) DEFAULT '1',
      PRIMARY KEY (`index`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;

SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }
}
/**
 * Create the `staff_names` table if it does not exist.
 *
 * Stores staff details: username, fullname, familyname, firstname, title,
 * initials, email, admin_level, inactive flag.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function create_staff_names_table_structure($db){

    $query = <<<SQL
    --
    -- Table structure for table `staff_names`
    --

    CREATE TABLE IF NOT EXISTS `staff_names` (
    `staffid` int(11) NOT NULL AUTO_INCREMENT,
      `initials` varchar(9) DEFAULT NULL,
      `title` varchar(10) DEFAULT NULL,
      `firstname` varchar(25) DEFAULT NULL,
      `familyname` varchar(50) DEFAULT NULL,
      `fullname` varchar(75) DEFAULT NULL,
      `email` varchar(150) DEFAULT NULL,
      `username` varchar(25) NOT NULL,
      `room` varchar(10) DEFAULT NULL,
      `inactive` int(1) DEFAULT '0',
      `admin_level` int(1) DEFAULT '0',
      PRIMARY KEY (`staffid`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

    ALTER TABLE `staff_names` ADD UNIQUE KEY `username` (`username`);

SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }
}

/**
 * Create the assessment question categories template/examples tables if they do not exist.
 *
 * These tables seed the rubric builder with example category structures
 * that admins can copy when setting up a new module's marking criteria.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function create_assessqs_cats_templates_egs($db)
{
    $query = <<<SQL

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_cats_template_effort`
--

CREATE TABLE IF NOT EXISTS `assessqs_cats_template_effort` (
  `cat` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `weight_percent` float NOT NULL,
  `factor` float NOT NULL,
  `get_feedback` int DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_cats_template_effort`
--

INSERT INTO `assessqs_cats_template_effort` (`cat`, `description`, `weight_percent`, `factor`) VALUES
('1', 'Student''s personal contribution to the project	', 100, 0.2);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_cats_template_numeric_p1`
--

CREATE TABLE IF NOT EXISTS `assessqs_cats_template_numeric_p1` (
  `cat` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `weight_percent` float NOT NULL,
  `factor` float NOT NULL,
  `get_feedback` int DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_cats_template_numeric_p1`
--

INSERT INTO `assessqs_cats_template_numeric_p1` (`cat`, `description`, `weight_percent`, `factor`) VALUES
('1', 'Gap in body of knowledge', 30, 0.075),
('2', 'Research Methodology', 30, 0.075),
('3', 'Project planning', 20, 0.05),
('4', 'Writing', 20, 0.05);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_cats_template_numeric_p2`
--

CREATE TABLE IF NOT EXISTS `assessqs_cats_template_numeric_p2` (
  `cat` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `weight_percent` float NOT NULL,
  `factor` float NOT NULL,
    `get_feedback` int DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_cats_template_numeric_p2`
--

INSERT INTO `assessqs_cats_template_numeric_p2` (`cat`, `description`, `weight_percent`, `factor`) VALUES
('1', 'Presentation, Structure and Style of Work ', 10, 0.025),
('2', 'Referencing ', 10, 0.033),
('3', 'Coverage and Content ', 80, 0.08);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_cats_template_obj`
--

CREATE TABLE IF NOT EXISTS `assessqs_cats_template_obj` (
  `cat` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `weight_percent` float NOT NULL,
  `factor` float NOT NULL,
    `get_feedback` int DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_cats_template_obj`
--

INSERT INTO `assessqs_cats_template_obj` (`cat`, `description`, `weight_percent`, `factor`) VALUES
('1', 'Objectives', 100, 0.167);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_cats_template_pres`
--

CREATE TABLE IF NOT EXISTS `assessqs_cats_template_pres` (
  `cat` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `weight_percent` float NOT NULL,
  `factor` float NOT NULL,
    `get_feedback` int DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_cats_template_pres`
--

INSERT INTO `assessqs_cats_template_pres` (`cat`, `description`, `weight_percent`, `factor`) VALUES
('1', 'Presentation', 100, 0.2);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_cats_template_report`
--

CREATE TABLE IF NOT EXISTS `assessqs_cats_template_report` (
  `cat` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `weight_percent` float NOT NULL,
  `factor` float NOT NULL,
    `get_feedback` int DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_cats_template_report`
--

INSERT INTO `assessqs_cats_template_report` (`cat`, `description`, `weight_percent`, `factor`) VALUES
('1', 'Presentation, Structure and Style of Work ', 10, 0.025),
('2', 'Referencing ', 10, 0.033),
('3', 'Coverage and Content ', 80, 0.08);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_cats_template_report_icme`
--

CREATE TABLE IF NOT EXISTS `assessqs_cats_template_report_icme` (
  `cat` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `weight_percent` float NOT NULL,
  `factor` float NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_cats_template_report_icme`
--

INSERT INTO `assessqs_cats_template_report_icme` (`cat`, `description`, `weight_percent`, `factor`) VALUES
('1', 'Gap in body of knowledge', 20, 0.5),
('2', 'Research methodology', 20, 0.5),
('3', 'Data analysis', 20, 0.5),
('4', 'Contribution to body of knowledge', 20, 0.5),
('5', 'Writing', 20, 0.5);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }
}

/**
 * Create the assessment question templates/examples tables if they do not exist.
 *
 * These tables store example rubric questions that can be copied into new modules.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function create_assessqs_templates_egs($db)
{
    $query = <<<SQL

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_template_effort`
--

CREATE TABLE IF NOT EXISTS `assessqs_template_effort` (
  `qid` int(11) DEFAULT NULL,
  `cat` int(11) DEFAULT NULL,
  `question` text,
  `entries` int(11) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `mark1` int(11) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `mark2` int(11) DEFAULT NULL,
  `text3` varchar(255) DEFAULT NULL,
  `mark3` int(11) DEFAULT NULL,
  `text4` varchar(255) DEFAULT NULL,
  `mark4` int(11) DEFAULT NULL,
  `text5` varchar(255) DEFAULT NULL,
  `mark5` int(11) DEFAULT NULL,
  `text6` varchar(255) DEFAULT NULL,
  `mark6` int(11) DEFAULT NULL,
  `text7` varchar(255) DEFAULT NULL,
  `mark7` int(11) DEFAULT NULL,
  `text8` varchar(255) DEFAULT NULL,
  `mark8` int(11) DEFAULT NULL,
  `text9` varchar(255) DEFAULT NULL,
  `mark9` int(11) DEFAULT NULL,
  `text10` varchar(255) DEFAULT NULL,
  `mark10` int(11) DEFAULT NULL,
  `text11` varchar(255) DEFAULT NULL,
  `mark11` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT 100
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_template_effort`
--

INSERT INTO `assessqs_template_effort` (`qid`, `cat`, `question`, `entries`, `text1`, `mark1`, `text2`, `mark2`, `text3`, `mark3`, `text4`, `mark4`, `text5`, `mark5`, `text6`, `mark6`, `text7`, `mark7`, `text8`, `mark8`, `text9`, `mark9`, `text10`, `mark10`, `text11`, `mark11`, `weight`) VALUES
(1, 1, 'How would you assess the student''s participation at meetings?', 5, 'Did not engage', 0, 'Supervisor driven', 35, 'Supervisor led with some input from student', 67, 'Student led with supervisor assisting direction', 75, 'Student driven', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(2, 1, 'How organised was the student when attending meetings?', 5, 'Never', 0, 'Occasionally', 35, 'Sometimes', 67, 'Often', 75, 'Always', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(3, 1, 'In undertaking the project work, how thorough would you say the student was?', 5, 'Not at all', 0, 'Seldom', 33, 'Occasionally', 67, 'Methodical', 75, 'Very thorough', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(4, 1, 'To what extent did the student show initiative?', 5, 'No initiative', 0, 'Occasional initiative', 33, 'Regularly showed initiative', 67, 'Good initiative', 75, 'Inspired initiative', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(5, 1, 'How concerned and conscientious was the student in undertaking the project?', 5, 'Not at all concerned or conscientious', 0, 'Somewhat concerned and conscientious', 33, 'Concerned and conscientious', 67, 'Very concerned and conscientious', 75, 'Exceptionally concerned and conscientious', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(6, 1, 'Feedback comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_template_numeric_p1`
--

CREATE TABLE IF NOT EXISTS `assessqs_template_numeric_p1` (
  `qid` int(11) DEFAULT NULL,
  `cat` int(11) DEFAULT NULL,
  `question` text,
  `entries` int(11) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `mark1` int(11) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `mark2` int(11) DEFAULT NULL,
  `text3` varchar(255) DEFAULT NULL,
  `mark3` int(11) DEFAULT NULL,
  `text4` varchar(255) DEFAULT NULL,
  `mark4` int(11) DEFAULT NULL,
  `text5` varchar(255) DEFAULT NULL,
  `mark5` int(11) DEFAULT NULL,
  `text6` varchar(255) DEFAULT NULL,
  `mark6` int(11) DEFAULT NULL,
  `text7` varchar(255) DEFAULT NULL,
  `mark7` int(11) DEFAULT NULL,
  `text8` varchar(255) DEFAULT NULL,
  `mark8` int(11) DEFAULT NULL,
  `text9` varchar(255) DEFAULT NULL,
  `mark9` int(11) DEFAULT NULL,
  `text10` varchar(255) DEFAULT NULL,
  `mark10` int(11) DEFAULT NULL,
  `text11` varchar(255) DEFAULT NULL,
  `mark11` int(11) DEFAULT NULL,
    `weight` int(11) DEFAULT 100
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_template_numeric_p1`
--

INSERT INTO `assessqs_template_numeric_p1` (`qid`, `cat`, `question`, `entries`, `text1`, `mark1`, `text2`, `mark2`, `text3`, `mark3`, `text4`, `mark4`, `text5`, `mark5`, `text6`, `mark6`, `text7`, `mark7`, `text8`, `mark8`, `text9`, `mark9`, `text10`, `mark10`, `text11`, `mark11`) VALUES
(1, 1, 'Coverage of literature', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(2, 1, 'Research problem', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(3, 1, 'Aim(s)', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(4, 1, 'Objective(s)', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(5, 1, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 2, 'Research design', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(2, 2, 'Research method', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(3, 2, 'Research instruments', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(4, 2, 'Research ethics', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(5, 2, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 3, 'Activities', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(2, 3, 'Activity durations', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(3, 3, 'Activity logic', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(4, 3, 'Research Programme', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(5, 3, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 4, 'Grammar', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(2, 4, 'Spelling', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(3, 4, 'Presentation', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(4, 4, 'References', 11, '0', 0, '1', 1, '2', 2, '3', 3, '4', 4, '5', 5, '6', 6, '7', 7, '8', 8, '9', 9, '10', 10),
(5, 4, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_template_numeric_p2`
--

CREATE TABLE IF NOT EXISTS `assessqs_template_numeric_p2` (
  `qid` int(11) DEFAULT NULL,
  `cat` int(11) DEFAULT NULL,
  `question` text,
  `entries` int(11) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `mark1` int(11) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `mark2` int(11) DEFAULT NULL,
  `text3` varchar(255) DEFAULT NULL,
  `mark3` int(11) DEFAULT NULL,
  `text4` varchar(255) DEFAULT NULL,
  `mark4` int(11) DEFAULT NULL,
  `text5` varchar(255) DEFAULT NULL,
  `mark5` int(11) DEFAULT NULL,
  `text6` varchar(255) DEFAULT NULL,
  `mark6` int(11) DEFAULT NULL,
  `text7` varchar(255) DEFAULT NULL,
  `mark7` int(11) DEFAULT NULL,
  `text8` varchar(255) DEFAULT NULL,
  `mark8` int(11) DEFAULT NULL,
  `text9` varchar(255) DEFAULT NULL,
  `mark9` int(11) DEFAULT NULL,
  `text10` varchar(255) DEFAULT NULL,
  `mark10` int(11) DEFAULT NULL,
  `text11` varchar(255) DEFAULT NULL,
  `mark11` int(11) DEFAULT NULL,
    `weight` int(11) DEFAULT 100
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_template_numeric_p2`
--

INSERT INTO `assessqs_template_numeric_p2` (`qid`, `cat`, `question`, `entries`, `text1`, `mark1`, `text2`, `mark2`, `text3`, `mark3`, `text4`, `mark4`, `text5`, `mark5`, `text6`, `mark6`, `text7`, `mark7`, `text8`, `mark8`, `text9`, `mark9`, `text10`, `mark10`, `text11`, `mark11`) VALUES
(1, 1, 'Coverage of literature', 11, 'Missing', 0, 'Mainly not comprehensive + Mainly not justified', 1, 'Mainly not comprehensive + Mainly justified', 2, 'Mainly not comprehensive + Completely justified', 3, 'Mainly comprehensive + Mainly not justified', 4, 'Exhaustive  + Mainly not justified', 5, 'Mainly comprehensive + Mainly justified', 6, 'Mainly comprehensive + Completely justified', 7, 'Exhaustive + Mainly justified', 8, 'Exhaustive + Completely justified', 9, 'Outstanding', 10),
(2, 1, 'Research problem', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate + Mainly justified', 2, 'Mainly inaccurate  + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate  + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(3, 1, 'Aim(s)', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate  + Mainly justified', 2, 'Mainly inaccurate + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(4, 1, 'Objective(s)', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(5, 1, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 2, 'Research design', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(2, 2, 'Research method', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(3, 2, 'Research instruments', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(4, 2, 'Research ethics', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(5, 2, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 3, 'Data collection', 11, 'Missing', 0, 'Not comprehensive + Mainly not justified', 1, 'Not comprehensive + Mainly justified', 2, 'Not comprehensive + Completely justified', 3, 'Fairly comprehensive + Mainly not justified', 4, 'Exhaustive + Mainly not justified', 5, 'Fairly comprehensive + Mainly justified', 6, 'Fairly comprehensive + Completely justified', 7, 'Exhaustive + Mainly justified', 8, 'Exhaustive + Completely justified', 9, 'Outstanding', 10),
(2, 3, 'Data analysis', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate  + Mainly justified', 2, 'Mainly inaccurate  + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate  + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(3, 3, 'Interpretation of results', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate  + Mainly justified', 2, 'Mainly inaccurate  + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate  + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(4, 3, 'Discussion of results', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(5, 3, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 4, 'Conclusions', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(2, 4, 'Limitations', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(3, 4, 'Recommendations', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate  + Mainly justified', 2, 'Mainly inaccurate  + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate  + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(4, 4, 'Contribution to knowledge', 11, 'Missing', 0, 'Not comprehensive + Mainly not justified', 1, 'Not comprehensive + Mainly justified', 2, 'Not comprehensive + Completely justified', 3, 'Fairly comprehensive + Mainly not justified', 4, 'Exhaustive + Mainly not justified', 5, 'Fairly comprehensive + Mainly justified', 6, 'Fairly comprehensive + Completely justified', 7, 'Exhaustive + Mainly justified', 8, 'Exhaustive + Completely justified', 9, 'Outstanding', 10),
(5, 4, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 5, 'Grammar', 4, 'Mainly inaccurate', 2, 'Mainly accurate', 6, 'Completely accurate', 8, 'Outstanding', 10, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(2, 5, 'Spelling', 4, 'Mainly inaccurate', 2, 'Mainly accurate', 6, 'Completely accurate', 8, 'Outstanding', 10, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(3, 5, 'Presentation', 4, 'Mainly inaccurate', 2, 'Mainly accurate', 6, 'Completely accurate', 8, 'Outstanding', 10, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(4, 5, 'References', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(5, 5, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_template_obj`
--

CREATE TABLE IF NOT EXISTS `assessqs_template_obj` (
  `qid` int(11) DEFAULT NULL,
  `cat` int(11) DEFAULT NULL,
  `question` text,
  `entries` int(11) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `mark1` int(11) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `mark2` int(11) DEFAULT NULL,
  `text3` varchar(255) DEFAULT NULL,
  `mark3` int(11) DEFAULT NULL,
  `text4` varchar(255) DEFAULT NULL,
  `mark4` int(11) DEFAULT NULL,
  `text5` varchar(255) DEFAULT NULL,
  `mark5` int(11) DEFAULT NULL,
  `text6` varchar(255) DEFAULT NULL,
  `mark6` int(11) DEFAULT NULL,
  `text7` varchar(255) DEFAULT NULL,
  `mark7` int(11) DEFAULT NULL,
  `text8` varchar(255) DEFAULT NULL,
  `mark8` int(11) DEFAULT NULL,
  `text9` varchar(255) DEFAULT NULL,
  `mark9` int(11) DEFAULT NULL,
  `text10` varchar(255) DEFAULT NULL,
  `mark10` int(11) DEFAULT NULL,
  `text11` varchar(255) DEFAULT NULL,
  `mark11` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT 100
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_template_obj`
--

INSERT INTO `assessqs_template_obj` (`qid`, `cat`, `question`, `entries`, `text1`, `mark1`, `text2`, `mark2`, `text3`, `mark3`, `text4`, `mark4`, `text5`, `mark5`, `text6`, `mark6`, `text7`, `mark7`, `text8`, `mark8`, `text9`, `mark9`, `text10`, `mark10`, `text11`, `mark11`, `weight`) VALUES
(1, 1, 'Are the objectives specific; saying what the activities will be, described in terms of a verb etc.?', 4, 'Not specific at all', 10, 'A bit vauge', 33, 'Reasonably clear and precise', 66, 'Fully clear what work is being done', 90, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 20),
(2, 1, 'Are the objectives measurable: demonstrate that reference points of where it starts and where is gets to are defined?', 4, 'No percetible measures', 0, 'Some mention of measuring', 33, 'A methodology for quantification  but not clear how it will be achieved', 66, 'Perfectly clear how and why quantification will take place', 90, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 20),
(3, 1, 'Are the objectives realistic?', 4, 'Very unrealistic objectives', 15, 'Partially realistic or unambitious', 33, 'Realistic in terms of knowledge and resources but over ambitious in time', 70, 'Ambitious, stretching existing knowledge and capabilities,  yet time limited', 90, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 20),
(4, 1, 'How well were the aims and objectives expressed?', 5, 'Totally unrealistic / too simplistic', 10, 'Some minor thought shown but they are simplistic or unrealistic', 25, 'Adequate, but some important issues overlooked.', 50, 'Good. Clearly stated but some missing elements', 70, 'Excellent set of objectives that will ensure an excellent project if achieved', 90, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(5, 1, 'How would you describe the student''s engagement with the project and literature?', 5, 'No evidence of engagement with project or literature', 0, 'Scant evidence of engagement, e.g. superficial literature review', 25, 'Some engagement, but perhaps supervisor led', 50, 'Engaged with some appreciation of key literature', 70, 'Engaged fully with the project, identifying current state of knowledge', 90, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(7, 1, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0),
(6, 1, 'How was the structure and presentation of the workplan?', 5, 'Incomplete / missing', 0, 'Badly structured / poorly presented', 40, 'Adequate', 50, 'Good', 70, 'Excellent, with clear and logical presentation', 90, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_template_pres`
--

CREATE TABLE IF NOT EXISTS `assessqs_template_pres` (
  `qid` int(11) DEFAULT NULL,
  `cat` int(11) DEFAULT NULL,
  `question` text,
  `entries` int(11) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `mark1` int(11) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `mark2` int(11) DEFAULT NULL,
  `text3` varchar(255) DEFAULT NULL,
  `mark3` int(11) DEFAULT NULL,
  `text4` varchar(255) DEFAULT NULL,
  `mark4` int(11) DEFAULT NULL,
  `text5` varchar(255) DEFAULT NULL,
  `mark5` int(11) DEFAULT NULL,
  `text6` varchar(255) DEFAULT NULL,
  `mark6` int(11) DEFAULT NULL,
  `text7` varchar(255) DEFAULT NULL,
  `mark7` int(11) DEFAULT NULL,
  `text8` varchar(255) DEFAULT NULL,
  `mark8` int(11) DEFAULT NULL,
  `text9` varchar(255) DEFAULT NULL,
  `mark9` int(11) DEFAULT NULL,
  `text10` varchar(255) DEFAULT NULL,
  `mark10` int(11) DEFAULT NULL,
  `text11` varchar(255) DEFAULT NULL,
  `mark11` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT 100
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_template_pres`
--

INSERT INTO `assessqs_template_pres` (`qid`, `cat`, `question`, `entries`, `text1`, `mark1`, `text2`, `mark2`, `text3`, `mark3`, `text4`, `mark4`, `text5`, `mark5`, `text6`, `mark6`, `text7`, `mark7`, `text8`, `mark8`, `text9`, `mark9`, `text10`, `mark10`, `text11`, `mark11`, `weight`) VALUES
(1, 1, 'Was the presentation well structured?', 6, 'Lacking any structure', 25, 'Unstructured', 40, 'Adequate structure', 55, 'Clear structure', 65, 'Excellent structure', 75, 'Outstanding, professional structure', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(2, 1, 'Was the project conveyed well?', 6, 'Little or no conveying of message', 25, 'Attempt made to communicate, but unsuccessfully', 40, 'Good communication', 55, 'Very good communication', 65, 'Excellent communication', 75, 'Inspirational, evocative communication', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(3, 1, 'Was the presentation audible, with eye contact and at an appropriate pace?', 6, 'Inaudible, with no eye contact and poorly paced', 25, 'Audible or with eye contact or well-paced', 40, 'Audible and/or with eye contact and/or well-paced', 55, 'Audible or with eye contact or well-paced', 65, 'As above, but excelling in one or two aspects', 75, 'Outstanding, professional and inspired', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(4, 1, 'How were the responses to questions?', 6, 'No response', 20, 'Unclear or otherwise lacking', 20, 'Adequate', 50, 'Good', 60, 'Accurate and well-informed', 70, 'Insightful and detailed', 80, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(5, 1, 'Were the slides well presented?', 5, 'Messy / untidy', 20, 'Acceptable but lacking in some aspects', 45, 'Legible, not cluttered, good mix of text and images', 60, 'Excellent layout', 75, 'Professional layout', 80, 'Insightful and detailed', 80, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(6, 1, 'Did the speaker stick to time?', 4, 'Far too short (<6 mins)', -10, 'A little too short (6-8 mins)', -5, 'On time (8-12 minutes)', 0, 'Too long (>12 minutes)', -6, 'Professional layout', 80, 'Insightful and detailed', 80, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(7, 1, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, 'NULL', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_template_report`
--

CREATE TABLE IF NOT EXISTS `assessqs_template_report` (
  `qid` int(11) DEFAULT NULL,
  `cat` int(11) DEFAULT NULL,
  `question` text,
  `entries` int(11) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `mark1` int(11) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `mark2` int(11) DEFAULT NULL,
  `text3` varchar(255) DEFAULT NULL,
  `mark3` int(11) DEFAULT NULL,
  `text4` varchar(255) DEFAULT NULL,
  `mark4` int(11) DEFAULT NULL,
  `text5` varchar(255) DEFAULT NULL,
  `mark5` int(11) DEFAULT NULL,
  `text6` varchar(255) DEFAULT NULL,
  `mark6` int(11) DEFAULT NULL,
  `text7` varchar(255) DEFAULT NULL,
  `mark7` int(11) DEFAULT NULL,
  `text8` varchar(255) DEFAULT NULL,
  `mark8` int(11) DEFAULT NULL,
  `text9` varchar(255) DEFAULT NULL,
  `mark9` int(11) DEFAULT NULL,
  `text10` varchar(255) DEFAULT NULL,
  `mark10` int(11) DEFAULT NULL,
  `text11` varchar(255) DEFAULT NULL,
  `mark11` int(11) DEFAULT NULL,
  `weight` int(11) DEFAULT 100
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_template_report`
--

INSERT INTO `assessqs_template_report` (`qid`, `cat`, `question`, `entries`, `text1`, `mark1`, `text2`, `mark2`, `text3`, `mark3`, `text4`, `mark4`, `text5`, `mark5`, `text6`, `mark6`, `text7`, `mark7`, `text8`, `mark8`, `text9`, `mark9`, `text10`, `mark10`, `text11`, `mark11`, `weight`) VALUES
(1, 1, 'Was the report of an appropriate length?', 3, 'Too short', -50, 'Too Long', -51, 'Acceptable', 0, '', 0, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(2, 1, 'How well presented was the report in terms of line spacing, incorporation of diagrams, figures and tables?', 5, 'Messy or untidy', 20, 'Acceptable', 50, 'Good', 65, 'Very good', 75, 'Professional', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(3, 1, 'How would you assess the student''s written English?', 5, 'Poor unclear writing AND serious  errors of language', 20, 'Poor unclear writing OR serious  errors of language', 35, 'Competent writing with few serious grammatical or spelling errors', 65, 'Accurate, with minimal grammatical or spelling errors', 75, 'Fluent and sophisticated, with minimal grammatical or spelling errors', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(4, 1, 'Did the structure of the report make sense in terms of the connectedness and presentation of material?', 5, 'Poorly structured', 20, 'Adequate structure', 35, 'Good structure', 65, 'Very good structure', 75, 'Excellent or outstanding structure', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(5, 1, 'How would you rate the student''s use of methods, data analysis and/or data presentation in the report, e.g. clear diagrammatic labelling and legends, application of relevant statistical procedures, etc.?', 5, 'Absent or inappropriate', 20, 'Sometimes used', 35, 'Mostly used appropriately', 65, 'Almost always used appropriately', 75, 'Inspired use of data analysis or data presentation', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(6, 1, 'Feedback comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(1, 2, 'How complete was the use of references?', 5, 'No references given, or references irrelevant.', 15, 'A few references given that justify conclusions.', 35, 'Some references given that justify conclusions.', 55, 'Good reference list, justifying conclusions.', 65, 'Complete reference list, justifying conclusions.', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(2, 2, 'How accurate was the use of references?', 5, 'No use of referencing', 15, 'Incorrect or inconsistent use of referencing', 30, 'Occasional correct use of referencing', 40, 'Largely correctly and consistently used.', 65, 'Correctly and consistently used.', 85, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(3, 2, 'Were references used appropriately throughout the written report?', 6, 'No references present.', 0, 'Minimal or inappropriate use of references.', 15, 'References used appropriately on occasion', 40, 'References often used appropriately', 60, 'References used appropriately in almost all instances', 70, 'References used appropriately throughout the dissertation', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(4, 2, 'Feedback comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(1, 3, 'Assess the student''s understanding of the subject area.', 6, 'Lacking detail or understanding', 15, 'Insight from lecture material with little external input', 45, 'Insight drawn primarily from course lectures', 55, 'Analysis and insight beyond course lectures', 65, 'Critical insight, and originality', 72, 'Extensive insight and originality', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(2, 3, 'How clearly were the aims and objectives of the project presented and explained?', 5, 'Poorly', 15, 'Occasionally muddled', 30, 'Fairly clear', 50, 'Clear', 70, 'Crystal clear', 85, 'Extensive insight and originality', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(3, 3, 'How rigorous would you regard the student''s approach to both the background material and the core detail?', 6, 'No rigour', 15, 'Very little consideration to background material', 45, 'Competent use of core material, with some additional input from background material.', 55, 'Thorough use of core material with good use of background information.', 65, 'Use of core material and substantial background information.', 75, 'Use of core material and extensive background information.', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(4, 3, 'How would you assess the student''s logical reasoning in the written report?', 6, 'No evidence of logic or understanding', 15, 'Little evidence of logic or understanding', 25, 'Some logical reasoning', 40, 'Logic beyond course material', 60, 'Excellent logic and understanding', 75, 'Inspired logic and exceptional understanding', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(5, 3, 'Assess the student''s critical judgement in the written report', 6, 'No evidence of analysis or synthesis', 15, 'Some evidence of analysis, but lacking synthesis', 30, 'Good analysis of data but lacking or incorrect synthesis', 45, 'Good analysis with some synthesis', 65, 'Excellent analysis and good attempt at synthesis', 75, 'Excellent analysis and synthesis', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(6, 3, 'How well has the student placed their work in context?', 6, 'Unaware of others'' work in the field', 15, 'Little understanding of other work in field', 30, 'Aware of other work, but hasn?t placed work in context', 45, 'Aware of other work and has placed their work in context.', 65, 'Clear sense of how results build on existing knowledge', 75, 'Excellent sense of how results build on existing knowledge', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(7, 3, 'Are the arguments structured logically to address the stated aims and objectives?', 6, 'Neither logical structure nor links with aims and objectives', 15, 'Unstructured or failing to link with aims and objectives', 30, 'Some structured arguments and linked to some aims and objectives', 45, 'Structured arguments linking to aims and ojectives', 65, 'Well structured arguments linking to aims and objectives', 75, 'Excellent presentation of arguments linking to aims and objectives', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(8, 3, 'How well does the student draw conclusions from their work?', 6, 'Fails to draw any conclusions', 10, 'Conclusions are poor or inaccurately drawn', 30, 'Conclusions are adequate but perhaps not linked to results.', 45, 'Good, well drawn conclusions.', 60, 'Very good and thoughtful conclusions', 75, 'Excellent and insightful conclusions linked to results.', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(9, 3, 'How would you assess the student''s level of originality?', 6, 'None whatsoever', 15, 'Occasional glimpses of original thought', 30, 'Competent rather than original', 50, 'Comprehensive rather than original', 65, 'Originality of new ideas', 75, 'Extensive insight and originality', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(10, 3, 'How would you rate the student''s overall technical understanding?', 6, 'Poor', 20, 'Mediocre', 45, 'Average', 55, 'Good', 65, 'Excellent', 75, 'Outstanding', 85, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100),
(11, 3, 'Feedback comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100);

-- --------------------------------------------------------

--
-- Table structure for table `assessqs_template_report_icme`
--

CREATE TABLE IF NOT EXISTS `assessqs_template_report_icme` (
  `qid` int(11) DEFAULT NULL,
  `cat` int(11) DEFAULT NULL,
  `question` text,
  `entries` int(11) DEFAULT NULL,
  `text1` varchar(255) DEFAULT NULL,
  `mark1` int(11) DEFAULT NULL,
  `text2` varchar(255) DEFAULT NULL,
  `mark2` int(11) DEFAULT NULL,
  `text3` varchar(255) DEFAULT NULL,
  `mark3` int(11) DEFAULT NULL,
  `text4` varchar(255) DEFAULT NULL,
  `mark4` int(11) DEFAULT NULL,
  `text5` varchar(255) DEFAULT NULL,
  `mark5` int(11) DEFAULT NULL,
  `text6` varchar(255) DEFAULT NULL,
  `mark6` int(11) DEFAULT NULL,
  `text7` varchar(255) DEFAULT NULL,
  `mark7` int(11) DEFAULT NULL,
  `text8` varchar(255) DEFAULT NULL,
  `mark8` int(11) DEFAULT NULL,
  `text9` varchar(255) DEFAULT NULL,
  `mark9` int(11) DEFAULT NULL,
  `text10` varchar(255) DEFAULT NULL,
  `mark10` int(11) DEFAULT NULL,
  `text11` varchar(255) DEFAULT NULL,
  `mark11` int(11) DEFAULT NULL,
    `weight` int(11) DEFAULT 100
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `assessqs_template_report_icme`
--

INSERT INTO `assessqs_template_report_icme` (`qid`, `cat`, `question`, `entries`, `text1`, `mark1`, `text2`, `mark2`, `text3`, `mark3`, `text4`, `mark4`, `text5`, `mark5`, `text6`, `mark6`, `text7`, `mark7`, `text8`, `mark8`, `text9`, `mark9`, `text10`, `mark10`, `text11`, `mark11`) VALUES
(1, 1, 'Coverage of literature', 11, 'Missing', 0, 'Mainly not comprehensive + Mainly not justified', 1, 'Mainly not comprehensive + Mainly justified', 2, 'Mainly not comprehensive + Completely justified', 3, 'Mainly comprehensive + Mainly not justified', 4, 'Exhaustive  + Mainly not justified', 5, 'Mainly comprehensive + Mainly justified', 6, 'Mainly comprehensive + Completely justified', 7, 'Exhaustive + Mainly justified', 8, 'Exhaustive + Completely justified', 9, 'Outstanding', 10),
(2, 1, 'Research problem', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate + Mainly justified', 2, 'Mainly inaccurate  + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate  + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(3, 1, 'Aim(s)', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate  + Mainly justified', 2, 'Mainly inaccurate + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(4, 1, 'Objective(s)', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(5, 1, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 2, 'Research design', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(2, 2, 'Research method', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(3, 2, 'Research instruments', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(4, 2, 'Research ethics', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(5, 2, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 3, 'Data collection', 11, 'Missing', 0, 'Not comprehensive + Mainly not justified', 1, 'Not comprehensive + Mainly justified', 2, 'Not comprehensive + Completely justified', 3, 'Fairly comprehensive + Mainly not justified', 4, 'Exhaustive + Mainly not justified', 5, 'Fairly comprehensive + Mainly justified', 6, 'Fairly comprehensive + Completely justified', 7, 'Exhaustive + Mainly justified', 8, 'Exhaustive + Completely justified', 9, 'Outstanding', 10),
(2, 3, 'Data analysis', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate  + Mainly justified', 2, 'Mainly inaccurate  + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate  + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(3, 3, 'Interpretation of results', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate  + Mainly justified', 2, 'Mainly inaccurate  + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate  + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(4, 3, 'Discussion of results', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(5, 3, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 4, 'Conclusions', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(2, 4, 'Limitations', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(3, 4, 'Recommendations', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not justified', 1, 'Mainly inaccurate  + Mainly justified', 2, 'Mainly inaccurate  + Completely justified', 3, 'Mainly accurate  + Mainly not justified', 4, 'Completely accurate + Mainly not justified', 5, 'Mainly accurate  + Mainly justified', 6, 'Mainly accurate  + Completely justified', 7, 'Completely accurate + Mainly justified', 8, 'Completely accurate + Completely justified', 9, 'Outstanding', 10),
(4, 4, 'Contribution to knowledge', 11, 'Missing', 0, 'Not comprehensive + Mainly not justified', 1, 'Not comprehensive + Mainly justified', 2, 'Not comprehensive + Completely justified', 3, 'Fairly comprehensive + Mainly not justified', 4, 'Exhaustive + Mainly not justified', 5, 'Fairly comprehensive + Mainly justified', 6, 'Fairly comprehensive + Completely justified', 7, 'Exhaustive + Mainly justified', 8, 'Exhaustive + Completely justified', 9, 'Outstanding', 10),
(5, 4, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(1, 5, 'Grammar', 4, 'Mainly inaccurate', 2, 'Mainly accurate', 6, 'Completely accurate', 8, 'Outstanding', 10, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(2, 5, 'Spelling', 4, 'Mainly inaccurate', 2, 'Mainly accurate', 6, 'Completely accurate', 8, 'Outstanding', 10, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(3, 5, 'Presentation', 4, 'Mainly inaccurate', 2, 'Mainly accurate', 6, 'Completely accurate', 8, 'Outstanding', 10, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0),
(4, 5, 'References', 11, 'Missing', 0, 'Mainly inaccurate + Mainly not comprehensive', 1, 'Mainly inaccurate + Mainly comprehensive', 2, 'Mainly inaccurate + Exhaustive', 3, 'Mainly accurate + Mainly not comprehensive', 4, 'Completely accurate + Mainly not comprehensive', 5, 'Mainly accurate + Mainly comprehensive', 6, 'Mainly accurate + Exhaustive', 7, 'Completely accurate + Mainly comprehensive', 8, 'Completely accurate + Exhaustive', 9, 'Outstanding', 10),
(5, 5, 'Feedback Comments:', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0, '', 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

}

/**
 * Create a module-specific project setup table if it does not exist.
 *
 * Each module has its own setup table named via $dash_projects_setup
 * (e.g. `dash_projects_setup_123`).
 *
 * @param PDO    $db                  Active PDO connection.
 * @param string $dash_projects_setup Table name for this module's setup.
 * @return void
 */
function create_dash_projects_setup_table($db, $dash_projects_setup)
{
    $query = <<<SQL
--
-- Table structure for table `assessment_criteria_flags`
--

CREATE TABLE IF NOT EXISTS $dash_projects_setup (
  `id` int(11) NOT NULL,
  `prefix` text NOT NULL,
  `description` text NOT NULL,
  `title` text NOT NULL,
  `subtitle` text NOT NULL,
  `visible` int(11) DEFAULT '0',
  `active` int(11) DEFAULT '0',
  `staff` int(11) DEFAULT '0',
  `student` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SQL;

    try {
        $db->query($query);
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

    update_dash_projects_setup_tables($db, $dash_projects_setup);
}

/**
 * Add any missing columns to an existing module project setup table.
 *
 * Called during setup to bring older module tables up to the current schema
 * without dropping and recreating them.
 *
 * @param PDO    $db                  Active PDO connection.
 * @param string $dash_projects_setup Table name for this module's setup.
 * @return void
 */
function update_dash_projects_setup_tables($db, $dash_projects_setup)
{
    // Check that the id=0 is there . Thi sis the ON/OFF switch: staff, student .NE. 0
    $id = 0;
    $query = "SELECT * FROM $dash_projects_setup WHERE id=$id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows == 0){
        // Need to create this row
        $prefix = "notused_";
        $description = "Switch to show Dash buttons or not";
        $title = "DASH";
        $subtitle = "Tools for viewing marks work and feeding back";
        $visible = 0; // Not uesed
        $active = 0; // Not Used
        $staff = 0; // Initially not visible
        $student = 0; // Initially not visible
        $query = "INSERT INTO $dash_projects_setup (`id`, `prefix`, `description`, `title`, `subtitle`, `visible`, `active`, `staff`, `student`)
              VALUES ($id, '$prefix', '$description', '$title', '$subtitle', $visible, $active, $staff, $student)";

        try {
            $db->query($query);
        } catch (PDOException $ex) {
            $err_msg = $ex->getMessage();
            echo($err_msg . "<BR>\n");
        }
    }

    // PEGA id = 1
    //=========================================================================================================
    $id = 1;
    $query = "SELECT * FROM $dash_projects_setup WHERE id=$id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows == 0) {
        $prefix = "peg_";
        $description = "Workload modelling for all activities";
        $title = "PEGA";
        $subtitle = "Workload mapping";
        $visible = 0;
        $active = 0;
        $staff = 0;
        $student = 0;
        $query = "INSERT INTO $dash_projects_setup (`id`, `prefix`, `description`, `title`, `subtitle`, `visible`, `active`, `staff`, `student`)
              VALUES ($id, '$prefix', '$description', '$title', '$subtitle', $visible, $active, $staff, $student)";

        try {
            $db->query($query);
        } catch (PDOException $ex) {
            $err_msg = $ex->getMessage();
            echo($err_msg . "<BR>\n");
        }
    }

    // HAMID id = 2
    //=========================================================================================================
    $id = 2;
    $query = "SELECT * FROM $dash_projects_setup WHERE id=$id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows == 0) {
        $prefix = "ha_";
        $description = "How am I doing?";
        $title = "HAMID";
        $subtitle = "Reflect on assessment performance";
        $visible = 0;
        $active = 0;
        $staff = 0;
        $student = 0;
        $query = "INSERT INTO $dash_projects_setup (`id`, `prefix`, `description`, `title`, `subtitle`, `visible`, `active`, `staff`, `student`)
              VALUES ($id, '$prefix', '$description', '$title', '$subtitle', $visible, $active, $staff, $student)";

        try {
            $db->query($query);
        } catch (PDOException $ex) {
            $err_msg = $ex->getMessage();
            echo($err_msg . "<BR>\n");
        }
    }

    // DIST id = 3
    //=========================================================================================================
    $id = 3;
    $query = "SELECT * FROM $dash_projects_setup WHERE id=$id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows == 0) {
        $prefix = "di_";
        $description = "Mark Distribution Charts";
        $title = "DIST";
        $subtitle = "Create distribution graphics of assessment marks";
        $visible = 0;
        $active = 0;
        $staff = 0;
        $student = 0;
        $query = "INSERT INTO $dash_projects_setup (`id`, `prefix`, `description`, `title`, `subtitle`, `visible`, `active`, `staff`, `student`)
              VALUES ($id, '$prefix', '$description', '$title', '$subtitle', $visible, $active, $staff, $student)";

        try {
            $db->query($query);
        } catch (PDOException $ex) {
            $err_msg = $ex->getMessage();
            echo($err_msg . "<BR>\n");
        }
    }

    // CWT id = 4
    //=========================================================================================================
    $id = 4;
    $query = "SELECT * FROM $dash_projects_setup WHERE id=$id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows == 0) {
        $prefix = "cwt_";
        $description = "Check what coursework is coming up";
        $title = "CWT";
        $subtitle = "Coursework Timetables and Graphics";
        $visible = 0;
        $active = 0;
        $staff = 0;
        $student = 0;
        $query = "INSERT INTO $dash_projects_setup (`id`, `prefix`, `description`, `title`, `subtitle`, `visible`, `active`, `staff`, `student`)
              VALUES ($id, '$prefix', '$description', '$title', '$subtitle', $visible, $active, $staff, $student)";

        try {
            $db->query($query);
        } catch (PDOException $ex) {
            $err_msg = $ex->getMessage();
            echo($err_msg . "<BR>\n");
        }
    }

    // KMAP id = 5
    //=========================================================================================================
    $id = 5;
    $query = "SELECT * FROM $dash_projects_setup WHERE id=$id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows == 0) {
        $prefix = "kmap_";
        $description = "Collect, Collate and Map  Key Assessment Information";
        $title = "KAI";
        $subtitle = "Key Assessment Information";
        $visible = 0;
        $active = 0;
        $staff = 0;
        $student = 0;
        $query = "INSERT INTO $dash_projects_setup (`id`, `prefix`, `description`, `title`, `subtitle`, `visible`, `active`, `staff`, `student`)
              VALUES ($id, '$prefix', '$description', '$title', '$subtitle', $visible, $active, $staff, $student)";

        try {
            $db->query($query);
        } catch (PDOException $ex) {
            $err_msg = $ex->getMessage();
            echo($err_msg . "<BR>\n");
        }
    }

    // PEER id = 6
    //=========================================================================================================
    $id = 6;
    $query = "SELECT * FROM $dash_projects_setup WHERE id=$id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();

    if ($num_rows == 0) {
        $prefix = "peer_";
        $description = "Peer assessment";
        $title = "PEER";
        $subtitle = "Assessment of peers in group-work";
        $visible = 0;
        $active = 0;
        $staff = 0;
        $student = 0;
        $query = "INSERT INTO $dash_projects_setup (`id`, `prefix`, `description`, `title`, `subtitle`, `visible`, `active`, `staff`, `student`)
              VALUES ($id, '$prefix', '$description', '$title', '$subtitle', $visible, $active, $staff, $student)";

        try {
            $db->query($query);
        } catch (PDOException $ex) {
            $err_msg = $ex->getMessage();
            echo($err_msg . "<BR>\n");
        }
    }
}

/**
 * Create the global assessment criteria tables if they do not exist.
 *
 * These are the master criteria tables shared across modules as templates.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function create_assessment_criteria_tables($db)
{
    $query = <<<SQL
--
-- Table structure for table `assessment_criteria_flags`
--

CREATE TABLE IF NOT EXISTS `assessment_criteria_flags` (
  `id` int(11) NOT NULL,
  `visible_flag` int(11) DEFAULT '1',
  `class_flag` int(11) NOT NULL,
  `grade_text_flag` int(11) NOT NULL,
  `gpa_flag` int(11) NOT NULL,
  `descriptor_university_flag` int(11) NOT NULL,
  `descriptor_school_flag` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `assessment_criteria` (
  `mark` int(11) NOT NULL,
  `class` tinytext NOT NULL,
  `grade_text` tinytext NOT NULL,
  `gpa` float NOT NULL,
  `descriptor_university` text NOT NULL,
  `descriptor_school` text NOT NULL,
  PRIMARY KEY (`mark`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SQL;
    try {
        $db->query($query);
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

    // Empty these just in case
    try {
        $query = "TRUNCATE `assessment_criteria`";
        $db->query($query);
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }
    try {
        $query = "TRUNCATE `assessment_criteria_flags`";
        $db->query($query);
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

    populate_assessment_criteria_tables($db);
}

/**
 * Seed the assessment criteria tables with default category and question data.
 *
 * Only inserts rows if the tables are empty — safe to call on every setup run.
 *
 * @param PDO $db Active PDO connection.
 * @return void
 */
function populate_assessment_criteria_tables($db){

    $query = <<<SQL1
INSERT INTO `assessment_criteria_flags` (`id`, `visible_flag`, `class_flag`, `grade_text_flag`, `gpa_flag`, `descriptor_university_flag`, `descriptor_school_flag`) VALUES
(0, 1, 1, 1, 1, 1, 1);

SQL1;

    try {
        $db->query($query);
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

    $query = <<<SQL2
INSERT INTO `assessment_criteria` (`mark`, `class`, `grade_text`, `gpa`, `descriptor_university`, `descriptor_school`) VALUES
(0, 'Zero', 'F-', 0, 'Work of no merit OR absent.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(1, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(2, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(3, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(4, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(5, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(6, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(7, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(8, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(9, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(10, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(11, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(12, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(13, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(14, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(15, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(16, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(17, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(18, 'Low Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(19, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(20, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(21, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(22, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(23, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(24, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(25, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(26, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(27, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(28, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(29, 'Fail', 'F-', 0, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>No hint of understanding. Any knowledge shows major and fundamental misunderstandings. Could also be a trivial, superficial response to the wrong question.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used.</p>'),
(30, 'Fail', 'F', 0.5, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(31, 'Fail', 'F', 0.5, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(32, 'Fail', 'F', 0.5, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(33, 'Fail', 'F', 0.5, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(34, 'Fail', 'F', 0.5, 'Poor quality work well below the standards required for the appropriate stage of an honours degree.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(35, 'Marginal Fail', 'F+', 0.75, 'Work does not meet standards required for the appropriate stage of an honours degree. There may be evidence of some basic understanding of relevant concepts and techniques.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(36, 'Marginal Fail', 'F+', 0.75, 'Work does not meet standards required for the appropriate stage of an honours degree. There may be evidence of some basic understanding of relevant concepts and techniques.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(37, 'Marginal Fail', 'F+', 0.75, 'Work does not meet standards required for the appropriate stage of an honours degree. There may be evidence of some basic understanding of relevant concepts and techniques.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(38, 'Marginal Fail', 'D-', 1, 'Work does not meet standards required for the appropriate stage of an honours degree. There may be evidence of some basic understanding of relevant concepts and techniques.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(39, 'Marginal Fail', 'D-', 1, 'Work does not meet standards required for the appropriate stage of an honours degree. There may be evidence of some basic understanding of relevant concepts and techniques.', '<p><B>Coverage and Content</B><BR>Answer/project/dissertation lacking details, correct factual information and displaying little understanding of the subject.</P><p><B>Presentation Structure and Style</B><BR>Structure/presentation poor with unclear writing. Serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Few or no references given. Those that are given are not relevant and do not justify the conclusions and are incorrectly and inconsistently used. </p>'),
(40, 'Low 3rd', 'D', 1.5, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation drawn from lecture noted with little evidence of background reading. Some basic misunderstandings but goes some way to answering the scope of the question.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used.</p>'),
(41, 'Low 3rd', 'D', 1.5, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation drawn from lecture noted with little evidence of background reading. Some basic misunderstandings but goes some way to answering the scope of the question.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used.</p>'),
(42, 'Low 3rd', 'D', 1.5, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation drawn from lecture noted with little evidence of background reading. Some basic misunderstandings but goes some way to answering the scope of the question.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used.</p>'),
(43, 'Low 3rd', 'D+', 2, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation drawn from lecture noted with little evidence of background reading. Some basic misunderstandings but goes some way to answering the scope of the question.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used.</p>'),
(44, 'Mid 3rd', 'D+', 2, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation drawn from lecture noted with little evidence of background reading. Some basic misunderstandings but goes some way to answering the scope of the question.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used.</p>'),
(45, 'Mid 3rd', 'D+', 2, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation derived mainly from lecture course notes/material with marginal evidence of outside reading/research. Scope of answer/project/dissertation restricted. Evidence of good structure but lacking consistency in style.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used. </p>'),
(46, 'Mid 3rd', 'D+', 2, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation derived mainly from lecture course notes/material with marginal evidence of outside reading/research. Scope of answer/project/dissertation restricted. Evidence of good structure but lacking consistency in style.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used. </p>'),
(47, 'High 3rd', 'D+', 2, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation derived mainly from lecture course notes/material with marginal evidence of outside reading/research. Scope of answer/project/dissertation restricted. Evidence of good structure but lacking consistency in style.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used. </p>'),
(48, 'High 3rd', 'C-', 2.25, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>An answer/project/dissertation derived mainly from lecture course notes/material with marginal evidence of outside reading/research. Scope of answer/project/dissertation restricted. Evidence of good structure but lacking consistency in style.</P><p><B>Presentation Structure and Style</B><BR>Largely neat and accurate presentation but with grammatical and spelling errors.</P><p><B>Referencing</B><BR>A few references given that justify the conclusions, but inconsistently used. </p>'),
(49, 'High 3rd', 'C-', 2.25, 'Work of limited quality, demonstrating some relevant knowledge and understanding.', '<p><B>Coverage and Content</B><BR>A completely satisfactory answer/project/dissertation with good knowledge of course work and some knowledge of relevant literature.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(50, 'Low 2:ii', 'C', 2.5, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A completely satisfactory answer/project/dissertation with good knowledge of course work and some knowledge of relevant literature.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(51, 'Low 2:ii', 'C', 2.5, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A completely satisfactory answer/project/dissertation with good knowledge of course work and some knowledge of relevant literature.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(52, 'Low 2:ii', 'C', 2.5, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A completely satisfactory answer/project/dissertation with good knowledge of course work and some knowledge of relevant literature.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(53, 'Low 2:ii', 'C', 2.5, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A completely satisfactory answer/project/dissertation with good knowledge of course work and some knowledge of relevant literature.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(54, 'Mid 2:ii', 'C+', 2.75, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good answer/project/dissertation with relevant reference to course material and lectures with some evidence of incorporation of ideas from literature. Mostly accurate and logical factual statements/design-decision though overall synthesis.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(55, 'Mid 2:ii', 'C+', 2.75, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good answer/project/dissertation with relevant reference to course material and lectures with some evidence of incorporation of ideas from literature. Mostly accurate and logical factual statements/design-decision though overall synthesis.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(56, 'Mid 2:ii', 'C+', 2.75, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good answer/project/dissertation with relevant reference to course material and lectures with some evidence of incorporation of ideas from literature. Mostly accurate and logical factual statements/design-decision though overall synthesis.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(57, 'High 2:ii', 'B-', 3, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good answer/project/dissertation with relevant reference to course material and lectures with some evidence of incorporation of ideas from literature. Mostly accurate and logical factual statements/design-decision though overall synthesis.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(58, 'High 2:ii', 'B-', 3, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good answer/project/dissertation with relevant reference to course material and lectures with some evidence of incorporation of ideas from literature. Mostly accurate and logical factual statements/design-decision though overall synthesis.</P><p><B>Presentation Structure and Style</B><BR>Good presentation with a clear and competent writing style including few serious grammatical and spelling errors.</P><p><B>Referencing</B><BR>Some references given that justify the conclusions, but with omissions. References given justify the conclusions and are largely correctly and consistently used. </p>'),
(59, 'High 2:ii', 'B-', 3, 'Competent work, demonstrating reasonable knowledge and understanding, some analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good, comprehensive answer/project/dissertation but probably notable for comprehensive content rather than level of originality/complete understanding. Evidence of a fair degree of outside reading.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(60, 'Low 2:i', 'B-', 3, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good, comprehensive answer/project/dissertation but probably notable for comprehensive content rather than level of originality/complete understanding. Evidence of a fair degree of outside reading.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(61, 'Low 2:i', 'B', 3.25, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good, comprehensive answer/project/dissertation but probably notable for comprehensive content rather than level of originality/complete understanding. Evidence of a fair degree of outside reading.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(62, 'Low 2:i', 'B', 3.25, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good, comprehensive answer/project/dissertation but probably notable for comprehensive content rather than level of originality/complete understanding. Evidence of a fair degree of outside reading.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(63, 'Low 2:i', 'B', 3.25, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A good, comprehensive answer/project/dissertation but probably notable for comprehensive content rather than level of originality/complete understanding. Evidence of a fair degree of outside reading.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(64, 'Mid 2:i', 'B+', 3.5, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A very good answer/project/dissertation. Broad knowledge with good incorporation of literature and evidence of though/analysis beyond the course lectures.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(65, 'Mid 2:i', 'B+', 3.5, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A very good answer/project/dissertation. Broad knowledge with good incorporation of literature and evidence of though/analysis beyond the course lectures.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(66, 'Mid 2:i', 'B+', 3.5, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A very good answer/project/dissertation. Broad knowledge with good incorporation of literature and evidence of though/analysis beyond the course lectures.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(67, 'High 2:i', 'A-', 3.75, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A very good answer/project/dissertation. Broad knowledge with good incorporation of literature and evidence of though/analysis beyond the course lectures.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(68, 'High 2:i', 'A-', 3.75, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A very good answer/project/dissertation. Broad knowledge with good incorporation of literature and evidence of though/analysis beyond the course lectures.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(69, 'High 2:i', 'A-', 3.75, 'High quality work demonstrating good knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>A very good answer/project/dissertation. Broad knowledge with good incorporation of literature and evidence of though/analysis beyond the course lectures.</P><p><B>Presentation Structure and Style</B><BR>Very good presentation with excellent communication-graphic skills/media. Accurate writing style with minimal grammatical or spelling errors.</P><p><B>Referencing</B><BR>A good list of relevant references, but perhaps with some omissions. References given justify the conclusions and are correctly and consistently used. </p>'),
(70, 'Low 1st', 'A-', 3.75, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An extremely good answer/project/dissertation showing insight and critical ability within the subject and demonstrating evidence of a wide range of background reading.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work.</p>'),
(71, 'Low 1st', 'A', 4, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An extremely good answer/project/dissertation showing insight and critical ability within the subject and demonstrating evidence of a wide range of background reading.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work.</p>'),
(72, 'Low 1st', 'A', 4, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An extremely good answer/project/dissertation showing insight and critical ability within the subject and demonstrating evidence of a wide range of background reading.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work.</p>');
INSERT INTO `assessment_criteria` (`mark`, `class`, `grade_text`, `gpa`, `descriptor_university`, `descriptor_school`) VALUES
(73, 'Low 1st', 'A', 4, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An extremely good answer/project/dissertation showing insight and critical ability within the subject and demonstrating evidence of a wide range of background reading.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work.</p>'),
(74, 'Low 1st', 'A', 4, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An extremely good answer/project/dissertation showing insight and critical ability within the subject and demonstrating evidence of a wide range of background reading.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work.</p>'),
(75, 'Low 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An excellent answer/project/dissertation, well argued showing critical insight and critical ability and demonstrating evidence of substantial background reading, along with originality of new ideas.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(76, 'Low 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An excellent answer/project/dissertation, well argued showing critical insight and critical ability and demonstrating evidence of substantial background reading, along with originality of new ideas.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(77, 'Low 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An excellent answer/project/dissertation, well argued showing critical insight and critical ability and demonstrating evidence of substantial background reading, along with originality of new ideas.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(78, 'Mid 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An excellent answer/project/dissertation, well argued showing critical insight and critical ability and demonstrating evidence of substantial background reading, along with originality of new ideas.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(79, 'Mid 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An excellent answer/project/dissertation, well argued showing critical insight and critical ability and demonstrating evidence of substantial background reading, along with originality of new ideas.</P><p><B>Presentation Structure and Style</B><BR>Excellent presentation/professional communication-graphic skills. Fluent and sophisticated writing, with only very minor grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(80, 'Mid 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An outstanding answer/project/dissertation with evidence of extensive individual research, knowledge, insight and originality. Presented in a logical and well-argued manner and demonstrating extensive background reading, understanding and synthesis of subject.</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(81, 'Mid 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An outstanding answer/project/dissertation with evidence of extensive individual research, knowledge, insight and originality. Presented in a logical and well-argued manner and demonstrating extensive background reading, understanding and synthesis of subject.</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(82, 'Mid 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An outstanding answer/project/dissertation with evidence of extensive individual research, knowledge, insight and originality. Presented in a logical and well-argued manner and demonstrating extensive background reading, understanding and synthesis of subject.</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(83, 'Mid 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An outstanding answer/project/dissertation with evidence of extensive individual research, knowledge, insight and originality. Presented in a logical and well-argued manner and demonstrating extensive background reading, understanding and synthesis of subject.</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(84, 'Mid 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An outstanding answer/project/dissertation with evidence of extensive individual research, knowledge, insight and originality. Presented in a logical and well-argued manner and demonstrating extensive background reading, understanding and synthesis of subject.</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(85, 'High 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(86, 'High 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(87, 'High 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(88, 'High 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(89, 'High 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(90, 'High 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(91, 'High 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(92, 'High 1st', 'A+', 4.25, 'Very high quality work demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(93, 'Excellent 1st', 'A+', 4.25, 'Exceptional work of the highest quality, demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(94, 'Excellent 1st', 'A+', 4.25, 'Exceptional work of the highest quality, demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(95, 'Excellent 1st', 'A+', 4.25, 'Exceptional work of the highest quality, demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(96, 'Excellent 1st', 'A+', 4.25, 'Exceptional work of the highest quality, demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(97, 'Excellent 1st', 'A+', 4.25, 'Exceptional work of the highest quality, demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(98, 'Excellent 1st', 'A+', 4.25, 'Exceptional work of the highest quality, demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(99, 'Excellent 1st', 'A+', 4.25, 'Exceptional work of the highest quality, demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>'),
(100, 'Excellent 1st', 'A+', 4.25, 'Exceptional work of the highest quality, demonstrating excellent knowledge and understanding, analysis, organisation, accuracy, relevance, presentation and appropriate skills.', '<p><B>Coverage and Content</B><BR>An inspired answer/project/dissertation which goes beyond the expectation of students\' abilities. Presented in a state-of the art manner and demonstrating exceptional talent, extensive background reading, understanding and synthesis of subject</P><p><B>Presentation Structure and Style</B><BR>Outstanding presentation, professional communication skills/media and evocative expression of ideas. No grammatical or spelling errors.</P><p><B>Referencing</B><BR>A complete list of relevant references, which justify the conclusions and are correctly and consistently used in the piece of work. </p>');

SQL2;

    try {
        $db->query($query);
    } catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }
}

/**
 * Create a feedback records table for tracking which students have received feedback emails.
 *
 * @param PDO    $db                         Active PDO connection.
 * @param string $feedback_records_table_name Table name to create.
 * @return void
 */
function create_feedback_records_table_structure($db, $feedback_records_table_name){

    $query = <<<SQL
    --
    -- Table structure for table `feedback records`
    --

    CREATE TABLE IF NOT EXISTS `$feedback_records_table_name` (
  `sid` varchar(10) NOT NULL,
  `sent_p1` int(11) DEFAULT 0,
  `sent_p2` int(11) DEFAULT 0,
  `sent_p3` int(11) DEFAULT 0,
  `sent_p4` int(11) DEFAULT 0,
  `sent_p5` int(11) DEFAULT 0,
  `sent_final` int(11) DEFAULT 0,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }

}

/**
 * Create a project areas table for categorising project proposals by research area.
 *
 * @param PDO    $db                    Active PDO connection.
 * @param string $project_area_table_name Table name to create.
 * @return void
 */
function create_project_areas_table_structure($db, $project_area_table_name){

    $query = <<<SQL
    --
    -- Table structure for table `project_areas`
    --

    CREATE TABLE IF NOT EXISTS $project_area_table_name (
        `id` int(11) NOT NULL AUTO_INCREMENT,
      `area` char(255),
      `description` char(255),
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

SQL;
    try{
        $db->query($query);
    }catch (PDOException $ex) {
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
    }
}

/**
 * Return true if a column exists in a given table.
 *
 * Note: parameter order is ($column, $table) — the reverse of DoesColumnExistInTable()
 * in markstore_functions.php. Both do the same thing.
 *
 * @param PDO    $db     Active PDO connection.
 * @param string $column Column name.
 * @param string $table  Table name.
 * @return bool
 */
function doesColumnExists($db, $column, $table) {
    try {

        if (empty ($table) || empty ($column)) {
            return false;
        }
        $stmt = $db->prepare("SHOW COLUMNS FROM $table LIKE :column");
        $stmt->execute(array("column" => $column));
        $row = $stmt->fetchColumn();
        if ($row != false) {
            return true;
        } else {
            return false;
        }

    } catch (PDOException $e) {
        return false;
    }
}