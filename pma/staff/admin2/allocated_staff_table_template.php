<?php
function CreateAllocatedStaffTableQuery($allocated_staff_table_name)
{
  //$str = "`$allocated_staff_table_name`<BR>";

 $create_table_query = "CREATE TABLE `$allocated_staff_table_name` (
                        `staffid` int(11) NOT NULL,
                        `firstname` varchar(25) default NULL,
                        `familyname` varchar(50) default NULL,
                        `fullname` varchar(75) default NULL,
                        `num_supervise` int(11) default '0',
                        `num_second_mark` int(11) default '0',
                        `use` int(11) default '1',
                        PRIMARY KEY  (`staffid`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
  return $create_table_query;
}
?>