<?php
ini_set('display_startup_errors', 0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$debug = false;
if (array_key_exists('debug', $_GET)) {
    $debug = true;
}

$rel_dir = "staff";
//(@include_once("./staff/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("./staff/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
// Does the definition table exist?
$table_name = "definition";
try {
    // this will raise an exception if the table does not exist
    $query = "DESCRIBE $table_name";
    $db->query($query);

    $html_block = "
      <div>
          <a class='btn' href='./staff'>Staff Area</a>
          <br><br><br>
      </div>
      <div>
          <a class='btnr' href='./student'>Student Area</a>
      </div>\n";

} catch (PDOException $ex) {
    $html_block = "
      <div>
          <a class='btnr' href='./staff/admin2/first_use_staff.php'>Setup system for first use</a>
      </div>\n";
}

$protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';
$location = ".";
?>
<!doctype html>
<!-- Minified Cookie Consent served from our CDN -->
<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/1.0.9/cookieconsent.min.js"></script>
<script type="text/javascript">
    //    window.cookieconsent_options = {"message":"Be aware that this website uses cookies.","dismiss":"Got it!","learnMore":"More info","link":null,"theme":"light-bottom"};
</script>
<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.css" />
<script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.js"></script>
<script>
    window.addEventListener("load", function(){
        var p;
        window.cookieconsent.initialise({
                "palette": {
                    "popup": {
                        "background": "#000"
                    },
                    "button": {
                        "background": "#f1d600"
                    }
                },
                content: {
                    "position": "top",
                    "message": "Be aware that this website uses cookies to help you navigate the site smoothly. Use of these and data within the web-site follows the privacy policies of the University of Leeds.",
                    "dismiss": "Got it!",
                    "link": 'More info',
                    "href": './cookies_and_privacy.php',
                    "theme": "light-top"
                }
            },
            function (popup) {
                p = popup;
            }
        );
        document.getElementById('btn-open').onclick = function (e) {
            p.open();
        };
        function show_cookie_notice(){
            p.open();
        }
    });
</script>

<html lang="en">
<head>
    <meta charset="utf-8">

    <title>The Engineering Projects Marking App</title>
    <!--
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$protocol://$BASE_URL_VALUE") ?>/staff/images/favicon.ico"/>
    -->
    <link rel="Shortcut Icon" type="image/ico" href="<?php echo("$location") ?>/staff/images/favicon.ico"/>

    <STYLE type="text/css">
        .btn {
            -webkit-border-radius: 13px;
            -moz-border-radius: 13px;
            border-radius: 13px;
            font-family: Arial, sans-serif;
            color: #ffffff;
            font-size: 20px;
            background: #34d939;
            padding: 10px 20px 10px 20px;
            border: solid #1f628d 2px;
            text-decoration: none;
        }

        .btnr {
            -webkit-border-radius: 13px;
            -moz-border-radius: 13px;
            border-radius: 13px;
            font-family: Arial, sans-serif;
            color: #ffffff;
            font-size: 20px;
            background: #f28f00;
            padding: 10px 20px 10px 20px;
            border: solid #1f628d 2px;
            text-decoration: none;
        }

        .btng {
            -webkit-border-radius: 13px;
            -moz-border-radius: 13px;
            border-radius: 13px;
            font-family: Arial, sans-serif;
            color: darkblue;
            font-size: 15px;
            background: #f2f2f2;
            padding: 10px 20px 10px 20px;
            border: solid #1f628d 2px;
            text-decoration: none;
            margin-top: 20%;
        }

        .btn:hover {
            background: #3cfc59;
            text-decoration: none;
        }

        .buttonHolder{
            text-align: center;
        }

        .buttonHolderFooter{
            display:flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            bottom: 12%;
        }

        body {
            font-family: helvetica, Arial, Georgia, sans-serif;
        }

    </STYLE>

</head>

<body>

<div class="buttonHolder">
    <h2>Select where you want to be</h2>
    <?php echo($html_block) ?>
    <button type="button" class='btng' id="btn-open">Show Cookie and Privacy Info</button>
</div>

</body>
</html>