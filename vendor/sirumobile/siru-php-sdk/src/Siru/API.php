<?php
namespace Siru;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Siru\API\FeaturePhone;
use Siru\API\Kyc;
use Siru\API\OperationalStatus;
use Siru\API\Payment;
use Siru\API\Price;
use Siru\API\PurchaseStatus;

/**
 * This class is used to create instances of different API objects which in turn are used to call different API methods.
 */
class API {

    const ENDPOINT_STAGING = 'https://staging.sirumobile.com';
    const ENDPOINT_PRODUCTION = 'https://payment.sirumobile.com';

    /**
     * @var ClientInterface|null
     */
    private $guzzleClient;
    
    /**
     * Signature creator.
     * @var Signature
     */
    private $signature;

    /**
     * Siru API endpoint host name
     * @var string
     */
    private $endPoint;

    /**
     * Default values for payment requests.
     * @var array
     */
    private $defaults = [];

    /**
     * @param Signature $signature
     */
    public function __construct(Signature $signature)
    {
        $this->signature = $signature;
        $this->useStagingEndpoint();
        $this->setDefaults('merchantId', $signature->getMerchantId());
    }

    /**
     * Send API requests to Siru staging endpoint. Used for testing during integration.
     * 
     * @return API
     */
    public function useStagingEndpoint()
    {
        return $this->setEndpointUrl(self::ENDPOINT_STAGING);
    }

    /**
     * Send API requests to Siru production endpoint. You must explicitly call this method when in live environment.
     * 
     * @return API
     */
    public function useProductionEndpoint()
    {
        return $this->setEndpointUrl(self::ENDPOINT_PRODUCTION);
    }

    public function setEndpointUrl($url)
    {
        $this->endPoint = $url;

        return $this;
    }

    public function getEndpointUrl()
    {
        return $this->endPoint;
    }

    /**
     * Sets default values for payment requests.
     *
     * You can pass all values as an array with key/value pairs or you can give
     * field name as first parameter and value as second parameter.
     * Using NULL as value will remove the default value.
     *
     * @param   string|array $keyOrArray Field name or array of field/value pairs
     * @param   string|null  $value      Field value when $keyOrArray is a string
     * @return  API
     */
    public function setDefaults($keyOrArray, $value = null)
    {
        if(is_array($keyOrArray) || is_object($keyOrArray)) {

            foreach($keyOrArray as $k => $v) {
                $this->setDefaults($k, $v);
            }

        } else {
            if($value === null) {
                unset($this->defaults[$keyOrArray]);
            } else {
                $this->defaults[$keyOrArray] = $value;
            }
        }

        return $this;
    }

    /**
     * Returns default values or a single default value for payment requests.
     * 
     * @param  string|null $key Field name or null to return all defaults as an array
     * @return string|null|array
     */
    public function getDefaults($key = null)
    {
        if($key) {
            return array_key_exists($key, $this->defaults) ? $this->defaults[$key] : null;
        }

        return $this->defaults;
    }

    /**
     * @return Client
     */
    public function getGuzzleClient()
    {
        if ($this->guzzleClient === null) {
            $this->guzzleClient = new Client(['base_uri' => $this->endPoint, 'verify' => false]);
        }
        return $this->guzzleClient;
    }

    /**
     * Sets guzzle client that will be used for API requests.
     * Note that setting the client here will override selected endpoint URL.
     *
     * @param ClientInterface $client
     */
    public function setGuzzleClient(ClientInterface $client)
    {
        $this->guzzleClient = $client;
    }

    /**
     * Returns Payment API object.
     * All default values set using setDefaults() are automatically passed to Payment API object.
     * 
     * @return Payment
     */
    public function getPaymentApi()
    {
        $api = new Payment($this->signature, $this->getGuzzleClient());

        array_walk($this->defaults, function($value, $key) use ($api) {
            $api->set($key, $value);
        });

        return $api;
    }

    /**
     * Returns Purchase status API object. Used for retrieving single payment status or search payments.
     *
     * @return PurchaseStatus
     */
    public function getPurchaseStatusApi()
    {
        $api = new PurchaseStatus($this->signature, $this->getGuzzleClient());

        return $api;
    }

    /**
     * Returns KYC API object. Used for KYC data of successful payments.
     *
     * @return Kyc
     */
    public function getKycApi()
    {
        $api = new Kyc($this->signature, $this->getGuzzleClient());

        return $api;
    }

    /**
     * Returns Price API object. Used for calculating final call price for variant1 payments if needed.
     * 
     * @return Price
     */
    public function getPriceApi()
    {
        $api = new Price($this->signature, $this->getGuzzleClient());

        return $api;
    }

    /**
     * Returns Feature detection API object. Used to check if variant2 payments are possible for given IP-address.
     * 
     * @return FeaturePhone
     */
    public function getFeaturePhoneApi()
    {
        $api = new FeaturePhone($this->signature, $this->getGuzzleClient());

        return $api;
    }

    /**
     * Returns Operational status API which can be used to check if Siru API is up.
     * 
     * @return OperationalStatus
     */
    public function getOperationalStatusApi()
    {
        $api = new OperationalStatus($this->signature, $this->getGuzzleClient());

        return $api;
    }

}
