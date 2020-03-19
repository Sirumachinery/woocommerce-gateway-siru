<?php
namespace Siru\API;

use Siru\Exception\ApiException;
use Siru\Exception\InvalidResponseException;

/**
 * Siru KYC API methods.
 */
class Kyc extends AbstractAPI {
    
    /**
     * Lookup end-user KYC report by purchase UUID.
     *
     * Note: that if purchase is not found or KYC data has expired, method throws ApiException.
     *
     * Example response array:
     * Array
     * (
     *     [report] => Array
     *     (
     *         [firstName] => James,
     *         [lastName] => Smith
     *     )
     * )
     * 
     * @param  string $uuid Uuid received from Payment API
     * @return array        KYC data as an array
     * @throws InvalidResponseException
     * @throws ApiException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function findKycByUuid($uuid)
    {
        $fields = $this->signature->signMessage([ 'uuid' => $uuid ]);

        list($httpStatus, $body) = $this->send('/payment/kyc', 'GET', $fields);

        return $this->parseResponse($httpStatus, $body);
    }

    /**
     * Checks HTTP status code and parses response body to JSON.
     * 
     * @param  int    $httpStatus
     * @param  string $body
     * @return array
     * @throws InvalidResponseException
     * @throws ApiException
     */
    private function parseResponse($httpStatus, $body)
    {
        $json = $this->parseJson($body);

        if($httpStatus <> 200) {
            throw $this->createException($httpStatus, $json, $body);
        }
        
        return $json;        
    }

    /**
     * Creates an exception if error has occured.
     * 
     * @param  int            $httpStatus
     * @param  array          $json
     * @param  string         $body
     * @return ApiException
     */
    private function createException($httpStatus, $json, $body)
    {
        if(isset($json['error']['message'])) {
            $message = $json['error']['message'];
        } else {
            $message = 'Unknown error';
        }

        return new ApiException($message, 0, null, $body);
    }

}
