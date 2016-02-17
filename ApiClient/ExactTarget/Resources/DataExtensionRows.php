<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\ExactTarget\Resources;

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
     * @param   string  $extensionName  The Data Extension Name.
     * @param   array   $props          The properties (fields) to return with the response. An empty value returns all fields.
     * @param   array   $filter         A filter consists of three key/value pairs for filtering by property, operator, and value. @see createFilter()
     * @return  \ET_Get
     */
    public function get($extensionName, array $props = [], array $filter = [])
    {
        $this->log($extensionName);
        $handler = $this->getHandler();
        $handler->Name = $extensionName;
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
     * @param   string  $primaryKey     The primary field key of the extension, usually an email field of some kind.
     * @param   string  $value          The primary field value to match. Ensures a unique row is inserted.
     * @param   array   $props          The properties to send: an array of field key/values.
     * @return  \ET_Post
     */
    public function create($extensionKey, $primaryKey, $value, array $props)
    {
        $this->log($extensionKey);
        $props[$primaryKey] = $value;
        $handler = $this->getHandler();
        $handler->CustomerKey = $extensionKey;
        $handler->props = $props;
        return $this->handleResponse($handler->post());
    }

    /**
     * Updates a row in the data extension.
     * The props must contain the primary key and value in order to match the proper record.
     *
     * @param   string  $extensionKey   The Data Extension CustomerKey (aka External Key).
     * @param   string  $primaryKey     The primary field key of the extension, usually an email field of some kind.
     * @param   string  $value          The primary field value to match. Selects the row for update.
     * @param   array   $props          The properties to send: an array of field key/values.
     * @return  \ET_Post
     */
    public function update($extensionKey, $primaryKey, $value, array $props)
    {
        $this->log($extensionKey);
        $props[$primaryKey] = $value;
        $handler = $this->getHandler();
        $handler->CustomerKey = $extensionKey;
        $handler->props = $props;
        return $this->handleResponse($handler->patch());
    }

    /**
     * Deletes a row in the data extension.
     *
     * @param   string  $extensionKey   The Data Extension CustomerKey (aka External Key).
     * @param   string  $primaryKey     The primary field key of the extension, usually an email field of some kind.
     * @param   string  $value          The primary field value to match. Selects the row for deletion.
     * @return  \ET_Post
     */
    public function delete($extensionKey, $primaryKey, $value)
    {
        $this->log($extensionKey);
        $handler = $this->getHandler();
        $handler->CustomerKey = $extensionKey;
        $handler->props = [$primaryKey => $value];
        return $handler->delete();
    }
}
