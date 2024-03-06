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
//für preislider
add_action('wp_ajax_custom_frontend_ajax', 'save_custom_frontend_form');
add_action('wp_ajax_nopriv_custom_frontend_ajax', 'save_custom_frontend_form');
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
    vault_view();
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

function vault_view()
{
    $vaults = array("Advanced", "Basic", "Institutional", "Superior");
    foreach ($vaults as $vault) {

        add_settings_field(
            $vault,
            esc_html__($vault, 'default'),
            'price_update_options_vault_value',
            'price_update',
            'default',
            array(
                'id' => $vault,  // Unique ID for the field
                'options' => get_vault_values(),
            )
        );
        register_setting('price_update', $vault);
    }
}

function get_vault_values()
{
    $vault_values = array("0", "0.0025", "0.005", "0.0075", "0.01", "0.0125", "0.015", "0.0175", "0.02", "0.0225", "0.025", "0.0275", "0.03", "0.0325", "0.035", "0.0375", "0.04", "0.0425", "0.045", "0.0475", "0.05");
    $options = array();
    foreach ($vault_values as $value) {

        $status_title = $value;

        $options[$status_title] = esc_html__($status_title);

    }
    return $options;
}

function price_update_options_vault_value($args)
{
    $field_id = $args['id']; // Name of the select field
    $field_options = $args['options']; // Vault values

    // echo "<br><label for='$field_id'>" . esc_html__($args['id']) . "</label>";
    echo "<br><select name='$field_id' style='margin-left: 10px;margin-top: 5px;'>"; // Adjust the margin-left value as needed

    foreach ($field_options as $value => $label) {
        $selected = db_get_vault_value($value, $field_id);
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
function db_get_vault_value($status_id, $vault)
{
    global $wpdb;

    $step_info = $wpdb->prefix . "options";
    $selectedValue = '';

    $options = $wpdb->get_results("SELECT * FROM $step_info");
    if (!$options) {
        die('Error fetching data: ' . $wpdb->last_error);
    }

    foreach ($options as $option) {
        switch ($vault) {
            case "Institutional":
            case "Advanced":
            case "Superior":
            case "Basic":
                $selectedValue = ($vault == $option->option_name && $status_id == $option->option_value) ? ' selected' : '';
                break;
        }
        if ($selectedValue) {
            break;
        }
    }

    return $selectedValue;
}

function priceplugin_install()
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

register_activation_hook(__FILE__, 'priceplugin_install');

function priceplugin_deactivation()
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

register_deactivation_hook(__FILE__, 'priceplugin_deactivation');

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
/**
 *add slider preis
 **/
function add_sliderpreis($attr, $content = null)
{
    ob_start();
    global $wpdb;
    $unique_id = uniqid();
    $table_gold = $wpdb->prefix . "Gold";
    $option = shortcode_atts([
        'metal' => '',
        'lng' => '',
    ], $attr);
    $res = $wpdb->get_row("SELECT Date FROM $table_gold ORDER BY id ASC LIMIT 1");
    $timestamp = (!empty($res) && isset($res->Date)) ? $res->Date : '';
    ?>
    <div class="container" style="width:100%;">
        <div class="box">
            <p class="custom-common"><b><?php if ($option['lng'] == 'EN') {
                        echo "We'll show you how much platinum you're currently getting.";
                    } else {
                        echo "Wir zeigen Ihnen, wie viel ";
                    } echo $option['metal'] ?? 'Gold'; if ($option['lng'] == 'EN') {
                        echo " you currently get.";
                    } else {
                        echo " Sie aktuell bekommen.";
                    }?></b></p><br>
            <p class="custom-common"><?php if ($option['lng'] == 'EN') {
                    echo "Select your desired investment here. The values shown depend on your current one Vault level.";
                } else {
                    echo "Wählen Sie hier Ihr gewünschtes Investment aus. Die gezeigten Werte sind abhängig von Ihrer aktuellen
                Vault-Stufe.";
                }?></p>
        </div>
        <div class="box">
            <form class="slider-form" id="slider-form-<?php echo $unique_id; ?>">
                <?php wp_nonce_field('custom_text_nonce', 'custom_text_nonce') ?>
                <p class="custom-common"><b><?php if ($option['lng'] == 'EN') {
                            echo "How much do you want to invest?";
                        } else {
                            echo "Wie viel möchten Sie investieren?";
                        }?></b>
                    <input type="text"  name="preis" oninput="updateInvestment<?php echo $unique_id; ?>(this.value)"
                           class="investment-amount" id="investment-amount<?php echo $unique_id; ?>"
                           placeholder="250.000">
                </p>
                <br>
                <input type="range" min="0" max="20000000" class="range" id="range<?php echo $unique_id; ?>"
                       onchange="updateInvestment<?php echo $unique_id; ?>(this.value)" step="any" value="250000"
                       oninput="rangeInput<?php echo $unique_id; ?>()">
                <br><p class="custom-common"><?php if ($option['lng'] == 'EN') {
                        echo "You get for your investment ";
                    } else {
                        echo "Sie erhalten für Ihr Investment ";
                    }?><b
                            id="result<?php echo $unique_id; ?>"></b><b><?php echo $option['metal'] ?? 'Gold'; ?></b>
                </p>
                <input type="text" id="metal<?php echo $unique_id; ?>" name="metal" hidden="hidden"
                       value="<?= $option['metal'] ?>">
                <input type="text" id="lng<?php echo $unique_id; ?>" name="lng" hidden="hidden"
                       value="<?php echo $option['lng']; ?>">
                <p class="custom-common custom-cell">
                    <?php
                    if ($option['lng'] == 'EN') {
                        $date = date("d/m/Y H:i", strtotime($timestamp));
                        $stand = 'As of';
                    } else {
                        $date = date("d.m.Y H:i", strtotime($timestamp)) . " Uhr";
                        $stand = 'Stand';
                    }
                    ?>
                    <?=$stand.": ".$date?></p>
            </form>
        </div>
    </div>

    <script>
        var timer;

        function updateInvestment<?php echo $unique_id; ?>(value) {
            var amount = document.getElementById('investment-amount<?php echo $unique_id; ?>').value;
            var rangeElement = document.getElementById('range<?php echo $unique_id; ?>');
            rangeElement.value = amount;
            clearTimeout(timer);
            timer = setTimeout(function () {
                var nonce = '<?php echo wp_create_nonce('custom_text_nonce'); ?>';
                // value = value.replace(/\./g, '');
                //value = value.replace(',', '.'); //für berechnung muss . anstatt , in preis sein
                var data = {
                    action: 'custom_frontend_ajax',
                    preis: amount.replace(/\./g, ''),
                    metal: '<?php echo $option['metal']; ?>',
                    lng: '<?php echo $option['lng']; ?>',
                    custom_text_nonce: nonce
                };

                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function (response) {
                    document.getElementById('result<?php echo $unique_id; ?>').innerHTML = response;

                });
            }, 1000);
        }

        function rangeInput<?php echo $unique_id; ?>() {
            var amount = document.getElementById('investment-amount<?php echo $unique_id; ?>');
            var rangeElement = document.getElementById('range<?php echo $unique_id; ?>');

            rangeElement.addEventListener("input", (event) => {
                amount.value = parseInt(event.target.value).toLocaleString('de-DE');
                //parseFloat(event.target.value).toLocaleString('de-DE');
                //Math.round(event.target.value);
                //  amount.value.toLocaleString("de-DE");
            });
            rangeElement.addEventListener("mousemove", (event) => {
                //amount.value = event.target.value;
                var x = (event.target.value - rangeElement.min) / (rangeElement.max - rangeElement.min) * 100;
                var color = 'linear-gradient(90deg, rgb(10, 210, 240) ' + x + '%, #274956 ' + x + '%)';
                rangeElement.style.background = color;
            });

        }

    </script>

    <?php
    return ob_get_clean();
}

add_shortcode('preissilder', 'add_sliderpreis');

function save_custom_frontend_form()
{
    if (isset($_POST['preis']) && isset($_POST['custom_text_nonce']) && wp_verify_nonce($_POST['custom_text_nonce'], 'custom_text_nonce')) {
        $preis = sanitize_text_field($_POST['preis']);
        $metal = sanitize_text_field($_POST['metal']);
        $lng = sanitize_text_field($_POST['lng']);

        update_option('_custom_text_frontend', $preis);
        $gramm = sanitize_text_field(priceToGramm($preis, $metal,$lng));
        echo esc_attr($gramm) . " g ";
        exit();
    }
}

function priceToGramm($preis, $metal ,$lng )
{
    global $wpdb;
    $table_gold = $wpdb->prefix . "Gold";
    $table_silber = $wpdb->prefix . "Silver";
    $table_palladium = $wpdb->prefix . "Palladium";
    $table_platin = $wpdb->prefix . "Platinum";


    if ($preis < 250000) {
        $vault = 'Basic';
    } elseif ($preis >= 250000 && $preis < 1000000) {
        $vault = 'Advanced';
    } elseif ($preis >= 1000000 && $preis <= 10000000) {
        $vault = 'Superior';
    } elseif ($preis > 10000000) {
        $vault = 'Institutional';
    }

    switch ($metal) {
        case 'Gold':
            $result = $wpdb->get_results("SELECT * FROM $table_gold LIMIT 1");
            break;
        case 'Silber':
            $result = $wpdb->get_results("SELECT * FROM $table_silber LIMIT 1");
            break;
        case 'Platin':
            $result = $wpdb->get_results("SELECT * FROM $table_platin LIMIT 1");
            break;
        case 'Palladium':
            $result = $wpdb->get_results("SELECT * FROM $table_palladium LIMIT 1");
            break;
    }

    $gramm = 0;

    if (isset($result[0])) {
        switch ($vault) {
            case 'Basic':
                $gramm = ($result[0]->{'BasicSale'}) ? $preis / $result[0]->{'BasicSale'} : 0;
                break;
            case 'Advanced':
                $gramm = ($result[0]->{'AvancedSale'}) ? $preis / $result[0]->{'AvancedSale'} : 0;
                break;
            case 'Superior':
                $gramm = ($result[0]->{'SuperiorSale'}) ? $preis / $result[0]->{'SuperiorSale'} : 0;
                break;
            case 'Institutional':
                $gramm = ($result[0]->{'InstutionalSale'}) ? $preis / $result[0]->{'InstutionalSale'} : 0;
                break;
            default:
                // Handle unknown vault type if needed
                break;
        }
    }

    $roundGramm = round($gramm, 2);
    if ($lng == 'EN') {
        $formatWert = number_format($roundGramm, 2, '.', ',');
    } else {
        $formatWert = number_format($roundGramm, 2, ',', '.');
    }
    return $formatWert;
}
?>