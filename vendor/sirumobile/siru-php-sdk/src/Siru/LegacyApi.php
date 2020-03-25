<?php

namespace Siru;

use GuzzleHttp\ClientInterface;
use Siru\Transport\GuzzleTransport;
use Siru\Transport\TransportInterface;

abstract class LegacyApi
{

    /**
     * @return TransportInterface
     */
    abstract protected function getTransport() : TransportInterface;

    /**
     * @param TransportInterface $transport
     */
    abstract protected function setTransport(TransportInterface $transport);

    /**
     * @return ClientInterface
     * @deprecated Use TransportFactory instead
     */
    public function getGuzzleClient() : ClientInterface
    {
        $transport = $this->getTransport();
        if (!$transport instanceof GuzzleTransport) {
            $transport = new GuzzleTransport();
            $this->setTransport($transport);
        }
        return $transport->getGuzzleClient();
    }

    /**
     * Sets guzzle client that will be used for API requests.
     * Note that setting the client here will override selected endpoint URL.
     *
     * @param ClientInterface $client
     * @deprecated Use TransportFactory instead
     */
    public function setGuzzleClient(ClientInterface $client)
    {
        $transport = $this->getTransport();
        if (!$transport instanceof GuzzleTransport) {
            $transport = new GuzzleTransport();
            $this->setTransport($transport);
        }
        $transport->setGuzzleClient($client);
    }

}