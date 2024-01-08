<?php
/**
 * Plugin Name: PricePlugin
 * Plugin URI: https://flexgold.com/plugins/PricPlugin
 * Description:With this plugin product prices are displayed
 * Author: Hadiseh postkal
 * Author URI: --
 * Version: 1.0.0
 * License:  later
 */
//Prevent script from executing on direct access
if (defined('ABSPATH')) {
    require_once ABSPATH . 'wp-load.php';
} else {
    require_once dirname(__DIR__) . '/wp-load.php';
}
require_once 'Cronjobs/PriceUpdater.php';
add_action('admin_menu', 'price_update_options_page');
add_action('admin_init', 'price_update_register_my_setting');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'settings_link');

function price_update_options_page()
{
    add_menu_page(
        'Price-Update flexgold',// browser title
        'PriceUpdate',
        'manage_options',
        'price_update',
        'price_update_options_view',
        "dashicons-money",
        110
    );
}

function price_update_register_my_setting()
{
    volt_view();
    htaccess_view();
    token_view();
}

// Erzeuge den Inhalt der Optionen-Seite
function price_update_options_view()
{
    return include 'admin/admin-view.php';
}

function settings_link($links)
{
    $mylinks = array(
        '<a href="' . admin_url('admin.php?page=price_update') . '">Settings</a>',
    );
    $actions = array_merge($links, $mylinks);
    return $actions;
}

function volt_view()
{
    $volts = array("Advanced", "Basic", "Institutional", "Superior");
    foreach ($volts as $volt) {

        add_settings_field(
            $volt,
            esc_html__($volt, 'default'),
            'price_update_options_volt_value',
            'price_update',
            'default',
            array(
                'id' => $volt,  // Unique ID for the field
                'options' => get_volts_values(),
            )
        );
        register_setting('price_update', $volt);
    }
}

function get_volts_values()
{
    $volt_werte = array("0", "0.0025", "0.005", "0.0075", "0.01", "0.0125", "0.015", "0.0175", "0.02", "0.0225", "0.025", "0.0275", "0.03", "0.0325", "0.035", "0.0375", "0.04", "0.0425", "0.045", "0.0475", "0.05");
    $options = array();
    foreach ($volt_werte as $wert) {

        $status_title = $wert;

        $options[$status_title] = esc_html__($status_title);

    }
    return $options;
}

function price_update_options_volt_value($args)
{
    $field_id = $args['id']; // Name of the select field
    $field_options = $args['options']; // Volts values

    // echo "<br><label for='$field_id'>" . esc_html__($args['id']) . "</label>";
    echo "<br><select name='$field_id' style='margin-left: 10px;margin-top: 5px;'>"; // Adjust the margin-left value as needed

    foreach ($field_options as $value => $label) {
        $selected = db_get_volt_value($value, $field_id);
        echo "<option value='$value' $selected>$label</option>";
    }

    echo "</select><br><br>";

}

function htaccess_view()
{

    add_settings_field(
        'htaccess_field_user',
        esc_html__('htaccess', 'default'),
        function ()  // Callback for view/html
        {
            echo "<br><label for='username' >Username:</label><br>";
            echo "<input type='text' name='htaccess_field_user' id='htaccess_field_user' value='" . esc_attr(get_option('htaccess_field_user')) . "'><br>";
        },
        'price_update',
    );

    add_settings_field(
        'htaccess_field_pass',
        esc_html__('', 'default'),
        function () // Callback for view/html
        {
            echo "<label for='password'>Password:</label><br>";
            echo "<input type='password' name='htaccess_field_pass' id='htaccess_field_pass' value='" . esc_attr(get_option('htaccess_field_pass')) . "'><br><br>";

        },
        'price_update',
    );
    register_setting('price_update', 'htaccess_field_user');
    register_setting('price_update', 'htaccess_field_pass');
}

function token_view()
{
    add_settings_field(
        'token_field_token', // Field ID
        esc_html__('Token und URL', 'default'), // Field title
        function ()  // Callback for view/html
        {
            echo "<br><label for='token_field_token'>Token:</label><br>";
            echo "<input type='text' name='token_field_token' id='token_field_token' value='" . esc_attr(get_option('token_field_token')) . "'><br>";
        },
        'price_update', // Page on which to add the settings field
    );

    add_settings_field(
        'token_field_url', // Field ID
        esc_html__('', 'default'), // Field title
        function () // Callback for view/html
        {
            echo "<label for='token_field_url'>URL:</label><br>";
            echo "<input type='url' name='token_field_url' id='token_field_url' value='" . esc_attr(get_option('token_field_url')) . "'>";
        },
        'price_update', // Page on which to add the settings field
    );
    register_setting('price_update', 'token_field_token');
    register_setting('price_update', 'token_field_url');
}

//combobox mit value aus datenbank befüllen
function db_get_volt_value($status_id, $volt)
{
    global $wpdb;

    $step_info = $wpdb->prefix . "options";
    $selectedValue = '';

    $options = $wpdb->get_results("SELECT * FROM $step_info");
    if (!$options) {
        die('Error fetching data: ' . $wpdb->last_error);
    }

    foreach ($options as $option) {
        switch ($volt) {
            case "Institutional":
            case "Advanced":
            case "Superior":
            case "Basic":
                $selectedValue = ($volt == $option->option_name && $status_id == $option->option_value) ? ' selected' : '';
                break;
            case "AnotherField":
                // Handle AnotherField comparison
                break;
        }
        if ($selectedValue) {
            break;
        }
    }

    return $selectedValue;
}

function pluginprefix_install()
{
    // Create Tables
    global $wpdb;
    $tables = array(
        $wpdb->prefix . "Gold",
        $wpdb->prefix . "Silver",
        $wpdb->prefix . "Palladium",
        $wpdb->prefix . "Platinum",
    );

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    foreach ($tables as $table) {
        $create_table = "
            CREATE TABLE {$table} (
                id int(11) NOT NULL AUTO_INCREMENT,
                Name varchar(512) COLLATE utf8_persian_ci NOT NULL,
                BasicSale double NOT NULL,
                AvancedSale double NOT NULL,
                SuperiorSale double NOT NULL,
                InstutionalSale double NOT NULL,
                BasicPurchase double NOT NULL,
                AvancedPurchase double NOT NULL,
                SuperiorPurchase double NOT NULL,
                InstutionalPurchase double NOT NULL,
                Date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        ";

        $wpdb->query($create_table);
        $wpdb_errors = $wpdb->last_error;

        if (!empty($wpdb_errors)) {
            error_log("Error creating table $table: $wpdb_errors");
        }
    }
    // Cron job run
    if (!wp_next_scheduled('scheduled_product_tasks')) {
        wp_schedule_event(time(), 'products_five_seconds', 'scheduled_product_tasks');
    }
}

register_activation_hook(__FILE__, 'pluginprefix_install');

function pluginprefix_deactivation()
{
    // To remove the table
    global $wpdb;
    $tables = array(
        $wpdb->prefix . "Gold",
        $wpdb->prefix . "Silver",
        $wpdb->prefix . "Palladium",
        $wpdb->prefix . "Platinum",
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
    wp_enqueue_style('prices_style', plugins_url('/css/style.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'prices_load_scripts');

function add_kaufpreise($attr, $content = null)
{
    global $wpdb;
    $table_gold = $wpdb->prefix . "Gold";
    $table_silber = $wpdb->prefix . "Silver";
    $table_palladium = $wpdb->prefix . "Palladium";
    $table_platin = $wpdb->prefix . "Platinum";
    $option = shortcode_atts([
        'metal' => '',
        'vaultlevel' => '',
        'type' => '',
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
    $timestamp = (!empty($res) && isset($res->Date)) ? $res->Date : '';

    if ($option['type'] == 'Kaufpreis' && $option['lng'] == 'EN')
        $title = 'Sale price';
    elseif ($option['type'] == 'Verkaufspreis' && $option['lng'] == 'EN')
        $title = 'Purchase price';
    else
        $title = $option['type'];
    ?>
    <table style="width:100%">
        <tr>
            <th class="custom-common custom-header "><?= $title; ?>*
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
                Stand: <?= $date ?></td>
            <!--<td class="custom-common custom-cell">
                55.81 chf /g
            </td> -->
        </tr>
        <?php foreach ($result as $row) {
            switch (true) {
                case $option['vaultlevel'] == 'standard' && $option['type'] == 'Verkaufspreis' :
                    $stufe = $row->BasicPurchase;
                    break;
                case $option['vaultlevel'] == 'standard' && $option['type'] == 'Kaufpreis' :
                    $stufe = $row->BasicSale;
                    break;
                case  $option['vaultlevel'] == 'advanced' && $option['type'] == 'Verkaufspreis':
                    $stufe = $row->AvancedPurchase;
                    break;
                case  $option['vaultlevel'] == 'advanced' && $option['type'] == 'Kaufpreis':
                    $stufe = $row->AvancedSale;
                    break;
                case $option['vaultlevel'] == 'superior' && $option['type'] == 'Verkaufspreis':
                    $stufe = $row->SuperiorPurchase;
                    break;
                case $option['vaultlevel'] == 'superior' && $option['type'] == 'Kaufpreis':
                    $stufe = $row->SuperiorSale;
                    break;
                case $option['vaultlevel'] == 'institutional' && $option['type'] == 'Verkaufspreis':
                    $stufe = $row->InstutionalPurchase;
                    break;
                case $option['vaultlevel'] == 'institutional' && $option['type'] == 'Kaufpreis':
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