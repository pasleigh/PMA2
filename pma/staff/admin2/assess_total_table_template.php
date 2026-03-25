<?php
function CreateAccessTotalTableQuery($assess_total_table_name)
{
  //$str = "`$assess_total_table_name`<BR>";
  $create_table_query = "CREATE TABLE IF NOT EXISTS `$assess_total_table_name` (
      `SID` char(10) default NULL,
      `retmark1` float default NULL,
      `third_marker_req1` int(11) default 0,
      `mark_method1` char(4) default NULL,
      `mark_reason1` text,
      `retmark2` float default NULL,
      `third_marker_req2` int(11) default 0,
      `mark_method2` char(4) default NULL,
      `mark_reason2` text,
      `retmark3` float default NULL,
      `third_marker_req3` int(11) default 0,
      `mark_method3` char(4) default NULL,
      `mark_reason3` text,
      `retmark4` float default NULL,
      `third_marker_req4` int(11) default 0,
      `mark_method4` char(4) default NULL,
      `mark_reason4` text,
      `retmark5` float default NULL,
      `third_marker_req5` int(11) default 0,
      `mark_method5` char(4) default NULL,
      `mark_reason5` text,
      `total` float default NULL,
      `locked` int(11) default NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

  return $create_table_query;
}
?>
