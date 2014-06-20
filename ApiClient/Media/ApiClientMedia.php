<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Media;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ApiClientMedia extends ApiClientAbstract
{

    const BASE_ENDPOINT = 'api';

    /**
     * An array of request methods that this API supports
     *
     * @var array
     */
    protected $supportedMethods = ['GET', 'PUT'];

    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = ['host', 'user', 'key'];

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
     * Performs a file upload to the media server via PUT
     *
     * @param  string $filePath    The location on the media server where the file should be stored.
     * @param  string $fileName    The file name to be stored on the media server
     * @param  string $file        The absolute path to the file on disk
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function upload($filePath, $fileName, UploadedFile $file)
    {
        // What.
        $endpoint = sprintf("/upload?file_name=%s&file_path=%s", $fileName, $filePath);
        $parameters = [
            'file_name' => $fileName,
            'file_path' => $filePath
        ];

        $response = $this->handleRequest($endpoint, $parameters, 'PUT', null, array($file));
        return $response;
    }

    /**
     * Clears resized images after a new crop via GET
     *
     * @param  string $fileName    The file name to be stored on the media server
     * @param  string $filePath    The location on the media server where the file should be stored.
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function regenerate($filePath, $fileName)
    {
        $endpoint = sprintf("/regen/%s/%s", $filePath, $fileName);
        return $this->handleRequest($endpoint);
    }

    /**
     * Handles a request by creating a Request object and sending it to the Kernel
     *
     * @param  string $endpoint   The API endpoint
     * @param  array  $parameters The request parameters
     * @param  string $method     The request method
     * @return Symfony\Component\HttpFoundation\Response
     */
    protected function handleRequest($endpoint, array $parameters = array(), $method = 'GET', $content = null, $files = array())
    {
        $request = $this->createRequest($endpoint, $parameters, $method, $content, $files);

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
            return $parsedResponse;
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
    protected function createRequest($endpoint, array $parameters = array(), $method = 'GET', $content = null, $files = array())
    {
        if ($this->hasValidConfig()) {

            $method = strtoupper($method);
            if (!in_array($method, $this->supportedMethods)) {
                // Request method not allowed by the API
                throw new \Exception(sprintf('The request method %s is not allowed. Only %s methods are supported.'), $method, implode(', ', $this->supportedMethods));
            }

            // Transform files


            // Create initial request object
            // $request = $this->httpKernel->createSimpleRequest($this->getUri($endpoint), $method, $parameters, $content);
            $request = $this->httpKernel->createRequest($this->getUri($endpoint), $method, $parameters, array(), $files, array(), $content);

            $headers = array(
                'x-base-user'   => $this->config->get('user'),
                'x-base-key'    => $this->config->get('key'),
            );

            // Add the headers to the request
            $request->headers->add($headers);

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
