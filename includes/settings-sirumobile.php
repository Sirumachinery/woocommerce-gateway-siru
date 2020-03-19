<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Siru Mobile payment gateway settings.
 */
return array(

    'enabled' => array(
        'title' => __('Enable/Disable', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable SiruMobile Payment', 'woocommerce-gateway-siru'),
        'desc_tip'    => true,
        'default' => 'no'
    ),

    'title' => array(
        'title' => __('Title', 'woocommerce'),
        'type' => 'text',
        'placeholder' => __('Siru Mobile', 'woocommerce-gateway-siru')
    ),
    'description' => array(
        'title' => __('Description', 'woocommerce'),
        'type' => 'text',
        'placeholder' => __('Pay using your mobile phone.', 'woocommerce-gateway-siru')
    ),

    'sandbox' => array(
        'title' => __('Test mode', 'woocommerce-gateway-siru'),
        'type' => 'checkbox',
        'label' => __('Use Siru Mobile staging environment.', 'woocommerce-gateway-siru'),
        'description'    => __('Staging environment is for testing mobile payments without actually charging the user. Remember that you may need separate credentials for staging and production endpoints.', 'woocommerce-gateway-siru'),
        'default' => 'yes'
    ),
    'log_enabled' => array(
        'title' => __( 'Debug Log', 'woocommerce' ),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'woocommerce'),
        /* translators: %s is full path to log file */
        'description'    => sprintf(__('Log payment events to <code>%s</code>.', 'woocommerce-gateway-siru'), wc_get_log_file_path('siru')),
        'default' => 'yes'
    ),
    'merchant_id' => array(
        'title' => __('Merchant Id', 'woocommerce-gateway-siru'),
        'type' => 'number',
        'description' => __('REQUIRED: Your merchantId provided by Siru Mobile', 'woocommerce-gateway-siru')
    ),
    'merchant_secret' => array(
        'title' => __('Merchant secret', 'woocommerce-gateway-siru'),
        'type' => 'text',
        'description' => __('REQUIRED: Your merchant secret provided by Siru Mobile', 'woocommerce-gateway-siru')
    ),
    'submerchant_reference' => array(
        'title' => __('Sub-merchant reference', 'woocommerce-gateway-siru'),
        'type' => 'text',
        'desc_tip' => __('Optional store identifier if you have more than one store using the same merchantId.', 'woocommerce-gateway-siru')
    ),
    'purchase_country' => array(
        'title' => __('Purchase country', 'woocommerce-gateway-siru'),
        'type' => 'select',
        'options' => array(
            'FI' => __('Finland', 'woocommerce')
        ),
        'default' => 'FI'
    ),
    'tax_class' => array(
        'title' => __('Tax class', 'woocommerce-gateway-siru'),
        'type' => 'select',
        'desc_tip' => __('The VAT class sent to mobile operator. You can only select one tax class for mobile payments. Tax class 3 is the general rate, classes 1 and 2 are reduced rates. For more information, please see Siru Mobile API documentation.', 'woocommerce-gateway-siru'),
        'options' => array(
            "0" => __('no tax', 'woocommerce-gateway-siru'),
            "1" => __('tax class 1', 'woocommerce-gateway-siru'),
            "2" => __('tax class 2', 'woocommerce-gateway-siru'),
            "3" => __('tax class 3', 'woocommerce-gateway-siru'),
        ),
        'default' => "3"
    ),
    'service_group' => array(
        'title' => __('Service group', 'woocommerce-gateway-siru'),
        'type' => 'select',
        'desc_tip' => __("The Finnish Communications Regulatory Authority requires paid phone services to be categorized into service groups. A customer's phone subscription may bar calls to certain service groups.", 'woocommerce-gateway-siru'),
        'options' => array(
            '1' => __('Non-profit services', 'woocommerce-gateway-siru'),
            '2' => __('Online services', 'woocommerce-gateway-siru'),
            '3' => __('Entertainment services', 'woocommerce-gateway-siru'),
            '4' => __('Adult entertainment services', 'woocommerce-gateway-siru'),
        ),
        'default' => 2
    ),
    'maximum_payment' => array(
        'title' => __('Maximum payment allowed', 'woocommerce-gateway-siru'),
        'type' => 'text',
        'desc_tip' => __('Maximum payment amount available for mobile payments.', 'woocommerce-gateway-siru'),
        'default' => 60
    ),

);
