<?PHP
$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
$location = "../..";
$gear_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
  <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
  <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
</svg>';
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo("$school_title - $running_title") ?></title>
<!--
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/images/favicon.ico"/>
    <link href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/css/pagedesc.css" type="text/css" rel="stylesheet"/>
-->
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$location") ?>/staff/images/favicon.ico"/>
    <link href="<?php echo("$location") ?>/staff/css/pagedesc.css" type="text/css" rel="stylesheet"/>

    <!--  classic colors (pre 1.4.0)-->
    <link rel="stylesheet" href="https://code.jquery.com/mobile/1.4.3/jquery.mobile.icons-1.4.3.min.css"/>
    <!--
    <link rel="stylesheet" type="text/css"
          href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/frameworks/jquery.mobile.themes/classic/theme-classic.css">
          -->
    <link rel="stylesheet" type="text/css"
          href="<?php echo("$location") ?>/staff/frameworks/jquery.mobile.themes/classic/theme-classic.css">
    <link rel="stylesheet" href="https://code.jquery.com/mobile/1.4.3/jquery.mobile.structure-1.4.3.min.css"/>

    <script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
    <script src="https://code.jquery.com/mobile/1.4.3/jquery.mobile-1.4.3.min.js"></script>

    <style>
        .ui-mobile #jqm-homeheader {
            padding: 5px 0 0 0;
            text-align: center
        }

        .ui-mobile #jqm-homeheader h1 {
            margin: 0 0 10px;
        }

        .ui-mobile #jqm-homeheader p {
            margin: 0;
        }

        .ui-header .jqm-home {
            top: 0.65em;
        }

        .online {
            border-width: 1px;
            border: none;
            text-align: center;
            font-size: 8px;
        }

        .smaller {
            text-align: center;
            font-size: 10px;
        }

        .makecenter {
            text-align: center;
        }

        .ui-btn {
            white-space: normal;
        }

        .ui-link {
            white-space: normal;
        !important
        }
    </style>

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
    <meta name="mobile-web-app-capable" content="yes"/>
    <meta name="mobile-web-app-status-bar-style" content="black"/>
    <!--<meta HTTP-EQUIV="Refresh" CONTENT=300>-->

</head> 
  