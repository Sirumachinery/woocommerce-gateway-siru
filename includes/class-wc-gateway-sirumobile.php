<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Gateway class for Siru Mobile payments.
 * 
 * @class       WC_Gateway_Sirumobile
 * @extends     WC_Payment_Gateway
 * @version     0.1.1
 * @package     SiruMobile
 * @author      Siru Mobile
 */
class WC_Gateway_Sirumobile extends WC_Payment_Gateway
{

    const TRANSIENT_CACHE_ID = 'wc_siru_ip_check';

    /**
     * @var WC_Logger Logger
     */
    public static $log = false;

    /**
     * @var boolean
     */
    public static $log_enabled = false;

    /**
     * Instruction text that would be shown in receipt page.
     * @var string
     */
    private $instructions = '';

    /**
     * Plugin directory name.
     * @var string
     */
    public static $base_name;

    /**
     * Constructor.
     */
    public function __construct()
    {
        self::$base_name = basename(dirname(dirname( __FILE__ )));

        require_once(WP_PLUGIN_DIR . '/' . self::$base_name . '/vendor/autoload.php');

        $this->id = 'siru';
        $this->method_title = 'Siru Mobile';
        $this->method_description = __('Enable payments by mobile phone. A new transaction is created using Siru Mobile payment gateway where user is redirected to confirm payment. Payments are charged in users mobile phone bill. Mobile payment is only available in Finland when using mobile internet connection.', 'siru-mobile');

        $this->icon = apply_filters( 'woocommerce_sirumobile_icon', plugins_url(self::$base_name) . '/assets/sirumobile-logo.png' );
        $this->has_field = false;
        self::$log_enabled = ($this->get_option('log_enabled', 'yes') == 'yes');

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

        $cache = (array) get_transient(self::TRANSIENT_CACHE_ID);

        // We keep IP verification results in cache to avoid API call on each pageload
        if(isset($cache[$ip])) {
            return $cache[$ip];
        }

        $api = $this->getSiruAPI();

        try {
            $allowed = $api->getFeaturePhoneApi()->isFeaturePhoneIP($ip);

            // Cache result for one houre
            $cache[$ip] = $allowed;
            set_transient(self::TRANSIENT_CACHE_ID, $cache, 3600);

            return $allowed;

        } catch (\Siru\Exception\ApiException $e) {
            self::log(sprintf('ApiException: Unable to verify if %s is allowed to use mobile payments. %s', $ip, $e->getMessage()));
            return true;
        }
    }

    /**
     * Handles payment notification sent by Siru.
     */
    public function callbackHandler()
    {

        $entityBody = file_get_contents('php://input');
        $entityBodyAsJson = json_decode($entityBody, true);

        if(is_array($entityBodyAsJson) && isset($entityBodyAsJson['siru_event'])) {

            require_once WP_PLUGIN_DIR . '/' . self::$base_name . '/includes/class-wc-gateway-sirumobile-response.php';
            $response = new WC_Gateway_Sirumobile_Response($this->getSignature());

            $response->handleNotify($entityBodyAsJson);
        }
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
     * Configures payment gateway admin section.
     */
    public function init_form_fields()
    {
        $this->form_fields = include( WP_PLUGIN_DIR . '/' . self::$base_name . '/includes/settings-sirumobile.php' );
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {

        $order = wc_get_order($order_id);
        $api = $this->getSiruAPI();

        try {

            $url = WC()->cart->get_checkout_url();
            $notifyUrl = WC()->api_request_url('WC_Gateway_Sirumobile');

            $purchaseCountry = esc_attr( $this->get_option( 'purchase_country', 'FI' ) );
            $taxClass = (int)esc_attr( $this->get_option( 'tax_class' ) );
            $serviceGroup = esc_attr( $this->get_option( 'service_group' ) );
            $instantPay = $this->get_option('instantpay', 'yes') === 'yes' ? 1: 0;
            $customerReference = $order->customer_user > 0 ? $order->customer_user : '';
            $basePrice = $this->calculateBasePrice($order);

            //@Todo should we block siru if cart has items where tax class differs from select tax class

            // Create transaction to Siru API
            $transaction = $api->getPaymentApi()
                ->set('variant', 'variant2')
                ->set('purchaseCountry', $purchaseCountry)
                ->set('basePrice', $basePrice)
                ->set('redirectAfterSuccess',  $this->get_return_url( $order ))
                ->set('redirectAfterFailure', $url)
                ->set('redirectAfterCancel', $url)
                ->set('notifyAfterSuccess', $notifyUrl)
                ->set('notifyAfterFailure', $notifyUrl)
                ->set('notifyAfterCancel', $notifyUrl)
                ->set('taxClass', $taxClass)
                ->set('serviceGroup', $serviceGroup)
                ->set('instantPay', $instantPay)
                ->set('customerFirstName', $order->billing_first_name)
                ->set('customerLastName', $order->billing_last_name)
                ->set('customerEmail', $order->billing_email)
                ->set('customerLocale', get_locale())
                ->set('purchaseReference', $order->id)
                ->set('customerReference', $customerReference)
                ->createPayment();

            // Store Siru UUID to order
            add_post_meta($order_id, '_siru_uuid', $transaction['uuid']);
            $order->add_order_note(sprintf(__('New Siru Mobile transaction %s', 'siru-mobile'), $transaction['uuid']));

            self::log(sprintf('Created new pending payment for order %s. UUID %s.', $order_id, $transaction['uuid']));

            return array(
                'result' => 'success',
                'redirect' => $transaction['redirect']
            );

        } catch (\Siru\Exception\InvalidResponseException $e) {
            self::log('InvalidResponseException: Unable to contact payment API. Check credentials.');
       #     wc_add_notice( 'Unable to connect to the payment gateway, please try again.', 'error' );

        } catch (\Siru\Exception\ApiException $e) {
            self::log('ApiException: Failed to create transaction. ' . implode(" ", $e->getErrorStack()));
       #     wc_add_notice( 'An error occured while starting mobile payment, please try again.', 'error' );
        }

        return array(
            'result'    => 'fail',
            'redirect'  => ''
        );
    }

    /**
     * Returns basePrice for Siru API which requires price without VAT.
     * @param  WC_Abstract_Order $order
     * @return float
     */
    private function calculateBasePrice(WC_Abstract_Order $order)
    {
        $total = $order->get_total() - $order->get_total_tax();
        $total = number_format($total, 2, '.', '');

        return $total;
    }

    /**
     * Output for the order received page.
     * @todo  what if signature is not valid or updating order fails??
     */
    public function thankyou_page($order_id)
    {

        $signature = $this->getSignature();

        if(isset($_GET['siru_event']) == true) {
            require_once WP_PLUGIN_DIR . '/' . self::$base_name . '/includes/class-wc-gateway-sirumobile-response.php';
            $response = new WC_Gateway_Sirumobile_Response($this->getSignature());

            $response->handleRequest($_GET);
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

    /**
     * @return \Siru\API
     */
    private function getSiruAPI()
    {
        $signature = $this->getSignature();
        $api = new \Siru\API($signature);

        // Use sandbox endpoint if configured by admin
        if($this->get_option('sandbox', 'yes') === 'yes'){
            $api->useStagingEndpoint();
        }

        return $api;
    }

}
