<?php
namespace Siru\API;

/**
 * Checks Siru API operational status.
 */
class OperationalStatus extends AbstractAPI {
    
    /**
     * Sends request to status API and returns HTTP status code.
     * 
     * 200 Siru Mobile is up and all should be well.
     * 503 Siru Mobile is temporarily down for maintenance. 
     * 500 There is a problem with Siru Mobile API.
     * 
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function check()
    {
        list($httpStatus) = $this->send('/status', 'GET');

        return (int) $httpStatus;
    }

}
