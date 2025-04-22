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
    $this->bounceOnNoActiveProviders();
    $activityCheck = 0;
    // This is really bad - we are doing a check on a language-specific subject...
    // shouldn't we be using an activity type instead???
    $activitySubject = 'SMS Received';
    foreach ($this->_activityHolderIds as $value) {
      if (CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $value, 'subject', 'id') !== $activitySubject) {
        $activityCheck++;
      }
    }
    if ($activityCheck == count($this->_activityHolderIds)) {
      CRM_Core_Error::statusBounce(ts("The Reply SMS Could only be sent for activities with '%1' subject.",
        [1 => $activitySubject]
      ));
    }
    $this->assign('single', $this->_single);
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->buildSmsForm();
  }

  /**
   * Get the relevant activity name.
   *
   * This is likely to be further refactored/ clarified.
   *
   * @internal
   *
   * @return string
   */
  protected function getActivityName() {
    return 'SMS Received';
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function filterContactIDs(): void {
    $form = $this;
    if (!empty($this->_activityHolderIds)) {
      $extendTargetContacts = 0;
      $invalidActivity = 0;
      $validActivities = 0;
      foreach ($form->_activityHolderIds as $id) {
        //valid activity check
        if (CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $id, 'subject', 'id') !== $this->getActivityName()) {
          $invalidActivity++;
          continue;
        }

        $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
        $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
        //target contacts limit check
        $ids = array_keys(CRM_Activity_BAO_ActivityContact::getNames($id, $targetID));

        if (count($ids) > 1) {
          $extendTargetContacts++;
          continue;
        }
        $validActivities++;
        $form->_contactIds = empty($form->_contactIds) ? $ids : array_unique(array_merge($form->_contactIds, $ids));
      }

      if (!$validActivities) {
        $errorMess = "";
        if ($extendTargetContacts) {
          $errorMess = ts('One selected activity consists of more than one target contact.', [
            'count' => $extendTargetContacts,
            'plural' => '%count selected activities consist of more than one target contact.',
          ]);
        }
        if ($invalidActivity) {
          $errorMess = ($errorMess ? ' ' : '');
          $errorMess .= ts('The selected activity is invalid.', [
            'count' => $invalidActivity,
            'plural' => '%count selected activities are invalid.',
          ]);
        }
        CRM_Core_Error::statusBounce(ts("%1: SMS Reply will not be sent.", [1 => $errorMess]));
      }
    }

    //activity related variables
    $form->assign('invalidActivity', $invalidActivity ?? NULL);
    $form->assign('extendTargetContacts', $extendTargetContacts ?? NULL);
  }

  protected function isInvalidRecipient($contactID): bool {
    //to check for "if the contact id belongs to a specified activity type"
    // @todo use the api instead - function is deprecated.
    $actDetails = CRM_Activity_BAO_Activity::getContactActivity($contactID);
    return $this->getActivityName() !==
      CRM_Utils_Array::retrieveValueRecursive($actDetails, 'subject');
  }

}
