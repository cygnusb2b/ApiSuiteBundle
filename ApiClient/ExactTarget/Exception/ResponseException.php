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
            $message = sprintf('Root Message: %s', $response->message);
        } else {
            if (isset($response->results) && is_array($response->results)) {
                foreach ($response->results as $row) {
                    if (isset($row->ErrorMessage) && !empty($row->ErrorMessage)) {
                        $message = sprintf('Row ErrorMessage: %s', $row->ErrorMessage);
                        break;
                    }
                    if (isset($row->ValueErrors)) {
                        $message = sprintf('Row ValueErrors: %s', json_encode($row->ValueErrors));
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
