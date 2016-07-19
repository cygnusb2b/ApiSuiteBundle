<?php

namespace Cygnus\ApiSuiteBundle\ApiClient\Google;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

class ApiClientYoutube extends ApiClientAbstract
{
    /**
     * The configuration options.
     *
     * @var ParameterBag
     */
    protected $config;

    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = [
        'key',
    ];

    /**
     * Constructor. Sets the configuration for this API client instance
     *
     * @param   array   $config     The config options
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Retrieves videos from a playlist
     */
    public function retrievePlaylistVideos(array $criteria = [], array $fields = [], array $sort = [], $limit = 50, $skip = 0)
    {
        if (!isset($criteria['playlistId'])) {
            throw new \InvalidArgumentException(sprintf('`playlistId` is a required parameter for %s!', __METHOD__));
        }

        $criteria = $this->prepareCriteria($criteria, $fields, $sort, $limit, $skip);
        $request = $this->httpKernel->createSimpleRequest(
            'https://www.googleapis.com/youtube/v3/playlistItems',
            'GET',
            $criteria
        );
        return $this->doRequest($request);
    }

    private function prepareCriteria(array $criteria = [], array $fields = [], array $sort = [], $limit = 50, $skip = 0)
    {
        // Limit
        if ($limit > 50) {
            throw new \OutOfBoundsException(sprintf('Specified limit of `%s` is above maximum: 50', $limit));
        }
        $criteria['maxResults'] = $limit;

        // Fields
        if (empty($fields)) {
            $fields[] = 'snippet';
        }
        $criteria['part'] = implode(',', $fields);

        // Sort
        if (empty($sort)) {
            $sort[] = 'date';
        }
        $criteria['order'] = implode(',', $sort);

        $criteria['key'] = $this->config->get('key');

        return $criteria;
    }
}
