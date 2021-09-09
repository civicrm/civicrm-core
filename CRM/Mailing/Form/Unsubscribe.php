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
class CRM_Mailing_Form_Unsubscribe extends CRM_Core_Form {

  /**
   * Prevent people double-submitting the form (e.g. by double-clicking).
   * https://lab.civicrm.org/dev/core/-/issues/1773
   *
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * @var int
   */
  private $_job_id;

  /**
   * @var int
   */
  private $_queue_id;

  /**
   * @var string
   */
  private $_hash;

  /**
   * @var string
   */
  private $_email;

  public function preProcess() {
    $this->_job_id = $job_id = CRM_Utils_Request::retrieve('jid', 'Integer', $this);
    $this->_queue_id = $queue_id = CRM_Utils_Request::retrieve('qid', 'Integer', $this);
    $this->_hash = $hash = CRM_Utils_Request::retrieve('h', 'String', $this);
    $isConfirm = CRM_Utils_Request::retrieveValue('confirm', 'Boolean', FALSE, FALSE, 'GET');

    if (!$job_id || !$queue_id || !$hash) {
      throw new CRM_Core_Exception(ts('Missing Parameters'));
    }

    // verify that the three numbers above match
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);
    if (!$q) {
      throw new CRM_Core_Exception(ts("There was an error in your request"));
    }

    list($displayName, $email) = CRM_Mailing_Event_BAO_Queue::getContactInfo($queue_id);
    $this->assign('display_name', $displayName);
    $emailMasked = CRM_Utils_String::maskEmail($email);
    $this->assign('email_masked', $emailMasked);
    $this->assign('email', $email);
    $this->_email = $email;

    $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($job_id, $queue_id, $hash, TRUE);
    $this->assign('groups', $groups);
    $groupExist = NULL;
    foreach ($groups as $value) {
      if ($value) {
        $groupExist = TRUE;
      }
    }
    if (!$groupExist && !$isConfirm) {
      $statusMsg = ts('%1 has already been unsubscribed.', [1 => $email]);
      CRM_Core_Session::setStatus($statusMsg, '', 'error');
    }
    $this->assign('groupExist', $groupExist);
  }

  public function buildQuickForm() {
    CRM_Utils_System::addHTMLHead('<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">');
    $this->setTitle(ts('Unsubscribe Confirmation'));

    $buttons = [
      [
        'type' => 'next',
        'name' => ts('Unsubscribe'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ];

    $this->addButtons($buttons);
  }

  public function postProcess() {
    $confirmURL = CRM_Utils_System::url("civicrm/mailing/unsubscribe", "reset=1&jid={$this->_job_id}&qid={$this->_queue_id}&h={$this->_hash}&confirm=1");
    $this->assign('confirmURL', $confirmURL);
    CRM_Core_Session::singleton()->pushUserContext($confirmURL);

    // Email address verified
    $groups = CRM_Mailing_Event_BAO_Unsubscribe::unsub_from_mailing($this->_job_id, $this->_queue_id, $this->_hash);

    if (count($groups)) {
      CRM_Mailing_Event_BAO_Unsubscribe::send_unsub_response($this->_queue_id, $groups, FALSE, $this->_job_id);
    }

    $statusMsg = ts('%1 has been unsubscribed successfully.', [1 => $this->_email]);
    CRM_Core_Session::setStatus($statusMsg, '', 'success');
  }

}
