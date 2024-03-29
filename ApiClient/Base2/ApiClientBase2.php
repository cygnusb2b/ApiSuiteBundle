<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Base2;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Cygnus\ApiSuiteBundle\ApiClient\CacheableInterface;
use Cygnus\ApiSuiteBundle\RemoteKernel\RemoteKernelInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Cygnus\ApiSuiteBundle\Traits\CacheTraitRedis;

class ApiClientBase2 extends ApiClientAbstract implements CacheableInterface
{
    use CacheTraitRedis;

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
    public function contentLookup($contentIds)
    {
        $parameters = [];

        if (is_array($contentIds)) {
            $endpoint = '/content';

            if (count($contentIds) == 1) {
                $endpoint .= sprintf('/%s', $contentIds[0]);
            } else {
                $parameters = [
                    'content_id'    => implode('|', $contentIds),
                ];
            }
        } else {
            $endpoint = sprintf('/content/%s', $contentIds);
        }

        $response = $this->handleRequest($endpoint, $parameters);

        if (!is_array($response) || !isset($response['content']) || empty($response['content'])) {
            throw new \Exception(sprintf('A successful content response was received, but is missing data. The content likely doesn\'t exist. Tried id %s', $contentIds));
        }
        return $response;
    }


    /**
     * Performs a contract search against the sales tool api
     *
     * @param  string $search The search string to pass
     * @return array  The decodeed json response
     */
    public function contractsLookup($search)
    {
        $parameters = ['q' => $search];
        $endpoint = '/contracts/search';
        return $this->handleRequest($endpoint, $parameters);
    }

    public function contentLookupByRange($pubgroup, $startingId = 0, $limit = 10)
    {
        $endpoint = '/content';
        $parameters = [
            'pubgroup'  => strtolower($pubgroup),
            'start'     => (int) $startingId,
            'count'     => (int) $limit,
            'base3'     => true,
        ];

        $response = $this->handleRequest($endpoint, $parameters);

        if (!is_array($response) || !isset($response['content']) || empty($response['content'])) {
            throw new \Exception(sprintf('A successful content response was received, but is missing data. The content likely doesn\'t exist. Tried id %s', $startingId));
        }
        return $response;
    }

    /**
     * Performs a fields lookup for a content type
     *
     * @param  string $contentType The content type key to lookup, such as press_release
     * @param  string $pubgroup    The pubgroup, such as fcp
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function fieldsLookup($contentType, $pubgroup)
    {
        $endpoint = '/field/content';
        $parameters = array(
            'content_type'  => strtolower($contentType),
            'pubgroup'      => strtolower($pubgroup),
        );
        return $this->handleRequest($endpoint, $parameters);
    }

    /**
     * Performs a single field lookup by Id
     *
     * @param int $fieldId The legacy field ID to look up.
     */
    public function fieldLookup($fieldId)
    {
        $endpoint = sprintf("/field/%s", $fieldId);
        return $this->handleRequest($endpoint);
    }

    /**
     * Performs a single field_rel lookup by Id
     *
     * @param int $fieldRelId The legacy field_rel ID to look up.
     */
    public function fieldRelLookup($fieldRelId)
    {
        $endpoint = sprintf("/field_rel/%s", $fieldRelId);
        return $this->handleRequest($endpoint);
    }

    /**
     * Performs a single publication lookup by id
     *
     * @param  mixed $pubId The publication ids, as a single id, an array of ids, or a comma seperated list
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function pubLookupById($pubId)
    {
        $endpoint = '/pub';

        if (is_string($pubId)) {
            $pubIds = explode(',', $pubId);
        } elseif (is_array($pubId)) {
            $pubIds = $pubId;
        } else {
            $pubIds = (array) $pubId;
        }

        $response = $this->handleRequest($endpoint);

        if (!is_array($response) || !isset($response['pub']) || empty($response['pub'])) {
            throw new \Exception(sprintf('A successful pub response was received, but is missing data. The pub ids likely do not exist. Tried id %s', implode(',', $pubIds)));
        }

        $found = [];
        foreach ($response['pub'] as $pubKey => $publication) {
            $pubId = $publication['pub_id'];
            if (in_array($pubId, $pubIds)) {
                $found[$pubId] = $publication;
            }
        }
        return $found;
    }

    /**
     * Performs a single publication lookup by pub code
     *
     * @param  string $pubCode The publication code, such as fcp or rpn
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function pubLookupByPub($pubCode)
    {
        $endpoint = '/pub';
        $parameters = array(
            'pub'       => strtolower($pubCode),
        );
        return $this->handleRequest($endpoint, $parameters);
    }

    /**
     * Performs a multiple publication lookup by a pubgroup code
     *
     * @param  string $pubgroup The publication code, such as fcp emsr
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function pubLookupByPubGroup($pubgroup)
    {
        $endpoint = '/pub';
        $parameters = array(
            'pubgroup'       => strtolower($pubgroup),
        );

        $response = $this->handleRequest($endpoint, $parameters);

        if (!is_array($response) || !isset($response['pub']) || empty($response['pub'])) {
            throw new \Exception(sprintf('A successful pub response was received, but is missing data. The pubgroup likely doesn\'t exist. Tried id "%s"', $pubgroup));
        }
        return $response;
    }

    /**
     * Performs a channel lookup by channel id and pub
     *
     * @param  string $channelId   The channel id
     * @param  string $pub         The publication, such as et
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function channelLookupById($channelId, $pub)
    {
        $endpoint = '/channel';
        $parameters = array(
            'channel_id'    => $channelId,
            'pub'           => strtolower($pub),
        );

        $response = $this->handleRequest($endpoint, $parameters);

        if (!is_array($response) || !isset($response['channel']) || !isset($response['channel'][strtoupper($pub)]) || isset($response['channel'][strtoupper($pub)][''])) {
            throw new \Exception(sprintf('A successful channel response was received, but is missing data. The channel likely doesn\'t exist. Tried id %s', $channelId));
        }
        return $response;
    }

    /**
     * Performs a channel lookup by pub
     *
     * @param  string $pub         The publication, such as fcp
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function channelLookupByPub($pub)
    {
        $endpoint = '/channel';
        $parameters = array(
            'pub'           => strtolower($pub),
        );
        return $this->handleRequest($endpoint, $parameters);
    }

    /**
     * Performs a channel lookup by channel type and pub
     *
     * @param  string $channelType The channel type, such as website
     * @param  string $pub         The publication, such as et
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function channelLookupByType($channelType, $pub)
    {
        $endpoint = '/channel';
        $parameters = array(
            'channel'   => strtolower($channelType),
            'pub'       => strtolower($pub),
        );
        return $this->handleRequest($endpoint, $parameters, 'GET', 30);
    }

    /**
     * Performs a vocab lookup by vocab key (e.g. fcp_categories) and pub
     *
     * @param  string $vocab        The vocab key
     * @param  string $pubgroup     The pubgroup, such as fcp
     * @param  bool   $includeTerms Whether to include all terms
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function vocabLookup($vocab, $pubgroup, $terms = false)
    {
        $endpoint = '/vocab';
        $parameters = array(
            'vocab'     => strtolower($vocab),
            'pub'       => strtolower($pubgroup),
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
        $response = $this->handleRequest($endpoint, $parameters);

        if (!is_array($response) || !isset($response['term_vocab']) || empty($response['term_vocab'])) {
            throw new \Exception(sprintf('A successful term vocab response was received, but is missing data. The term vocab likely doesn\'t exist. Tried id %s', implode(',', $termVocabIds)));
        }
        return $response;
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

                if (!is_array($parsedResponse) || !isset($parsedResponse['status']) || $parsedResponse['status'] == 0) {
                    throw new \Exception(sprintf('%s Invalid status received.', $baseError));
                }

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
