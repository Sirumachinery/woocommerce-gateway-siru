<?php

namespace Siru;

use Siru\Transport\GuzzleTransport;
use Siru\Transport\TransportInterface;
use Siru\Transport\WordPressTransport;

class TransportFactory
{

    /**
     * @return TransportInterface
     */
    public static function create() : TransportInterface
    {
        if (class_exists('\GuzzleHttp\ClientInterface') === true) {
            return new GuzzleTransport();
        }
        if (defined('ABSPATH') === true && function_exists('wp_remote_get') === true) {
            return new WordPressTransport();
        }

        throw new \RuntimeException(__CLASS__ . ' requires either GuzzleHttp or WP_Http installed.');
    }

}