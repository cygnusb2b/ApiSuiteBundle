<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Varnish;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;
use Symfony\Component\HttpFoundation\Cookie;

class ApiClientVarnish extends ApiClientAbstract
{
    /**
     * The active varnish host
     * @var string
     */
    protected $host = 'localhost';

    /**
     * Returns the active varnish host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the active varnish host
     *
     * @param   string  $host   The hostname of the varnish server
     * @return  self
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Returns the full URI for the requested endpoint.
     *
     * @param   string  $endpoint
     */
    public function getUri($endpoint)
    {
        return sprintf('http://%s/%s', $this->getHost(), ltrim($endpoint, '/'));
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
        $request = $this->createRequest($endpoint, [], $method);
        $request->headers->add([
            'Cache-Control' => 'no-cache',
            'Cookie'        => 'REFRESH=true'
        ]);
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
        return $this->httpKernel->createSimpleRequest($this->getUri($endpoint), $method, $content);
    }

    /**
     * Sends a PURGE request to the active varnish host.
     *
     * @param   string  $endpoint   The endpoint to purge. Can also be a regular expression. Must be relative to self::$host.
     * @return  Symfony\Component\HttpFoundation\Response
     */
    public function purge($endpoint)
    {
        return $this->handleRequest($endpoint, [], 'PURGE');
    }

    /**
     * Crawls the requested URI.
     *
     * @param   string  $endpoint   The endpoint to crawl. Must be relative to self::$host.
     * @return  Symfony\Component\HttpFoundation\Response
     */
    public function crawl($endpoint, $method = 'GET')
    {
        return $this->handleRequest($endpoint, [], $method);
    }
}
