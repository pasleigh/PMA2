<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 28/09/2015
 * Time: 08:51
 */


class CSVparse
{
    var $mappings = array();

    function parse_file($filename)
    {
        $id = fopen($filename, "r"); //open the file
        //echo("Opening $filename ($id)<BR>");
        $data = fgetcsv($id, filesize($filename)); /*This will get us the */
        /*main column names */
        //print_r3("csv data",$data);
        //echo("read $filename<BR>");

        if(!$this->mappings){
            $this->mappings = $data;
        }
        //echo("CSV filename : $filename<BR>\n");
        while($data = fgetcsv($id, filesize($filename)))
        {
            //print_r3("csv data",$data);
            if($data[0])
            {
                //echo("About to do data foreach<BR>\n");
                foreach($data as $key => $value) {
                    $converted_data[$this->mappings[$key]] = addslashes($value);
                }
                $table[] = $converted_data; /* put each line into */
            }                                 /* its own entry in    */
        }                                     /* the $table array    */
        fclose($id); //close file
        return $table;
    }
}

########################################################################################################

if (!function_exists('json_encode'))
{
    function json_encode($a=false)
    {
        if (is_null($a)) return 'null';
        if ($a === false) return 'false';
        if ($a === true) return 'true';
        if (is_scalar($a))
        {
            if (is_float($a))
            {
                // Always use "." for floats.
                return floatval(str_replace(",", ".", strval($a)));
            }

            if (is_string($a))
            {
                static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
                return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
            }
            else
                return $a;
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a))
        {
            if (key($a) !== $i)
            {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList)
        {
            foreach ($a as $v) $result[] = json_encode($v);
            return '[' . join(',', $result) . ']';
        }
        else
        {
            foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
            return '{' . join(',', $result) . '}';
        }
    }
}


function require_safe($filename) {
    //$path = realpath(getcwd() . $filename);
    //$path && return require($path);
    //trigger_error("Could not find file {$path}", E_USER_ERROR);
    if (file_exists("must_have.php")) {
        require "$filename";
    }
    else {
        echo "could not include $filename...<BR>\n";
    }
}


/*
* A mathematical decimal difference between two informed dates
*
* Author: Sergio Abreu
* Website: http://sites.sitesbr.net
*
* Features:
* Automatic conversion on dates informed as string.
* Possibility of absolute values (always +) or relative (-/+)
*/

function s_datediff( $str_interval, $dt_menor, $dt_maior, $relative=false){

    if( is_string( $dt_menor)) $dt_menor = date_create( $dt_menor);
    if( is_string( $dt_maior)) $dt_maior = date_create( $dt_maior);

    $diff = date_diff( $dt_menor, $dt_maior, ! $relative);

    switch( $str_interval){
        case "y":
            $total = $diff->y + $diff->m / 12 + $diff->d / 365.25; break;
        case "m":
            $total= $diff->y * 12 + $diff->m + $diff->d/30 + $diff->h / 24;
            break;
        case "d":
            $total = $diff->y * 365.25 + $diff->m * 30 + $diff->d + $diff->h/24 + $diff->i / 60;
            break;
        case "h":
            $total = ($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h + $diff->i/60;
            break;
        case "i": //minute
            $total = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i + $diff->s/60;
            break;
        case "s":
            $total = ((($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i)*60 + $diff->s;
            break;
    }
    if( $diff->invert)
        return -1 * $total;
    else
        return $total;
}

/* Enjoy and feedback me ;-) */

//This is a very simple function to calculate the difference between two datetime values, returning the result in seconds. To convert to minutes, just divide the result by 60. In hours, by 3600 and so on.

function second_diff($dt1,$dt2){
    //$dt1 = date_time value
    //$dt1 = date_time value
    // Added inval to all funcation 10/03/2023
    $m1 = intval(substr($dt1,5,2));
    $d1 = intval(substr($dt1,8,2));
    $h1 = intval(substr($dt1,11,2));
    $y1 = intval(substr($dt1,0,4));
    $i1 = intval(substr($dt1,14,2));
    $s1 = intval(substr($dt1,17,2));

    $y2 = intval(substr($dt2,0,4));
    $m2 = intval(substr($dt2,5,2));
    $d2 = intval(substr($dt2,8,2));
    $h2 = intval(substr($dt2,11,2));
    $i2 = intval(substr($dt2,14,2));
    $s2 = intval(substr($dt2,17,2));

    $r1=date('U',mktime($h1,$i1,$s1,$m1,$d1,$y1));
    $r2=date('U',mktime($h2,$i2,$s2,$m2,$d2,$y2));

    // Returns diff in seconds
    return ($r1-$r2);

}

function minute_diff($dt1, $dt2){
    $diff = second_diff($dt1,$dt2);
    return $diff/60;
}

function hour_diff($dt1, $dt2){
    $diff = second_diff($dt1,$dt2);
    return $diff/(60*60);
}

function day_diff($dt1, $dt2){
    $diff = second_diff($dt1,$dt2);
    return $diff/(60*60*24);
}

function secondsToTime($seconds) {
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
    return $dtF->diff($dtT)->format('%a days, %h hours, %i mins and %s secs');
}

?>