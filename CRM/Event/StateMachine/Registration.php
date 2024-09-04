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
 * State machine for managing different states of the EventWizard process.
 */
class CRM_Event_StateMachine_Registration extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param object $controller
   * @param int $action
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);
    try {
      $id = CRM_Utils_Request::retrieve('id', 'Positive', $controller, TRUE);
    }
    catch (CRM_Core_Exception $e) {
      CRM_Utils_System::sendInvalidRequestResponse(ts('Missing Event ID'));
    }
    $is_monetary = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $id, 'is_monetary');
    $is_confirm_enabled = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $id, 'is_confirm_enabled');

    $pages = ['CRM_Event_Form_Registration_Register' => NULL];

    //handle additional participant scenario, where we need to insert participant pages on runtime
    $additionalParticipant = NULL;

    // check that the controller has some data, hence we dont send the form name
    // which results in an invalid argument error
    $values = $controller->exportValues();
    //first check POST value then QF
    if (isset($_POST['additional_participants']) && CRM_Utils_Rule::positiveInteger($_POST['additional_participants'])) {
      // we need to use $_POST since the QF framework has not yet been called
      // and the additional participants page is the next one, so need to set this up
      // now
      $additionalParticipant = $_POST['additional_participants'];
    }
    elseif (isset($values['additional_participants']) && CRM_Utils_Rule::positiveInteger($values['additional_participants'])) {
      $additionalParticipant = $values['additional_participants'];
    }

    if ($additionalParticipant) {
      $additionalParticipant = CRM_Utils_Type::escape($additionalParticipant, 'Integer');
      $controller->set('addParticipant', $additionalParticipant);
    }

    //to add instances of Additional Participant page, only if user has entered any additional participants
    if ($additionalParticipant) {
      $extraPages = CRM_Event_Form_Registration_AdditionalParticipant::getPages($additionalParticipant);
      $pages = array_merge($pages, $extraPages);
    }

    $additionalPages = [
      'CRM_Event_Form_Registration_Confirm' => NULL,
      'CRM_Event_Form_Registration_ThankYou' => NULL,
    ];

    $pages = array_merge($pages, $additionalPages);

    // CRM-11182 - Optional confirmation screen
    if (!$is_confirm_enabled && !$is_monetary) {
      unset($pages['CRM_Event_Form_Registration_Confirm']);
    }

    $this->addSequentialPages($pages);
  }

}
