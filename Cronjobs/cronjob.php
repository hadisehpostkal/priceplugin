<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once('datebase.php');
require_once("PriceUpdater.php");
$data = new PriceUpdater();
$data->addPrices($data->updatePrices());

