<?php
function CreateProjectMasterNameTableQuery($master_name_table_name)
{
    //echo("$master_name_table_name<BR>");
    //$str = "`$master_name_table_name`<BR>";
    //echo("$str<BR>");

    $create_table_query = "CREATE TABLE `$master_name_table_name` (
              `id` int(11) NOT NULL auto_increment,
              `date_added` datetime default NULL,
              `date_modified` datetime default NULL,
              `f_title` varchar(150) default NULL,
              `f_supervisor` varchar(75) default NULL,
              `f_supervisor_id` int(11) default NULL,
              PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=373 ;

            ";

    return $create_table_query;

}

function CreateProjectDetailsTableQuery($project_details_table_name)
{
    //echo("$project_details_table_name<BR>");
    //$str = "`$project_details_table_name`<BR>";
    //echo("$str<BR>");

    $create_table_query = "CREATE TABLE `$project_details_table_name` (
            `id` int(11) NOT NULL auto_increment,
              `master_id` int(11) NOT NULL default '0',
              `date_added` datetime default NULL,
              `date_modified` datetime default NULL,
              `f_assocstaff` varchar(75) default NULL,
              `f_assocstaff_id` int(11) default NULL,
              `f_outline` text,
              `f_relmods` text,
              `f_backread` text,
              `f_relsites` text,
              `f_skills` text,
              `f_software` text,
              `f_softsupport` text,
              `f_labs` text,
              `f_works` text,
              `f_programmes` text,
              `f_progs` text,
              `f_UGprojects` int(11) default NULL,
              `f_PGprojects` int(11) default NULL,
              `f_furtherinfo` text,
              PRIMARY KEY  (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=373 ;
            ";

    return $create_table_query;

}
?>