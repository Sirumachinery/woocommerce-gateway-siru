<?php
namespace Siru\Tests\API;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Siru\API\FeaturePhone;
use Siru\Signature;
use Siru\Transport\TransportInterface;

class FeaturePhoneTest extends TestCase
{

    /**
     * @var Signature
     */
    private $signature;

    /**
     * @var FeaturePhone
     */
    private $api;

    /**
     * @var TransportInterface|MockObject
     */
    private $transport;

    public function setUp()
    {
        $this->signature = new Signature(1, 'xooxer');
        $this->transport = $this->createMock(TransportInterface::class);
        $this->api = new FeaturePhone($this->signature, $this->transport);
    }

    /**
     * @test
     */
    public function ipIsFeaturePhone()
    {
        $ip = '1.1.1.1';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) use ($ip) {
                return isset($fields['ip']) &&
                    isset($fields['merchantId']) &&
                    isset($fields['signature']) &&
                    $fields['ip'] === $ip &&
                    $fields['merchantId'] === 1;
            }), '/payment/ip/feature-check')
            ->willReturn([
                200,
                '{"ipPaymentsEnabled":true}'
            ]);

        $this->assertTrue($this->api->isFeaturePhoneIP($ip));
    }

    /**
     * @test
     */
    public function ipIsNotFeaturePhone()
    {
        $ip = '1.1.1.1';
        $this->transport
            ->expects($this->once())
            ->method('request')
            ->with($this->callback(function(array $fields) use ($ip) {
                return isset($fields['ip']) &&
                    isset($fields['merchantId']) &&
                    isset($fields['signature']) &&
                    $fields['ip'] === $ip &&
                    $fields['merchantId'] === 1;
            }), '/payment/ip/feature-check')
            ->willReturn([
                200,
                '{"ipPaymentsEnabled":false}'
            ]);

        $this->assertFalse($this->api->isFeaturePhoneIP($ip));
    }

}
