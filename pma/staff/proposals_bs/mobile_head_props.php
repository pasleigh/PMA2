<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 31/03/2018
 * Time: 14:38
 */?>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <?php if (function_exists('generate_csrf_token')): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <link rel="Shortcut Icon" type="image/ico" href="../images/favicon.ico"/>

    <link rel="stylesheet" type="text/css" href="../frameworks/Bootstrap-3.3.7/css/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="../frameworks/DataTables-1.10.16/css/dataTables.bootstrap.min.css"/>

    <link rel="stylesheet" type="text/css" href="../frameworks/DataTables-1.10.16/css/jquery.dataTables.css" />

    <script type="text/javascript" src="../frameworks/jQuery-3.2.1/jquery-3.2.1.min.js"></script>
    <script type="text/javascript" src="../frameworks/popper.min.js"></script>
    <script type="text/javascript" src="../frameworks/Bootstrap-3.3.7/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="../frameworks/DataTables-1.10.16/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="../frameworks/DataTables-1.10.16/js/dataTables.bootstrap.min.js"></script>

    <script type="text/javascript" charset="utf8" src="../frameworks/datatables-plugins/1.10.16/date-uk.js"></script>

    <link rel="stylesheet" type="text/css" href="../frameworks/jqueryui/1.12.1/jquery-ui.css"/>
    <script type="text/javascript" src="../frameworks/jqueryui/1.12.1/jquery-ui.js"></script>

    <!-- Custom styles for this template -->
    <!--<link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">-->

    <link rel="stylesheet" type="text/css" href="../frameworks/bootstrap-plugins/jquery.bootstrap-touchspin.min.css"/>
    <script type="text/javascript" charset="utf8" src="../frameworks/bootstrap-plugins/jquery.bootstrap-touchspin.min.js"></script>

    <link rel="stylesheet" type="text/css" href="../frameworks/bootstrap-plugins/bootstrap-treeview.min.css"/>
    <script type="text/javascript" charset="utf8" src="../frameworks/bootstrap-plugins/bootstrap-treeview.min.js"></script>

    <script src="../frameworks/showdown.min.js"></script>
    <script src="../frameworks/turndown.js"></script>

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

