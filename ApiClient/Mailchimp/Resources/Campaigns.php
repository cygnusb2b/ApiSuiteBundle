<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Resources;

use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpException;

class Campaigns extends AbstractResource
{
    /**
     * Get the content (both html and text) for a campaign either as it would appear in the campaign archive or as the raw, original content.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/content.php
     *
     * @param  string   $cid
     * @param  array    $options
     * @return array
     */
    public function content($cid, array $options = [])
    {
        $body = [
            'cid'       => $cid,
            'options'   => $options,
        ];
        return $this->sendRequest('content', $body);
    }

    /**
     * Create a new draft campaign to send. You can not have more than 32,000 campaigns in your account.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/create.php
     *
     * @param  string   $type
     * @param  array    $options
     * @param  array    $content
     * @param  array    $segmentOpts
     * @param  array    $typeOpts
     * @return array
     */
    public function create($type = 'regular', array $options, array $content, array $segmentOpts = [], array $typeOpts = [])
    {
        $body = [
            'type'          => $type,
            'options'       => $options,
            'content'       => $content,
            'segment_opts'  => $segmentOpts,
            'type_opts'     => $typeOpts,
        ];
        return $this->sendRequest('create', $body);
    }

    /**
     * Delete a campaign. Seriously, "poof, gone!" - be careful! Seriously, no one can undelete these.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/delete.php
     *
     * @param  string   $cid
     * @return array
     */
    public function delete($cid)
    {
        $body = [
            'cid'   => $cid,
        ];
        return $this->sendRequest('delete', $body);
    }

    /**
     * Retrieves a single campaign by id.
     *
     * @param  string  $id
     * @return array
     * @throws MailchimpException
     */
    public function findById($id)
    {
        $campaign = $this->getList(['campaign_id' => $id]);
        if (!isset($campaign['data'][0])) {
            throw new MailchimpException(sprintf('Unable to find campaign using id %s', $id));
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
    public function getList(array $filters = [], $start = 0, $limit = 25, $sortField = 'create_time', $sortDir = 'DESC')
    {
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
        return $this->sendRequest('list', $body);
    }

    /**
     * Replicate a campaign.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/replicate.php
     *
     * @param  string   $cid
     * @return array
     */
    public function replicate($cid)
    {
        $body = [
            'cid'   => $cid,
        ];
        return $this->sendRequest('replicate', $body);
    }

    /**
     * Returns information on whether a campaign is ready to send and possible issues we may have detected with it.
     * Very similar to the confirmation step in the app.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/ready.php
     *
     * @param  string   $cid
     * @return array
     */
    public function ready($cid)
    {
        $body = [
            'cid'   => $cid,
        ];
        return $this->sendRequest('unschedule', $body);
    }

    /**
     * Schedule a campaign to be sent in the future.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/schedule.php
     *
     * @param  string           $cid
     * @param  \DateTime        $scheduleTime
     * @param  \DateTime|null   $scheduleTimeB
     * @return array
     */
    public function schedule($cid, \DateTime $scheduleTime, \DateTime $scheduleTimeB = null)
    {
        $format = 'Y-m-d H:i:s';
        $body = [
            'cid'           => $cid,
            'schedule_time' => $scheduleTime->format($format),
        ];
        if (null !== $scheduleTimeB) {
            $body['schedule_time_b'] = $scheduleTimeB->format($format);
        }
        return $this->sendRequest('schedule', $body);
    }

    /**
     * Allows one to test their segmentation rules before creating a campaign using them.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/segment-test.php
     *
     * @param  string   $cid
     * @param  array    $options
     * @return array
     */
    public function segmentTest($listId, array $options)
    {
        $body = [
            'list_id'   => $listId,
            'options'   => $options,
        ];
        return $this->sendRequest('segment-test', $body);
    }

    /**
     * Send a given campaign immediately. For RSS campaigns, this will "start" them.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/send.php
     *
     * @param  string   $cid
     * @return array
     */
    public function send($cid)
    {
        $body = [
            'cid'   => $cid,
        ];
        return $this->sendRequest('send', $body);
    }

    /**
     * Send a test of this campaign to the provided email addresses.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/send-test.php
     *
     * @param  string   $cid
     * @param  array    $testEmails
     * @param  string   $sendType
     * @return array
     */
    public function sendTest($cid, array $testEmails, $sendType = 'html')
    {
        $body = [
            'cid'           => $cid,
            'test_emails'   => $testEmails,
            'send_type'     => $sendType,
        ];
        return $this->sendRequest('send-test', $body);
    }

    /**
     * Update just about any setting besides type for a campaign that has not been sent.
     * See campaigns/create() for details. Caveats:
     *
     * - If you set a new list_id, all segmentation options will be deleted and must be re-added.
     * - If you set template_id, you need to follow that up by setting it's 'content'
     * - If you set segment_opts, you should have tested your options against campaigns/segment-test().
     * - To clear/unset segment_opts, pass an empty string or array as the value. Various wrappers may require one or the other.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/update.php
     *
     * @param  string   $cid
     * @param  string   $name
     * @param  array    $value
     * @return array
     */
    public function update($cid, $name, array $value)
    {
        $body = [
            'cid'   => $cid,
            'name'  => $name,
            'value' => $value,
        ];
        return $this->sendRequest('update', $body);
    }

    /**
     * Unschedule a campaign that is scheduled to be sent in the future.
     * @link https://apidocs.mailchimp.com/api/2.0/campaigns/unschedule.php
     *
     * @param  string   $cid
     * @return array
     */
    public function unschedule($cid)
    {
        $body = [
            'cid'   => $cid,
        ];
        return $this->sendRequest('unschedule', $body);
    }
}
