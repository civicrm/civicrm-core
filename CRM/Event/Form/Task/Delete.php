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
 * $Id$
 *
 */

/**
 * This class provides the functionality to delete a group of
 * participations. This class provides functionality for the actual
 * deletion.
 */
class CRM_Event_Form_Task_Delete extends CRM_Event_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific participation?
   *
   * @var bool
   */
  protected $_single = FALSE;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {

    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviEvent', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }
    parent::preProcess();
    foreach ($this->_participantIds as $participantId) {
      if (CRM_Event_BAO_Participant::isPrimaryParticipant($participantId)) {
        $this->assign('additionalParticipants', TRUE);
      }
    }
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $deleteParticipants = [
      1 => ts('Delete this participant record along with associated participant record(s).'),
      2 => ts('Delete only this participant record.'),
    ];

    $this->addRadio('delete_participant', NULL, $deleteParticipants, NULL, '<br />');
    $this->setDefaults(['delete_participant' => 1]);

    $this->addDefaultButtons(ts('Delete Participations'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    $participantLinks = NULL;
    if (CRM_Utils_Array::value('delete_participant', $params) == 2) {
      $links = [];
      foreach ($this->_participantIds as $participantId) {
        $additionalId = (CRM_Event_BAO_Participant::getAdditionalParticipantIds($participantId));
        $participantLinks = (CRM_Event_BAO_Participant::getAdditionalParticipantUrl($additionalId));
      }
    }
    $deletedParticipants = $additionalCount = 0;
    foreach ($this->_participantIds as $participantId) {
      if (CRM_Utils_Array::value('delete_participant', $params) == 1) {
        $primaryParticipantId = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Participant", $participantId, 'registered_by_id', 'id');
        if (CRM_Event_BAO_Participant::isPrimaryParticipant($participantId)) {
          $additionalIds = (CRM_Event_BAO_Participant::getAdditionalParticipantIds($participantId));
          $additionalCount += count($additionalIds);
          foreach ($additionalIds as $value) {
            CRM_Event_BAO_Participant::deleteParticipant($value);
          }
          CRM_Event_BAO_Participant::deleteParticipant($participantId);
          $deletedParticipants++;
        }
        // delete participant only if it is not an additional participant
        // or if it is additional and its primary participant is not selected in $this->_participantIds.
        elseif (empty($primaryParticipantId) || (!in_array($primaryParticipantId, $this->_participantIds))) {
          CRM_Event_BAO_Participant::deleteParticipant($participantId);
          $deletedParticipants++;
        }
      }
      else {
        CRM_Event_BAO_Participant::deleteParticipant($participantId);
        $deletedParticipants++;
      }
    }
    if ($additionalCount) {
      $deletedParticipants += $additionalCount;
    }

    $status = ts('%count participant deleted.', ['plural' => '%count participants deleted.', 'count' => $deletedParticipants]);

    if ($participantLinks) {
      $status .= '<p>' . ts('The following participants no longer have an event fee recorded. You can edit their registration and record a replacement contribution by clicking the links below:')
        . '</p>' . $participantLinks;
    }
    CRM_Core_Session::setStatus($status, ts('Removed'), 'info');
  }

}
