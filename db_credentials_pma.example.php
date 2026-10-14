<?php
// Copy this file to db_credentials.php and fill in real values.
// db_credentials.php must never be committed to version control.

return [
    'mech'        => ['db' => 'projects_mech',       'user' => 'projects_mech',       'pass' => 'CHANGE_ME', 'sess' => 'PMA_MECH'],
    'chem'        => ['db' => 'projects_chem',       'user' => 'projects_chem',       'pass' => 'CHANGE_ME', 'sess' => 'PMA_CHEM'],
    'civil'       => ['db' => 'projects_civil',      'user' => 'projects_civil',      'pass' => 'CHANGE_ME', 'sess' => 'PMA_CIVIL'],
    'elec'        => ['db' => 'projects_elec',       'user' => 'projects_elec',       'pass' => 'CHANGE_ME', 'sess' => 'PMA_ELEC'],
    'scape'       => ['db' => 'projects_scape',      'user' => 'projects_scape',      'pass' => 'CHANGE_ME', 'sess' => 'PMA_SCAPE'],
    'swjtu_civil' => ['db' => 'projects_swjtcivil',  'user' => 'projects_swjtcivil',  'pass' => 'CHANGE_ME', 'sess' => 'PMA_SWJTCIVIL'],
    'swjtu_mech'  => ['db' => 'projects_swjtmech',   'user' => 'projects_swjtmech',   'pass' => 'CHANGE_ME', 'sess' => 'PMA_SWJTMECH'],
    'swjtu_elec'  => ['db' => 'projects_swjtelec',   'user' => 'projects_swjtelec',   'pass' => 'CHANGE_ME', 'sess' => 'PMA_SWJTELEC'],
    'its'         => ['db' => 'projects_its',        'user' => 'projects_its',        'pass' => 'CHANGE_ME', 'sess' => 'PMA_ITS'],
    'env'         => ['db' => 'projects_env',        'user' => 'projects_env',        'pass' => 'CHANGE_ME', 'sess' => 'PMA_ENV'],

    // SMTP login used for outgoing email (see the mail settings in pma/config_main.php)
    'mail'        => ['user' => 'noreply@example.ac.uk', 'pass' => 'CHANGE_ME'],
];

