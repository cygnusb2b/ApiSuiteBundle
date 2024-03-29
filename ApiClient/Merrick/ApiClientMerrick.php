<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Merrick;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Cygnus\ApiSuiteBundle\ApiClient\CacheableInterface;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Cygnus\ApiSuiteBundle\Traits\CacheTraitRedis;

class ApiClientMerrick extends ApiClientAbstract implements CacheableInterface
{
    use CacheTraitRedis;

    const BASE_ENDPOINT = 'api';

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
     * Performs a channel lookup by channel id and pub
     *
     * @param  string $channelId   The channel id
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function channelLookupById($channelIds)
    {
        $endpoint = '/channel';
        $parameters = [];
        if (is_array($channelIds)) {
            $parameters = [
                'channel_id'    => implode('|', $channelIds),
            ];
        } else {
            $endpoint .= sprintf('/%s', $channelIds);
        }
        $response = $this->handleRequest($endpoint, $parameters);
        if (!is_array($channelIds) || count($channelIds) == 1) {
            $channelId = is_array($channelIds) ? reset($channelIds) : $channelIds;
            $response = ['status' => $response['status'], 'channel' => [$channelId => $response['channel']]];
        }
        return $response;
    }

    /**
     * Performs a count of pending changes in merrick.
     *
     * @param  string $shortName The merrick/base2 pubcode.
     * @return int    $count     The number of pending changes
     */
    public function pendingChangesCount($shortName)
    {
        $endpoint = '/content';
        $parameters = [
            'pending' => 'true',
            'content_type' => 'company|product',
            'pubgroup' => $shortName,
            'count' => 'true'
        ];
        $response = $this->handleRequest($endpoint, $parameters, 'GET', 60);

        if (!isset($response['content']) || !is_numeric($response['content'])) {
            return 0;
        }
        return (int) $response['content'];
    }

    /**
     * Performs a section lookup by channel id and pub
     *
     * @param  string $sectionId   The section id
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function sectionLookupById($sectionIds)
    {
        $endpoint = '/section';
        $parameters = [];
        if (is_array($sectionIds)) {
            $parameters = [
                'section_id'    => implode('|', $sectionIds),
            ];
        } else {
            $endpoint .= sprintf('/%s', $sectionIds);
        }
        $response = $this->handleRequest($endpoint, $parameters);
        return $response;
    }

    public function sectionLookupBySectionAndChannelId($channelId, $sectionId)
    {
        $endpoint = '/section';
        $parameters = [
            'channel_id'    => $channelId,
            'section_id'    => $sectionId,
        ];
        return $this->handleRequest($endpoint, $parameters);
    }

    /**
     * Performs a section lookup by channel id and pub
     *
     * @param  string $sectionId   The section id
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function sectionLookupByTermVocabId($termVocabId)
    {
        $endpoint = '/section';
        $parameters = [
            'term_vocab_id' => $termVocabId,
        ];
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
    protected function handleRequest($endpoint, array $parameters = array(), $method = 'GET', $ttl = 0)
    {
        $request = $this->createRequest($endpoint, $parameters, $method);

        // Generate Cache Key
        $cacheKey = $this->generateCacheKey($request);

        if (!is_null($parsedResponse = $this->getCache($cacheKey))) {
            // Parsed response found in cache. Return it.
            return $parsedResponse;
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

            // Set the dev access cookie
            $request->cookies->set('da', 'aba8787161b82fe2cbe566582ed505b1');

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
