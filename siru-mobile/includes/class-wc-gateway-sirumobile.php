<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(ABSPATH . '/wp-content/plugins/siru-mobile/vendor/autoload.php');

if (session_id() == '')
    session_start();



/**
 * Gateway class for Siru Mobile payments.
 * 
 * @class       WC_Gateway_Sirumobile
 * @extends     WC_Payment_Gateway
 * @version     0.1
 * @package     SiruMobile
 * @author      Siru Mobile
 */
class WC_Gateway_Sirumobile extends WC_Payment_Gateway
{

    /**
     * @var WC_Logger Logger
     */
    public static $log = false;

    /**
     * @var boolean
     */
    public static $log_enabled = true;

    /**
     * Instruction text that would be shown in receipt page.
     * @var string
     */
    private $instructions = '';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = 'siru';
        $this->method_title = 'Siru Mobile';
        $this->method_description = __('Enable payments by mobile phone. A new transaction is created using Siru Mobile payment gateway where user is redirected to confirm payment. Payments are charged in users mobile phone bill. Mobile payment is only available in Finland when using mobile internet connection.', 'siru-mobile');
        $this->icon = '';
        $this->title = 'Siru Mobile';
        $this->description = __('Pay using your mobile phone.', 'siru-mobile');
        $this->has_field = false;

        // Set maximum payment amount allowed for mobile payments
        $this->max_amount = number_format(esc_attr( get_option( 'siru_mobile_maximum_payment_allowed' ) ), 2);

        if (empty($_GET['siru_event']) || $_GET['siru_event'] != 'success') {
            $this->order_button_text = __('Continue to payment', 'siru-mobile');
        }

        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));

        add_action('woocommerce_thankyou_siru', array( $this, 'thankyou_page' ));

        if ($this->is_valid_for_use() === false) {
            $this->enabled = 'no';
        } else {
            // Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_sirumobile', array($this, 'callbackHandler'));
        }
    }

    /**
     * Returns wether or not Siru payments are available.
     * @return boolean
     */
    public function is_available()
    {
        $merchantId = esc_attr( get_option( 'siru_mobile_merchant_id' ) );
        if(empty($merchantId) == true) {
            return false;
        }

        if(parent::is_available() == false) {
            return false;
        }

        return $this->isIpAllowed();
    }

    /**
     * Checks if plugin is available in select currency.
     * @return bool
     */
    public function is_valid_for_use() {
        return in_array(get_woocommerce_currency(), apply_filters('woocommerce_paypal_supported_currencies', array('EUR')));
    }

    /**
     * Show error in admin section if plugin is not available in select country.
     */
    public function admin_options() {
        if ( $this->is_valid_for_use() ) {
            parent::admin_options();
        } else {
            ?>
            <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( 'Siru mobile payments are not available in your store currency.', 'siru-mobile' ); ?></p></div>
            <?php
        }
    }

    /**
     * Checks from Siru API if mobile payments are available for end users IP-address.
     * Results are cached for one hour.
     * @return boolean
     */
    private function isIpAllowed()
    {
        $ip = WC_Geolocation::get_ip_address();

        $cache = (array) get_transient('wc_siru_ip_check');

        // We keep IP verification results in session to avoid API call on each pageload
        if(isset($cache[$ip])) {
            return $cache[$ip];
        }

        $signature = $this->getSignature();
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

            return $allowed;

        } catch (\Siru\Exception\ApiException $e) {
            self::log(sprintf('ApiException: Unable to verify if %s is allowed to use mobile payments. %s', $ip, $e->getMessage()));
            return true;
        }
    }

    public function callbackHandler()
    {
        $signature = $this->getSignature();

        $entityBody = file_get_contents('php://input');
        $entityBodyAsJson = json_decode($entityBody, true);

        if(is_array($entityBodyAsJson) && isset($entityBodyAsJson['siru_event'])) {

            if($signature->isNotificationAuthentic($entityBodyAsJson)) {
                self::log(sprintf('Received %s notification.', $entityBodyAsJson['siru_event']));
                // Notification was sent by Siru Mobile and is authentic
            } else {
                self::log(sprintf('Received %s notification with invalid or missing signature.', $entityBodyAsJson['siru_event']));
            }
        }

        error_log('no data for callback');

#            wp_die( 'Callback failed', 'Siru Mobile', array( 'response' => 500 ) );
    }

    /**
     * Logging method.
     * @param string $message
     */
    public static function log( $message ) {
        if ( self::$log_enabled ) {
            if ( empty( self::$log ) ) {
                self::$log = new WC_Logger();
            }
            self::$log->add( 'siru', $message );
        }
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

        $signature = $this->getSignature();
        $api = new \Siru\API($signature);

        // Use production endpoint if configured by admin
        $endPoint = esc_attr( get_option( 'siru_mobile_api_endpoint' ) );
        if(!$endPoint){
            $api->useStagingEndpoint();
        }

        try {

            $url = $woocommerce->cart->get_checkout_url();

            $total = $order->get_total();
            $total = number_format($total, 2);

            $purchaseCountry = esc_attr( get_option( 'siru_mobile_purchase_country' ) );
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

            // notifyAfter should be WC()->api_request_url('WC_Gateway_Sirumobile')
            $transaction = $api->getPaymentApi()
                ->set('variant', 'variant2')
                ->set('purchaseCountry', $purchaseCountry)
                ->set('basePrice', $total)
                ->set('redirectAfterSuccess',  $this->get_return_url( $order ))
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


    /**
     * Output for the order received page.
     */
    public function thankyou_page($order_id)
    {

        $signature = $this->getSignature();

        if(isset($_GET['siru_event']) == true) {
            if($signature->isNotificationAuthentic($_GET)) {
                if($_GET['siru_event'] == 'success') {

                    $order = wc_get_order($order_id);

                    // Mark as completed
                    $order->update_status( 'completed', __( 'payment completed', 'siru-mobile' ) );

                    // Reduce stock levels
                    $order->reduce_order_stock();

                    // Remove cart
                    WC()->cart->empty_cart();

                }
            }
        }

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
        if ($this->instructions && !$sent_to_admin && 'siru' === $order->payment_method && $order->is_paid()) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }

    /**
     * Creates instance of \Siru\Signature using merchant id and secret from settings.
     * @return \Siru\Signature
     */
    private function getSignature()
    {
        $merchantId = esc_attr( get_option( 'siru_mobile_merchant_id' ) );
        $secret = esc_attr( get_option( 'siru_mobile_merchant_secret' ) );

        return new \Siru\Signature($merchantId, $secret);
    }

}


/**
 * @param $gateways
 * @return array
 */
function wc_siru_add_to_gateways($gateways)
{
    $gateways[] = 'WC_Gateway_Sirumobile';
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'wc_siru_add_to_gateways');
