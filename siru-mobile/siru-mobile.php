<?php
/*
Plugin Name: Siru Mobile
Plugin URI: https://github.com/Sirumachinery/siru-woocommerce-plugin
Description: Siru Mobile Payment
Version: 0.1
Author: Siru Mobile
Author URI: https://sirumobile.com
License: MIT
Text Domain: siru-mobile
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Exit if woocommerce is not installed
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

/**
 * Load WC_Gateway_Sirumobile if Woocommerce is available.
 */
add_action('plugins_loaded', 'wc_gateway_sirumobile_init', 11);
function wc_gateway_sirumobile_init() {

    if (!class_exists('WC_Payment_Gateway')) return;

    require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-gateway-sirumobile.php';

    add_filter('woocommerce_payment_gateways', 'wc_siru_add_to_gateways');
}

/**
 * Add Siru gateway class to woocommerce gateways.
 * @param $gateways
 * @return array
 */
function wc_siru_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_Sirumobile';
    return $gateways;
}

/**
 * Create Settings link next to plugin.
 */
add_filter('plugin_action_links', 'wc_gateway_sirumobile_settings_link', 10, 2 );
function wc_gateway_sirumobile_settings_link($links, $file) {
    $this_plugin = plugin_basename( __FILE__ );

    if ($file == $this_plugin){
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=siru') . '">'.__("Settings", "woocommerce").'</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}

/**
 * Load text domain for translations.
 */
add_action('plugins_loaded', 'wc_gateway_sirumobile_load_language', 12);
function wc_gateway_sirumobile_load_language() {
    $plugin_base = basename( dirname( __FILE__ ) );
    $path = $plugin_base . '/languages';
    load_plugin_textdomain($plugin_base, false, $path);
}

/**
 * Do some cleanups during plugin deactivation.
 */
function wc_gateway_sirumobile_deactivate() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-gateway-sirumobile.php';
    delete_transient(WC_Gateway_Sirumobile::TRANSIENT_CACHE_ID);
}
register_deactivation_hook( __FILE__, 'wc_gateway_sirumobile_deactivate' );

/**
 * Do some cleanups during plugin deactivation.
 */
function wc_gateway_sirumobile_uninstall() {
    delete_option('woocommerce_siru_settings');
}
register_uninstall_hook( __FILE__, 'wc_gateway_sirumobile_uninstall' );
