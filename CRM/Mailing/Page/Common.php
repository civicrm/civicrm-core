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
class CRM_Mailing_Page_Common extends CRM_Core_Page {
  protected $_type = NULL;

  /**
   * Run page.
   *
   * This includes assigning smarty variables and other page processing.
   *
   * @return string
   * @throws Exception
   */
  public function run() {
    $job_id = CRM_Utils_Request::retrieve('jid', 'Integer');
    $queue_id = CRM_Utils_Request::retrieve('qid', 'Integer');
    $hash = CRM_Utils_Request::retrieve('h', 'String');

    // @todo - stop requiring job - at least for actions where it is not required
    // as queue_id + hash is expected to be enough now.
    if (!$job_id ||
      !$queue_id ||
      !$hash
    ) {
      throw new CRM_Core_Exception(ts("Missing input parameters"));
    }

    // verify that the three numbers above match
    $q = CRM_Mailing_Event_BAO_MailingEventQueue::verify(NULL, $queue_id, $hash);
    if (!$q) {
      throw new CRM_Core_Exception(ts("There was an error in your request"));
    }

    $cancel = CRM_Utils_Request::retrieve("_qf_{$this->_type}_cancel", 'String');
    if (isset($cancel)) {
      $config = CRM_Core_Config::singleton();
      CRM_Utils_System::redirect($config->userFrameworkBaseURL);
    }

    $confirm = CRM_Utils_Request::retrieve('confirm', 'Boolean');

    list($displayName, $email) = CRM_Mailing_Event_BAO_MailingEventQueue::getContactInfo($queue_id);
    $this->assign('display_name', $displayName);
    $this->assign('email', $email);
    $this->assign('confirm', $confirm);

    $groups = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing(NULL, $queue_id, $hash, TRUE);
    $this->assign('groups', $groups ?? []);
    $groupExist = NULL;
    foreach ($groups as $value) {
      // How about we just array_filter - only question is before or after the assign?
      if ($value) {
        $groupExist = TRUE;
      }
    }
    // @todo - can we just check if groups is empty here & in the template?
    $this->assign('groupExist', $groupExist);

    if ($confirm) {
      if ($this->_type === 'unsubscribe') {
        $groups = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing(NULL, $queue_id, $hash);
        if (!empty($groups)) {
          CRM_Mailing_Event_BAO_MailingEventUnsubscribe::send_unsub_response($queue_id, $groups, FALSE, $job_id);
        }
        else {
          // should we indicate an error, or just ignore?
        }
      }
      elseif ($this->_type === 'resubscribe') {
        $groups = CRM_Mailing_Event_BAO_MailingEventResubscribe::resub_to_mailing($job_id, $queue_id, $hash);
        if (!empty($groups)) {
          CRM_Mailing_Event_BAO_MailingEventResubscribe::send_resub_response($queue_id, $groups, $job_id);
        }
        else {
          // should we indicate an error, or just ignore?
        }
      }
      else {
        if (CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_domain(NULL, $queue_id, $hash)) {
          CRM_Mailing_Event_BAO_MailingEventUnsubscribe::send_unsub_response($queue_id, NULL, TRUE, $job_id);
        }
        else {
          // should we indicate an error, or just ignore?
        }
      }
    }
    else {
      $confirmURL = CRM_Utils_System::url("civicrm/mailing/{$this->_type}",
        "reset=1&jid={$job_id}&qid={$queue_id}&h={$hash}&confirm=1"
      );
      $this->assign('confirmURL', $confirmURL);
      // push context for further process CRM-4431
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext($confirmURL);
    }

    return parent::run();
  }

}
