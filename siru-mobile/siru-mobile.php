<?php
/*
Plugin Name: Siru Mobile
Plugin URI:
Description: Siru Moblie Payment
Version: 2.6
Author: Siru Mobile
Author URI: https://sirumobile.com
License: GPLv2 or later
Text Domain: siru-mobile
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once ABSPATH . '/wp-content/plugins/siru-mobile/index.php';


/**
 * Create Settings Link
 */

function add_settings_link($links, $file) {
    static $this_plugin;
    if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);

    if ($file == $this_plugin){
        $settings_link = '<a href="admin.php?page=siru-mobile-settings">'.__("Settings", "siru-mobile").'</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}

add_filter('plugin_action_links', 'add_settings_link', 10, 2 );


add_filter( 'locale', 'my_theme_localized' );
function my_theme_localized( $locale ){

    if ( ! isset( $_GET['l'] ) )
        return esc_attr( $_GET['l'] );


    return $locale;
}

add_action('plugins_loaded', 'true_load_language');

function true_load_language(){
    load_plugin_textdomain( 'siru-mobile', false, basename( dirname( __FILE__ ) ) . '/lang' );
}



/**
 * Create Settings Menu
 */

add_action( 'admin_menu', 'createOTUMenu' );
function createOTUMenu() {
    add_menu_page( 'Siru Mobile', 'Siru Mobile', 'manage_options', 'siru-mobile-settings', 'createOTUMenuPage', 'dashicons-smartphone', 32 );
}


/**
 * Create Settings Menu Page
 */

function createOTUMenuPage() { ?>
    <?php add_thickbox(); ?>
    <div class="wrap">
    <h1>Siru Mobile Settings</h1>

    <?php settings_errors(); ?>

    <div id="tab-settings-page">
        <form method="post" action="options.php">
            <?php settings_fields( 'siru-mobile-settings-group' ); ?>
            <?php do_settings_sections( 'siru-mobile-settings-group' ); ?>
            <table class="form-table"  style="padding: 5px">
                <tr>
                    <th scope="row"><?php _e( 'Use live environment', 'siru-mobile' ) ?></th>
                    <td><input type="checkbox" name="siru_mobile_api_endpoint" value="1" <?php checked( get_option( 'siru_mobile_api_endpoint' ), 1 ); ?> /></td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'MerchantId*', 'siru-mobile' ) ?></th>
                    <td><input type="text" required name="siru_mobile_merchant_id" class="regular-text" value="<?php echo esc_attr( get_option( 'siru_mobile_merchant_id' ) ); ?>"/></td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Merchant secret*', 'siru-mobile' ) ?></th>
                    <td><input type="text" required name="siru_mobile_merchant_secret" class="regular-text" value="<?php echo esc_attr( get_option( 'siru_mobile_merchant_secret') ); ?>"/></td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Purchase country*', 'siru-mobile' ) ?></th>
                    <td>
                        <select name="siru_mobile_purchase_country"  <?php echo esc_attr( get_option( 'siru_mobile_purchase_country') ); ?>>
                            <?php $countries = ['FI','UK']; foreach ($countries as $value): ?>
                                <option  <?= selected(  get_option( 'siru_mobile_purchase_country'), $value ); ?> value="<?=$value?>"><?=$value?></option>
                            <?php  endforeach; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Submerchant reference', 'siru-mobile' ) ?></th>
                    <td><input type="text" name="siru_mobile_submerchant_references" class="regular-text" value="<?php echo esc_attr( get_option( 'siru_mobile_submerchant_references') ); ?>"/></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Tax class', 'sirumobile-tax-class' ) ?></th>
                    <td>
                        <select name="siru_mobile_tax_class" required>
                            <?php $tax = 0; while ($tax < 4): ?>
                                <option <?= (get_option( 'siru_mobile_tax_class') == '' && $tax == 3 )? 'selected' : ''?> <?= selected(  get_option( 'siru_mobile_tax_class'), $tax ); ?> value="<?=$tax?>"><?=$tax?></option>
                                <?php $tax++; endwhile; ?>
                        </select>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e( 'Service group', 'siru-mobile' ) ?></th>
                    <td>
                        <select name="siru_mobile_service_group" required>
                            <?php $service = 0; while ($service < 4): ?>
                                <option <?= (get_option( 'siru_mobile_service_group') == '' && $service == 2 )? 'selected' : ''?> <?= selected(  get_option( 'siru_mobile_service_group'), $service ); ?> value="<?=$service?>"><?=$service?></option>
                                <?php $service++; endwhile; ?>
                        </select>
                    </td>
                </tr>


                <tr>
                    <th scope="row"><?php _e( 'Instant payment', 'siru-mobile' ) ?></th>

                    <td>
                        <select name="siru_mobile_instant_pay" >
                            <?php $instant = 0; while ($instant < 2): ?>
                                <option <?= (get_option( 'siru_mobile_instant_pay') == '' && $instant == 1 )? 'selected' : ''?> <?= selected( get_option( 'siru_mobile_instant_pay'), $instant ); ?>value="<?=$instant?>"><?=$instant?></option>
                                <?php $instant++; endwhile; ?>
                        </select>
                    </td>

                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Maximum payment allowed*', 'siru-mobile' ) ?></th>
                    <td><input type="number" required name="siru_mobile_maximum_payment_allowed" class="regular-text" value="<?= (get_option( 'siru_mobile_maximum_payment_allowed' ) == '')? 60: esc_attr( get_option( 'siru_mobile_maximum_payment_allowed' ) ); ?>"/></td>
                </tr>


            </table>
            <p><?php submit_button(); ?></p>

        </form>
    </div>
    </div>

<?php }

/**
 * Register Settings
 */

add_action( 'admin_init', 'registerOTUSettings' );
function registerOTUSettings() {
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_api_endpoint' );
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_merchant_id' );
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_merchant_secret' );
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_purchase_country' );
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_submerchant_references' );
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_tax_class' );
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_instant_pay' );
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_service_group' );
    register_setting( 'siru-mobile-settings-group', 'siru_mobile_maximum_payment_allowed' );
}

















































