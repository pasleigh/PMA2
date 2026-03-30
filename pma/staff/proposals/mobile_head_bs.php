<?php
$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
$location = "../..";
$gear_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
  <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
  <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
</svg>';
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo(($school_title_h ?? $school_title ?? '') . ' - ' . ($running_title_h ?? $running_title ?? '')); ?></title>
    <?php if (function_exists('generate_csrf_token')): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$location") ?>/staff/images/favicon.ico"/>
    <link href="<?php echo("$location") ?>/staff/css/pagedesc.css" type="text/css" rel="stylesheet"/>

    <link href="../frameworks/bootstrap-5.2.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../frameworks/DataTables-1.10.16/css/dataTables.bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="../frameworks/DataTables-1.10.16/css/jquery.dataTables.css"/>

    <script type="text/javascript" src="../frameworks/jQuery-3.2.1/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="../frameworks/popper.min.js"></script>
    <script type="text/javascript" src="../frameworks/bootstrap-5.2.3-dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="../frameworks/DataTables-1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="../frameworks/DataTables-1.10.16/js/dataTables.bootstrap.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="../frameworks/jqueryui/1.12.1/jquery-ui.css"/>
    <script type="text/javascript" src="../frameworks/jqueryui/1.12.1/jquery-ui.js"></script>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">

    <link rel="stylesheet" href="../css/site-bootstrap.css">

    <style>
        /* --- Navbar offset: push content below fixed navbar --- */
        body {
            padding-top: 56px;
            font-size: 1rem;
            font-family: "Open Sans", "Helvetica Neue", Arial, sans-serif;
        }

        /* --- Override pagedesc.css heading colours for dark background --- */
        h3, h4, h5, h6 {
            color: #ffffff;
        }
        h1 { font-size: 1.6rem;  color: #ffc107; } /* amber — prominent, warm */
        h2 { font-size: 1.35rem; color: #6ea8fe; } /* Bootstrap blue-on-dark */
        h3 { font-size: 1.15rem; font-weight: 600; }
        h4 { font-size: 1.05rem; font-weight: 600; }

        /* Inline warning/alert text colours */
        .text-deadline  { color: #ffc107; font-weight: 600; } /* amber */
        .text-passed    { color: #f87171; font-weight: 600; } /* soft red */
        .text-admin-ok  { color: #6bc43d; font-weight: 600; } /* green */

        /* Module reference line under main heading */
        .module-ref {
            font-size: 0.95rem;
            color: #adb5bd;
            margin-bottom: 1rem;
        }

        /* --- Form controls on dark background --- */
        .form-control, .form-select {
            background-color: #2b3035;
            color: #f8f9fa;
            border-color: #6c757d;
        }
        .form-control:focus, .form-select:focus {
            background-color: #2b3035;
            color: #f8f9fa;
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }
        input[type="submit"], input[type="button"] {
            font-size: 1rem;
        }

        /* --- Divider --- */
        hr { border-color: #6c757d; opacity: 0.5; }

        /* --- Buttons: allow text wrap, consistent spacing --- */
        .btn {
            white-space: normal !important;
            max-width: 500px;
            margin-bottom: 6px;
            word-wrap: break-word;
            font-size: 0.95rem;
        }

        /* --- Logo image area --- */
        .props-logo { padding: 0.75rem 0 0.5rem; }
    </style>

    <?php if (function_exists('generate_csrf_token')): ?>
    <script>
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
    <?php endif; ?>

</head>
