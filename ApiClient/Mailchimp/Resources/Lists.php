<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Resources;

use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpException;

class Lists extends AbstractResource
{
    /**
     * Subscribe a batch of email addresses to a list at once.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/batch-subscribe.php
     *
     * @param  string   $listId
     * @param  array    $batch
     * @param  bool     $doubleOptin
     * @param  bool     $updateExisting
     * @param  bool     $replaceInterests
     * @return array
     */
    public function batchSubscribe($listId, array $batch, $doubleOptin = false, $updateExisting = true, $replaceInterests = false)
    {
        $n = 0;
        $batches = [];
        $batchSize = $this->root->getBatchSize();
        foreach (array_values($batch) as $i => $record) {
            if (0 === $i % $batchSize) {
                $n++;
            }
            $batches[$n][] = $record;
        }

        $responses = [];
        foreach ($batches as $batch) {
            $body = [
                'id'                => $listId,
                'batch'             => $batch,
                'double_optin'      => $doubleOptin,
                'update_existing'   => $updateExisting,
                'replace_interests' => $replaceInterests,
            ];

            $responses[] = $this->sendRequest('batch-subscribe', $body);
        }
        return $responses;
    }

    /**
     * Retrieves the member information from a list for a single email address.
     *
     * @param  string  $listId The List ID.
     * @param  string  $email  The Email address.
     * @return array
     * @throws MailchimpException
     */
    public function findMemberByEmail($listId, $email)
    {
        $member = $this->memberInfo($listId, [['email' => $email]]);
        if (!isset($member['data'][0])) {
            throw new MailchimpException(sprintf('Unable to find member info using email %s', $email));
        }
        return $member['data'][0];
    }

    /**
     * Retrieves the member information from a list for a single email id.
     *
     * @param  string  $listId The List ID.
     * @param  string  $euid   The Email UID.
     * @return array
     * @throws MailchimpException
     */
    public function findMemberByEuid($listId, $euid)
    {
        $member = $this->memberInfo($listId, [['euid' => $euid]]);
        if (!isset($member['data'][0])) {
            throw new MailchimpException(sprintf('Unable to find member info using id %s', $euid));
        }
        return $member['data'][0];
    }

    /**
     * Add a single Interest Group.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-group-add.php
     *
     * @param  string   $listId
     * @param  string   $name
     * @param  int      $groupingId
     * @return array
     */
    public function interestGroupAdd($listId, $name, $groupingId)
    {
        $body = [
            'id'            => $listId,
            'group_name'    => $name,
            'grouping_id'   => $groupingId,
        ];
        return $this->sendRequest('interest-group-add', $body);
    }

    /**
     * Delete a single Interest Group.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-group-del.php
     *
     * @param  string   $listId
     * @param  string   $name
     * @param  int      $groupingId
     * @return array
     */
    public function interestGroupDel($listId, $name, $groupingId)
    {
        $body = [
            'id'            => $listId,
            'group_name'    => $name,
            'grouping_id'   => (Integer) $groupingId,
        ];
        return $this->sendRequest('interest-group-del', $body);
    }

    /**
     * Get the list of interest groupings for a given list, including the label, form information, and included groups for each.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/interest-groupings.php
     *
     * @param  string   $listId
     * @param  bool     $counts
     * @return array
     */
    public function interestGroupings($listId, $counts = false)
    {
        $body = [
            'id'    => $listId,
            'counts'=> $counts,
        ];
        return $this->sendRequest('interest-groupings', $body);
    }

    /**
     * Get all the information for particular members of a list.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/member-info.php
     *
     * @param  string       $id     The list id to connect to.
     * @param  array        $emails An array of up to 50 email structs.
     * @return array
     */
    public function memberInfo($id, array $emails)
    {
        $body = [
            'id'    => $id,
            'emails'=> $emails,
        ];
        return $this->sendRequest('member-info', $body);
    }

    /**
     * Add a new merge tag to a given list.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/merge-var-add.php
     *
     * @param  string   $listId
     * @param  string   $tag
     * @param  string   $name
     * @param  array    $options
     * @return array
     */
    public function mergeVarAdd($listId, $tag, $name, array $options = [])
    {
        $body = [
            'id'        => $listId,
            'tag'       => $tag,
            'name'      => $name,
            'options'   => $options,
        ];
        return $this->sendRequest('merge-var-add', $body);
    }

    /**
     * Get the list of merge tags for a given list, including their name, tag, and required setting.
     * @link https://apidocs.mailchimp.com/api/2.0/lists/merge-vars.php
     *
     * @param  string   $listId
     * @return array
     */
    public function mergeVars($listId)
    {
        $body = [
            'id'    => [$listId],
        ];
        return $this->sendRequest('merge-vars', $body);
    }
}
