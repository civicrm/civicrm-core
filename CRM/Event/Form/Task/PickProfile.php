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
 * This class provides the functionality for batch profile update for event participations
 */
class CRM_Event_Form_Task_PickProfile extends CRM_Event_Form_Task {

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum event participations that should be allowed to update.
   * @var int
   */
  protected $_maxParticipations = 100;

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

    $this->setTitle(ts('Update multiple participants'));

    $validate = FALSE;
    //validations
    if (count($this->_participantIds) > $this->_maxParticipations) {
      CRM_Core_Session::setStatus(ts("The maximum number of records you can select for Update multiple participants is %1. You have selected %2. Please select fewer participants from your search results and try again.", [
        1 => $this->_maxParticipations,
        2 => count($this->_participantIds),
      ]));
      $validate = TRUE;
    }

    // then redirect
    if ($validate) {
      CRM_Utils_System::redirect($this->_userContext);
    }
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $types = ['Participant'];
    $profiles = CRM_Core_BAO_UFGroup::getProfiles($types, TRUE);

    if (empty($profiles)) {
      CRM_Core_Session::setStatus(ts("To use Update multiple participants, you need to configure a profile containing only Participant fields (e.g. Participant Status, Participant Role, etc.). Configure a profile at 'Administer CiviCRM >> Customize >> CiviCRM Profile'."));
      CRM_Utils_System::redirect($this->_userContext);
    }

    $ufGroupElement = $this->add('select', 'uf_group_id', ts('Select Profile'),
      [
        '' => ts('- select profile -'),
      ] + $profiles, TRUE
    );
    $this->addDefaultButtons(ts('Continue'));
  }

  /**
   * Add local and global form rules.
   *
   *
   * @return void
   */
  public function addRules() {
    $this->addFormRule(['CRM_Event_Form_Task_PickProfile', 'formRule']);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    return TRUE;
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->exportValues();

    $this->set('ufGroupId', $params['uf_group_id']);

    // also reset the batch page so it gets new values from the db
    $this->controller->resetPage('Batch');
  }

}
