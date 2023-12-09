<?php
/**
 * Plugin Name: PricePlugin
 * Plugin URI: https://daneshjooyar.com/plugins/PricPlugin
 * Description:With this plugin product prices are displayed
 * Author: Hadiseh postkal
 * Author URI: --
 * Version: 1.0.0
 * License:  later
 */


function pluginprefix_install()
{
    //create Tables
    global $wpdb, $table_prefix;
    $tables = array(
        $table_prefix . "Gold",
        $table_prefix . "Silver",
        $table_prefix . "Palladium",
        $table_prefix . "Platinum",
    );
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    foreach ($tables as $table) {
        $create_table = "
CREATE TABLE {$table} (
id int(11) NOT NULL AUTO_INCREMENT,
Name varchar(512) COLLATE utf8_persian_ci NOT NULL,
BasicSale double  NOT NULL,
AvancedSale double  NOT NULL,
SuperiorSale double  NOT NULL,
InstutionalSale double  NOT NULL,
BasicPurchase double  NOT NULL,
AvancedPurchase double  NOT NULL,
SuperiorPurchase double  NOT NULL,
InstutionalPurchase double  NOT NULL,
Date TIMESTAMP NOT NULL DEFAULT CURRENT_DATE(),
PRIMARY KEY (id)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
";
        dbDelta($create_table);
    }
    //cronjob run
    if (!wp_next_scheduled('scheduled_product_tasks')) {
        wp_schedule_event(time(), 'products_five_seconds', 'scheduled_product_tasks');
    }

}

register_activation_hook(__FILE__, 'pluginprefix_install');
function pluginprefix_deactivation()
{
    // To remove the table
    global $wpdb, $table_prefix;
    $tables = array(
        $table_prefix . "Gold",
        $table_prefix . "Silver",
        $table_prefix . "Palladium",
        $table_prefix . "Platinum",
    );
    foreach ($tables as $table) {
        $sql = "DROP TABLE IF EXISTS $table";
        //To remove the table
        $result = $wpdb->query($sql);
        if ($result === false) {
            error_log("Error dropping table $table: " . $wpdb->last_error);
        }
    }
    //deactive cronjob
    wp_clear_scheduled_hook('scheduled_product_tasks');
}

register_deactivation_hook(__FILE__, 'pluginprefix_deactivation');

function prices_load_scripts()
{
    wp_enqueue_style('lotmgr_map_style', plugins_url('/css/style.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'prices_load_scripts');

function add_kaufpreise($attr, $content = null)
{
    global $wpdb, $table_prefix;
    $table_gold = $table_prefix . "Gold";
    $table_silber = $table_prefix . "Silver";
    $table_palladium = $table_prefix . "Palladium";
    $table_platin = $table_prefix . "Platinum";
    $option = shortcode_atts([
        'metal' => '',
        'value' => '',
        'status' => '',
        'lng' => '',
    ], $attr);
    switch ($option['metal']) {
        case 'gold' :
            $result = $wpdb->get_results("SELECT * FROM $table_gold ");
            break;
        case 'silber' :
            $result = $wpdb->get_results("SELECT * FROM $table_silber ");
            break;
        case 'platin' :
            $result = $wpdb->get_results("SELECT * FROM $table_platin ");
            break;
        case 'palladium' :
            $result = $wpdb->get_results("SELECT * FROM $table_palladium ");
            break;
    }
    ob_start();
    $res = $wpdb->get_row("SELECT Date FROM $table_gold ORDER BY id ASC LIMIT 1");
    $timestamp = $res->Date;

    if ($option['status'] == 'Kaufpreis' && $option['lng'] == 'EN')
        $title = 'Sale price';
    elseif ($option['status'] == 'Verkaufpreis' && $option['lng'] == 'EN')
        $title = 'Purchase price';
    else
        $title = $option['status'];
    ?>
    <table style="width:100%">
        <tr>
            <th class="custom-common custom-header "><?= $title; ?>
                *
            </th>
            <th></th>
        </tr>
        <tr>
            <td class="custom-common custom-cell">
                <?php
                if ($option['lng'] == 'EN')
                    $date = date("d/m/Y H:i", strtotime($timestamp));
                else
                    $date = date("d.m.Y H:i", strtotime($timestamp)) . " Uhr";
                ?>
                stand: <?= $date ?></td>
            <td class="custom-common custom-cell">
                55.81 chf /g
            </td>
        </tr>
        <?php foreach ($result as $row) {
            switch (true) {
                case $option['value'] == 'standard' && $option['status'] == 'Kaufpreis' :
                    $stufe = $row->BasicPurchase;
                    break;
                case $option['value'] == 'standard' && $option['status'] == 'Verkaufpreis' :
                    $stufe = $row->BasicSale;
                    break;
                case  $option['value'] == 'advanced' && $option['status'] == 'Kaufpreis':
                    $stufe = $row->AvancedPurchase;
                    break;
                case  $option['value'] == 'advanced' && $option['status'] == 'Verkaufpreis':
                    $stufe = $row->AvancedSale;
                    break;
                case $option['value'] == 'superior' && $option['status'] == 'Kaufpreis':
                    $stufe = $row->SuperiorPurchase;
                    break;
                case $option['value'] == 'superior' && $option['status'] == 'Verkaufpreis':
                    $stufe = $row->SuperiorSale;
                    break;
                case $option['value'] == 'institutional' && $option['status'] == 'Kaufpreis':
                    $stufe = $row->InstutionalPurchase;
                    break;
                case $option['value'] == 'institutional' && $option['status'] == 'Verkaufpreis':
                    $stufe = $row->InstutionalSale;
                    break;
            }
            ?>
            <tr>
                <td class="custom-common  custom-cell-name">
                    <?php if ($option['metal'] == 'silber' && $option['lng'] == 'EN')
                        echo str_replace('Silber', 'Silver', $row->Name);
                    elseif ($option['metal'] == 'platin' && $option['lng'] == 'EN')
                        echo str_replace('Platin', 'Platinum', $row->Name);
                    else echo $row->Name; ?>
                </td>
                <td class="custom-common  custom-cell-name"><?php echo $stufe; ?>
                    CHF
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php
    return ob_get_clean();
}

add_shortcode('Preisliste', 'add_kaufpreise');
?>
<?php
//create cronjob
/*add_filter('cron_schedules','cron_price');
function cron_price ($schedules) {
    if(!isset($schedules["products_five_seconds"])) {
        $schedules['products_five_seconds'] = array(
                'interval' => 5,
                'display' => _('Every Five Seconds'),
        );
    }
    return $schedules;
}
add_action('scheduled_product_tasks','product_to_sync');
function product_to_sync() {
    echo "set cronjob";
}
*/
//add_action('init',"read_db");
