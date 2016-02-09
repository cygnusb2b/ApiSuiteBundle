<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\ExactTargetFuel\Resources;

use \ET_DataExtension_Row;

class DataExtensionRows extends AbstractResource
{
    /**
     * {@inheritDoc}
     */
    protected function getHandlerObject()
    {
        return new ET_DataExtension_Row();
    }

    /**
     * Use the Get method to return data from existing data extension rows.
     * @link https://code.exacttarget.com/apis-sdks/fuel-sdks/data-extension-rows/data-extension-row-retrieve.html
     *
     * @param   string  $extensionKey   The Data Extension CustomerKey (aka External Key).
     * @param   array   $props  The properties (fields) to return with the response. An empty value returns all fields.
     * @param   array   $filter A filter consists of three key/value pairs for filtering by property, operator, and value. @see createFilter()
     * @return  \ET_Get
     */
    public function get($extensionKey, array $props = [], array $filter = [])
    {
        $handler = $this->getHandler();
        $handler->CustomerKey = $extensionKey;
        $handler->props = $props;
        if (!empty($filter)) {
            $handler->filter = $filter;
        }
        return $handler->get();
    }

    /**
     * Creates a new row in the data extension.
     *
     * @param   string  $extensionKey   The Data Extension CustomerKey (aka External Key).
     * @param   array   $props          The properties to send: an array of field key/values.
     * @return  \ET_Post
     */
    public function create($extensionKey, array $props)
    {
        $handler = $this->getHandler();
        $handler->CustomerKey = $extensionKey;
        $handler->props = $props;
        return $this->handleResponse($handler->post());
    }
}
