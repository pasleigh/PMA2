<?php
/**
 * PDO prepared-query helpers.
 *
 * All three functions build and execute parameterised queries from associative
 * arrays so that callers never construct SQL strings with raw user data.
 * On success they return the executed PDOStatement; on failure they echo the
 * error and return the error-message string.
 */

/**
 * Execute a prepared UPDATE statement.
 *
 * Builds: UPDATE $table SET col1=:col1, ... WHERE wcol1=:wcol1 AND ...
 *
 * @param PDO    $db         Active PDO connection.
 * @param string $table      Table name (not user-supplied).
 * @param array  $data       Associative array of column => value pairs to SET.
 * @param array  $where_data Associative array of column => value pairs for WHERE (ANDed).
 * @return PDOStatement|string Executed statement on success, error message string on failure.
 */
function perform_update_prepared_query($db, $table, $data, $where_data){
    // $data is an associative array dbcolumn names and the data
    // [ 'my_col_name' => my_data ];
    // $where_data is the same for the WHERE clause. concatanated as AND
    /*e.g. Data abnd where_data arrays
     * $data =array(
                $CXtotal => $marktot,
                $Ctotal => $mark_total_all_cats,
                $CXFeedback => $feedback_text
            );
       $where_data =array(
                'sid' => $sid
            );
     */
    $setStr = "";
    foreach( $data as $col_name => $col_value)
    {
        $setStr .= "`$col_name` = :$col_name,";
    }
    $setStr = rtrim($setStr, ",");

    $all_data = $data;
    $where_keys = array_keys($where_data);
    $setWhereStr = "";
    for($i = 0; $i < sizeof($where_keys); $i++)
    {
        $col_name = $where_keys[$i];
        $col_value = $where_data[$col_name];
        if($i < sizeof($where_keys) - 1){
            $setWhereStr .= "`$col_name` = :$col_name AND ";
        }else{
            // The last one , none AND
            $setWhereStr .= "`$col_name` = :$col_name ";
        }
        $all_data[$col_name] = $col_value;
    }

    $query = "UPDATE $table SET $setStr WHERE  $setWhereStr";

    try{
        $results = $db->prepare($query);
        $results->execute($all_data);
    }catch (PDOException $ex) {
        echo("Unable to perform query: $query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
        $results = $err_msg;
    }
    return $results;
}

/**
 * Execute a prepared INSERT statement.
 *
 * Builds: INSERT $table SET col1=:col1, col2=:col2, ...
 *
 * @param PDO    $db    Active PDO connection.
 * @param string $table Table name (not user-supplied).
 * @param array  $data  Associative array of column => value pairs to INSERT.
 * @return PDOStatement|string Executed statement on success, error message string on failure.
 */
function perform_insert_prepared_query($db, $table, $data){
    // $data is an associative array dbcolumn names and the data
    // [ 'my_col_name' => my_data ];
    // e.g. Data array:
    /*
     $data =array(
                $CXtotal => $marktot,
                $Ctotal => $mark_total_all_cats,
                $CXFeedback => $feedback_text,
                'sid' => $sid
            );
     */

    $setStr = "";
    foreach( $data as $col_name => $col_value)
    {
        $setStr .= "`$col_name` = :$col_name,";
    }
    $setStr = rtrim($setStr, ",");

    $query = "INSERT $table SET $setStr";

    try{
        $results = $db->prepare($query);
        $results->execute($data);
    }catch (PDOException $ex) {
        echo("Unable to perform query: $query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
        $results = $err_msg;
    }
    return $results;
}

function perform_select_prepared_query($db, $table, $where_data, $extra){
    // $where_data is an associative array dbcolumn names and the data
    // [ 'my_col_name' => my_data ];
    // concatanated as AND
    // $extra is something tagged on the end , e..g ORDER BY , LIMIT etc

    $where_keys = array_keys($where_data);
    $setWhereStr = "";
    for($i = 0; $i < sizeof($where_keys); $i++)
    {
        $col_name = $where_keys[$i];
        $col_value = $where_data[$col_name];
        if($i < sizeof($where_keys) - 1){
            $setWhereStr .= "`$col_name` = :$col_name AND ";
        }else{
            // The last one , none AND
            $setWhereStr .= "`$col_name` = :$col_name ";
        }
        $all_data[$col_name] = $col_value;
    }

    $query = "SELECT * FROM $table WHERE $setWhereStr $extra";

    try{
        $results = $db->prepare($query);
        $results->execute($all_data);
    }catch (PDOException $ex) {
        echo("Unable to perform query: $query <BR>\n");
        $err_msg = $ex->getMessage();
        echo($err_msg . "<BR>\n");
        $results = $err_msg;
    }
    return $results;
}

