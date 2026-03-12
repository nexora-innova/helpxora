<?php

include('../../../inc/includes.php');

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

Session::checkLoginUser();
Session::checkRight('config', READ);

$action = $_GET['action'] ?? '';

if ($action == 'get_categories') {
    $type = (int)($_GET['type'] ?? 1);
    $current_value = (int)($_GET['value'] ?? 0);
    $condition = [];
    if ($type == 1) {
        $condition['is_incident'] = 1;
    } else {
        $condition['is_request'] = 1;
    }
    
    ITILCategory::dropdown([
        'name' => 'itilcategories_id',
        'value' => $current_value,
        'condition' => $condition,
        'display' => true
    ]);
    return;
}
