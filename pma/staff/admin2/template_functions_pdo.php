<?PHP
function GetQsTemplatesSelect($db, $NameKey, $MatchID){
   
   $template_select_block = "\n<select name=\"$NameKey\">\n"; 
   $template_select_block .= "<option value=\"-1\">--- Select question template ---</option>\n";
   
   $template_table_search ="%assessqs_template_%";
   $query = "SHOW TABLES LIKE '$template_table_search'";

   //$get_template_list_res = mysql_query($get_template_list) or die(" Unable to create template table list from database. <BR>Query: $get_template_list");
    try{
        $setup = $db->query($query);
        $results = $setup->fetchAll();
    } catch (PDOException $ex) {
        echo "An Error occured accessing the templates tables<BR> Query = $query<BR>\n";
        $err_msg = $ex->getMessage();
        exit();
    }
    foreach ($results as $row ) {
       $template_table_name = $row[0];
       
       //if (strpos($template_table_name,'_cats') !== false) {
       //  echo 'true';
       //}
       if ( $template_table_name == $MatchID ) {
         $template_select_block .= "<option value=\"$template_table_name\" selected=\"selected\">$template_table_name</option>\n";
       }else{
         $template_select_block .= "<option value=\"$template_table_name\">$template_table_name</option>\n";
       }
   }
   $template_select_block .= "</select>\n";
   
   return $template_select_block ;
}

function GetStaffNameTemplatesSelect($db, $NameKey, $MatchID){
   
   $template_select_block = "\n<select name=\"$NameKey\">\n"; 
   //$template_select_block .= "<option value=\"-1\">--- Select question template ---</option>\n";
   
   $template_table_search ="%staff_names%";
   $query = "SHOW TABLES LIKE '$template_table_search'";

    try{
        $setup = $db->query($query);
        $results = $setup->fetchAll();
    } catch (PDOException $ex) {
        echo "An Error occured accessing the staff name templates tables<BR> Query = $query<BR>\n";
        $err_msg = $ex->getMessage();
        exit();
    }
    foreach ($results as $row ) {
       $template_table_name = $row[0];
       
       //if (strpos($template_table_name,'_cats') !== false) {
       //  echo 'true';
       //}
       if ( $template_table_name == $MatchID ) {
         $template_select_block .= "<option value=\"$template_table_name\" selected=\"selected\">$template_table_name</option>\n";
       }else{
         $template_select_block .= "<option value=\"$template_table_name\">$template_table_name</option>\n";
       }
   }
   $template_select_block .= "</select>\n";
   
   return $template_select_block ;
}
?>
