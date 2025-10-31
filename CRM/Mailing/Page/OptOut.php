<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Mailing_Page_OptOut extends CRM_Core_Page {

  /**
   * Run page.
   *
   * This includes assigning smarty variables and other page processing.
   *
   * @return string
   * @throws Exception
   */
  public function run() {
    $isOneClick = ($_SERVER['REQUEST_METHOD'] === 'POST' && CRM_Utils_Request::retrieve('List-Unsubscribe', 'String') === 'One-Click');
    if ($isOneClick) {
      $this->handleOneClick();
      return NULL;
    }

    $wrapper = new CRM_Utils_Wrapper();
    return $wrapper->run('CRM_Mailing_Form_Optout', $this->_title);
  }

  /**
   *
   * Pre-condition: Validated the _job_id, _queue_id, _hash.
   * Post-condition: Unsubscribed
   *
   * @link https://datatracker.ietf.org/doc/html/rfc8058
   * @return void
   */
  public function handleOneClick(): void {
    $jobId = CRM_Utils_Request::retrieve('jid', 'Integer');
    $queueId = CRM_Utils_Request::retrieve('qid', 'Integer');
    $hash = CRM_Utils_Request::retrieve('h', 'String');

    $q = CRM_Mailing_Event_BAO_MailingEventQueue::verify(NULL, $queueId, $hash);
    if (!$q) {
      CRM_Utils_System::sendResponse(
        new \GuzzleHttp\Psr7\Response(400, [], ts("Invalid request: bad parameters"))
      );
    }

    if (CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_domain($jobId, $queueId, $hash)) {
      CRM_Mailing_Event_BAO_MailingEventUnsubscribe::send_unsub_response($queueId, NULL, TRUE, $jobId);
    }

    CRM_Utils_System::sendResponse(
      new \GuzzleHttp\Psr7\Response(200, [], 'OK')
    );
  }

}
