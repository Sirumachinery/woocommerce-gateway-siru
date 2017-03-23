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
function wc_gateway_sirumobile_init() {

    if (!class_exists('WC_Payment_Gateway')) return;

    require_once ABSPATH . '/wp-content/plugins/siru-mobile/includes/hooks.php';
    require_once ABSPATH . '/wp-content/plugins/siru-mobile/includes/class-wc-gateway-sirumobile.php';

}

add_action('plugins_loaded', 'wc_gateway_sirumobile_init', 11);
