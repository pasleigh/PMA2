<?php

$zipname = $_POST['zip_filename'];
$saveas_zipname = $_POST['saveas_zip_filename'];
///Then download the zipped file.
echo("File to download: $zipname<BR>");
echo("Filename to saveas: $saveas_zipname<BR>");

header('Pragma: public');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Cache-Control: private', false);
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename=' . $saveas_zipname);
//header("Content-Transfer-Encoding: binary");
header('Content-Length: ' . filesize($zipname));
readfile("$zipname");
