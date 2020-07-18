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
class CRM_Event_Form_Task_ParticipantStatus extends CRM_Event_Form_Task_Batch {

  public function buildQuickForm() {
    // CRM_Event_Form_Task_Batch::buildQuickForm() gets ufGroupId
    // from the form, so set it here to the id of the reserved profile
    $dao = new CRM_Core_DAO_UFGroup();
    $dao->name = 'participant_status';
    $dao->find(TRUE);
    $this->set('ufGroupId', $dao->id);

    $statuses = CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label');
    asort($statuses, SORT_STRING);
    $this->add('select', 'status_change', ts('Change All Statuses'),
      [
        '' => ts('- select status -'),
      ] + $statuses
    );

    $this->assign('context', 'statusChange');
    // CRM-4321: display info on users being notified if any of the below statuses is enabled
    parent::assignToTemplate();
    parent::buildQuickForm();
  }

}
