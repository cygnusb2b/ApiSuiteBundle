<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\ExactTarget\Resources;

use \ET_DataExtension_Column;

class DataExtensionColumns extends AbstractResource
{
    /**
     * {@inheritDoc}
     */
    protected function getHandlerObject()
    {
        return new ET_DataExtension_Column();
    }

    /**
     * Use the Get method to return information about existing data extension columns.
     * @link https://code.exacttarget.com/apis-sdks/fuel-sdks/data-extension-columns/data-extension-column-retrieve.html
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
     * Finds data extension columns by the CustomerKey.
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
