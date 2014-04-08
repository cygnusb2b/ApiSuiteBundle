<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\OpenX;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;
use Symfony\Component\HttpFoundation\Cookie;

class ApiClientOpenX extends ApiClientAbstract
{
    const VERSION = '3.0';

    /**
     * An array of request methods that this API supports
     *
     * @var array
     */
    protected $supportedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];

    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = [
        'consumerKey',
        'consumerSecret',
        'realm',
        'requestTokenUrl',
        'accessTokenUrl',
        'authorizeUrl',
        'email',
        'password',
        'loginUrl',
        'oxInstance',
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

    public function adGet($adId)
    {
        $endpoint = '/ad/' . $adId;
        return $this->handleRequest($endpoint);
    }

    public function accountGet($accountId)
    {
        $endpoint = '/account';
        return $this->handleRequest($endpoint);
    }

    /**
     * Checks whether the current session is authorized
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $request = $this->httpKernel->createSimpleRequest($this->getUri('/session/validate'), 'PUT');
        $response = $this->doRequest($request);
        return ($response->getStatusCode() == 200);
    }

    /**
     * Sets the configuration options for this API client and passes the OAuth settings to the Kernel
     *
     * @param  array $config The config options
     * @return self
     */
    public function setConfig(array $config) 
    {
        ApiClientAbstract::setConfig($config);
        if ($this->httpKernel instanceof RemoteKernelInterface) {
            $this->httpKernel->setConfig($config);
        }
        return $this;
    }

    /**
     * Determines if the API instance has a valid configuration
     *
     * @return bool
     */
    public function hasValidConfig()
    {
        return (ApiClientAbstract::hasValidConfig() && $this->httpKernel->hasValidConfig());
    }

    
    /**
     * Handles an API request and returns a Response object
     *
     * @param  string $endpoint The API endpoint
     * @param  string $content  The body content to send with the request
     * @param  string $method   The request method: GET, POST, etc
     * @return Symfony\Component\HttpFoundation\Response
     * @throws Exception If the client is unable to obtain API authorization
     */
    public function handleRequest($endpoint, $content = null, $method = 'GET')
    {
        $request = $this->createRequest($endpoint, $content, $method);
        if ($this->isAuthorized()) {
            return $this->doRequest($request);
        } else {
            if ($this->doOauthDance()) {
                $response = $this->doRequest($request);
                // Add the JSON content type, since it isn't explicitally returned with the API response
                $response->headers->set('content-type', 'application/json; charset=UTF-8');
                return $response;
            } else {
                throw new \Exception('Unable to authorize the API session.');
            }
            
            
        }
    }

    /**
     * Executes the 'OAuth Dance' to obtain API session authorization
     *
     * @return bool Whether authorization was obtained
     */
    protected function doOauthDance()
    {
        $requestToken = $this->httpKernel->getRequestToken();

        // Login - simulate the User Auth step
        $parameters = array(
            'email'         => $this->config->get('email'),
            'password'      => $this->config->get('password'),
            'oauth_token'   => $requestToken->getToken(),
        );

        $request = $this->httpKernel->createSimpleRequest(
            $this->config->get('loginUrl'),
            'POST',
            $parameters
        );

        $response = $this->doRequest($request);
        $parsedContent = parse_url($response->getContent());
        parse_str($parsedContent['query'], $authData);

        // Get the Access Token
        $accessToken = $this->httpKernel->getAccessToken($authData);

        $cookieDomain = parse_url($this->config->get('oxInstance'))['host'];
        $this->httpKernel->getClient()->createCookie('openx3_access_token', $accessToken->getToken(), 0, '/', $cookieDomain);

        // Validate the session
        return $this->isAuthorized();
    }

    /**
     * Creates a request that can be sent to the RemoteKernel
     *
     * @param  string $endpoint The API endpoint
     * @param  string $content  The body content to send with the request
     * @param  string $method   The request method: GET, POST, etc
     * @return Symfony\Component\HttpFoundation\Request
     */
    protected function createRequest($endpoint, $content = null, $method = 'GET')
    {
        $headers = array('Content-Type' => 'application/json');

        // Handle the request body content
        if (is_scalar($content)) {
            $content = (string) $content;
        } elseif (is_array($content)) {
            $content = @json_encode($content);
        }

        // Create initial request object
        return $this->httpKernel->createSimpleRequest($this->getUri($endpoint), $method, array(), $content);
    }

    /**
     * Gets the API hostname
     *
     * @return string
     */
    public function getHost()
    {
        return trim($this->config->get('oxInstance'), '/');
    }

    /**
     * Gets the API base endpoint, based on API version
     *
     * @return string
     */
    public function getBaseEndpoint()
    {
        if (self::VERSION == '3.0') {
            return '/ox/3.0/a';
        } else {
            return '/ox/' . self::VERSION;
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
        $uri = rtrim($this->getHost(), '/');
        
        // Add the API endpoint, if sent
        if (!is_null($endpoint)) {
            $uri .= $this->getBaseEndpoint() . '/' . trim($endpoint, '/');
        }
        return $uri;
    }
}
