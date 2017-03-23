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
    $path = 'siru-mobile/lang';
    load_plugin_textdomain('siru-mobile', false, $path);
}


/**
 * Notify user if he has not configured the plugin.
 */
/*add_action('admin_notices', 'wc_gateway_sirumobile_admin_notices');
function wc_gateway_sirumobile_admin_notices() {

    if ( is_plugin_active('siru-mobile/siru-mobile.php') == true && !get_option( 'woocommerce_siru_settings' ) ) {
        echo "<div class='notice-warning notice is-dismissible'><p><b>Please configure</b> <a href=".admin_url('admin.php?page=siru-mobile-settings').">Siru Mobile</a> payment gateway.</p></div>";
    }
}
*/
