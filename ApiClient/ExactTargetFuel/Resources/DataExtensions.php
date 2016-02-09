<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\ExactTargetFuel\Resources;

use \ET_DataExtension;

class DataExtensions extends AbstractResource
{
    /**
     * {@inheritDoc}
     */
    protected function getHandlerObject()
    {
        return new ET_DataExtension();
    }

    /**
     * Use the Get method to return information about existing data extensions.
     * Optionally, you can set the props property when using the Get method in order to limit the number of fields returned. If you do not define the props property, the call returns all fields.
     * Optionally, you can set the filter property to limit the number of results returned.
     * @link https://code.exacttarget.com/apis-sdks/fuel-sdks/data-extensions/data-extension-retrieve.html
     *
     * @param   array   $props  The properties (fields) to return with the response. An empty value returns all fields.
     * @param   array   $filter A filter consists of three key/value pairs for filtering by property, operator, and value. @see createFilter()
     * @return  \ET_Get
     */
    public function get(array $props = [], array $filter = [])
    {
        $handler = $this->getHandler();
        $handler->props = $props;
        if (!empty($filter)) {
            $handler->filter = $filter;
        }
        return $handler->get();
    }

    /**
     * Gets a Data Extension by CustomerKey (aka External Key).
     *
     * @param   string  $customerKey
     * @param   array   $props
     * @return  \ET_Get
     */
    public function getByCustomerKey($customerKey, array $props = [])
    {
        $filter = $this->createFilter('CustomerKey', 'equals', $customerKey);
        return $this->get($props, $filter);
    }
}
