<?php

namespace Cygnus\ApiSuiteBundle\ApiClient\ExactTarget;

use \ET_Client;
use Cygnus\ApiSuiteBundle\ApiClient\ExactTarget\Resources\AbstractResource;
use Symfony\Component\HttpFoundation\ParameterBag;

require_once 'SDK/ET_Client.php';

class ApiClientExactTarget
{
    /**
     * The configuration options.
     *
     * @var ParameterBag
     */
    protected $config;

    /**
     * The ExactTarget Fuel API Client.
     *
     * @var ET_Client
     */
    protected $client;

    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = [
        'clientid',
        'clientsecret',
    ];

    /**
     * Constructor. Sets the configuration for this API client instance
     *
     * @param   array   $config     The config options
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->setResources();
    }

    /**
     * Sets all API resources, such as lists, campaigns, etc.
     *
     * @return  self
     */
    protected function setResources()
    {
        $namespace = 'Cygnus\\ApiSuiteBundle\\ApiClient\\ExactTarget\\Resources';
        $resources = [
            'data-extensions'           => 'DataExtensions',
            'data-extension-columns'    => 'DataExtensionColumns',
            'data-extension-rows'       => 'DataExtensionRows',
            'subscribers'               => 'Subscribers',
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
     * @param   string              $key
     * @param   AbstractResource    $resource
     * @return  self
     */
    protected function addResource($key, AbstractResource $resource)
    {
        $this->resources[$key] = $resource;
        return $this;
    }

    /**
     * Gets an API resource.
     *
     * @param   string  $key
     * @return  AbstractResource
     * @throws  \RuntimeException If resource is not found.
     */
    public function getResource($key)
    {
        if (false === $this->hasResource($key)) {
            throw new \RuntimeException(sprintf('No ExactTarget Fuel API resource exists for "%s"', $key));
        }
        return $this->resources[$key];
    }

    /**
     * Determines if the API client as a resource.
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasResource($key)
    {
        return isset($this->resources[$key]);
    }

    /**
     * Sets the configuration options for this API client
     *
     * @param   array   $config     The config options
     * @return  self
     */
    public function setConfig(array $config)
    {
        $this->config = new ParameterBag($config);
        if (false === $this->config->has('defaultwsdl')) {
            $this->config->set('defaultwsdl', 'https://webservice.exacttarget.com/etframework.wsdl');
        }

        if (false === $this->config->has('appsignature')) {
            $this->config->set('appsignature', 'none');
        }

        if (false === $this->config->has('debug')) {
            $this->config->set('debug', false);
        }

        if (false === $this->config->has('xmlloc')) {
            $location = sprintf('%s/ExactTargetWSDL.xml', sys_get_temp_dir());
            $this->config->set('xmlloc', $location);
        }
        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getClient()
    {
        if (null === $this->client) {
            $this->initClient();
        }
        return $this->client;
    }

    protected function initClient()
    {
        if (false === $this->hasValidConfig()) {
            throw new \RuntimeException('The API client configuration is not valid.');
        }
        $this->client = new ET_Client(true, (Boolean) $this->config->get('debug'), $this->config->all());
        return $this;
    }

    /**
     * Determines if the API instance has a valid configuration
     *
     * @return  bool
     */
    public function hasValidConfig()
    {
        foreach ($this->requiredConfigOptions as $option) {
            if (!$this->config->has($option)) return false;
        }
        return true;
    }
}
