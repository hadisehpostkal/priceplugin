<?php
class PriceUpdater
{
    private $wpdb;
    private $table_prefix;

    public function __construct()
    {
        global $wpdb, $table_prefix;
        $this->wpdb = $wpdb;
        $this->table_prefix = $table_prefix;
    }
    private function getApiUrl()
    {
        $tb_option = $this->wpdb->prefix . "options";
        $result= $this->wpdb->get_results("SELECT * FROM $tb_option");
        foreach ($result as $option) {
            if ('token_field_url' == $option->option_name)
                $url = $option->option_value;// "https://prices.flexgold.com/tradeprices";
        }
        return $url;
    }

    private function getApiToken()
    {
        $tb_option = $this->wpdb->prefix . "options";
        $result= $this->wpdb->get_results("SELECT * FROM $tb_option");
        foreach ($result as $option) {
            if('token_field_token' == $option->option_name)
                $token =$option->option_value; // "eyJhbGciOiJSUzUxMiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJhMDEwMTAwMDAwMDAwMFQiLCJyb2xlIjoiYzpmbGV4Z29sZDpzOmV4dDp3ZWJzaXRlIiwiaXNzIjoiaHR0cHM6Ly9hdXRoLmZsZXhnb2xkLmNvbS8iLCJleHAiOjIwMTU4NDY1MzgsImF1ZCI6WyJmbGV4Z29sZCIsInBvc3RncmFwaHFsIl0sImlhdCI6MTcwMDIyNzMzOCwianRpIjoiYTBFMDEwMDAwMDAwQlA4In0.ArpzAPP63gABZvjE_cjSrbfiKJu83yk41jk68mE8F4dzH5Uwo-EsdWsjbNZu4a5_vMIiohoGW57Fr3VeWmgudXeidrO2BnBVod1E8UaSAIlwswhGsbRzyifrgHfnhcKWjtOq_ILQw1Dnq8vQpLbVnh1QfavfdhF2S4pfY052D1mn2cjz-pApbU7z_1ifUEXdaUatFCFYw4H1TV7JdhqlV1EkNOtpe5LWxQldaDENrDGBGT1zx7eyCCNmSD6cDQUUxR2KgppdXDMy8UbVHqobTl3i5cmJz8XzGteSIkduglk5ccuSVV3d9cyvywUF5Hct4aewAjLa1se81W4CAy1cGQ";
        }
        return $token;
    }
    /**
     * Fetches data from the API.
     */
    public function updatePrices()
    {
        $url = $this->getApiUrl();
        $token = $this->getApiToken();
        try {
            $options = [
                "http" => [
                    "header" => "Authorization: Bearer $token",
                    "method" => "GET",
                    "protocol_version" => 1.1, // Aktualisiere das HTTP-Protokoll
                ],
            ];
            $context = stream_context_create($options);
            $data = file_get_contents($url, false, $context);

            if ($data === FALSE) {
                throw new Exception("Fehler beim Abrufen der Daten: " . error_get_last()['message']);
            }
            $data = json_decode($data, true); //data aus Api
//        echo "<pre>";var_dump($data['CHF']); echo "</pre>";exit();
            echo "Daten wurde Erfolgreich gespeichert";
            return $data;
        } catch (Exception $e) {
            echo 'Fehler: ' . $e->getMessage();
        }
    }

    private function get_price($data, $step, $status, $metall)
    {
        $result = 0;
        $tb_option = $this->wpdb->prefix . "options";
        $res= $this->wpdb->get_results("SELECT * FROM $tb_option");
        foreach ($res as $option) {
            if ($status == "Sale" && $option->option_name == $step) {
                $result = (double)$data["CHF"][$step][$status][$metall][$option->option_value];

            }
            if ($status == "Purchase")
                $result = (double)$data["CHF"][$step][$status][$metall];
        }
        return $result;

    }

    public function addPrices($data)
    {
        $timstamp = $data['lastUpdate'];
        $tables = array(
            $this->table_prefix . "Gold",
            $this->table_prefix . "Silver",
            $this->table_prefix . "Palladium",
            $this->table_prefix . "Platinum",
        );
        $names = [
            "1 g " => 1,
            "5 g " => 5,
            "10 g " => 10,
            "50 g " => 50,
            "100 g " => 100,
        ];
        $metals = [
            "Gold",
            "Silver",
            "Palladium",
            "Platinum"
        ];

        foreach ($tables as $table) {
            foreach ($metals as $metal) {
                if (strpos($table, $metal) !== false) {
                    foreach ($names as $key => $val) {
                        if ($metal === "Silver") {
                            // Change the $key to "Silber"
                            $key = $key . "Silber";
                        } elseif ($metal === "Platinum") {
                            // Change the $key to "Silber"
                            $key = $key . "Platin";
                        } else {
                            $key = $key . $metal;
                        }
                        if ($this->doesTableExist($this->wpdb, $table) && $this->isDataInTable($this->wpdb, $table)) {
                            $sql = $this->generateUpdateSQL($data, $table, $key, $val, $metal, $timstamp);
                            $this->wpdb->query($sql);
                        } else {
                            $sql = $this->generateSQL($data, $table, $key, $val, $metal, $timstamp);
                            $this->wpdb->query($sql);
                        }


                    }
                }
            }
        }
    }

    private function doesTableExist($wpdb, $table)
    {
        $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        return $result !== null;
    }

    private function isDataInTable($wpdb, $table)
    {
        $result = $wpdb->get_var("SELECT COUNT(*) FROM `$table`");
        return intval($result) > 4;
    }

    private function generateSQL($data, $table, $key, $val, $metal, $timstamp)
    {

        $basicPurchase = $this->round_price($this->get_price($data, "Basic", "Purchase", $metal) * (double)$val);
        $advancedPurchase = $this->round_price($this->get_price($data, "Advanced", "Purchase", $metal) * (double)$val);
        $superiorPurchase = $this->round_price($this->get_price($data, "Superior", "Purchase", $metal) * (double)$val);
        $institutionalPurchase = $this->round_price($this->get_price($data, "Institutional", "Purchase", $metal) * (double)$val);
        $basicSale = $this->round_price($this->get_price($data, "Basic", "Sale", $metal) * (double)$val);
        $advancedSale = $this->round_price($this->get_price($data, "Advanced", "Sale", $metal) * (double)$val);
        $superiorSale = $this->round_price($this->get_price($data, "Superior", "Sale", $metal) * (double)$val);
        $instutionalSale = $this->round_price($this->get_price($data, "Institutional", "Sale", $metal) * (double)$val);

        return "INSERT INTO `$table` (`Name`, `BasicSale`, `AvancedSale`, `SuperiorSale`, `InstutionalSale`, `BasicPurchase`, `AvancedPurchase`, `SuperiorPurchase`, `InstutionalPurchase`, `Date`)
            VALUES ('$key', '" . $basicPurchase . "',
                              '" . $advancedPurchase . "',
                              '" . $superiorPurchase . "',
                              '" . $institutionalPurchase . "',
                              '" . $basicSale . "',
                              '" . $advancedSale . "',
                              '" . $superiorSale . "',
                              '" . $instutionalSale . "',
                              '$timstamp');";

    }

    private function generateUpdateSQL($data, $table, $key, $val, $metal, $timestamp)
    {
        $basicPurchase = $this->round_price($this->get_price($data, "Basic", "Purchase", $metal) * (double)$val);
        $advancedPurchase = $this->round_price($this->get_price($data, "Advanced", "Purchase", $metal) * (double)$val);
        $superiorPurchase = $this->round_price($this->get_price($data, "Superior", "Purchase", $metal) * (double)$val);
        $institutionalPurchase = $this->round_price($this->get_price($data, "Institutional", "Purchase", $metal) * (double)$val);
        $basicSale = $this->round_price($this->get_price($data, "Basic", "Sale", $metal) * (double)$val);
        $advancedSale = $this->round_price($this->get_price($data, "Advanced", "Sale", $metal) * (double)$val);
        $superiorSale = $this->round_price($this->get_price($data, "Superior", "Sale", $metal) * (double)$val);
        $institutionalSale = $this->round_price($this->get_price($data, "Institutional", "Sale", $metal) * (double)$val);

        return "UPDATE `$table` SET 
            `BasicSale` = '" . $basicSale . "',
            `AvancedSale` = '" . $advancedSale . "',
            `SuperiorSale` = '" . $superiorSale . "',
            `InstutionalSale` = '" . $institutionalSale . "',
            `BasicPurchase` = '" . $basicPurchase . "',
            `AvancedPurchase` = '" . $advancedPurchase . "',
            `SuperiorPurchase` = '" . $superiorPurchase . "',
            `InstutionalPurchase` = '" . $institutionalPurchase . "',
            `Date` = '$timestamp'
            WHERE `Name` = '$key';";
    }

    private function round_price($price, $round = 2)
    {
        $roundedPrice = round($price, $round);
        return $roundedPrice;
    }
}