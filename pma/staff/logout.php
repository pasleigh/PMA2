
<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 22/05/2017
 * Time: 16:19
 */
$base_url = $_COOKIE["BASE_URL"] ?? '';
$base_url_safe = htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8');
$random = rand();
$logout_url = "<a href='http://" . $random . "@" . $base_url_safe . "'>Switch User / Logout</a>";
echo($logout_url);
?>
<script>
    //alert("This is about to attempt to log you out by redirecting you to the start and asking for a new user login.");
    //var base_url = "<?php echo($base_url); ?>";
    //window.open("http://.@" + base_url ,"_self");
</script>
