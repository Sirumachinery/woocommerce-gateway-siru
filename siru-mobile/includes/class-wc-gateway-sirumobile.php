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
     * Array that maps Siru tax class codes to tax percentages.
     * @var array
     */
    public static $tax_classes = array(
        0 => 0,
        1 => 10,
        2 => 14,
        3 => 24
    );

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
        $this->has_field = false;

        // Set maximum payment amount allowed for mobile payments
        $this->max_amount = number_format((float) $this->get_option('maximum_payment'), 2);

        if (empty($_GET['siru_event']) || $_GET['siru_event'] != 'success') {
            $this->order_button_text = __('Continue to payment', 'siru-mobile');
        }

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', __('Siru Mobile'));
        $this->description = $this->get_option('description', __('Pay using your mobile phone.', 'siru-mobile'));

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
        if($this->enabled === 'no') {
            return false;
        }

        $merchantId = trim(esc_attr($this->get_option('merchant_id')));
        $secret = trim(esc_attr($this->get_option('merchant_secret')));
        if(empty($merchantId) == true || empty($secret) == true) {
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

        // Use sandbox endpoint if configured by admin
        if($this->get_option('sandbox', 'yes') === 'yes'){
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

                $order_id = $entityBodyAsJson['purchaseReference'];
                $order  = wc_get_order($order_id);
                $uuid = $entityBodyAsJson['uuid'];

                // Notification was sent by Siru Mobile and is authentic
                if ($order->has_status( 'completed')) {
                    self::log(sprintf('Received %s notification for completed order %s. Ignoring.', $entityBodyAsJson['siru_event'], $order_id));
                    return;
                } else {
                    self::log(sprintf('Received %s notification for order %s.', $entityBodyAsJson['siru_event'], $order_id));
                }

                switch($entityBodyAsJson['siru_event']) {
                    case 'success':
                        $order->payment_complete($uuid);
                        break;

                    case 'canceled':
                        break;

                    case 'failure':
                        $order->update_status('failed', $uuid);
                        break;
                }

                
            } else {
                self::log(sprintf('Received %s notification with invalid or missing signature.', $entityBodyAsJson['siru_event']));
            }
        }

        error_log('no data for callback');

#            wp_die( 'Callback failed', 'Siru Mobile', array( 'response' => 500 ) );
    }

    /**
     * Logs message
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
     * Returns default value for Siru Tax class based on Woocommerce base tax rates.
     * @param  integer $default
     * @return integer
     */
    private function get_base_tax_class($default = 3)
    {
        foreach(WC_Tax::get_base_tax_rates() as $rate) {
            $key = array_search((int) $rate['rate'], self::$tax_classes);
            if($key !== false) return $key;
        }

        return $default;
    }

    /**
     * Configures payment gateway admin section.
     */
    public function init_form_fields()
    {
        $tax_classes = self::$tax_classes;
        foreach($tax_classes as &$percentage) {
            $percentage = "{$percentage}%";
        }
        unset($percentage);

        $this->form_fields = array(

            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable SiruMobile Payment', 'siru-mobile'),
                'desc_tip'    => true,
                'default' => 'no'
            ),

            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'placeholder' => __('Siru Mobile', 'siru-mobile')
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'text',
                'placeholder' => __('Pay using your mobile phone.', 'siru-mobile')
            ),

            'sandbox' => array(
                'title' => __('Sandbox', 'siru-mobile'),
                'type' => 'checkbox',
                'label' => __('Use Siru Mobile sandbox environment.', 'siru-mobile'),
                'description'    => __('Sandbox environment is for testing mobile payments without actually charging the user. Remember that you may need separate credentials for sandbox and production endpoints.'),
                'default' => 'yes'
            ),
            'merchant_id' => array(
                'title' => __('Merchant Id', 'siru-mobile'),
                'type' => 'text',
                'description' => __('REQUIRED: Your merchantId provided by Siru Mobile', 'siru-mobile')
            ),
            'merchant_secret' => array(
                'title' => __('Merchant secret', 'siru-mobile'),
                'type' => 'text',
                'description' => __('REQUIRED: Your merchant secret provided by Siru Mobile', 'siru-mobile')
            ),
            'submerchant_reference' => array(
                'title' => __('Sub-merchant reference', 'siru-mobile'),
                'type' => 'text',
                'desc_tip' => __('Optional store identifier if you have more than one store using the same merchantId.', 'siru-mobile')
            ),
            'purchase_country' => array(
                'title' => __('Purchase country', 'siru-mobile'),
                'type' => 'select',
                'options' => array(
                    'FI' => 'Finland'
                ),
                'default' => 'FI'
            ),
            'tax_class' => array(
                'title' => __('Tax class', 'siru-mobile'),
                'type' => 'select',
                'options' => $tax_classes,
                'default' => $this->get_base_tax_class()
            ),
            'service_group' => array(
                'title' => __('Service group', 'siru-mobile'),
                'type' => 'select',
                'options' => array(
                    '1' => __('Non-profit services'),
                    '2' => __('Online services'),
                    '3' => __('Entertainment services'),
                    '4' => __('Adult entertainment services'),
                ),
                'default' => 2
            ),
            'instantpay' => array(
                'title' => __('Instant payment', 'siru-mobile'),
                'type' => 'checkbox',
                'label' => __('Use fast checkout process', 'siru-mobile'),
                'default' => 'yes'
            ),
            'maximum_payment' => array(
                'title' => __('Maximum payment allowed', 'siru-mobile'),
                'type' => 'text',
                'desc_tip' => __('Maximum payment amount available for mobile payments.', 'siru-mobile'),
                'default' => 60
            ),
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

        // Use sandbox endpoint if configured by admin
        if($this->get_option('sandbox', 'yes') === 'yes'){
            $api->useStagingEndpoint();
        }

        try {

            $url = $woocommerce->cart->get_checkout_url();

            $total = $order->get_total();
            $total = number_format($total, 2);

            $purchaseCountry = esc_attr( $this->get_option( 'purchase_country', 'FI' ) );
            $taxClass = (int)esc_attr( $this->get_option( 'tax_class' ) );

            // Siru variant2 requires price w/o VAT
            // To avoid decimal errors, deduct VAT from total instead of using $order->get_subtotal()
            // @todo what if VAT percentages change?
            $taxPercentages = array(1 => 0.1, 2 => 0.14, 3 => 0.24);
            if(isset($taxPercentages[$taxClass]) == true) {
                $total = bcdiv($total, $taxPercentages[$taxClass] + 1, 2);
            }

            $serviceGroup = esc_attr( $this->get_option( 'service_group' ) );
            $instantPay = $this->get_option('instantpay', 'yes') === 'yes' ? 1: 0;

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
                ->set('purchaseReference', $order->id)
                ->createPayment();

            return array(
                'result' => 'success',
                'redirect' => $transaction['redirect']
            );

        } catch (\Siru\Exception\InvalidResponseException $e) {
            self::log('InvalidResponseException: Unable to contact payment API. Check credentials.');

        } catch (\Siru\Exception\ApiException $e) {
            self::log('ApiException: Failed to create transaction. ' . implode(" ", $e->getErrorStack()));
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
        $merchantId = esc_attr( $this->get_option( 'merchant_id' ) );
        $secret = esc_attr( $this->get_option( 'merchant_secret' ) );

        return new \Siru\Signature($merchantId, $secret);
    }

}
