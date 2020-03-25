<?php
namespace Siru\API;

use Siru\Exception\ApiException;
use Siru\Exception\InvalidResponseException;

/**
 * API for checking if given IP-address is allowed to use variant2 mobile payments.
 */
class FeaturePhone extends AbstractAPI
{

    /**
     * @param  string  $ip IPv4 address
     * @return bool        True if variant2 payments are possible from this IP-address
     * @throws InvalidResponseException
     * @throws ApiException
     */
    public function isFeaturePhoneIP(string $ip) : bool
    {
        $signedFields = $this->signature->signMessage([ 'ip' => $ip ]);

        list($httpStatus, $body) = $this->transport->request($signedFields, '/payment/ip/feature-check');

        // Validate response
        $json = $this->parseJson($body);

        if($httpStatus <> 200) {
            throw $this->createException($httpStatus, $json, $body);
        }

        return $json['ipPaymentsEnabled'] == true;
    }

    private function createException($httpCode, $json, $body) : ApiException
    {
        if(isset($json['error']['message'])) {
            $message = $json['error']['message'];
        } else {
            $message = 'Unknown error';
        }

        return new ApiException($message, 0, null, $body);
    }

}
