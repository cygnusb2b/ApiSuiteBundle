<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\ExactTarget\Resources;

use Cygnus\ApiSuiteBundle\ApiClient\ExactTarget\Exception\ResponseException;
use Cygnus\ApiSuiteBundle\ApiClient\ExactTarget\ApiClientExactTarget;

abstract class AbstractResource
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var ApiClientExactTarget
     */
    protected $root;

    /**
     * @var object
     */
    protected $handler;

    /**
     * Constructor.
     *
     * @param   string                      $key
     * @param   ApiClientExactTarget    $root
     */
    public function __construct($key, ApiClientExactTarget $root)
    {
        $this->key = $key;
        $this->root = $root;
    }

    protected function log($parameters = null)
    {
        $callee = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1];
        $class = explode('\\', $callee['class']);
        $callee = sprintf('%s%s%s', array_pop($class), $callee['type'], $callee['function']);

        $message = $callee;
        if (is_scalar($parameters)) {
            $message = sprintf('%s: `%s`', $message, $parameters);
        } elseif (is_array($parameters)) {
            $message = sprintf('%s: %s', $message, json_encode($parameters));
        } else {
            $message = sprintf('%s: [%s]', $message, gettype($parameters));
        }
        $this->root->log($message);
    }

    /**
     * Creates an API resource get filter.
     *
     * @see     createDateFilter()      For filtering by date values.
     * @param   string  $propertyName   The property to filter.
     * @param   string  $operator       One of equals, notEquals, greaterThan, or lessThan.
     * @param   string  $value          The value to filter by.
     * @return  array
     */
    public function createFilter($propertyName, $operator, $value)
    {
        return [
            'Property'          => $propertyName,
            'SimpleOperator'    => $operator,
            'Value'             => $value,
        ];
    }

    /**
     * Creates an API resource get filter, where the value is a date.
     *
     * @see     createFilter()          For filtering by regular values.
     * @param   string  $propertyName   The property to filter.
     * @param   string  $operator       One of equals, notEquals, greaterThan, or lessThan.
     * @param   string  $dateValue      The date value to filter by.
     * @return  array
     */
    public function createDateFilter($propertyName, $operator, $dateValue)
    {
        $filter = $this->createFilter($propertyName, $operator, $dateValue);
        $filter['DateValue'] = $filter['Value'];
        unset($filter['Value']);
        return $filter;
    }

    /**
     * Handles a response from the ExactTarget API.
     *
     * @param   object  $response   The SDK response object
     * @return  array
     * @throws  object
     */
    protected function handleResponse($response)
    {
        if (false === $response->status) {
            throw new ResponseException($response);
        }
        return $response;
    }

    /**
     * Gets the handling object from the SDK for making requests against the API resource.
     *
     * @return  object
     */
    abstract protected function getHandlerObject();

    /**
     * Initializes the handling object from the SDK.
     *
     * @return  self
     */
    public function initHandler()
    {
        $handler = $this->getHandlerObject();
        $handler->authStub = $this->root->getClient();
        $this->handler = $handler;
        return $this;
    }

    /**
     * Gets the handling object from the SDK.
     *
     * @return  object
     */
    public function getHandler()
    {
        if (null === $this->handler) {
            $this->initHandler();
        }
        return $this->handler;
    }

    /**
     * Gets the global config from the API client.
     *
     * @return  \Symfony\Component\HttpFoundation\ParameterBag
     */
    public function getClientConfig()
    {
        return $this->root->getConfig();
    }
}
