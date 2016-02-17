<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\ExactTarget\Resources;

use \ET_Subscriber;

class Subscribers extends AbstractResource
{
    /**
     * {@inheritDoc}
     */
    protected function getHandlerObject()
    {
        return new ET_Subscriber();
    }

    /**
     * Use the Get method to return information about existing subscribers.
     * Optionally, you can set the props property when using the Get method in order to limit the number of fields returned. If you do not define the props property, the call returns all fields.
     * @link https://code.exacttarget.com/apis-sdks/fuel-sdks/subscribers/subscriber-retrieve.html
     *
     * @param   array   $props  The properties (fields) to return with the response. An empty value returns all fields.
     * @param   array   $filter A filter consists of three key/value pairs for filtering by property, operator, and value. @see createFilter()
     * @return  \ET_Get
     */
    public function get(array $props = [], array $filter = [])
    {
        $this->log($props);
        $handler = $this->getHandler();
        $handler->props = $props;
        if (!empty($filter)) {
            $handler->filter = $filter;
        }
        return $handler->get();
    }
}
