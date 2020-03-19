# WooCommerce Siru Mobile Payment Gateway

This plugin provides mobile payments in Finland using Direct carrier billing through Siru Mobile.

## Features

* Easy to install
* Supports mobile payments through any Finnish mobile subscription
* Detects automatically if user is using mobile internet
* Available in English and Finnish
* Test plugin against Siru Mobile sandbox API before going live

## Requirements

* WooCommerce 3.0+
* API credentials from Siru Mobile
* Payment gateway is only available in Finland and supports EUR as currency

## Installation

* Either unpack the archive to Wordpress plugins directory /wp-content/plugins/ or upload archive at wp-admin/plugin-install.php?tab=upload
* Go to Plugins menu in WordPress Administration to activate the plugin
* Go to WooCommerce --> Settings --> Checkout --> Siru Mobile and configure payment settings as instructed by Siru Mobile

## Limitations

* This plugin provides only Direct carrier billing for Finland in EUR currency. Payment method will
  not be available in other countries or currencies.
* DCB requires mobile internet connection. Payment method is not available when using for example wifi connection.
  Note: This does not apply if you are using staging environment for testing where fake DCB payments are always available.
* DCB only allows one tax class during payment. If the shopping cart contains items in multiple tax classes, payment
  method is not available.
