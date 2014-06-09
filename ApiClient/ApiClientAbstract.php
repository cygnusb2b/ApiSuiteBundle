<?php
namespace Cygnus\ApiSuiteBundle\ApiClient;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientInterface;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiClientAbstract implements ApiClientInterface
{
    /**
     * The remote HttpKernel for sending Request objects and receiving Response objects
     *
     * @var Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface
     */
    protected $httpKernel;

    /**
     * The configuration options
     *
     * @var Symfony\Component\HttpFoundation\ParameterBag
     */
    protected $config;

    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = array();

    /**
     * Runs a closure multiple times
     *
     * @param  Closure   $retry      The closure to retry
     * @param  int       $retryLimit The number of times to retry the closure
     *
     * @return Closure   Closure's result
     * @throws Exception Closure's exception
     */
    protected function retry(\Closure $retry, $retryLimit = 0)
    {
        $firstException = null;
        for ($i = 0; $i <= $retryLimit; $i++) {
            try {
                return $retry();
            } catch (\Exception $e) {
                if (!$firstException) {
                    $firstException = $e;
                }
                if ($i === $retryLimit) {
                    throw $firstException;
                }
            }
        }

        throw $e;
    }

    /**
     * Sets the remote RemoteKernelInterface for sending Request objects and returning Response objects
     *
     * @param  Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface $httpKernel
     * @return void
     */
    public function setRemoteHttpKernel(RemoteKernelInterface $httpKernel)
    {
        $this->httpKernel = $httpKernel;
    }

    /**
     * Sets the configuration options for this API client
     *
     * @param  array $config The config options
     * @return self
     */
    public function setConfig(array $config)
    {
        $this->config = new ParameterBag($config);
        return $this;
    }

    /**
     * Determines if the API instance has a valid configuration
     *
     * @return bool
     */
    public function hasValidConfig()
    {
        foreach ($this->requiredConfigOptions as $option) {
            if (!$this->config->has($option)) return false;
        }
        return true;
    }

    /**
     * Takes a Request object and performs the request via the RemoteKernelInterface
     * This should return a Response object
     *
     * @param  Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function doRequest(Request $request)
    {
        return $this->httpKernel->handle($request);
    }


}
