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


function pluginprefix_install() {
    //create Tables
    global $wpdb, $table_prefix;
    $tables = array(
            $table_prefix . "tbGold",
            $table_prefix . "tbSilber",
            $table_prefix . "tbpalladium",
            $table_prefix . "tbPlatin",
        );
    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    foreach ($tables as $table) {
        $create_table = "
CREATE TABLE {$table} (
id int(11) NOT NULL AUTO_INCREMENT,
name varchar(512) COLLATE utf8_persian_ci NOT NULL,
Akstufe1 double  NOT NULL,
Akstufe2 double  NOT NULL,
Akstufe3 double  NOT NULL,
Akstufe4 double  NOT NULL,
Vkstufe1 double  NOT NULL,
Vkstufe2 double  NOT NULL,
Vkstufe3 double  NOT NULL,
Vkstufe4 double  NOT NULL,
Date TIMESTAMP NOT NULL DEFAULT CURRENT_DATE(),
PRIMARY KEY (id)
) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
" ;
        dbDelta($create_table);
    }
    //cronjob run
    if(!wp_next_scheduled('scheduled_product_tasks')) {
        wp_schedule_event(time(),'products_five_seconds','scheduled_product_tasks');
    }

}
register_activation_hook( __FILE__, 'pluginprefix_install' );
function pluginprefix_deactivation() {
    // To remove the table
    global $wpdb, $table_prefix;
    $tables = array(
        $table_prefix . "tbGold",
        $table_prefix . "tbSilber",
        $table_prefix . "tbpalladium",
        $table_prefix . "tbPlatin",
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
register_deactivation_hook( __FILE__, 'pluginprefix_deactivation' );


function add_kaufpreise($attr,$content=null) {
    global $wpdb, $table_prefix;
    $table_gold = $table_prefix . "tbGold";
    $table_silber = $table_prefix . "tbSilber";
    $table_palladium= $table_prefix . "tbpalladium";
    $table_platin = $table_prefix . "tbPlatin";
    $option = shortcode_atts([
        'metal' => '',
        'value' => '',
        'kaufstatus' => ''
    ],$attr);
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
    ?>
    <table style="width:100%">
        <tr>
            <th style="font-family: 'Montserrat'; font-size:18px; letter-spacing: 1px;font-weight: 600;"><?=$option['kaufstatus'] ?>*</th>
            <th></th>
        </tr>
        <tr>
            <td style="font-family: 'Montserrat'; font-size:18px; letter-spacing: 1px;font-weight: 300;font-style: italic; color:#969BB4" >stand: <?=date("Y-m-d H:i:s");?></td>
            <td style="font-family: 'Montserrat'; font-size:18px; letter-spacing: 1px;font-weight: 300;font-style: italic; color:#969BB4" >55.81 chf /g</td>
        </tr>
        <?php foreach ($result as $row) {
            switch (true) {
                case $option['value']=='standard' && $option['kaufstatus']=='kaufpreis' :
                    $stufe=$row->Akstufe1;
                    break;
                case $option['value']=='standard' && $option['kaufstatus']=='verkaufpreis' :
                    $stufe=$row->Vkstufe1;
                    break;
                case  $option['value']=='advanced' && $option['kaufstatus']=='kaufpreis':
                    $stufe=$row->Akstufe2;
                    break;
                case  $option['value']=='advanced' && $option['kaufstatus']=='verkaufpreis':
                    $stufe=$row->Vkstufe2;
                    break;
                case $option['value']=='superior' && $option['kaufstatus']=='kaufpreis':
                    $stufe=$row->Akstufe3;
                    break;
                case $option['value']=='superior' && $option['kaufstatus']=='verkaufpreis':
                    $stufe=$row->Vkstufe3;
                    break;
                case $option['value']=='institutional' && $option['kaufstatus']=='kaufpreis':
                    $stufe=$row->Akstufe4;
                    break;
                case $option['value']=='institutional' && $option['kaufstatus']=='verkaufpreis':
                    $stufe=$row->Vkstufe4;
                    break;
            }
            ?>
            <tr>
                <td style="font-family: 'Montserrat'; font-size:18px; letter-spacing: 1px;font-weight: 300;font-style: italic;"><?php echo $row->name; ?>CHF</td>
                <td style="font-family: 'Montserrat'; font-size:18px; letter-spacing: 1px;font-weight: 300;font-style: italic;"><?php echo $stufe; ?>CHF</td>
            </tr>
        <?php } ?>
    </table>
    <?php
    return ob_get_clean();
}
add_shortcode('display_kaufprices', 'add_kaufpreise');
?>
<?php
//create cronjob
add_filter('cron_schedules','cron_price');
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
//add_action('init',"read_db");