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

            if (empty($_GET['siru_event']) || $_GET['siru_event'] == 'failure') {

                unset($_SESSION['token_checkout']);

                $this->order_button_text = __('Continue to payment', 'woocommerce-sirumobile-checkout');
            }

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );


            $this->init_form_fields();
            $this->init_settings();
        }

        /**
         *
         */
        public function init_form_fields()
        {
            $this->form_fields = array(

                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-siru-mobile'),
                    'type' => 'checkbox',
                    'label' => __('Enable SiruMobile Payment', 'wc-gateway-offline'),
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
            $order = wc_get_order($order_id);

            $token_active = $_SESSION['token_checkout'] ;

            $merchantId = esc_attr( get_option( 'siru_mobile_merchant_id' ) );
            $secret = esc_attr( get_option( 'siru_mobile_merchant_secret' ) );

            $signature = new \Siru\Signature($merchantId, $secret);

            $failure = false;

            if(isset($_GET['siru_event']) == true) {
                if($signature->isNotificationAuthentic($_GET)) {
                    if($_GET['siru_event'] != 'failure' && !is_null($token_active)){
                        $failure = true;
                    }
                }
            }

            if ($failure) {

                // Mark as on-hold (we're awaiting the payment)
                $order->update_status( 'completed', __( 'payment completed', 'wc-gateway-offline' ) );

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
//                ?siru_uuid=632ffd0c-9b73-4484-8141-c3a5b61364f3&siru_merchantId=75&siru_submerchantReference=&siru_purchaseReference=&siru_event=success&siru_signature=9dad00684a74f53bb17dac4cd0b9ecbfa65a29f9cadcafdd2711e6e4aab2455023760057a3a8a0a6b8e488e65aec7bd83a47221d9ab9743526ec504da9ce5170

                try {
                    $url = get_site_url() . '/checkout';

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
                    echo "Unable to contact Payment API.";

                } catch (\Siru\Exception\ApiException $e) {
                    echo "API reported following errors:<br />";
                    foreach ($e->getErrorStack() as $error) {
                        echo $error . "<br />";
                    }
                }

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

    if ( !is_plugin_active('siru-mobile/index.php') ) {
        echo "<div class=' notice-warning notice is-dismissible'><p><b>Please make all configuration's </b> <a href=".admin_url('admin.php?page=siru-mobile-settings')."> Siru Mobile</a></a></p></div>";
    }
}



