<?php
$protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
$location = '..';
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

    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$location") ?>/images/favicon.ico"/>
    <link href="<?php echo("$location") ?>/css/pagedesc.css" type="text/css" rel="stylesheet"/>

    <link href="<?php echo("$location") ?>/frameworks/bootstrap-5.2.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/DataTables-1.10.16/css/dataTables.bootstrap.min.css"/>

    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/DataTables-1.10.16/css/jquery.dataTables.css" />

    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/jQuery-3.2.1/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/popper.min.js"></script>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/bootstrap-5.2.3-dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/DataTables-1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/DataTables-1.10.16/js/dataTables.bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" rel="stylesheet">

    <script type="text/javascript" charset="utf8" src="<?php echo("$location") ?>/frameworks/datatables-plugins/1.10.16/date-uk.js"></script>

    <style>
        /* This causes the bootstrap button to wrap text and thus fit in the width*/
        .btn{
            white-space:normal !important;
            max-width:400px;
            margin-bottom:4px;
            word-wrap:break-word;
        }

    </style>

    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/jqueryui/1.12.1/jquery-ui.css"/>
    <script type="text/javascript" src="<?php echo("$location") ?>/frameworks/jqueryui/1.12.1/jquery-ui.js"></script>


    <!-- Custom styles for this template -->
   <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">

    <script src="<?php echo("$location") ?>/frameworks/bowser.min.js"></script>

    <script src="<?php echo("$location") ?>/frameworks/js.cookie.js"></script>


    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/bootstrap-plugins/jquery.bootstrap-touchspin.min.css"/>
    <script type="text/javascript" charset="utf8" src="<?php echo("$location") ?>/frameworks/bootstrap-plugins/jquery.bootstrap-touchspin.min.js"></script>


    <link rel="stylesheet" type="text/css" href="<?php echo("$location") ?>/frameworks/bootstrap-plugins/bootstrap-treeview.min.css"/>
    <script type="text/javascript" charset="utf8" src="<?php echo("$location") ?>/frameworks/bootstrap-plugins/bootstrap-treeview.min.js"></script>


    <script src="<?php echo("$location") ?>/frameworks/highcharts/highcharts.js"></script>
    <script src="<?php echo("$location") ?>/frameworks/highcharts/exporting.js"></script>


    <link rel="stylesheet" href="<?php echo("$location") ?>/css/site-bootstrap.css">

    <script>
    /* Inject school name as a decorative label at the left of every admin navbar */
    (function () {
        var schoolTitle = <?php echo json_encode($school_title ?? ''); ?>;
        if (!schoolTitle) return;
        document.addEventListener('DOMContentLoaded', function () {
            var container = document.querySelector('nav.navbar .container, nav.navbar .container-fluid');
            if (!container) return;
            var lbl = document.createElement('span');
            lbl.textContent = schoolTitle;
            lbl.style.cssText = 'color:#d4a84b;font-style:italic;font-size:1.25rem;'
                              + 'margin-right:1.1rem;white-space:nowrap;cursor:default;'
                              + 'opacity:0.85;';
            container.insertBefore(lbl, container.firstChild);
        });
    })();
    </script>

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