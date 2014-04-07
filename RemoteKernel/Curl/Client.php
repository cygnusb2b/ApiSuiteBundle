<?php
namespace Cygnus\ApiSuiteBundle\RemoteKernel\Curl;

use Cygnus\ApiSuiteBundle\RemoteKernel\Curl\Processor\ResponseHeaderProcessor;
use Cygnus\ApiSuiteBundle\RemoteKernel\Curl\Processor\ResponseBodyProcessor;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Monolog\Logger;

class Client
{
    /**
     * The CURL resource handler
     *
     * @var resource
     */
    protected $handle;

    /**
     * The Request object to use
     *
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * The Response object to use
     *
     * @var Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * An array of cURL options for the request.
     *
     * @var array
     */
    protected $options = array();

    /**
     * The response header processing service
     *
     * @var Cygnus\CurlBundle\Curl\Processor\ResponseHeaderProcessor
     */
    private $headerProcessor;

    /**
     * The response body processing service
     *
     * @var Cygnus\CurlBundle\Curl\Processor\ResponseHeaderProcessor
     */
    private $bodyProcessor;

    public $cookieJar;

    /**
     * Constructor; initialize the cURL handle and set the response processors
     *
     * @return void
     */
    public function __construct(ResponseHeaderProcessor $h, ResponseBodyProcessor $b, Logger $l)
    {
        $this->headerProcessor = $h;
        $this->bodyProcessor = $b;
        $this->cookieJar = new CookieJar();
        $this->logger = $l;
        $this->initHandle();
    }

    /**
     * Executes a cURL request
     *
     * @return Symfony\Component\HttpFoundation\Response
     * @throws \Exception If $this->request is not an intance of the Request class
     */
    public function execute()
    {
        if (!$this->request instanceof Request) {
            throw new \Exception('Unable to handle cURL request. An instance of Symfony\Component\HttpFoundation\Request was not set.');
        }
        $this->prepareRequest();
        return $this->performRequest();
    }

    /**
     * Sets the Request object
     *
     * @param  Symfony\Component\HttpFoundation\Request $request
     * @return Cygnus\CurlBundle\Curl\Client
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        $this->setOptionsFromRequest();
        return $this;
    }

    /**
     * Sets the Request object
     *
     * @param  Symfony\Component\HttpFoundation\Request $request
     * @return Cygnus\CurlBundle\Curl\Client
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Gets the cURL response as a Response object
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the base cURL options from the request
     * URI, Port, and Request Method
     *
     * @return void
     */
    protected function setOptionsFromRequest()
    {
        $this
            ->setUri($this->request->getUri())
            ->setPort($this->request->getPort())
            ->setMethod($this->request->getMethod());
    }

    /**
     * Preparation method before every cURL request
     * Handles cURL options for cookies, headers, and request method specific needs
     *
     * @return void
     */
    private function prepareRequest()
    {
        // Request Cookies

        // Request Headers
        $this->setRequestHeaders();

        // Method Specific Options
        $this->setRequestMethodOptions();

    }

    /**
     * Performs the cURL request, handles the cURL response and returns it as a Response object
     *
     * @return Symfony\Component\HttpFoundation\Response
     * @throws \Exception On a PHP curl error
     */
    private function performRequest()
    {
        $ch = $this->getHandle();
        curl_setopt_array($ch, $this->getOptions());
        curl_exec($ch);

        $this->logger->info('Curl: '. $this->request->getUri());

        if (!curl_errno($ch)) {
            $this->handleResponse();
            $this->completeRequest();
            return $this->getResponse();
        } else {
            throw new \Exception(sprintf('PHP cURL Error: (%s), Error: %s', curl_error($ch), curl_errno($ch)));
        }
    }

    /**
     * Handles the PHP cURL response and converts it into a Response object
     *
     * @return void
     */
    private function handleResponse()
    {
        $responseBody = $this->bodyProcessor->get();
        $responseHeaders = $this->headerProcessor->get();
        $statusCode = $this->headerProcessor->getStatusCode();
        $statusMessage = $this->headerProcessor->getStatusMessage();
        $protocolVersion = $this->headerProcessor->getProtocolVersion();

        $response = new Response($responseBody, $statusCode, $responseHeaders);

        $response->setProtocolVersion($protocolVersion);
        $response->setStatusCode($statusCode, $statusMessage);

        if ($response->headers->has('Transfer-Encoding')) {
            // The cURL response was sent in chunks
            // Since the response body was already processed via $this->bodyProcessor, remove the header and add Content-Length
            $response->headers->remove('Transfer-Encoding');
            $response->headers->set('Content-Length', strlen($response->getContent()));
        }
        $this->setResponse($response);
    }

    /**
     * Runs methods after the request is executed
     * Used primarily for cleanup: headers and data aren't saved after each request (unless flagged as sticky)
     *
     * @return self
     */
    private function completeRequest()
    {
        $this->resetOptions();
        return $this;
    }

    /**
     * Sets cURL header options for this request
     *
     * @return self
     */
    private function setRequestHeaders()
    {
        $headers = explode("\r\n", (string) $this->request->headers);
        if (!empty($headers)) {
            $this->addOption(CURLOPT_HTTPHEADER, $headers);
        }
        return $this;
    }

    /**
     * Sets specific cURL options based on the request method
     *
     * @return self
     */
    private function setRequestMethodOptions()
    {
        $method = $this->request->getMethod();
        switch ($method) {
            case 'POST':
                $this->setPostFields();
                break;
            default:
                break;
        }
        return $this;
    }

    /**
     * Sets the POST fields or POST body for the request
     *
     * @return self
     */
    private function setPostFields()
    {
        $content = $this->request->getContent();

        if (!empty($content)) {
            $post = $content;
        } else {
            $post = http_build_query($this->request->request->all());
        }

        $this->addOption(CURLOPT_POSTFIELDS, $post);
        return $this;
    }


    /**
     * Sets the URI option for this cURL request
     *
     * @param  string $uri The URI
     * @return self
     */
    public function setUri($uri)
    {
        $this->addOption(CURLOPT_URL, $uri);
        return $this;
    }

    /**
     * Sets the port option for this cURL request
     *
     * @param  int $port The port number
     * @return self
     */
    public function setPort($port)
    {
        $this->addOption(CURLOPT_PORT, $port);
        return $this;
    }

    /**
     * Sets the Request Method option for this cURL request
     *
     * @param  string $method The request method (GET, POST, etc)
     * @return self
     * @throws \Exception If an unsupported method is passed
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);
        $supported = $this->getSupportedMethods();

        if (array_key_exists($method, $supported)) {
            list($option, $value) = array_values($supported[$method]);
            $this->addOption($option, $value);
            return $this;
        } else {
            throw new \Exception(sprintf('Unsupported request method %s specified. Only %s methods are allowed', $method, implode(', ', array_keys($supported))));
        }
    }

    /**
     * Returns the curl_getinfo about a cURL request
     *
     * @param  int|null $option
     * @return array
     */
    public function getCurlInfo($option = null)
    {
        if (is_int($option)) {
            return curl_getinfo($this->getHandle(), $option);
        } else {
            return curl_getinfo($this->getHandle());
        }
    }

    /**
     * Returns an array of support request methods, along with their cURL option constant and value
     *
     * @return array
     */
    public function getSupportedMethods()
    {
        return array(
            'GET'   => array(
                'option'    => CURLOPT_HTTPGET,
                'value'     => true,
            ),
            'POST'   => array(
                'option'    => CURLOPT_POST,
                'value'     => true,
            ),
            'PUT'   => array(
                'option'    => CURLOPT_PUT,
                'value'     => true,
            ),
            'DELETE'   => array(
                'option'    => CURLOPT_CUSTOMREQUEST,
                'value'     => 'DELETE',
            ),
            'OPTIONS'   => array(
                'option'    => CURLOPT_CUSTOMREQUEST,
                'value'     => 'OPTIONS',
            ),
            'HEAD'   => array(
                'option'    => CURLOPT_NOBODY,
                'value'     => true,
            ),
        );
    }

    public function createCookie($name, $value = null, $expire = 0, $path = '/', $domain = null, $secure = false, $httpOnly = true)
    {
        $cookie = new Cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        $this->setCookie($cookie);
        return $cookie;
    }

    public function setCookie(Cookie $cookie)
    {
        $this->cookieJar->set($cookie);
    }

    /**
     * Initialize and set the cURL resource handle
     *
     * @return Cygnus\CurlBundle\Curl\Client
     */
    public function initHandle()
    {
        $this->handle = curl_init();
        $this->initDefaultOptions();
        return $this;
    }

    /**
     * Sets the default cURL options for each request
     *
     * @return void
     */
    private function initDefaultOptions()
    {
        $defaults = array(
            CURLOPT_RETURNTRANSFER  => true,
            // CURLINFO_HEADER_OUT     => true,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_HEADERFUNCTION  => array($this->headerProcessor, "process"),
            CURLOPT_WRITEFUNCTION   => array($this->bodyProcessor,   "process"),
        );
        $this->setOptions($defaults);
    }

    /**
    * Reset the CURL options for the request, except for cookies
    *
    * @return Curl_Client
    */
    public function resetOptions()
    {
        $ch = $this->getHandle();
        $this->options = array();
        // curl_reset($this->getHandle()) # Available in PHP 5.5

        // Unset any previously sent POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        // Unset any previously sent headers
        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
        // Reset request method
        curl_setopt($ch, CURLOPT_HTTPGET, true);

        $this->headerProcessor->reset();
        $this->bodyProcessor->reset();

        return $this;
    }

    /**
     * Return the CURL resource handler
     *
     * @return resource
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * Add a cURL option for the request
     *
     * @param  const $option The CURL option constant
     * @param  mixed $value The option value
     * @return Cygnus\CurlBundle\Curl\Client
     */
    public function addOption($option, $value)
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Add a cURL option for the request
     *
     * @param  const $option The CURL option constant
     * @param  mixed $value The option value
     * @return Cygnus\CurlBundle\Curl\Client
     */
    public function removeOption($option, $value)
    {
        if (array_key_exists($option, $options)) {
            unset($this->options[$option]);
        }
        return $this;
    }

    /**
     * Return the value of the specified option
     *
     * @param  const $option The CURL option constant
     * @return mixed The option value
     */
    public function getOption($option)
    {
        if (array_key_exists($option, $options)) {
            return $this->options[$option];
        } else {
            return null;
        }
    }

    /**
     * Return the array of cURL options
     *
     * @return array The option values
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set CURL options for the request
     *
     * @param array The cURL options
     * @return Cygnus\CurlBundle\Curl\Client
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Destructor; close the curl connection
     *
     * @return void
     */
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            curl_close($this->handle);
        }
    }
}
