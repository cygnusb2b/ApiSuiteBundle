<?php

namespace Cygnus\ApiSuiteBundle\ApiClient\ExactTarget\Exception;

use \Exception;

class ResponseException extends Exception
{
    /**
     * @var array
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param object    $response   The ExactTarget API SDK response.
     */
    public function __construct($response)
    {
        $this->response = $response;

        $message = 'An unknown error occurred';
        if (isset($response->message)) {
            $message = $response->message;
        } else {
            if (isset($response->results) && is_array($response->results)) {
                foreach ($response->results as $row) {
                    if (isset($row->ErrorMessage)) {
                        $message = $row->ErrorMessage;
                        break;
                    }
                }
            }
        }

        $code = isset($response->code) ? (Integer) $response->code : 0;
        parent::__construct($message, $code);
    }

    /**
     * Gets the API response body.
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }
}
