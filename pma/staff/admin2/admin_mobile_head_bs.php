<?php
$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
$location = "..";
$gear_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
  <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
  <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
</svg>';
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php echo(($school_title_h ?? '') . ' - ' . ($running_title_h ?? '')); ?></title>
    <?php if (function_exists('generate_csrf_token')): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
<!--
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/images/favicon.ico"/>
    <link href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/css/pagedesc.css" type="text/css" rel="stylesheet"/>
-->
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$location") ?>/images/favicon.ico"/>
    <link href="<?php echo("$location") ?>/css/pagedesc.css" type="text/css" rel="stylesheet"/>

    <!--<link rel="stylesheet" type="text/css" href="./frameworks/Bootstrap-3.3.7/css/bootstrap.min.css"/>-->
    <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">-->
    <link href="<?php echo("$location") ?>/frameworks/bootstrap-5.2.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/DataTables-1.10.16/css/dataTables.bootstrap.min.css"/>

    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/DataTables-1.10.16/css/jquery.dataTables.css" />

    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/jQuery-3.2.1/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/popper.min.js"></script>
    <!--<script type="text/javascript" src="./frameworks/Bootstrap-3.3.7/js/bootstrap.min.js"></script>-->
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>-->
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/bootstrap-5.2.3-dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/DataTables-1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/DataTables-1.10.16/js/dataTables.bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" rel="stylesheet">
    <!--
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.16/css/dataTables.bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.3/css/jquery.dataTables.css" type="text/css" rel="stylesheet" />

    <script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.16/js/dataTables.bootstrap.min.js"></script>
-->
    <script type="text/javascript" charset="utf8" src="<?php echo("$location") ?>/frameworks/datatables-plugins/1.10.16/date-uk.js"></script>
    <!--
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/plug-ins/1.10.16/sorting/date-uk.js"></script>
-->
    <style>
        /* This causes the bootstrap button to wrap text and thus fit in the width*/
        .btn{
            white-space:normal !important;
            max-width:400px;
            margin-bottom:4px;
            word-wrap:break-word;"
        }

    </style>

    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/jqueryui/1.12.1/jquery-ui.css"/>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/jqueryui/1.12.1/jquery-ui.js"></script>

    <!--
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.css"/>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.js"></script>
-->
    <!-- Custom styles for this template -->
   <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">


    <script src="<?php echo("$location") ?>/frameworks/bowser.min.js"></script>

    <script src="<?php echo("$location") ?>/frameworks/js.cookie.js"></script>

    <!--
    <link href="css/jquery.bootstrap-touchspin.min.css" rel="stylesheet" type="text/css" media="all">
    <script src="js/jquery.bootstrap-touchspin.min.js"></script>
-->
    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/bootstrap-plugins/jquery.bootstrap-touchspin.min.css"/>
    <script type="text/javascript" charset="utf8" src="<?php echo("$location") ?>/frameworks/bootstrap-plugins/jquery.bootstrap-touchspin.min.js"></script>
    <!--
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-touchspin/3.1.2/jquery.bootstrap-touchspin.min.css"/>
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-touchspin/3.1.2/jquery.bootstrap-touchspin.min.js"></script>
-->

    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/bootstrap-plugins/bootstrap-treeview.min.css"/>
    <script type="text/javascript" charset="utf8" src="<?php echo("$location") ?>/frameworks/bootstrap-plugins/bootstrap-treeview.min.js"></script>
<!--
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.css"/>
    <script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-treeview/1.2.0/bootstrap-treeview.min.js"></script>
-->

    <script src="<?php echo("$location") ?>/frameworks/highcharts/highcharts.js"></script>
    <script src="<?php echo("$location") ?>/frameworks/highcharts/exporting.js"></script>

    <!--
    <script type="text/javascript" charset="utf8" src="https://code.highcharts.com/highcharts.js"></script>
    <script type="text/javascript" charset="utf8" src="https://code.highcharts.com/highcharts-more.js"></script>
    <script type="text/javascript" charset="utf8" src="https://code.highcharts.com/highcharts-3d.js"></script>
    <script type="text/javascript" charset="utf8" src="https://code.highcharts.com/modules/exporting.js"></script>
    <script type="text/javascript" charset="utf8" src="https://code.highcharts.com/modules/heatmap.js"></script>
    <script type="text/javascript" charset="utf8" src="https://code.highcharts.com/modules/histogram-bellcurve.js"></script>
-->
    <link rel="stylesheet" href="<?php echo("$location") ?>/css/site-bootstrap.css">

    <script>
    /* CSRF auto-injection: add the session token to every form and to jQuery AJAX headers */
    (function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (!meta) return;
        var token = meta.getAttribute('content');
        function injectForms() {
            document.querySelectorAll('form').forEach(function (form) {
                if (!form.querySelector('input[name="csrf_token"]')) {
                    var inp = document.createElement('input');
                    inp.type  = 'hidden';
                    inp.name  = 'csrf_token';
                    inp.value = token;
                    form.appendChild(inp);
                }
            });
            if (typeof jQuery !== 'undefined') {
                jQuery.ajaxSetup({ headers: { 'X-CSRF-Token': token } });
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', injectForms);
        } else {
            injectForms();
        }
    })();
    </script>

    <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css"
            integrity="sha512-pTaEn+6gF1IeWv3W1+7X7eM60TFu/agjgoHmYhAfLEU8Phuf6JKiiE8YmsNC0aCgQv4192s4Vai8YZ6VNM6vyQ=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    />
    <script
            src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"
            integrity="sha512-IOebNkvA/HZjMM7MxL0NYeLYEalloZ8ckak+NDtOViP7oiYzG5vn6WVXyrJDiJPhl4yRdmNAG49iuLmhkUdVsQ=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
    ></script>
</head>