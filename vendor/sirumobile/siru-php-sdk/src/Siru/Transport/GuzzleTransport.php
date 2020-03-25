<?php

namespace Siru\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class GuzzleTransport implements TransportInterface
{

    /**
     * GuzzleHttp client for making requests.
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $baseUrl = '';

    /**
     * @param ClientInterface $client
     * @internal
     */
    public function setGuzzleClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return ClientInterface
     * @internal
     */
    public function getGuzzleClient() : ClientInterface
    {
        if ($this->client === null) {
            $this->client = new Client(['verify' => false]);
        }
        return $this->client;
    }

    /**
     * @inheritDoc
     */
    public function setBaseUrl(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @inheritDoc
     */
    public function request(array $fields, string $endPoint, string $method = 'GET') : array
    {
        $options = [];
        if ($method === 'GET') {
            $options['query'] = $fields;
        } elseif ($method === 'POST') {
            $options['json'] = $fields;
        }

        try {
            $response = $this->getGuzzleClient()->request($method, $this->baseUrl . $endPoint, $options);
            return [
                $response->getStatusCode(),
                $response->getBody()
            ];
        } catch(RequestException $e) {
            $response = $e->getResponse();
            if ($response !== null) {
                return [
                    $response->getStatusCode(),
                    $response->getBody()
                ];
            }
        } catch(GuzzleException $e) {

        }
        return [null, ''];
    }

}