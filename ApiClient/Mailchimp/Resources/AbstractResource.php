<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Resources;

use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\ApiClientMailchimp;

abstract class AbstractResource
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var ApiClientMailchimp
     */
    protected $root;

    /**
     * Constructor.
     *
     * @param ApiClientMailchimp    $root
     */
    public function __construct($key, ApiClientMailchimp $root)
    {
        $this->key = $key;
        $this->root = $root;
    }

    /**
     * Gets the API endpoint for this resource, based on the provided action.
     *
     * @param  string   $action
     * @return string
     */
    public function getEndpoint($action)
    {
        return sprintf('/%s/%s', $this->key, trim($action, '/'));
    }

    /**
     * Sends an API request.
     *
     */
    public function sendRequest($action, array $body)
    {
        $endpoint = $this->getEndpoint($action);
        return $this->root->handleRequest($endpoint, $body);
    }
}
