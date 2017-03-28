<?php

/**
 * Create Settings Link
 */
add_filter('plugin_action_links', 'wc_gateway_sirumobile_settings_link', 10, 2 );
function wc_gateway_sirumobile_settings_link($links, $file) {
    $this_plugin = 'siru-mobile/siru-mobile.php';

    if ($file == $this_plugin){
        $settings_link = '<a href="admin.php?page=siru-mobile-settings">'.__("Settings", "woocommerce").'</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}


/**
 * Add filter that allows changing locale with GET parameter.
 */
add_filter( 'locale', 'wc_gateway_sirumobile_theme_localized' );
function wc_gateway_sirumobile_theme_localized( $locale ) {
    if (isset($_GET['l']) == true) {
        return esc_attr( $_GET['l'] );
    }

    return $locale;
}


/**
 * Load text domain for translations.
 */
add_action('plugins_loaded', 'wc_gateway_sirumobile_load_language', 12);
function wc_gateway_sirumobile_load_language(){
    $path = 'siru-mobile/languages';
    load_plugin_textdomain('siru-mobile', false, $path);
}
