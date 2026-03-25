<?php
function CreateAllocatedTableQuery($allocated_table_name)
{
  //$str = "`$allocated_table_name`<BR>";

 $create_table_query = "CREATE TABLE `$allocated_table_name` (
                        `projectid` int(11) NOT NULL auto_increment,
                        `sid` varchar(10) default NULL,
                        `masterid` int(11) default NULL,
                        `title` varchar(150) default NULL,
                        `supervisor_id` int(11) DEFAULT NULL,
                        `second_marker_id` int(11) DEFAULT NULL,
                        `marker_p1m1_id` int(11) DEFAULT NULL,
                        `marker_p1m2_id` int(11) DEFAULT NULL,
                        `marker_p1m3_id` int(11) DEFAULT NULL,
                        `marker_p2m1_id` int(11) DEFAULT NULL,
                        `marker_p2m2_id` int(11) DEFAULT NULL,
                        `marker_p2m3_id` int(11) DEFAULT NULL,
                        `marker_p3m1_id` int(11) DEFAULT NULL,
                        `marker_p3m2_id` int(11) DEFAULT NULL,
                        `marker_p3m3_id` int(11) DEFAULT NULL,
                        `marker_p4m1_id` int(11) DEFAULT NULL,
                        `marker_p4m2_id` int(11) DEFAULT NULL,
                        `marker_p4m3_id` int(11) DEFAULT NULL,
                        `marker_p5m1_id` int(11) DEFAULT NULL,
                        `marker_p5m2_id` int(11) DEFAULT NULL,
                        `marker_p5m3_id` int(11) DEFAULT NULL,
                        `inactive` int(1) default '0',
                        PRIMARY KEY  (`projectid`)
                      ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=101 ;";

  return $create_table_query;
}
?>