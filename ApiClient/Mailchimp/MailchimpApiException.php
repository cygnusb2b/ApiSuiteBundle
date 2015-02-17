<?php

namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp;

use \Exception;

class MailchimpApiException extends Exception
{
    protected $name;

    protected $response;

    public function __construct($name = '', $message = '', $code = 0, \Exception $previous = null)
    {
        $this->name = $name;
        parent::__construct($message, (Integer) $code, $previous);
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }
}
