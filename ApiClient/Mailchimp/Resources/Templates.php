<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Resources;

use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpException;

class Templates extends AbstractResource
{
    /**
     * Create a new user template, NOT campaign content.
     * These templates can then be applied while creating campaigns.
     * @link https://apidocs.mailchimp.com/api/2.0/templates/add.php
     *
     * @param  string   $name
     * @param  string   $html
     * @param  int|null $folderId
     * @return array
     */
    public function add($name, $html, $folderId = null)
    {
        $body = [
            'name'  => $name,
            'html'  => $html,
        ];
        if (null !== $folderId) {
            $body['folder_id'] = (Integer) $folderId;
        }
        return $this->sendRequest('add', $body);
    }

    /**
     * Delete (deactivate) a user template.
     * @link https://apidocs.mailchimp.com/api/2.0/templates/del.php
     *
     * @param  int  $templateId
     * @return array
     */
    public function del($templateId)
    {
        $body = [
            'template_id'   => (Integer) $templateId,
        ];
        return $this->sendRequest('del', $body);
    }

    /**
     * Retrieve various templates available in the system, allowing some thing similar to our template gallery to be created.
     * @link https://apidocs.mailchimp.com/api/2.0/templates/list.php
     *
     * @param  array    $types
     * @param  array    $filters
     * @return array
     */
    public function getList(array $types = [], array $filters = [])
    {
        $body = [
            'types'     => $types,
            'filters'   => $filters,
        ];
        return $this->sendRequest('list', $body);
    }

    /**
     * Pull details for a specific template to help support editing.
     * @link https://apidocs.mailchimp.com/api/2.0/templates/info.php
     *
     * @param  int      $templateId
     * @param  string   $type
     * @return array
     */
    public function info($templateId, $type = 'user')
    {
        $body = [
            'template_id'   => (Integer) $templateId,
            'type'          => $type,
        ];
        return $this->sendRequest('info', $body);
    }

    /**
     * Undelete (reactivate) a user template.
     * @link https://apidocs.mailchimp.com/api/2.0/templates/undel.php
     *
     * @param  int  $templatedId
     * @return array
     */
    public function undel($templateId)
    {
        $body = [
            'template_id'   => (Integer) $templateId,
        ];
        return $this->sendRequest('undel', $body);
    }

    /**
     * Replace the content of a user template, NOT campaign content.
     * @link https://apidocs.mailchimp.com/api/2.0/templates/update.php
     *
     * @param  int      $templateId
     * @param  array    $values
     * @return array
     */
    public function update($templateId, array $values)
    {
        $body = [
            'template_id'   => (Integer) $templateId,
            'values'        => $values,
        ];
        return $this->sendRequest('update', $body);
    }
}
