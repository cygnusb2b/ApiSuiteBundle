<?php

namespace Cygnus\ApiSuiteBundle\ApiClient\Google;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

class ApiClientYoutube extends ApiClientAbstract
{
    /**
     * {@inheritdoc}
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
     * Retrieves videos from a playlist.
     * Pagination must be handled outside this method using `pageToken`
     * fields from the api response.
     *
     * @see     https://developers.google.com/youtube/v3/docs/playlistItems/list
     *
     * @param   array   $criteria   The parameters to be included with the request
     * @param   array   $fields     Field groups to be included in the response. See documentation
     * @param   array   $sort       The order the return items in
     * @param   int     $limit      The number of items to return. Maximum of 50 enforced server-side.
     *
     * @throws  InvalidArgumentException    If the playlistId criteria was not specified.
     * @throws  OutOfBoundsException        If attempting to use a $limit beyond 50.
     *
     * @return  Symfony\Component\HttpFoundation\Response
     */
    public function retrievePlaylistVideos(array $criteria = [], array $fields = [], array $sort = [], $limit = 50)
    {
        if (!isset($criteria['playlistId'])) {
            throw new \InvalidArgumentException('`playlistId` is a required parameter!');
        }

        $criteria = $this->prepareCriteria($criteria, $fields, $sort, $limit);
        $request = $this->httpKernel->createSimpleRequest(
            'https://www.googleapis.com/youtube/v3/playlistItems',
            'GET',
            $criteria
        );
        return $this->doRequest($request);
    }

    /**
     * Merges sort, limit parameters into query criteria. Enforces the API key injection
     *
     * @return  array
     */
    private function prepareCriteria(array $criteria = [], array $fields = [], array $sort = [], $limit = 50)
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
