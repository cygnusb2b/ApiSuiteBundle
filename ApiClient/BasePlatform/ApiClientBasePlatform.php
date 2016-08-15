<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\BasePlatform;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Cygnus\ApiSuiteBundle\ApiClient\CacheableInterface;
use Cygnus\ApiSuiteBundle\Traits\CacheTraitRedis;

class ApiClientBasePlatform extends ApiClientAbstract implements CacheableInterface
{
    use CacheTraitRedis;

    const BASE_ENDPOINT = 'api/2.0rcpi';

    /**
     * An array of request methods that this API supports
     *
     * @var array
     */
    protected $supportedMethods = ['GET', 'POST'];

    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = ['host'];

    /**
     * Constructor. Sets the configuration for this Omeda API client instance
     *
     * @param  array $config The config options
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->setConfig($config);
    }

    /**
     * Performs a contract search against the sales tool api
     *
     * @param  string $search The search string to pass
     * @return array  The decodeed json response
     */
    public function contractsLookup($queryString, array $fields = [])
    {
        $parameters = [];
        if (!empty($fields)) {
            $parameters['include'] = implode(',', $fields);
        }
        $endpoint = '/search/contracts/contract';

        $content = [
            'data'  => [
                'query' => [
                    'query_string'  => [
                        'query' => $queryString
                    ]
                ]
            ]
        ];
        $content = json_encode($content);

        return $this->handleRequest($endpoint, 'POST', $parameters, $content);
    }

    /**
     * Handles a request by creating a Request object and sending it to the Kernel
     *
     * @param  string $endpoint   The API endpoint
     * @param  array  $parameters The request parameters
     * @param  string $method     The request method
     * @return Symfony\Component\HttpFoundation\Response
     */
    protected function handleRequest($endpoint, $method = 'GET', array $parameters = [], $content = null, $ttl = 0)
    {
        $request = $this->createRequest($endpoint, $method, $parameters, $content);

        // Generate Cache Key
        $cacheKey = $this->generateCacheKey($request);

        if (!is_null($parsedResponse = $this->getCache($cacheKey))) {
            // Parsed response found in cache. Return it.
            // return $parsedResponse;
        }

        // Only perform retries for non-modifying methods
        $retryLimit = (in_array($method, array('GET', 'OPTIONS', 'HEAD')))
            ? 3
            : 0;

        return $this->retry(function() use($request, $cacheKey, $ttl) {

            // Get the API response object
            $response = $this->doRequest($request);
            $baseError = sprintf('Unable to complete API request "%s" with errors:', $request->getRequestUri());

            if ($response->isClientError()) {
                // Client error, parse response and throw exception
                $content = @json_decode($response->getContent(), true);

                if (is_array($content) && array_key_exists('errors', $content)) {
                    throw new \Exception(sprintf('%s %s', $baseError, implode(', ', $content['errors'])));
                } else {
                    throw new \Exception(sprintf('%s An unknown client-side error has occurred.', $baseError));
                }

            } elseif ($response->isServerError()) {
                // Server error, throw generic exception
                throw new \Exception(sprintf('%s An unknown server-side error has occurred.', $baseError));
            } elseif ($response->isSuccessful()) {
                // Ok. Parse JSON response, cache and return
                $parsedResponse = @json_decode($response->getContent(), true);

                $this->setCache($cacheKey, $parsedResponse, $ttl);
                return $parsedResponse;
            }
        }, $retryLimit);
    }

    /**
     * Creates a new Request object based on API method parameters
     * This should return a Response object
     *
     * @param  string $endpoint   The API endpoint
     * @param  array  $parameters The request parameters
     * @param  string $method     The request method
     * @return Symfony\Component\HttpFoundation\Request
     * @throws \Exception If the API configuration is invalid, or a non-allowed request method is passed
     */
    protected function createRequest($endpoint, $method = 'GET', array $parameters = [], $content = null)
    {
        if ($this->hasValidConfig()) {
            $method = strtoupper($method);
            if (!in_array($method, $this->supportedMethods)) {
                // Request method not allowed by the API
                throw new \Exception(sprintf('The request method %s is not allowed. Only %s methods are supported.'), $method, implode(', ', $this->supportedMethods));
            }

            // Create initial request object
            $request = $this->httpKernel->createSimpleRequest($this->getUri($endpoint), $method, $parameters, $content);

            // Set default headers
            $headers = [
                'Content-Type'  => 'application/json',
            ];

            // Add the headers to the request
            $request->headers->add($headers);

            return $request;
        } else {
            throw new \Exception(sprintf('The Base Platform API configuration is not valid. The following options must be set: %s', implode(', ', $this->requiredConfigOptions)));
        }
    }

    /**
     * Gets the full request URI based on an API endpoint
     *
     * @param  string $endpoint The API endpoint
     */
    public function getUri($endpoint)
    {
        return sprintf('http://%s/%s/%s', $this->getHost(), self::BASE_ENDPOINT, ltrim($endpoint, '/'));
    }

    /**
     * Gets the API hostname
     *
     * @return string
     */
    public function getHost()
    {
        return str_replace(['http://', 'https://'], '', trim($this->config->get('host'), '/'));
    }
}
