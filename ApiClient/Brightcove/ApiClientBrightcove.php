<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Brightcove;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;

class ApiClientBrightcove extends ApiClientAbstract
{
    const API_HOST = '//api.brightcove.com';

    /**
     * An array of request methods that this API supports
     *
     * @var array
     */
    protected $supportedMethods = ['GET'];

    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = [
        'readToken',
        // 'writeToken',
    ];

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
     * Handles an API request and returns a Response object
     *
     * @param  string                                    $endpoint The API endpoint
     * @param  string                                    $content  The body content to send with the request
     * @param  string                                    $method   The request method: GET, POST, etc
     * @return Symfony\Component\HttpFoundation\Response
     * @throws Exception                                 If the client is unable to obtain API authorization
     */
    public function handleRequest($endpoint, array $content = array(), $method = 'GET')
    {
        if (!$this->hasValidConfig()) {
            throw new \Exception(sprintf('The Brightcove API configuration is not valid. The following options must be set: %s', implode(', ', $this->requiredConfigOptions)));
        }

        $request = $this->createRequest($endpoint, $content, $method);
        return $this->doRequest($request);
    }

    /**
     * Creates a request that can be sent to the RemoteKernel
     *
     * @param  string                                   $endpoint The API endpoint
     * @param  string                                   $content  The body content to send with the request
     * @param  string                                   $method   The request method: GET, POST, etc
     * @return Symfony\Component\HttpFoundation\Request
     */
    protected function createRequest($endpoint, array $content = array(), $method = 'GET')
    {
        $headers = array('Content-Type' => 'application/json');

        $content['token'] = ('GET' == $method) ? $this->config->get('readToken') : $this->config->get('writeToken');

        // Create initial request object
        return $this->httpKernel->createSimpleRequest($this->getUri($endpoint), $method, $content);
    }

    /**
     * Gets the API hostname
     *
     * @return string
     */
    public function getHost()
    {
        return trim(self::API_HOST, '/');
    }

    /**
     * Gets the full request URI based on an API endpoint
     *
     * @param  string $endpoint The API endpoint
     * @return string The request URI
     */
    public function getUri($endpoint = null)
    {
        $uri = rtrim($this->getHost(), '/');

        // Add the API endpoint, if sent
        if (!is_null($endpoint)) {
            $uri .= '/' . trim($endpoint, '/');
        }

        return '//'.$uri;
    }

    public function findVideoById($identifier)
    {
        $endpoint = 'services/library';
        $parameters = array(
            'command' => 'find_video_by_id',
            'video_id' => $identifier,
        );
        return $this->handleRequest($endpoint, $parameters);
    }

    public function findAllVideos($pageNumber = 0, $fields = array())
    {
        $endpoint = 'services/library';
        $parameters = array(
            'command' => 'find_all_videos',
            'video_fields' => implode(',', $fields),
            'page_number' => $pageNumber,
        );
        return $this->handleRequest($endpoint, $parameters);
    }
}
