<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp;

use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;

use \DateTime;

class ApiClientMailchimp extends ApiClientAbstract
{
    /**
     * An array of required configuration options
     *
     * @var array
     */
    protected $requiredConfigOptions = ['apikey', 'host'];

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
     * Get the list of campaigns and their details matching the specified filters.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/list.php
     *
     * @param  array        $filters
     * @param  int          $start
     * @param  int          $limit
     * @param  string|null  $sortField
     * @param  string       $sortDir
     * @return array
     */
    public function campaignsList(array $filters = [], $start = 0, $limit = 0, $sortField = null, $sortDir = 'DESC')
    {
        $endpoint = '/campaigns/list';
        $body = [
            'filters'   => $filters,
            'sort_dir'  => $sortDir,
        ];
        if (!empty($start)) {
            $body['start'] = (Integer) $start;
        }
        if (!empty($limit)) {
            $body['limit'] = (Integer) $limit;
        }
        if (!empty($sortField)) {
            $body['sort_field'] = $sortField;
        }
        return $this->handleRequest($endpoint, $body);
    }

    /**
     * Get all the information for particular members of a list.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/member-info.php
     *
     * @param  string       $id     The list id to connect to.
     * @param  array        $emails An array of up to 50 email structs.
     * @return array
     */
    public function listsMemberInfo($id, array $emails)
    {
        $endpoint = '/lists/member-info';
        $body = [
            'id'    => $id,
            'emails'=> $emails,
        ];
        return $this->handleRequest($endpoint, $body);
    }

    /**
     * Retrieves the member information from a last for a single email address.
     *
     * @param  string  $listId The List ID.
     * @param  string  $euid   The Email UID.
     * @return array
     * @throws \InvalidArgumentException If the member data cannot be retrieved off the response.
     */
    public function listsFindMemberByEuid($listId, $euid)
    {
        $member = $this->listsMemberInfo($listId, [['euid' => $euid]]);
        if (!isset($member['data'][0])) {
            throw new \InvalidArgumentException(sprintf('Unable to find member info using id %s', $euid));
        }
        return $member['data'][0];
    }

    /**
     * Retrieves a single campaign by id.
     *
     * @param  string  $id
     * @return array
     * @throws \InvalidArgumentException If the campaign cannot be retrieved off the response.
     */
    public function campaignFindById($id)
    {
        $campaign = $this->campaignsList(['campaign_id' => $id]);
        if (!isset($campaign['data'][0])) {
            throw new \InvalidArgumentException(sprintf('Unable to find campaign using id %s', $id));
        }
        return $campaign['data'][0];
    }

    /**
     * Handles a request by creating a Request object and sending it to the Kernel
     *
     * @param  string $endpoint     The API endpoint
     * @param  array  $body         The request body content to use
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function handleRequest($endpoint, array $body = [])
    {
        $request = $this->createRequest($endpoint, $body);
        $response = $this->doRequest($request);

        $baseError = sprintf('Unable to complete API request "%s" with errors:', $request->getRequestUri());

        if ($response->isSuccessful()) {
            // Ok. Parse JSON response, cache and return
            $parsedResponse = @json_decode($response->getContent(), true);
            if (!is_array($parsedResponse)) {
                throw new \Exception(sprintf('%s Unable to parse the response body.', $baseError));
            }
            if (isset($parsedResponse['errors']) && !empty($parsedResponse['errors'])) {
                throw new \Exception(sprintf('%s %s.', $baseError, json_encode($parsedResponse['errors'])));
            }
            return $parsedResponse;
        } else {
            throw new \Exception(sprintf('%s %s.', $baseError, $response->getContent()));
        }
    }

    /**
     * Creates a new Request object based on API method parameters
     * This should return a Response object
     *
     * @param  string $endpoint The API endpoint
     * @param  array  $body     The request body content to use
     * @return \Symfony\Component\HttpFoundation\Request
     * @throws \Exception If the API configuration is invalid, or a non-allowed request method is passed
     */
    protected function createRequest($endpoint, array $body)
    {
        if ($this->hasValidConfig()) {
            $body['apikey'] = $this->getApiKey();
            $content = @json_encode($body);
            // Create initial request object
            $request = $this->httpKernel->createSimpleRequest($this->getUri($endpoint), 'POST', array(), $content);
            return $request;
        } else {
            throw new \Exception(sprintf('The Mailchimp API configuration is not valid. The following options must be set: %s', implode(', ', $this->requiredConfigOptions)));
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
        $uri = 'https://' . $this->getHost() . '/2.0';

        // Add the API endpoint, if sent
        if (null !== $endpoint) {
            $uri .= rtrim($endpoint, '/');
        }
        return $uri;
    }

    /**
     * Gets the API hostname
     *
     * @return string
     */
    public function getHost()
    {
        return trim($this->config->get('host'), '/');
    }

    /**
     * Gets the API key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->config->get('apikey');
    }
}
