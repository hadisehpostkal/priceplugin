<?php
global $wpdb, $table_prefix;
$tables = array(
    $table_prefix . "tbGold",
    $table_prefix . "tbSilber",
    $table_prefix . "tbpalladium",
    $table_prefix . "tbPlatin",
);
//$url = "https://prices.flexgold.com/tradeprices";
$url="http://localhost:8080/wordpress/wp-content/plugins/PricePlugin/test.xml";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$xmlFile = curl_exec($ch);
curl_close($ch);
$res = json_decode(json_encode((array)simplexml_load_string($xmlFile)), 1);
$ArraysBasic=$res['Basic'];
$ArraysAdvaced=$res['advanced'];
$Arrayssuperior=$res['superior'];
$ArraysInstitutional=$res['Institutional'];
foreach ($ArraysBasic as $basic) {
  $prices1=$basic['Gold']['nul'];
    $sql = $wpdb->prepare(
        "INSERT INTO `%s` (`id`, `name`, `Akstufe1`, `Akstufe2`, `Akstufe3`, `Akstufe4`, `Vkstufe1`, `Vkstufe2`, `Vkstufe3`, `Vkstufe4`, `Date`) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s, NOW())",
        $tables[0],
        $prices1, $prices1, $prices1, $prices1, $prices1, $prices1, $prices1, $prices1, $prices1, $prices1
    );
    if ($wpdb->query($sql) === TRUE) {
        echo "Datensatz erfolgreich eingefügt.<br>";
    } else {
        echo "Fehler beim Einfügen des Datensatzes: " . $wpdb->error . "<br>";
    }
}




