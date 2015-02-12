<?php

namespace Cygnus\ApiSuiteBundle\ApiClient\Gigya;

use Symfony\Component\HttpFoundation\ParameterBag;
use \GSResponse;
use \GSObject;
use \GSRequest;

include_once 'SDK/GSSDK.php';

class ApiClientGigya
{
    /**
     * The configuration options
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
        'apiKey',
        'secretKey',
        'useHttps',
    ];

    /**
     * Constructor. Sets the configuration for this API client instance
     *
     * @param  array $config The config options
     * @return void
     */
    public function __construct(array $config = array())
    {
        $this->setConfig($config);
    }

    public function socializeDeleteAccount($uid)
    {
        $params = [
            'UID'   => $uid,
        ];
        return $this->sendRequest('socialize.deleteAccount', $params);
    }

    public function socializeNotifyRegistration($uid, $siteUid)
    {
        $params = [
            'UID'       => $uid,
            'siteUID'   => $siteUid,
        ];
        return $this->sendRequest('socialize.notifyRegistration', $params);
    }

    public function socializeNotifyLogin($siteUid, $newUser = false, array $userInfo = [])
    {
        $params = [
            'siteUID'   => $siteUid,
            'newUser'   => $newUser,
            // 'userInfo'  => $userInfo,
        ];
        // var_dump($params);
        // die();
        return $this->sendRequest('socialize.notifyLogin', $params);
    }

    public function socializeGetUserInfo($uid)
    {
        $params = [
            'UID'   => $uid,
        ];
        return $this->sendRequest('socialize.getUserInfo', $params);
    }

    public function accountsGetAccountInfo($uid)
    {
        $params = [
            'UID'   => $uid,
        ];
        return $this->sendRequest('accounts.getAccountInfo', $params);
    }

    /**
     * API: Identify Storage.
     * Method: Set Schema.
     * @link http://developers.gigya.com/037_API_reference/Identity_Storage/ids.setSchema
     *
     * @param  array    $profileSchema
     * @param  array    $dataSchema
     * @return GSResponse
     */
    public function idsSetSchema(array $profileSchema, array $dataSchema)
    {
        $params = [
            'profileSchema' => $profileSchema,
            'dataSchema'    => $dataSchema,
        ];
        return $this->sendRequest('ids.setSchema', $params);
    }


    /**
     * API: Identify Storage.
     * Method: Get Schema.
     * @link http://developers.gigya.com/037_API_reference/Identity_Storage/ids.getSchema
     *
     * @param  string   $filter
     * @return GSResponse
     */
    public function idsGetSchema($filter = 'full')
    {
        if (!in_array($filter, ['full', 'explicitOnly', 'clientOnly'])) {
            $filter = 'full';
        }

        // Set the request params
        $params = [
            'filter' => $filter,
        ];
        // Send the request
        return $this->sendRequest('ids.getSchema', $params);
    }

    /**
     * Sets the configuration options for this API client
     *
     * @param  array $config The config options
     * @return self
     */
    public function setConfig(array $config)
    {
        $this->config = new ParameterBag($config);
        $this->config->set('useHttps', false);
        return $this;
    }

    /**
     * Determines if the API instance has a valid configuration
     *
     * @return bool
     */
    public function hasValidConfig()
    {
        foreach ($this->requiredConfigOptions as $option) {
            if (!$this->config->has($option)) return false;
        }
        return true;
    }

    /**
     * Sends a Gigya API request.
     *
     * @param  string   $apiMethod  The Gigya API method, with namespace, e.g. socialize.login
     * @param  array    $params     The API method parameters.
     * @return GSResponse
     */
    protected function sendRequest($apiMethod, array $params = [])
    {
        $request = $this->prepareRequest($apiMethod, $params);
        $response = $request->send();
        // @todo Convert GSResponse object into Symfony response object?
        // $this->convertResponse($response);
        return $response;
    }

    /**
     * Prepares a Gigya API request.
     *
     * @param  string   $apiMethod  The Gigya API method, with namespace, e.g. socialize.login
     * @param  array    $params     The API method parameters.
     * @return GSRequest
     */
    protected function prepareRequest($apiMethod, array $params = [])
    {
        $params = new GSObject($params);
        return $this->createGSRequest($apiMethod, $params);
    }

    /**
     * Creates a GSRequest object.
     *
     * @param  string       $apiMethod  The Gigya API method name, such as 'socialize.setStatus'
     * @param  GSObject     $params     The request parameters. @todo Should this be allowed?
     * @param  string       $userKey    The user key to use instead of site key. @todo Should be a config option. Do we need it?
     * @return GSRequest
     */
    protected function createGSRequest($apiMethod, GSObject $params = null, $userKey = null)
    {
        if (!$this->hasValidConfig()) {
            throw new \RuntimeException('The configuration for this client is invalid.');
        }
        return new GSRequest(
            $this->getApiKey(),
            $this->getSecretKey(),
            $apiMethod,
            $params,
            $this->shouldUseHttps(),
            $userKey
        );
    }

    /**
     * Determines whether API calls should use HTTPS.
     *
     * @return bool
     */
    public function shouldUseHttps()
    {
        return (Boolean) $this->config->get('useHttps');
    }

    /**
     * Gets the API key.
     *
     * @return string|null
     */
    public function getApiKey()
    {
        return $this->config->get('apiKey');
    }

    /**
     * Gets the secret key.
     *
     * @return string|null
     */
    public function getSecretKey()
    {
        return $this->config->get('secretKey');
    }
}
