<?php

namespace Siru\Transport;

/**
 * Transport interface allows sending HTTP request to given end point and it returns the API response.
 */
interface TransportInterface
{

    /**
     * Set the base URL for all HTTP requests.
     *
     * @param string $baseUrl
     * @return mixed
     */
    public function setBaseUrl(string $baseUrl);

    /**
     * Sends request to Siru payment API using available transport mechanism.
     *
     * @param array $fields
     * @param string $endPoint
     * @param string $method
     * @return array Array where index 0 is HTTP status code and index 1 is the response body
     */
    public function request(array $fields, string $endPoint, string $method = 'GET') : array;

}
