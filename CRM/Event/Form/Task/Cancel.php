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

/**
 * This class provides the functionality for cancel registration for event participations
 */
class CRM_Event_Form_Task_Cancel extends CRM_Event_Form_Task {

  /**
   * Variable to store redirect path.
   * @var string
   */
  protected $_userContext;

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    // initialize the task and row fields
    parent::preProcess();

    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->setTitle(ts('Cancel Registration for Event Participation'));
    $session = CRM_Core_Session::singleton();
    $this->addDefaultButtons(ts('Cancel Registrations'), 'done');
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->exportValues();
    $value = [];

    foreach ($this->_participantIds as $participantId) {
      $value['id'] = $participantId;

      // Cancelled status id = 4
      $value['status_id'] = 4;
      CRM_Event_BAO_Participant::create($value);
    }
  }

}
