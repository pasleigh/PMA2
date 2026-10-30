<?php
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('../staff_functions.php')) or die('Cannot open staff_functions.php<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot open staff_functions.php<BR>');

$display_block = '';
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h2>\n";

if ($access) {
    $admin_users_table = 'admin_users';
    require_valid_csrf();

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = '';
    }

    if (array_key_exists('new_admin_staff_id', $_POST) == false) {
        $_POST['new_admin_staff_id'] = null;
    }
    $new_admin_staff_id = $_POST['new_admin_staff_id'];

    $display_block .= '<h1>Edit System Level Admin users </h1>';

    // Tidy the table: each username should only appear once
    $removed_duplicates = RemoveDuplicateAdminUsers($db, $admin_users_table);
    if (count($removed_duplicates) > 0) {
        $display_block .= "<div class='alert alert-info'>Removed duplicate admin user entries: " . implode(', ', $removed_duplicates) . "</div>\n";
    }

    if ($POST_OP == 'selected_new_staff') {
        try {
            $results = $db->prepare("SELECT * FROM $staff_names WHERE staffid=?");
            $results->execute([$new_admin_staff_id]);
        } catch (PDOException $ex) {
            echo("Unable to update data to the table $staff_names<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        if ($results->rowCount() == 0) {
            $display_block .= "<div class='alert alert-warning'>Please choose a staff member to add.</div>\n";
        } else {
            $staff_recs = $results->fetch();
            $firstname = $staff_recs['firstname'];
            $familyname = $staff_recs['familyname'];

            $fullname = $staff_recs['fullname'];
            $username = $staff_recs['username'];
            $email = $staff_recs['email'];
            $staffid = $staff_recs['staffid'];

            $mod_admin_level = 3;

            // Insert the data into the admin_users_module table
            // Need to check that it does not already exist
            try {
                $results_admin = $db->prepare("SELECT * FROM $admin_users_table WHERE username=?");
                $results_admin->execute([$username]);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            if ($results_admin->rowCount() == 0) {
                try {
                    $results_admin = $db->prepare("INSERT INTO $admin_users_table SET firstname=?,familyname=?,fullname=?,email=?,username=?,admin_level=?");
                    $results_admin->execute([$firstname, $familyname, $fullname, $email, $username, $mod_admin_level]);
                } catch (PDOException $ex) {
                    echo("Unable to update data to the table $admin_users_table<BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }

                $display_block .= "<B>Admin user added: $fullname</B><BR>\n";
            } else {
                $display_block .= "<B>Admin user already exists: $fullname</B><BR>Username: $username<hr>\n";
            }
        }
    } elseif ($POST_OP == 'submit_modified') {
        // Remove the ticked people from the admin_users table only - their
        // staff record is left untouched
        $delete_ids = $_POST['delete_ids'] ?? [];
        if (!is_array($delete_ids)) {
            $delete_ids = [];
        }
        $deleted_names = [];
        foreach ($delete_ids as $delete_id) {
            $delete_id = (int)$delete_id;
            try {
                $results = $db->prepare("SELECT username, firstname, familyname FROM $admin_users_table WHERE `id`=?");
                $results->execute([$delete_id]);
                $admin_rec = $results->fetch();
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            if ($admin_rec === false) {
                continue;
            }
            $admin_name = htmlspecialchars(stripslashes($admin_rec['firstname'] . ' ' . $admin_rec['familyname']));

            // Should not remove yourself - as you would be ejected from the admin section
            if ($admin_rec['username'] == $this_staff_username) {
                $display_block .= "<div class='alert alert-warning'>You cannot remove yourself as an admin user ($admin_name).</div>\n";
                continue;
            }
            try {
                $results = $db->prepare("DELETE FROM $admin_users_table WHERE `id`=?");
                $results->execute([$delete_id]);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $deleted_names[] = $admin_name;
        }
        if (count($deleted_names) > 0) {
            $display_block .= "<div class='alert alert-success'>Removed from the admin users table: " . implode(', ', $deleted_names) . "</div>\n";
        } else {
            $display_block .= "<div class='alert alert-info'>No admin users were removed.</div>\n";
        }
    }

    //---------------------------------------------------------------------------------
    // Display the admin users table

    $display_block .= '<HR>';
    $display_block .= '<H2>The current Admin users table</H2>';
    $display_block .= '<p>Everyone in this table should be admin level 3 (full system control : Super Admin).</p>';
    $display_block .= '<p>Tick anyone who should not be here and submit to remove them from the admin users table. Their staff record is not changed.</p>';

    $display_block .= "<form method='post' id='admin_users_form' action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "'>\n";
    $display_block .= csrf_token_field();

    $query = "select * from $admin_users_table order by familyname";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $display_block .= "<TABLE id='adminstaffinfo' class='hover compact cell-border' cellspacing='0' width='100%'>\n";
    $display_block .= '
    <thead>
      <tr>
        <th>Staff ID</th>
        <th>Full name</th>
        <th>Email</th>
        <th>Username</th>
        <th>Admin level</th>
        <th>Tick to Delete</th>
      </tr>
    </thead>';
    $display_block .= "<tbody>\n";
    $rows = $results->fetchAll();
    foreach ($rows as $recs) {
        $staffid = (int)$recs['id'];
        $fullname = htmlspecialchars(stripslashes($recs['firstname'] . ' ' . $recs['familyname']));
        $email = htmlspecialchars(stripslashes((string)$recs['email']));
        $username = htmlspecialchars(stripslashes((string)$recs['username']));
        $mod_admin_level = htmlspecialchars(stripslashes((string)$recs['admin_level']));
        // Highlight anyone who is not level 3 as they probably should not be in this table
        if ($mod_admin_level != '3') {
            $mod_admin_level = "<span class='text-danger fw-bold' title='Not level 3 - probably should not be in this table'>$mod_admin_level</span>";
        }
        $display_block .= "<TR>\n";
        $display_block .= "<TD>$staffid</TD>\n";
        $display_block .= "<TD>$fullname</TD>\n";
        $display_block .= "<TD>$email</TD>\n";
        $display_block .= "<TD>$username</TD>\n";
        $display_block .= "<TD>$mod_admin_level</TD>\n";
        $display_block .= "<TD><input type='checkbox' class='form-check-input' name='delete_ids[]' value='$staffid'></TD>\n";
        $display_block .= "</TR>\n";
    }
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type='hidden' name='op' value='submit_modified'>";

    $display_block .= "<p class='mt-3'><input class='btn btn-danger' type='submit' id='submitbutton' name='submit' value='Delete the ticked admin users'></p>";

    $display_block .= '</form>';

    //---------------------------------------------------------------------------------

    $display_block .= "<hr><h2>Add a new system-level admin person ...</H2>\n";
    $display_block .= "<p>Start typing a name to search. New admin users are added at level 3.</p>\n";
    $display_block .= "<form method='post' action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "'>\n";
    $display_block .= csrf_token_field();
    $NameKey = 'new_admin_staff_id';
    $MatchID = null;
    $display_block .= "<div style='max-width: 500px;'>" . GetStaffSelectID($db, $staff_names, $NameKey, $MatchID) . '</div>';
    $display_block .= "\n<input type='hidden' name='op' value='selected_new_staff'>";

    $display_block .= "<div class='m-3'><input class='btn btn-success' type='submit' id='submitbutton_new' name='submit_new' value='Add a NEW admin user'></div>";

    $display_block .= '</form>';
    //---------------------------------------------------------------------------------
}

/**
 * Delete duplicate rows (same username) from the admin users table, keeping
 * one row per username. A level 3 row is kept in preference, otherwise the
 * oldest (lowest id). Rows with a blank username are left alone.
 *
 * @param PDO    $db                Active PDO connection.
 * @param string $admin_users_table Admin users table name.
 * @return string[] Descriptions of the removed rows (HTML-escaped).
 */
function RemoveDuplicateAdminUsers($db, $admin_users_table) {
    $removed = [];
    try {
        $results = $db->query("SELECT id, username, firstname, familyname, admin_level FROM $admin_users_table ORDER BY id ASC");
        $rows = $results->fetchAll();
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        return $removed;
    }
    // Level 3 rows first, otherwise keep id order (usort is stable)
    usort($rows, fn($a, $b) => ((int)$b['admin_level'] == 3) <=> ((int)$a['admin_level'] == 3));

    $seen_usernames = [];
    foreach ($rows as $recs) {
        $username_key = strtolower(trim((string)$recs['username']));
        if ($username_key === '') {
            continue;
        }
        if (!isset($seen_usernames[$username_key])) {
            // First (preferred) row for this username - keep it
            $seen_usernames[$username_key] = true;
            continue;
        }
        try {
            $delete = $db->prepare("DELETE FROM $admin_users_table WHERE `id`=?");
            $delete->execute([$recs['id']]);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            continue;
        }
        $removed[] = htmlspecialchars(stripslashes($recs['firstname'] . ' ' . $recs['familyname'])
            . ' (' . $recs['username'] . ', level ' . $recs['admin_level'] . ')');
    }
    return $removed;
}

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Edit Staff details - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Admin index page</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
        <?php echo $display_block; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css"
      integrity="sha512-pTaEn+6gF1IeWv3W1+7X7eM60TFu/agjgoHmYhAfLEU8Phuf6JKiiE8YmsNC0aCgQv4192s4Vai8YZ6VNM6vyQ=="
      crossorigin="anonymous" referrerpolicy="no-referrer"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js"
        integrity="sha512-IOebNkvA/HZjMM7MxL0NYeLYEalloZ8ckak+NDtOViP7oiYzG5vn6WVXyrJDiJPhl4yRdmNAG49iuLmhkUdVsQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    // No paging, so every tick box is in the page and gets submitted
    $("#adminstaffinfo").dataTable({paging: false});

    $("#admin_users_form").on("submit", function () {
        var ticked = $(this).find("input[name='delete_ids[]']:checked").length;
        if (ticked == 0) {
            alert("Tick at least one admin user to delete.");
            return false;
        }
        return confirm("Remove " + ticked + " admin user(s)? Their staff records will not be changed.");
    });

    // Searchable staff name selector: an empty value makes selectize show the placeholder
    $("#new_admin_staff_id option[value='-1']").val("").text("");
    $("#new_admin_staff_id").selectize({
        placeholder: "Type to search for a staff name..."
    });
</script>
</html>
