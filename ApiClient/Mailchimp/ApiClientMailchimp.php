<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp;

use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpException;
use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpHttpException;
use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpResponseException;
use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Resources\AbstractResource;
use Cygnus\ApiSuiteBundle\ApiClient\ApiClientAbstract;
use Symfony\Component\HttpFoundation\Response;
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
        $this->setResources();
    }

    public function setResources()
    {
        $namespace = 'Cygnus\\ApiSuiteBundle\\ApiClient\\Mailchimp\\Resources';
        $resources = [
            'campaigns' => 'Campaigns',
            'lists'     => 'Lists',
            'templates' => 'Templates',
        ];
        foreach ($resources as $key => $class) {
            $fqcn = sprintf('%s\\%s', $namespace, $class);
            $this->resources[$key] = new $fqcn($key, $this);
        }
        return $this;
    }

    public function getResource($key)
    {
        return $this->resources[$key];
    }

    /**
     * Retrieves a single campaign by id.
     *
     * @deprecated
     * @param  string  $id
     * @return array
     * @throws \InvalidArgumentException If the campaign cannot be retrieved off the response.
     */
    public function campaignFindById($id)
    {
        return $this->getResource('campaigns')->findById($id);
    }

    /**
     * Get the list of campaigns and their details matching the specified filters.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/list.php
     *
     * @deprecated
     * @param  array        $filters
     * @param  int          $start
     * @param  int          $limit
     * @param  string|null  $sortField
     * @param  string       $sortDir
     * @return array
     */
    public function campaignsList(array $filters = [], $start = 0, $limit = 0, $sortField = null, $sortDir = 'DESC')
    {
        return $this->getResource('campaigns')->getList($filters, $start, $limit, $sortField, $sortDir);
    }

    /**
     * Subscribe a batch of email addresses to a list at once.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/batch-subscribe.php
     *
     * @deprecated
     * @param  string   $listId
     * @param  array    $batch
     * @param  bool     $doubleOptin
     * @param  bool     $updateExisting
     * @param  bool     $replaceInterests
     * @return array
     */
    public function listsBatchSubscribe($listId, array $batch, $doubleOptin = false, $updateExisting = true, $replaceInterests = false)
    {
        return $this->getResource('lists')->batchSubscribe($listId, $batch, $doubleOptin, $updateExisting, $replaceInterests);
    }

    /**
     * Add a single Interest Group.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-group-add.php
     *
     * @deprecated
     * @param  string   $listId
     * @param  string   $name
     * @param  int      $groupingId
     * @return array
     */
    public function listsInterestGroupAdd($listId, $name, $groupingId)
    {
        return $this->getResource('lists')->interestGroupAdd($listId, $name, $groupingId);
    }

    /**
     * Delete a single Interest Group.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-group-del.php
     *
     * @deprecated
     * @param  string   $listId
     * @param  string   $name
     * @param  int      $groupingId
     * @return array
     */
    public function listsInterestGroupDel($listId, $name, $groupingId)
    {
        return $this->getResource('lists')->interestGroupDel($listId, $name, $groupingId);
    }

    /**
     * Get the list of interest groupings for a given list, including the label, form information, and included groups for each.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-groupings.php
     *
     * @deprecated
     * @param  string   $listId
     * @param  bool     $counts
     * @return array
     */
    public function listsInterestGroupings($listId, $counts = false)
    {
        return $this->getResource('lists')->interestGroupings($listId, $counts);
    }

    /**
     * Retrieves the member information from a list for a single email address.
     *
     * @deprecated
     * @param  string  $listId The List ID.
     * @param  string  $email  The Email address.
     * @return array
     * @throws \InvalidArgumentException If the member data cannot be retrieved off the response.
     */
    public function listsFindMemberByEmail($listId, $email)
    {
        return $this->getResource('lists')->findMemberByEmail($listId, $email);
    }

    /**
     * Retrieves the member information from a list for a single email id.
     *
     * @param  string  $listId The List ID.
     * @param  string  $euid   The Email UID.
     * @return array
     * @throws \InvalidArgumentException If the member data cannot be retrieved off the response.
     */
    public function listsFindMemberByEuid($listId, $euid)
    {
        return $this->getResource('lists')->findMemberByEuid($listId, $euid);
    }

    /**
     * Get all the information for particular members of a list.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/member-info.php
     *
     * @deprecated
     * @param  string       $id     The list id to connect to.
     * @param  array        $emails An array of up to 50 email structs.
     * @return array
     */
    public function listsMemberInfo($id, array $emails)
    {
        return $this->getResource('lists')->memberInfo($id, $emails);
    }

    /**
     * Add a new merge tag to a given list.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/merge-var-add.php
     *
     * @deprecated
     * @param  string   $listId
     * @param  string   $tag
     * @param  string   $name
     * @param  array    $options
     * @return array
     */
    public function listsMergeVarAdd($listId, $tag, $name, array $options = [])
    {
        return $this->getResource('lists')->mergeVarAdd($listId, $tag, $name, $options);
    }

    /**
     * Get the list of merge tags for a given list, including their name, tag, and required setting.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/merge-vars.php
     *
     * @deprecated
     * @param  array    $listIds
     * @return array
     */
    public function listsMergeVars($listId)
    {
        return $this->getResource('lists')->mergeVars($listId);
    }

    /**
     * Handles a request by creating a Request object and sending it to the Kernel
     *
     * @param  string $endpoint     The API endpoint
     * @param  array  $body         The request body content to use
     * @return array
     */
    public function handleRequest($endpoint, array $body = [])
    {
        $request = $this->createRequest($endpoint, $body);

        try {
            $response = $this->doRequest($request);
            $parsed = json_decode($response->getContent(), true);
        } catch (\Exception $e) {
            throw new MailchimpHttpException('API Response processing failed. Unable to retrieve a response.', 0, $e);
        }

        if (!is_array($parsed)) {
            throw new MailchimpHttpException('API Response processing failed. Unable to parse the response.');
        }

        if (floor($response->getStatusCode() / 100) >= 4) {
            throw $this->handleException($parsed);
        }
        return $parsed;
    }

    /**
     * Handles (and throws) API exceptions when errors are encountered in the response body.
     *
     * @param  array    $body
     * @return MailchimpResponseException
     * @throws MailchimpException
     */
    protected function handleException(array $body)
    {
        if ('error' !== $result['status'] || !$result['name']) {
            throw new MailchimpException(sprintf('An unexpected error was received: %s', json_encode($body)));
        }
        return new MailchimpResponseException($body);
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
     * Gets the batch size for batch operations (such as batch subscribe).
     *
     * @return int
     */
    public function getBatchSize()
    {
        if ($this->config->has('batchSize')) {
            return (Integer) $this->config->get('batchSize');
        }
        return 5000;
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
