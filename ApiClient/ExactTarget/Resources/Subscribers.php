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
     * Creates a subscriber attribute.
     *
     * @param   string  $name
     * @param   string  $value
     * @return  array
     */
    public function createAttribute($name, $value)
    {
        return ['Name' => $name, 'Value' => $value];
    }

    /**
     * Use the Patch method to update an existing subscriber.
     * @link https://code.exacttarget.com/apis-sdks/fuel-sdks/subscribers/subscriber-update.html
     *
     * @param   string  $subscriberKey
     * @param   array   $props
     * @param   array   $attributes
     * @return  \ET_Patch
     */
    public function patch($subscriberKey, array $props = [], array $attributes = [])
    {
        $props['SubscriberKey'] = $subscriberKey;
        if (!empty($attributes)) {
            $props['Attributes'] = $attributes;
        }

        $this->log($props);
        $handler = $this->getHandler();
        $handler->props = $props;
        return $handler->patch();
    }

    /**
     * Use the Post method to create a new subscriber within a Marketing Cloud account.
     * @link https://code.exacttarget.com/apis-sdks/fuel-sdks/subscribers/subscriber-create.html
     *
     * @param   string  $emailAddress
     * @param   string  $subscriberKey
     * @param   array   $props
     * @param   array   $attributes
     * @return  \ET_Post
     */
    public function post($emailAddress, $subscriberKey, array $props = [], array $attributes = [])
    {
        $props['EmailAddress'] = $emailAddress;
        $props['SubscriberKey'] = $subscriberKey;
        if (!empty($attributes)) {
            $props['Attributes'] = $attributes;
        }

        $this->log($props);
        $handler = $this->getHandler();
        $handler->props = $props;
        return $handler->post();
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
