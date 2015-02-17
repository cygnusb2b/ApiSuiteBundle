<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp;

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
     * Subscribe a batch of email addresses to a list at once.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/batch-subscribe.php
     *
     * @param  string   $listId
     * @param  array    $batch
     * @param  bool     $doubleOptin
     * @param  bool     $updateExisting
     * @param  bool     $replaceInterests
     * @return array
     */
    public function listsBatchSubscribe($listId, array $batch, $doubleOptin = false, $updateExisting = true, $replaceInterests = false)
    {
        $n = 0;
        $batches = [];
        $batchSize = $this->getBatchSize();
        foreach (array_values($batch) as $i => $record) {
            if (0 === $i % $batchSize) {
                $n++;
            }
            $batches[$n][] = $record;
        }

        $responses = [];
        $endpoint = '/lists/batch-subscribe';
        foreach ($batches as $batch) {
            $body = [
                'id'                => $listId,
                'batch'             => $batch,
                'double_optin'      => $doubleOptin,
                'update_existing'   => $updateExisting,
                'replace_interests' => $replaceInterests,
            ];
            try {
                $responses[] = $this->handleRequest($endpoint, $body);
            } catch (MailchimpApiException $e) {
                if (212 === $e->getCode()) {
                    $responses[] = $e->getResponse();
                } else {
                    throw $e;
                }
            }
        }
        return $responses;
    }

    /**
     * Add a single Interest Group.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-group-add.php
     *
     * @param  string   $listId
     * @param  string   $name
     * @param  int      $groupingId
     * @return array
     */
    public function listsInterestGroupAdd($listId, $name, $groupingId)
    {
        $endpoint = '/lists/interest-group-add';
        $body = [
            'id'            => $listId,
            'group_name'    => $name,
            'grouping_id'   => $groupingId,
        ];
        return $this->handleRequest($endpoint, $body);
    }

    /**
     * Delete a single Interest Group.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-group-del.php
     *
     * @param  string   $listId
     * @param  string   $name
     * @param  int      $groupingId
     * @return array
     */
    public function listsInterestGroupDel($listId, $name, $groupingId)
    {
        $endpoint = '/lists/interest-group-del';
        $body = [
            'id'            => $listId,
            'group_name'    => $name,
            'grouping_id'   => (Integer) $groupingId,
        ];
        return $this->handleRequest($endpoint, $body);
    }

    /**
     * Get the list of interest groupings for a given list, including the label, form information, and included groups for each.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-groupings.php
     *
     * @param  string   $listId
     * @param  bool     $counts
     * @return array
     */
    public function listsInterestGroupings($listId, $counts = false)
    {
        $endpoint = '/lists/interest-groupings';
        $body = [
            'id'    => $listId,
            'counts'=> $counts,
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

    public function listsFindMemberByEmail($listId, $email)
    {
        $member = $this->listsMemberInfo($listId, [['email' => $email]]);
        if (!isset($member['data'][0])) {
            throw new \InvalidArgumentException(sprintf('Unable to find member info using email %s', $email));
        }
        return $member['data'][0];
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
     * Add a new merge tag to a given list.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/merge-var-add.php
     *
     * @param  string   $listId
     * @param  string   $tag
     * @param  string   $name
     * @param  array    $options
     * @return array
     */
    public function listsMergeVarAdd($listId, $tag, $name, array $options = [])
    {
        $endpoint = '/lists/merge-var-add';
        $body = [
            'id'        => $listId,
            'tag'       => $tag,
            'name'      => $name,
            'options'   => $options,
        ];
        return $this->handleRequest($endpoint, $body);
    }

    /**
     * Get the list of merge tags for a given list, including their name, tag, and required setting.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/merge-vars.php
     *
     * @param  array    $listIds
     * @return array
     */
    public function listsMergeVars($listId)
    {
        $endpoint = '/lists/merge-vars';
        $body = [
            'id'    => [$listId],
        ];
        return $this->handleRequest($endpoint, $body);
    }

    /**
     * Handles a request by creating a Request object and sending it to the Kernel
     *
     * @param  string $endpoint     The API endpoint
     * @param  array  $body         The request body content to use
     * @return array
     */
    protected function handleRequest($endpoint, array $body = [])
    {
        $request = $this->createRequest($endpoint, $body);
        $response = $this->doRequest($request);

        $parsedResponse = @json_decode($response->getContent(), true);

        // var_dump($parsedResponse);
        // die();

        if (!is_array($parsedResponse)) {
            throw new \RuntimeException(sprintf('Mailchimp API Error: Unable to parse the response body.'));
        }

        $this->handleException($parsedResponse);

        return $parsedResponse;
    }

    protected function handleException(array $body)
    {
        $errors = [];
        if (isset($body['errors']) && !empty($body['errors'])) {
            $errors = $body['errors'];
        } elseif (isset($body['error'])) {
            $errors = [$body];
        }

        foreach ($errors as $error) {
            $name = isset($error['name']) ? $error['name'] : 'Unspecified API error.';
            $e = new MailchimpApiException($name, $error['error'], $error['code']);
            $e->setResponse($body);
            throw $e;
        }
    }

    /**
     * Determines if a field value is an associative array.
     *
     * @param  mixed $value The value to check
     * @return bool
     */
    public function isAssociativeArray($value)
    {
        if (!is_array($value)) {
            return false;
        }
        return !self::isSequentialArray($value);
    }

    /**
     * Determines if a field value is a sequential array.
     *
     * @param  mixed $value The value to check
     * @return bool
     */
    public function isSequentialArray($value)
    {
        if (!is_array($value)) {
            return false;
        }
        return array_keys($value) === range(0, count($value) - 1);
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
