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
        'title' => __('Test mode', 'siru-mobile'),
        'type' => 'checkbox',
        'label' => __('Use Siru Mobile staging environment.', 'siru-mobile'),
        'description'    => __('Staging environment is for testing mobile payments without actually charging the user. Remember that you may need separate credentials for staging and production endpoints.', 'siru-mobile'),
        'default' => 'yes'
    ),
    'log_enabled' => array(
        'title' => __( 'Debug Log', 'woocommerce' ),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'woocommerce'),
        'description'    => sprintf(__('Log payment events to <code>%s</code>.', 'siru-mobile'), wc_get_log_file_path('siru')),
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
            'FI' => __('Finland', 'woocommerce')
        ),
        'default' => 'FI'
    ),
    'tax_class' => array(
        'title' => __('Tax class', 'siru-mobile'),
        'type' => 'select',
        'desc_tip' => __('The VAT class sent to mobile operator. You can only select one tax class for mobile payments. Tax class 3 is the general rate, classes 1 and 2 are reduced rates. For more information, please see Siru Mobile API documentation.', 'siru-mobile'),
        'options' => array(
            "0" => __('no tax', 'siru-mobile'),
            "1" => __('tax class 1', 'siru-mobile'),
            "2" => __('tax class 2', 'siru-mobile'),
            "3" => __('tax class 3', 'siru-mobile'),
        ),
        'default' => "3"
    ),
    'service_group' => array(
        'title' => __('Service group', 'siru-mobile'),
        'type' => 'select',
        'desc_tip' => __("The Finnish Communications Regulatory Authority requires paid phone services to be categorized into service groups. A customer's phone subscription may bar calls to certain service groups.", 'siru-mobile'),
        'options' => array(
            '1' => __('Non-profit services', 'siru-mobile'),
            '2' => __('Online services', 'siru-mobile'),
            '3' => __('Entertainment services', 'siru-mobile'),
            '4' => __('Adult entertainment services', 'siru-mobile'),
        ),
        'default' => 2
    ),
    'maximum_payment' => array(
        'title' => __('Maximum payment allowed', 'siru-mobile'),
        'type' => 'text',
        'desc_tip' => __('Maximum payment amount available for mobile payments.', 'siru-mobile'),
        'default' => 60
    ),

);
