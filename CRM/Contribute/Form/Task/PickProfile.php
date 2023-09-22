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
 * This class provides the functionality for batch profile update for contributions.
 */
class CRM_Contribute_Form_Task_PickProfile extends CRM_Contribute_Form_Task {

  /**
   * The title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum contributions that should be allowed to update
   * @var int
   */
  protected $_maxContributions = 100;

  /**
   * Variable to store redirect path
   * @var string
   */
  protected $_userContext;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    // initialize the task and row fields
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();

    $this->setTitle(ts('Update multiple contributions'));

    $validate = FALSE;
    //validations
    if (count($this->_contributionIds) > $this->_maxContributions) {
      CRM_Core_Session::setStatus(ts("The maximum number of contributions you can select for Update multiple contributions is %1. You have selected %2. Please select fewer contributions from your search results and try again.", [
        1 => $this->_maxContributions,
        2 => count($this->_contributionIds),
      ]), ts('Update multiple records error'), 'error');
      $validate = TRUE;
    }

    // than redirect
    if ($validate) {
      CRM_Utils_System::redirect($this->_userContext);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {

    $types = ['Contribution'];
    $profiles = CRM_Core_BAO_UFGroup::getProfiles($types, TRUE);

    if (empty($profiles)) {
      CRM_Core_Session::setStatus(ts("You will need to create a Profile containing the %1 fields you want to edit before you can use Update multiple contributions. Navigate to Administer CiviCRM > Customize Data and Screens > CiviCRM Profile to configure a Profile. Consult the online Administrator documentation for more information.", [1 => $types[0]]), ts('Profile Required'), 'error');
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
   */
  public function addRules() {
    $this->addFormRule(['CRM_Contribute_Form_Task_PickProfile', 'formRule']);
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
   */
  public function postProcess() {
    $params = $this->exportValues();

    $this->set('ufGroupId', $params['uf_group_id']);

    // also reset the batch page so it gets new values from the db
    $this->controller->resetPage('Batch');
  }

}
