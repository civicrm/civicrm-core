<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
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
      array(
        '' => ts('- select status -'),
      ) + $statuses
    );

    # CRM-4321: display info on users being notified if any of the below statuses is enabled
    self::assignToTemplate('statusChange');

    parent::buildQuickForm();
  }

  public function assignToTemplate($context) {
    $notifyingStatuses = array('Pending from waitlist', 'Pending from approval', 'Expired', 'Cancelled');
    $notifyingStatuses = array_intersect($notifyingStatuses, CRM_Event_PseudoConstant::participantStatus());
    $statuses = implode(', ', $notifyingStatuses);
    $status = ts('Participants whose status is changed FROM Pending Pay Later TO Registered or Attended will receive a confirmation email and their payment status will be set to completed. If this is not you want to do, you can change their participant status by editing their event registration record directly.');
    if (!empty($notifyingStatuses)) {
      $status .= '<br />' .ts("Participants whose status is changed TO any of the following will be automatically notified via email: %1", array(1 => $statuses));
    }
    $this->assign('status', $status);
    $this->assign('context', $context);
  }
}
