<?php

namespace Siru\Transport;

class WordPressTransport implements TransportInterface
{

    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * @inheritDoc
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function request(array $fields, string $endPoint, string $method = 'GET') : array
    {

        if ($method === 'GET') {
            $query = http_build_query($fields);
            $url = $this->baseUrl . $endPoint . '?' . $query;
            $response = wp_remote_get($url);
        } elseif($method === 'POST') {
            $response = wp_remote_post($this->baseUrl . $endPoint, [
                'body' => wp_json_encode($fields),
                'headers'     => [
                    'Content-Type' => 'application/json',
                ]
            ]);
        } else {
            return [null, ''];
        }

        /** @var int|string $httpCode Http status code or empty string */
        $httpCode = wp_remote_retrieve_response_code($response);
        /** @var string $body */
        $body = wp_remote_retrieve_body($response);

        return [
            $httpCode ?: null,
            $body
        ];
    }

}
