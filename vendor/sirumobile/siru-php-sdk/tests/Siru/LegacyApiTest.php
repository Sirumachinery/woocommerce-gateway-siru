<?php
namespace Siru\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Siru\LegacyApi;
use Siru\Transport\GuzzleTransport;
use Siru\Transport\TransportInterface;

class LegacyApiTest extends TestCase
{

    /**
     * @var LegacyApi|MockObject
     */
    private $api;

    public function setUp()
    {
        parent::setUp();
        $this->api = $this->getMockForAbstractClass(LegacyApi::class);
    }

    /**
     * @test
     */
    public function gettingGuzzleClientChangesTransport()
    {
        if (interface_exists('\GuzzleHttp\ClientInterface') === false) {
            $this->markTestSkipped('Guzzle client is not available.');
        }
#        $url = 'https://siru.tunk.io';

        $initialTransport = $this->createMock(TransportInterface::class);
        $this->api->expects($this->once())
            ->method('getTransport')
            ->willReturn($initialTransport);
        $this->api->expects($this->once())
            ->method('setTransport')
            ->with($this->isInstanceOf(GuzzleTransport::class));

        $client = $this->api->getGuzzleClient();

        $this->assertInstanceOf(ClientInterface::class, $client);

/*        $config = self::getProperty($client, 'config');
        $this->assertArrayHasKey('base_uri', $config);
        $this->assertEquals($url, $config['base_uri']);*/
    }

    /**
     * @test
     */
    public function settingGuzzleClientChangesTransport()
    {
        if (interface_exists('\GuzzleHttp\ClientInterface') === false) {
            $this->markTestSkipped('Guzzle client is not available.');
        }

        $initialTransport = $this->createMock(TransportInterface::class);
        $this->api->expects($this->once())
            ->method('getTransport')
            ->willReturn($initialTransport);
        $this->api->expects($this->once())
            ->method('setTransport')
            ->with($this->isInstanceOf(GuzzleTransport::class));

        $this->api->setGuzzleClient(new Client());
    }

    /**
     * @test
     */
    public function doesNothingIfGuzzleTransportIsAlreadyInUse()
    {
        if (interface_exists('\GuzzleHttp\ClientInterface') === false) {
            $this->markTestSkipped('Guzzle client is not available.');
        }
        $initialTransport = $this->createMock(GuzzleTransport::class);
        $this->api->expects($this->exactly(2))
            ->method('getTransport')
            ->willReturn($initialTransport);
        $this->api->expects($this->never())
            ->method('setTransport');

        $this->api->getGuzzleClient();
        $this->api->setGuzzleClient(new Client());
    }

    /**
     * Retrieves the value of the property, overcoming visibility problems.
     *
     * @param mixed $object
     * @param string $propertyName A property name
     * @return mixed
     * @throws \ReflectionException
     */
    protected static function getProperty($object, $propertyName)
    {
        $cls = new \ReflectionClass($object);
        $prop = $cls->getProperty($propertyName);
        $prop->setAccessible(true);
        $value = $prop->getValue($object);
        $prop->setAccessible(false);
        return $value;
    }

}
