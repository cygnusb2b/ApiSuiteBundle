<?php
namespace Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Resources;

use Cygnus\ApiSuiteBundle\ApiClient\Mailchimp\Exception\MailchimpException;

class Reports extends AbstractResource
{
    /**
     * Retrieve relevant aggregate campaign statistics (opens, bounces, clicks, etc.).
     * @link https://apidocs.mailchimp.com/api/2.0/reports/summary.php
     *
     * @param  string   $cid
     * @return array
     */
    public function summary($cid)
    {
        $body = [
            'cid'       => $cid,
        ];
        return $this->sendRequest('summary', $body);
    }

    /**
     * Retrieve the list of email addresses that opened a given campaign with how many times they opened.
     * @link https://apidocs.mailchimp.com/api/2.0/reports/summary.php
     *
     * @param  string   $cid
     * @param  int      $start      Optional for large data sets, the page number to start at - defaults to 1st page of data (page 0)
     * @param  int      $limit      Optional for large data sets, the number of results to return - defaults to 25, upper limit set at 100
     * @param  string   $sortField  Optional the data to sort by - "opened" (order opens occurred, default) or "opens" (total number of opens). Invalid fields will fall back on the default.
     * @param  string   $sortDir    Optional the direct - ASC or DESC. defaults to ASC (case insensitive)
     * @return array
     */
    public function opened($cid, $start = 0, $limit = 25, $sortField = 'opened', $sortDir = 'ASC')
    {
        $body = [
            'cid'       => $cid,
            'opts'      => [
                'start'         => $start,
                'limit'         => $limit,
                'sort_field'    => $sortField,
                'sort_dir'      => $sortDir,
            ],
        ];
        return $this->sendRequest('opened', $body);
    }
}
