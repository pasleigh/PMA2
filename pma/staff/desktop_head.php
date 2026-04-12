<?php
/*
function relativePath($from, $to, $ps = DIRECTORY_SEPARATOR)
{
  // Replace and windows backslash separators
  $from = str_replace("\\","/",$from);
  $to = str_replace("\\","/",$to);
  $arFrom = explode($ps, rtrim($from, $ps));
  $arTo = explode($ps, rtrim($to, $ps));
  while (count($arFrom) && count($arTo) && ($arFrom[0] == $arTo[0])) {
    array_shift($arFrom);
    array_shift($arTo);
  }
  return str_pad("", count($arFrom) * 3, '..' . $ps) . implode($ps, $arTo);
}

// This is added as it CONTEXT_DOCUMENT_ROOT is not defiend on my office PC
if(!isset($_SERVER['CONTEXT_DOCUMENT_ROOT'])){
  if(!isset($_SERVER['DOCUMENT_ROOT'])){
    echo("Error: Cannot set the document root on this system.<BR>\n");
  }else{
    $doc_root = $_SERVER['DOCUMENT_ROOT'];
  }
}else{
  $doc_root = $_SERVER['CONTEXT_DOCUMENT_ROOT'];
}

$relative_new = dirname(relativePath($doc_root, __FILE__, "/"));
$thisDIR_URL = "http://" . $_SERVER['HTTP_HOST'] . "/" . $relative_new;
$thisDIR_URL = "https://" .__BASE_URL__ . "/staff";

if (array_key_exists('BASE_URL', $_SESSION)) {
  $sessionBASE_URL = $_SESSION['BASE_URL'];
  echo("SESSION BASE_URL: " . $sessionBASE_URL . "<BR>\n");
  $thisDIR_URL = "http://" . $sessionBASE_URL . "/staff";
}
*/
$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
$location = '.';
$staff_location = '..';
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo(($school_title_h ?? '') . ' - ' . ($running_title_h ?? '')); ?></title>
    <?php if (function_exists('generate_csrf_token')): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <!--
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/images/favicon.ico"/>
    <link rel="icon" href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/images/projectsicon72.png"/>

    <link href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/css/pagedesc.css" type="text/css" rel="stylesheet"/>
-->
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$staff_location") ?>/staff/images/favicon.ico"/>
    <link rel="icon" href="<?php echo("$staff_location") ?>/staff/images/projectsicon72.png"/>

    <link href="<?php echo("$staff_location") ?>/staff/css/pagedesc.css" type="text/css" rel="stylesheet"/>
    <!--
    <link href="https://cdn.datatables.net/1.10.3/css/jquery.dataTables.css" type="text/css" rel="stylesheet" />
    <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
    <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.3/js/jquery.dataTables.min.js"></script>
-->

    <link rel="stylesheet" type="text/css" href="<?php echo($location)?>/frameworks/DataTables-1.10.16/css/jquery.dataTables.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo($location)?>/frameworks/jqueryui/1.12.1/jquery-ui.css"/>
    <script type="text/javascript" src="<?php echo($location)?>/frameworks/jQuery-3.2.1/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="<?php echo($location)?>/frameworks/jqueryui/1.12.1/jquery-ui.js"></script>
    <script type="text/javascript" src="<?php echo($location)?>/frameworks/DataTables-1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="<?php echo($location)?>/frameworks/DataTables-1.10.16/js/dataTables.bootstrap.min.js"></script>

    <script>
    /* CSRF auto-injection */
    (function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (!meta) return;
        var token = meta.getAttribute('content');
        function injectForms() {
            document.querySelectorAll('form').forEach(function (form) {
                if (!form.querySelector('input[name="csrf_token"]')) {
                    var inp = document.createElement('input');
                    inp.type = 'hidden'; inp.name = 'csrf_token'; inp.value = token;
                    form.appendChild(inp);
                }
            });
            if (typeof jQuery !== 'undefined') { jQuery.ajaxSetup({ headers: { 'X-CSRF-Token': token } }); }
        }
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', injectForms); } else { injectForms(); }
    })();
    </script>

</head>
