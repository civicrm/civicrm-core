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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality to sms a group of contacts.
 */
class CRM_Activity_Form_Task_SMS extends CRM_Activity_Form_Task {

  use CRM_Contact_Form_Task_SMSTrait;

  /**
   * Are we operating in "single mode", i.e. sending sms to one
   * specific contact?
   *
   * @var bool
   */
  public $_single = FALSE;

  /**
   * All the existing templates in the system.
   *
   * @var array
   */
  public $_templates = NULL;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    parent::preProcess();
    $form = $this;
    $this->bounceOnNoActiveProviders();
    $activityCheck = 0;
    foreach ($this->_activityHolderIds as $value) {
      if (CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $value, 'subject', 'id') != CRM_Contact_Form_Task_SMSCommon::RECIEVED_SMS_ACTIVITY_SUBJECT) {
        $activityCheck++;
      }
    }
    if ($activityCheck == count($this->_activityHolderIds)) {
      CRM_Core_Error::statusBounce(ts("The Reply SMS Could only be sent for activities with '%1' subject.",
        [1 => CRM_Contact_Form_Task_SMSCommon::RECIEVED_SMS_ACTIVITY_SUBJECT]
      ));
    }
    $this->assign('single', $this->_single);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    // Enable form element.
    $this->assign('SMSTask', TRUE);
    CRM_Contact_Form_Task_SMSCommon::buildQuickForm($this);
  }

}
