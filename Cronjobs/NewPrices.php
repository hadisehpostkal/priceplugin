<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once('database.php');
require_once("PriceUpdater.php");
global $wpdb;
$tb_option = $wpdb->prefix . "options";
$result = $wpdb->get_results("SELECT * FROM $tb_option ;");
$pass = false;
$usr = false;

foreach ($result as $option) {
    if ('htaccess_field_pass' == $option->option_name && isset($_GET['pass']) && $_GET['pass'] == $option->option_value)
        $pass=true;
    if ('htaccess_field_user' == $option->option_name && isset($_GET['usr']) && $_GET['usr'] == $option->option_value)
        $usr=true;
}
if($pass && $usr) {
    $data = new PriceUpdater();
    try {
        $data->addPrices($data->updatePrices());
    }catch (Exception $e) {
        echo 'Fehler: ' . $e->getMessage();
        exit();
    }
} else {
    echo "Invalid pass or usr";
}


