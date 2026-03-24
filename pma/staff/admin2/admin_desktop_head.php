<?PHP
$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
$location = "../..";
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
  <link href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/css/pagedesc.css" type="text/css" rel="stylesheet" />
-->
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$location") ?>/images/favicon.ico"/>
    <link rel="icon" href="<?php echo("$location") ?>/staff/images/projectsicon72.png"/>
    <link href="<?php echo("$location") ?>/staff/css/pagedesc.css" type="text/css" rel="stylesheet" />

  <!--
  <link href="https://cdn.datatables.net/1.10.3/css/jquery.dataTables.css" type="text/css" rel="stylesheet" />
  <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
  <script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.3/js/jquery.dataTables.min.js"></script>
  <script src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
  -->

  <link rel="stylesheet" type="text/css" href="<?php echo($location)?>/staff/frameworks/DataTables-1.10.16/css/jquery.dataTables.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo($location)?>/staff/frameworks/jqueryui/1.12.1/jquery-ui.css"/>
  <script type="text/javascript" src="<?php echo($location)?>/staff/frameworks/jQuery-3.2.1/jquery-3.2.1.min.js"></script>
  <script type="text/javascript" src="<?php echo($location)?>/staff/frameworks/jqueryui/1.12.1/jquery-ui.js"></script>
  <script type="text/javascript" src="<?php echo($location)?>/staff/frameworks/DataTables-1.10.16/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="<?php echo($location)?>/staff/frameworks/DataTables-1.10.16/js/dataTables.bootstrap.min.js"></script>

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

  <style>
    li {
      font-weight: normal;
      font-size: 110%;
      padding-top: 5px;
      padding-bottom: 3px;
    }
    li.itemHeader{
      color:  darkgreen;
      font-weight: bold;
      font-size: 130%;
    }
  </style>

</head>
  