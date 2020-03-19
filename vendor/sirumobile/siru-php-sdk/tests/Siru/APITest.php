<?php
namespace Siru\Tests;

use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Siru\API;
use Siru\Signature;

class APITest extends TestCase
{

    /**
     * @var Signature
     */
    private $signature;

    /**
     * @var API
     */
    private $api;

    public function setUp()
    {
        $this->signature = new Signature(1, 'xooxer');
        $this->api = new API($this->signature);
    }

    /**
     * @test
     */
    public function merchantIdIsDetectedFromSignature()
    {
        $this->assertEquals($this->signature->getMerchantId(), $this->api->getDefaults('merchantId'), 'MerchantId was not automatically set from signature.');
    }

    /**
     * @test
     */
    public function endPointIsSetCorrectly()
    {
        $this->api->useProductionEndpoint();
        $this->assertEquals(API::ENDPOINT_PRODUCTION, $this->api->getEndpointUrl(), 'Endpoint did not change as expected.');

        $this->api->useStagingEndpoint();
        $this->assertEquals(API::ENDPOINT_STAGING, $this->api->getEndpointUrl(), 'Endpoint did not change as expected.');

        $this->api->setEndpointUrl('https://siru.tunk.io');
        $this->assertEquals('https://siru.tunk.io', $this->api->getEndpointUrl(), 'Endpoint did not change as expected.');
    }

    /**
     * @test
     */
    public function canSetDefaultValuesForRequest()
    {
        $this->api->setDefaults([
            'xoo' => 'xer',
            'foo' => 'bar'
        ]);

        $this->assertEquals('xer', $this->api->getDefaults('xoo'), 'Request default value was not set correctly.');
        $this->assertEquals('bar', $this->api->getDefaults('foo'), 'Request default value was not set correctly.');
        $this->assertEquals(null, $this->api->getDefaults('unknown'), 'Unknown value should return null as default value.');

        $this->api->setDefaults('xoo', 'lusso');
        $this->api->setDefaults('lorem', 'ipsum');
        $this->assertEquals('lusso', $this->api->getDefaults('xoo'), 'Request default value was not set correctly.');
        $this->assertEquals('ipsum', $this->api->getDefaults('lorem'), 'Request default value was not set correctly.');

        $this->api->setDefaults('lorem', null);
        $this->assertEquals(null, $this->api->getDefaults('lorem'), 'Unknown value should return null as default value.');

        $allDefaults = $this->api->getDefaults();
        $expected = [
            'merchantId' => $this->signature->getMerchantId(),
            'xoo' => 'lusso',
            'foo' => 'bar'
        ];

        foreach($expected as $key => $value) {
            $this->assertArrayHasKey($key, $allDefaults);
            $this->assertEquals($value, $allDefaults[$key]);
        }
    }

    /**
     * @test
     */
    public function createsDefaultGuzzleClient()
    {
        $url = 'https://siru.tunk.io';

        $this->api->setEndpointUrl($url);
        $client = $this->api->getGuzzleClient();

        $this->assertInstanceOf(ClientInterface::class, $client);

        $config = self::getProperty($client, 'config');
        $this->assertArrayHasKey('base_uri', $config);
        $this->assertEquals($url, $config['base_uri']);
    }

    /**
     * @test
     */
    public function canOverrideGuzzleClient()
    {
        $mock = $this->createMock(ClientInterface::class);
        $this->api->setGuzzleClient($mock);

        $this->assertSame($mock, $this->api->getGuzzleClient());
    }

    /**
     * @test
     */
    public function returnsExpectedApiClasses()
    {
        $this->assertInstanceOf(API\Payment::class, $this->api->getPaymentApi());
        $this->assertInstanceOf(API\PurchaseStatus::class, $this->api->getPurchaseStatusApi());
        $this->assertInstanceOf(API\FeaturePhone::class, $this->api->getFeaturePhoneApi());
        $this->assertInstanceOf(API\OperationalStatus::class, $this->api->getOperationalStatusApi());
        $this->assertInstanceOf(API\Price::class, $this->api->getPriceApi());
        $this->assertInstanceOf(API\Kyc::class, $this->api->getKycApi());
    }

    /**
     * Retrieves the value of the property, overcoming visibility problems.
     *
     * @param mixed $object
     * @param string $propertyName A property name
     * @return mixed
     * @throws \ReflectionException
     */
    public static function getProperty($object, $propertyName)
    {
        $cls = new \ReflectionClass($object);
        $prop = $cls->getProperty($propertyName);
        $prop->setAccessible(true);
        $value = $prop->getValue($object);
        $prop->setAccessible(false);
        return $value;
    }

}
