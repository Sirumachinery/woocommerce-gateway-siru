<?php
namespace Siru\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Siru\Exception\ApiException;

class ApiExceptionTest extends TestCase
{

    /**
     * @test
     */
    public function responseBodyIsAvailable()
    {
        $body = 'response text';
        $exception = new ApiException('error', 123,null, $body);

        $this->assertEquals($body, $exception->getResponseBody());
    }

    /**
     * @test
     */
    public function errorStackIsAvailable()
    {
        $stack = [
            'xooxer'
        ];
        $exception = new ApiException('error', 123,null, '', $stack);

        $this->assertEquals($stack, $exception->getErrorStack());
    }

}
