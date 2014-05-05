<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Base2;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;
use Symfony\Component\HttpFoundation\Cookie;

class ApiClientBase2 extends ApiClientAbstract
{
    const BASE_ENDPOINT = 'api/v2';

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
    protected $requiredConfigOptions = ['host', 'user', 'key'];

    protected $responseCache = array();

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
     * Performs a Content lookup by ID
     *
     * @param  string|int $contentId The content id to lookup
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function contentLookup($contentId)
    {
        $endpoint = sprintf('/content/%s', $contentId);
        $response = $this->handleRequest($endpoint);
    }

    /**
     * Performs a channel lookup by channel type and pub
     *
     * @param  string $channelType The channel type, such as website
     * @param  string $pub         The publication, such as emsr
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function channelLookup($channelType, $pub)
    {
        $endpoint = '/channel';
        $parameters = array(
            'channel'   => strtolower($channelType),
            'pub'       => strtolower($pub),
        );
        return $this->handleRequest($endpoint, $parameters);
    }

    /**
     * Performs a vocab lookup by vocab key (e.g. fcp_categories) and pub
     *
     * @param  string $vocab        The vocab key
     * @param  string $pub          The publication, such as emsr
     * @param  bool   $includeTerms Whether to include all terms
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function vocabLookup($vocab, $pub, $terms = false)
    {
        $endpoint = '/vocab';
        $parameters = array(
            'vocab'     => strtolower($vocab),
            'pub'       => strtolower($pub),
        );
        if ($terms === true) {
            $parameters['terms'] = true;
        }
        return $this->handleRequest($endpoint, $parameters);
    }

    /**
     * Performs a Term Vocab lookup by ID or set of IDs
     *
     * @param  string|int|array $termVocabId The term vocab id to lookup
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function termVocabLookup($termVocabId)
    {
        $endpoint = '/term_vocab';

        if (is_string($termVocabId)) {
            $termVocabIds = explode(',', $termVocabId);
        } elseif (is_array($termVocabId)) {
            $termVocabIds = $termVocabId;
        } else {
            $termVocabIds = (array) $termVocabId;
        }

        $parameters = array(
            'term_vocab_id' => implode(',', $termVocabIds)
        );
        return $this->handleRequest($endpoint, $parameters);
    }

    /**
     * Handles a request by creating a Request object and sending it to the Kernel
     *
     * @param  string $endpoint   The API endpoint
     * @param  array  $parameters The request parameters
     * @param  string $method     The request method
     * @return Symfony\Component\HttpFoundation\Response
     */
    protected function handleRequest($endpoint, array $parameters = array(), $method = 'GET')
    {
        $request = $this->createRequest($endpoint, $parameters, $method);

        $cacheKey = $request->getRequestUri();

        if (array_key_exists($cacheKey, $this->responseCache)) {
            // Pull the parsed response from cache
            return $this->responseCache[$cacheKey];
        }

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
            $this->responseCache[$cacheKey] = @json_decode($response->getContent(), true);
            return $this->responseCache[$cacheKey];
        }
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
    protected function createRequest($endpoint, array $parameters = array(), $method = 'GET')
    {
        if ($this->hasValidConfig()) {

            $method = strtoupper($method);
            if (!in_array($method, $this->supportedMethods)) {
                // Request method not allowed by the API
                throw new \Exception(sprintf('The request method %s is not allowed. Only %s methods are supported.'), $method, implode(', ', $this->supportedMethods));
            }

            // Create initial request object
            $request = $this->httpKernel->createSimpleRequest($this->getUri($endpoint), $method, $parameters);

            // Set default headers
            $headers = array(
                'x-base-user'   => $this->config->get('user'),
                'x-base-key'    => $this->config->get('key'),
            );

            // Add the headers to the request
            $request->headers->add($headers);

            // echo '<pre>';
            // var_dump($request);
            // die();

            return $request;
        } else {
            throw new \Exception(sprintf('The Base2 API configuration is not valid. The following options must be set: %s', implode(', ', $this->requiredConfigOptions)));
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
