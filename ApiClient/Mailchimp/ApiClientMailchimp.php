<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp;

use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpException;
use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpHttpException;
use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpResponseException;
use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Resources\AbstractResource;
use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Symfony\Component\HttpFoundation\Response;
use \DateTime;

class ApiClientMailchimp extends ApiClientAbstract
{
    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = ['apikey', 'host'];

    /**
     * An array of API resource objects.
     *
     * @var AbstractResource
     */
    protected $resources = [];

    /**
     * Constructor. Sets the configuration for this Omeda API client instance
     *
     * @param  array $config The config options
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->setConfig($config);
        $this->setResources();
    }

    /**
     * Sets all API resources, such as lists, campaigns, etc.
     *
     * @return self
     */
    public function setResources()
    {
        $namespace = 'Cygnus\\ApiSuiteBundle\\ApiClient\\Mailchimp\\Resources';
        $resources = [
            'campaigns' => 'Campaigns',
            'lists'     => 'Lists',
            'templates' => 'Templates',
        ];
        foreach ($resources as $key => $class) {
            $fqcn = sprintf('%s\\%s', $namespace, $class);
            $this->addResource($key, new $fqcn($key, $this));
        }
        return $this;
    }

    /**
     * Adds an API resource to the client.
     *
     * @param  string               $key
     * @param  AbstractResource     $resource
     * @return self
     */
    protected function addResource($key, AbstractResource $resource)
    {
        $this->resources[$key] = $resource;
        return $this;
    }

    /**
     * Gets an API resource
     *
     * @param  string   $key
     * @return AbstractResource
     * @throws \RuntimeException If resource is not found.
     */
    public function getResource($key)
    {
        if (false === $this->hasResource($key)) {
            throw new \RuntimeException(sprintf('No Mailchimp API resource exists for "%s"', $key));
        }
        return $this->resources[$key];
    }

    /**
     * Determines if the API client as a resource.
     *
     * @param  string   $key
     * @return bool
     */
    public function hasResource($key)
    {
        return isset($this->resources[$key]);
    }

    /**
     * Magic call method to access resources as an object method.
     *
     * @return AbstractResource
     */
    public function __call($name, array $args)
    {
        return $this->getResource($name);
    }

    /**
     * Magic get method to access resources as an object property.
     *
     * @return AbstractResource
     */
    public function __get($name)
    {
        return $this->getResource($name);
    }

    /**
     * Handles a request by creating a Request object and sending it to the Kernel
     *
     * @param  string $endpoint     The API endpoint
     * @param  array  $body         The request body content to use
     * @return array
     */
    public function handleRequest($endpoint, array $body = [])
    {
        $request = $this->createRequest($endpoint, $body);

        try {
            $response = $this->doRequest($request);
            $parsed = json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            throw new MailchimpHttpException('API Response processing failed. Unable to retrieve a response.', 0, $e);
        }

        if (!is_array($parsed)) {
            throw new MailchimpHttpException('API Response processing failed. Unable to parse the response.');
        }

        if (floor($response->getStatusCode() / 100) >= 4) {
            throw $this->handleException($parsed);
        }
        return $parsed;
    }

    /**
     * Handles API exceptions when errors are encountered in the response body.
     *
     * @param  array    $body
     * @return MailchimpResponseException
     * @throws MailchimpException
     */
    protected function handleException(array $body)
    {
        if ('error' !== $result['status'] || !$result['name']) {
            throw new MailchimpException(sprintf('An unexpected error was received: %s', json_encode($body)));
        }
        return new MailchimpResponseException($body);
    }

    /**
     * Creates a new Request object based on API method parameters
     * This should return a Response object
     *
     * @param  string $endpoint The API endpoint
     * @param  array  $body     The request body content to use
     * @return \Symfony\Component\HttpFoundation\Request
     * @throws \Exception If the API configuration is invalid, or a non-allowed request method is passed
     */
    protected function createRequest($endpoint, array $body)
    {
        if ($this->hasValidConfig()) {
            $body['apikey'] = $this->getApiKey();
            $content = @json_encode($body);
            // Create initial request object
            $request = $this->httpKernel->createSimpleRequest($this->getUri($endpoint), 'POST', array(), $content);
            return $request;
        } else {
            throw new \Exception(sprintf('The Mailchimp API configuration is not valid. The following options must be set: %s', implode(', ', $this->requiredConfigOptions)));
        }
    }

    /**
     * Gets the full request URI based on an API endpoint
     *
     * @param  string $endpoint The API endpoint
     * @return string The request URI
     */
    public function getUri($endpoint = null)
    {
        $uri = 'https://' . $this->getHost() . '/2.0';

        // Add the API endpoint, if sent
        if (null !== $endpoint) {
            $uri .= rtrim($endpoint, '/');
        }
        return $uri;
    }

    /**
     * Gets the batch size for batch operations (such as batch subscribe).
     *
     * @return int
     */
    public function getBatchSize()
    {
        if ($this->config->has('batchSize')) {
            return (Integer) $this->config->get('batchSize');
        }
        return 5000;
    }

    /**
     * Gets the API hostname
     *
     * @return string
     */
    public function getHost()
    {
        return trim($this->config->get('host'), '/');
    }

    /**
     * Gets the API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->config->get('apikey');
    }
}
