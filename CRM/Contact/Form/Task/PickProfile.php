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
 * This class provides the functionality for Update multiple contacts
 */
class CRM_Contact_Form_Task_PickProfile extends CRM_Contact_Form_Task {

  /**
   * The title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum contacts that should be allowed to update
   * @var int
   */
  protected $_maxContacts = 100;

  /**
   * Maximum profile fields that will be displayed
   * @var int
   */
  protected $_maxFields = 9;

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

    $validate = FALSE;
    //validations
    if (count($this->_contactIds) > $this->_maxContacts) {
      CRM_Core_Session::setStatus(ts("The maximum number of contacts you can select for Update multiple contacts is %1. You have selected %2. Please select fewer contacts from your search results and try again.", [
        1 => $this->_maxContacts,
        2 => count($this->_contactIds),
      ]), ts('Maximum Exceeded'), 'error');
      $validate = TRUE;
    }

    if (CRM_Contact_BAO_Contact_Utils::checkContactType($this->_contactIds)) {
      CRM_Core_Session::setStatus(ts("Update multiple contacts requires that all selected contacts be the same basic type (e.g. all Individuals OR all Organizations...). Please modify your selection and try again."), ts('Contact Type Mismatch'), 'error');
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
    $this->setTitle(ts('Update multiple contacts'));

    foreach ($this->_contactIds as $id) {
      $this->_contactTypes = CRM_Contact_BAO_Contact::getContactTypes($id);
    }

    //add Contact type profiles
    $this->_contactTypes[] = 'Contact';

    $profiles = CRM_Core_BAO_UFGroup::getProfiles($this->_contactTypes);

    if (empty($profiles)) {
      $types = implode(' ' . ts('or') . ' ', $this->_contactTypes);
      CRM_Core_Session::setStatus(ts("The contact type selected for Update multiple contacts does not have a corresponding profile. Please set up a profile for %1s and try again.", [1 => $types]), ts('No Profile Available'), 'error');
      CRM_Utils_System::redirect($this->_userContext);
    }
    $ufGroupElement = $this->add('select', 'uf_group_id', ts('Select Profile'), ['' => ts('- select profile -')] + $profiles, TRUE, ['class' => 'crm-select2 huge']);

    $this->addDefaultButtons(ts('Continue'));
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(['CRM_Contact_Form_Task_PickProfile', 'formRule']);
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
    if (CRM_Core_BAO_UFField::checkProfileType($fields['uf_group_id'])) {
      $errorMsg['uf_group_id'] = "You cannot select a mixed profile for Update multiple contacts.";
    }

    if (!empty($errorMsg)) {
      return $errorMsg;
    }

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
