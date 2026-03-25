<?php
function CreateStudentsTableQuery($student_table_name)
{
  //$str = "`$student_table_name`<BR>";

 $create_table_query = "CREATE TABLE IF NOT EXISTS `$student_table_name` (
  `sid` char(10) NOT NULL UNIQUE,
  `name` char(255) DEFAULT NULL,
  `username` char(10) NOT NULL UNIQUE,
  `programme` char(64) DEFAULT NULL,
  `sel` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

  return $create_table_query;
}

function CreateStudentsProgrammesTableQuery($students_programmes_table_name)
{
 //$str = "$students_programmes_table_name<BR>";

 $create_table_query =
     "CREATE TABLE `$students_programmes_table_name` (
      `id` INT NOT NULL AUTO_INCREMENT ,
      `prog_full_name` VARCHAR(256) NOT NULL ,
      `prog_short_name_index` INT NOT NULL DEFAULT '-1' ,
      PRIMARY KEY (`id`), UNIQUE (`prog_full_name`)
     ) ENGINE = InnoDB;";

  return $create_table_query;
}

function CreateStudentsSelectionTableQuery($student_selection_table_name)
{
 //$str = "`$student_selection_table_name`<BR>";

 $create_table_query =
     "CREATE TABLE `$student_selection_table_name` (
      `sel_date` datetime default NULL,
      `sid` char(10) NOT NULL UNIQUE,
      `username` char(10) default NULL,
      `name` char(255) default NULL,
      `choice1` int(11) default NULL,
      `choice2` int(11) default NULL,
      `choice3` int(11) default NULL,
      `choice4` int(11) default NULL,
      `choice5` int(11) default NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

 return $create_table_query;
}
?>