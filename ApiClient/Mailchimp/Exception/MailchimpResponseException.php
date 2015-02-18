<?php

namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception;

class MailchimpResponseException extends MailchimpException
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param array         $response   The Mailchimp API response.
     */
    public function __construct(array $response)
    {
        $this->response = $response;
        $this->name = isset($response['name']) ? $response['name'] : null;

        $message = isset($response['error']) ? $response['error'] : '';
        $code = isset($response['code']) ? (Integer) $response['code'] : 0;

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

    /**
     * Gets the API error name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
