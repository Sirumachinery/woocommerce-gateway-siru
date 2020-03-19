<?php
/**
 * Plugin Name: Siru Mobile
 * Plugin URI: https://github.com/Sirumachinery/woocommerce-gateway-siru
 * Description: Siru Mobile payment gateway extension for Woocommerce.
 * Version: 1.0.0
 * Author: Siru Mobile
 * Author URI: https://sirumobile.com
 * License: MIT
 * Text Domain: siru-mobile
 * WC requires at least: 3.0.0
 * WC tested up to: 4.0.0
 *
 * Copyright 2017 Siru Mobile
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
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
    load_plugin_textdomain('siru-mobile', false, $path);
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
