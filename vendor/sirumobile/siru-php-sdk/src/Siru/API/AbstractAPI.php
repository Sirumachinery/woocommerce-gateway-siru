<?php
namespace Siru\API;

use Siru\Exception\InvalidResponseException;
use Siru\Signature;
use Siru\Transport\TransportInterface;

/**
 * Base class for each Siru API class.
 */
abstract class AbstractAPI
{
    
    /**
     * Signature creator.
     * @var Signature
     */
    protected $signature;

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @param Signature $signature
     * @param TransportInterface $transport
     */
    public function __construct(Signature $signature, TransportInterface $transport)
    {
        $this->signature = $signature;
        $this->transport = $transport;
    }

    /**
     * Tries to convert JSON string to array.
     * 
     * @param  string $body
     * @return array|false
     * @throws InvalidResponseException
     */
    protected function parseJson(string $body)
    {
        if(empty($body) === false) {
            $json = json_decode($body, true);
        }

        if(empty($json) === true) {
            throw new InvalidResponseException("Invalid response from API", 0, null, $body);
        }

        return $json;
    }

}
