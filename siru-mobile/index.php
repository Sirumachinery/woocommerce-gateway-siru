<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Offline Payment Gateway
 *
 * Provides an Offline Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Siru_Mobile
 * @extends     WC_Payment_Gateway
 * @version     1.0.0
 * @package     WooCommerce/Classes/Payment
 * @author      SkyVerge
 */

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;

add_action('plugins_loaded', 'wc_offline_gateway_init', 11);


require_once('vendor/autoload.php');

if (session_id() == '')
    session_start();



function wc_offline_gateway_init()
{
    /**
     * Class WC_Siru_Mobile
     */
    class WC_Siru_Mobile extends WC_Payment_Gateway
    {
        public $place_order;

        /**
         * WC_Siru_Mobile constructor.
         */
        public function __construct()
        {
            $this->id = 'siru';
            $this->method_description = 'Enable Siru Mobile For Checkout';
            $this->icon = '';
            $this->title = 'Siru Mobile';
            $this->description = 'Pay using your mobile phone.';
            $this->has_field = false;
            $this->method_title = 'Siru';

            if (empty($_GET['siru_event']) || $_GET['siru_event'] == 'failure' || $_GET['siru_event'] == 'cancel') {
                unset($_SESSION['token_checkout']);

                $this->order_button_text = __('Continue to payment', 'woocommerce-sirumobile-checkout');
            }

            $this->init_form_fields();
            $this->init_settings();

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        }

        /**
         *
         */
        public function init_form_fields()
        {
            $this->form_fields = array(

                'enabled' => array(
                    'title' => __('Enable/Disable', 'siru-mobile'),
                    'type' => 'checkbox',
                    'label' => __('Enable SiruMobile Payment', 'siru-mobile'),
                    'desc_tip'    => true,
                    'default' => 'no'
                )

            );
        }

        /**
         * @param int $order_id
         * @return array
         */
        public function process_payment($order_id)
        {
            global $woocommerce;

            $order = wc_get_order($order_id);

            $token_active = isset($_SESSION['token_checkout']) ? $_SESSION['token_checkout'] : null;

            $merchantId = esc_attr( get_option( 'siru_mobile_merchant_id' ) );
            $secret = esc_attr( get_option( 'siru_mobile_merchant_secret' ) );

            $signature = new \Siru\Signature($merchantId, $secret);

            $failure = false;

            if(isset($_GET['siru_event']) == true) {
                if($signature->isNotificationAuthentic($_GET)) {
                    if($_GET['siru_event'] != 'failure' && !is_null($token_active)) {
                        $failure = true;
                    }
                }
            }

            if ($failure) {

                // Mark as on-hold (we're awaiting the payment)
                $order->update_status( 'completed', __( 'payment completed', 'siru-mobile' ) );

                // Reduce stock levels
                $order->reduce_order_stock();

                // Remove cart
                WC()->cart->empty_cart();

                unset($_SESSION['token_checkout']);

                //  Return thankyou redirect
                return array(
                    'result'    => 'success',
                    'redirect'  => $this->get_return_url( $order )
                );

            } else {

                $api = new \Siru\API($signature);

                $api->useStagingEndpoint();

                $purchaseCountry= esc_attr( get_option( 'siru_mobile_purchase_country' ) );

                // success
//              ?siru_uuid=632ffd0c-9b73-4484-8141-c3a5b61364f3&siru_merchantId=75&siru_submerchantReference=&siru_purchaseReference=&siru_event=success&siru_signature=9dad00684a74f53bb17dac4cd0b9ecbfa65a29f9cadcafdd2711e6e4aab2455023760057a3a8a0a6b8e488e65aec7bd83a47221d9ab9743526ec504da9ce5170

                try {
#                    $url = get_site_url() . '/checkout';
                    $url = $woocommerce->cart->get_checkout_url();

                    $total =  $order->get_total();
                    $total = number_format($total,2);

                    $taxClass = (int)esc_attr( get_option( 'siru_mobile_tax_class' ) );

                    // Siru variant2 requires price w/o VAT
                    // To avoid decimal errors, deduct VAT from total instead of using $order->get_subtotal()
                    // @todo what if VAT percentages change?
                    $taxPercentages = array(1 => 0.1, 2 => 0.14, 3 => 0.24);
                    if(isset($taxPercentages[$taxClass]) == true) {
                        $total = bcdiv($total, $taxPercentages[$taxClass] + 1, 2);
                    }

                    $serviceGroup = esc_attr( get_option( 'siru_mobile_service_group' ) );
                    $instantPay = esc_attr( get_option( 'siru_mobile_instant_pay' ) );


                    $transaction = $api->getPaymentApi()
                        ->set('variant', 'variant2')
                        ->set('purchaseCountry', $purchaseCountry)
                        ->set('basePrice', $total)
                        ->set('redirectAfterSuccess', $url)
                        ->set('redirectAfterFailure', $url)
                        ->set('redirectAfterCancel', $url)
                        ->set('taxClass', $taxClass)
                        ->set('serviceGroup', $serviceGroup)
                        ->set('instantPay', $instantPay)
                        ->set('customerFirstName', $order->billing_first_name)
                        ->set('customerLastName', $order->billing_last_name)
                        ->set('customerEmail', $order->billing_email)
                        ->set('customerLocale', get_locale())
                        ->createPayment();

                    $_SESSION['token_checkout'] = true;

                    return array(
                        'result' => 'success',
                        'redirect' => $transaction['redirect']
                    );

                } catch (\Siru\Exception\InvalidResponseException $e) {
                    error_log('Siru Payment Gateway: Unable to contact payment API. Check credentials.');

                } catch (\Siru\Exception\ApiException $e) {
                    error_log('Siru Payment Gateway: Failed to create transaction. ' . implode(" ", $e->getErrorStack()));
                }

                return;
            }

        }

        /**
         * Output for the order received page.
         */
        public function thankyou_page()
        {
            if ($this->instructions) {
                echo wpautop(wptexturize($this->instructions));
            }
        }

        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {
            if ($this->instructions && !$sent_to_admin && 'offline' === $order->payment_method && $order->has_status('on-hold')) {
                echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
            }
        }


    }
}

/**
 * @param $gateways
 * @return array
 */
function wc_siru_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Siru_Mobile';
    return $gateways;
}

add_filter('woocommerce_payment_gateways', 'wc_siru_add_to_gateways');

/**
 * Initialize Gateway Settings Form Fields
 */
add_action('admin_notices', 'my_plugin_admin_notices');

function my_plugin_admin_notices() {

    if ( !is_plugin_active('siru-mobile/index.php') && !get_option( 'siru_mobile_merchant_secret' ) ) {
        echo "<div class='notice-warning notice is-dismissible'><p><b>Please configure</b> <a href=".admin_url('admin.php?page=siru-mobile-settings').">Siru Mobile</a> payment gateway.</p></div>";
    }
}

/**
 * Removes siru payment gateway option if maximum payment allowed is set and cart total exceeds it.
 * @param  array $gateways
 * @return array
 */
function wc_siru_disable_on_order_total($gateways) {
    global $woocommerce;

    if( isset($gateways['siru']) == true ) {

        $merchantId = esc_attr( get_option( 'siru_mobile_merchant_id' ) );

        if(empty($merchantId) == true) {
            unset($gateways['siru']);
        } else {

            $limit= number_format(esc_attr( get_option( 'siru_mobile_maximum_payment_allowed' ) ), 2);
            $total = number_format($woocommerce->cart->total, 2);

            if(bccomp($limit, 0, 2) == 1 && bccomp($limit, $total, 2) == -1) {
                unset($gateways['siru']);
            }

        }
    }

    return $gateways;
}

/**
 * Checks if users IP-address is allowed to make mobile payments. If not, remove Siru from payment options.
 * @param  array $gateways
 * @return array
 */
function wc_siru_verify_ip($gateways) {
    if( isset($gateways['siru']) == true ) {

        $ip = WC_Geolocation::get_ip_address();

        $cache = (array) get_transient('wc_siru_ip_check');

        // We keep IP verification results in session to avoid API call on each pageload
        if(isset($cache[$ip])) {
            if($cache[$ip] == false) {
                unset($gateways['siru']);
            }

            return $gateways;
        }

        $merchantId = esc_attr( get_option( 'siru_mobile_merchant_id' ) );
        $secret = esc_attr( get_option( 'siru_mobile_merchant_secret' ) );

        $signature = new \Siru\Signature($merchantId, $secret);

        $api = new \Siru\API($signature);

        // Use production endpoint if configured by admin
        $endPoint = esc_attr( get_option( 'siru_mobile_api_endpoint' ) );
        if(!$endPoint){
            $api->useStagingEndpoint();
        }

        try {

            $allowed = $api->getFeaturePhoneApi()->isFeaturePhoneIP($ip);

            // Cache result for one houre
            $cache[$ip] = $allowed;
            set_transient('wc_siru_ip_check', $cache, 3600);

            if($allowed == false) {
                unset($gateways['siru']);
            }

        } catch (\Siru\Exception\ApiException $e) {
            error_log(sprintf('Siru Payment Gateway: Unable to verify if %s is allowed to use mobile payments. %s', $ip, $e->getMessage()));
        }

    }

    return $gateways;
}

add_filter( 'woocommerce_available_payment_gateways', 'wc_siru_disable_on_order_total' );
add_filter( 'woocommerce_available_payment_gateways', 'wc_siru_verify_ip' );


function myplugin_init() {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain( 'my-plugin', false, $plugin_dir );
}
add_action('plugins_loaded', 'myplugin_init');

