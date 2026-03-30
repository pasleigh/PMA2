<?php
$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
$location = "..";
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

    <link href="../staff/frameworks/bootstrap-5.2.3-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../staff/frameworks/DataTables-1.10.16/css/dataTables.bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="../staff/frameworks/DataTables-1.10.16/css/jquery.dataTables.css"/>

    <script type="text/javascript" src="../staff/frameworks/jQuery-3.2.1/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="../staff/frameworks/popper.min.js"></script>
    <script type="text/javascript" src="../staff/frameworks/bootstrap-5.2.3-dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="../staff/frameworks/DataTables-1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="../staff/frameworks/DataTables-1.10.16/js/dataTables.bootstrap.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="../staff/frameworks/jqueryui/1.12.1/jquery-ui.css"/>
    <script type="text/javascript" src="../staff/frameworks/jqueryui/1.12.1/jquery-ui.js"></script>

    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
    <link rel="stylesheet" href="../staff/css/site-bootstrap.css">

    <style>
        /* --- Navbar offset: push content below fixed navbar --- */
        body {
            padding-top: 56px;
            font-size: 1rem;
            font-family: "Open Sans", "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
        }

        /* --- Override pagedesc.css heading colours for off-white background --- */
        h1 { font-size: 1.6rem;  color: #146c43; } /* dark green — matches navbar */
        h2 { font-size: 1.35rem; color: #0a58ca; } /* Bootstrap dark blue */
        h3 { font-size: 1.15rem; font-weight: 600; color: #333; }
        h4 { font-size: 1.05rem; font-weight: 600; color: #333; }

        /* Inline warning/alert text colours */
        .text-deadline  { color: #b45309; font-weight: 600; } /* amber */
        .text-passed    { color: #dca826; font-weight: 600; } /* red */
        .text-admin-ok  { color: #15803d; font-weight: 600; } /* green */

        /* Module reference line under main heading */
        .module-ref {
            font-size: 0.95rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        hr { border-color: #dee2e6; }

        /* --- Buttons: allow text wrap, consistent spacing --- */
        .btn {
            white-space: normal !important;
            max-width: 540px;
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
