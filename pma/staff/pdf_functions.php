<?php
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("./setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("./connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("./setup_tables.php")) OR die("Cannot setup database tables<BR>");

(@include_once("./admin2/admin_functions.php")) OR die("Cannot include admin functions<BR>");

$admin = 0;

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$is_module_admin = IsModuleAdmin($db, $this_staff_username, $pm_id, null, null);

if($is_module_admin) {
    $access_phrase = "You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful.";
}

$display_block .= "<div class='text-center'><h1 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h1></div>\n";

if ($access || $is_module_admin) {
    $display_block .= "<div class='row text-center mt-4'>";
    $display_block .= "<div class='col-2'></div>";
    $display_block .= "<div class='col-4'><H2>Email feedback to students <BR>with covering text</h2></div>";
    $display_block .= "<div class='col-4'><h2>Download zip of feedback pdfs</h2></div>";
    $display_block .= "<div class='col-2'></div>";
    $display_block .= "</div>";

    $email_block = "<div class='btn-group-vertical w-100' role='group'>";
    $download_block = "<div class='btn-group-vertical w-100' role='group'>";
    for ($i = 1; $i <= $num_parts; $i++) {
        $desc = $p_description[$i];
        $email_block .= "<A class='btn btn-secondary btn-lg' href='./admin2/email_project_feedback_to_students.php?pm_id=$pm_id&part=$i&op=select'>Email $desc feedback</A>\n";
        $download_block .= "<A class='btn btn-outline-secondary btn-lg' href='./projectmarking_save_part_pdf_feedback_to_zip.php?pm_id=$pm_id&part=$i&save=2'>Download $desc feedback</A>\n";
    }
    $email_block .= "</div>";
    $download_block .= "</div>";

    $display_block .= "<div class='row text-center'>";
    $display_block .= "<div class='col-2'></div>";
    $display_block .= "<div class='col-4'>$email_block</div>";
    $display_block .= "<div class='col-4'>$download_block</div>";
    $display_block .= "<div class='col-2'></div>";
    $display_block .= "</div>";

    // The overall module email
    $display_block .= "<div class='row text-center mt-5'>";
    $display_block .= "<div class='col-2'></div>";
    $display_block .= "<div class='col-8'><H2>Overall Module</H2></div>";
    $display_block .= "<div class='col-2'></div>";
    $display_block .= "</div>";
    $display_block .= "<div class='row text-center'>";
    $display_block .= "<div class='col-2'></div>";
    $display_block .= "<div class='col-8'>";
    $display_block .=    "<A class='btn btn-primary btn-lg w-100' href='./admin2/email_project_feedback_to_students.php?pm_id=$pm_id&part=$num_parts&op=select&list=1&summary_mark=1'>Email $desc feedback PDFs - With covering text and mark breakdown</A>\n";
    $display_block .= "</div>";
    $display_block .= "<div class='col-2'></div>";
    $display_block .= "</div>";

}
$admin_link_text = "";
$show_admin_link = "none";
$admin_link = "";
if (IsAdmin($db, $this_staff_username)) {
    $admin_link = "./admin2/";
    $admin_link_text = "Super Admin";
    $show_admin_link = "block";
} else if (IsModuleAdmin($db, $this_staff_username, 'all', null, null)) {
    $admin_link = "./admin2/";
    $admin_link_text = "Admin";
    $show_admin_link = "block";
}
?>
<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php
(@include_once("$mobile_head_bs")) OR die("Cannot find this file to include: $mobile_head<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Email Feedback to Students or Save as PDF</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./projectmarking_select_bs.php<?php echo("?pm_id=$pm_id"); ?>" class="nav-link px-2 text-white-50"><?php echo("$module_prefix$module1$module_postfix $short_code marking"); ?></a></li>
        </ul>
        <div class="navbar-brand ms-auto" style="display: <?php echo($show_admin_link);?>">
            <a target='_top' class="navbar-brand" href='<?php echo("$admin_link"); ?>'>
                <i class="bi bi-gear"></i>
                <?php echo("$admin_link_text"); ?>
            </a>
        </div>
    </div>
</nav>
<div id="wrapper">

    <div id="content">
        <div class="container">
            <div class="container-fluid">
        <?php echo("$display_block"); ?>
            </div>
        </div>
    </div> <!-- content -->

    <BR>
<!--++++----------End of page-----------+++++++-->
</div><!-- /wrapper -->
</body>
<script>
    $('#get_zip').on('click', function(event){
        //alert("clicked");
        event.preventDefault();
        var form_data = new FormData();
        let name = this.name;
        // pmid_234_part_6";
        let nameArray = name.split("_");
        let pm_id = nameArray[1]
        let part = nameArray[3];
        form_data.append('name', this.name);
        form_data.append('pm_id', pm_id);
        form_data.append('part', part);
        $.ajax({
            type: 'post',
            url: 'save_to_zip.php',
            data: form_data,
            contentType:false,
            processData:false,
            dataType: "json",
            success:function(mydata){
                //console.log(JSON.stringify(mydata));
                // No need to do anything with the returned data
                // It should have all been pushed into the database
                // could inform the user that it has been entered
                let div_id = '#download_' + mydata.part
                let download_html = mydata.html
                $(div_id).html(download_html)
                let zip_filename_on_server = mydata.zip_filename_on_server
                let saveas_zip_filename = mydata.saveas_zip_filename

                let action = 'save_zip'
                let paramsObj = {
                    zip_filename_on_server: zip_filename_on_server,
                    saveas_zip_filename: saveas_zip_filename,
                    op: action
                }
                let target = "./download_zip.php";
                post(target,paramsObj)
                //console.log(mydata.zip_info);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                alert('An error occurred... Look at the console (F12 or Ctrl+Shift+I, Console tab) for more information!');

                //$('#result').html('<p>status code: ' + jqXHR.status + '</p><p>errorThrown: ' + errorThrown + '</p><p>jqXHR.responseText:</p><div>' + jqXHR.responseText + '</div>');
                console.log('jqXHR:');
                console.log(jqXHR);
                console.log(jqXHR.responseText);
                console.log('textStatus:');
                console.log(textStatus);
                console.log('errorThrown:');
                console.log(errorThrown);
            }
        });

    });
    function post(path, params, method='post') {
        // The rest of this code assumes you are not using a library.
        // It can be made less verbose if you use one.
        const form = document.createElement('form');
        form.method = method;
        form.action = path;

        for (const key in params) {
            if (params.hasOwnProperty(key)) {
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = key;
                hiddenField.value = params[key];

                form.appendChild(hiddenField);
            }
        }

        document.body.appendChild(form);
        form.submit();
    }
</script>

</html>