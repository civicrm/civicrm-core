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
 * This class provides the functionality to email a group of contacts.
 */
class CRM_Activity_Form_Task_PickOption extends CRM_Activity_Form_Task {

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum Activities that should be allowed to update.
   * @var int
   */
  protected $_maxActivities = 100;

  /**
   * Variable to store redirect path.
   * @var int
   */
  protected $_userContext;

  /**
   * Variable to store contact Ids.
   * @var array
   */
  public $_contacts;

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {

    // initialize the task and row fields.
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();

    $this->setTitle(ts('Send Email to Contacts'));

    $validate = FALSE;
    //validations
    if (count($this->_activityHolderIds) > $this->_maxActivities) {
      CRM_Core_Session::setStatus(ts("The maximum number of Activities you can select to send an email is %1. You have selected %2. Please select fewer Activities from your search results and try again.", [
        1 => $this->_maxActivities,
        2 => count($this->_activityHolderIds),
      ]), ts("Maximum Exceeded"), "error");
      $validate = TRUE;
    }
    // then redirect
    if ($validate) {
      CRM_Utils_System::redirect($this->_userContext);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addElement('checkbox', 'with_contact', ts('With Contact'));
    $this->addElement('checkbox', 'assigned_to', ts('Assigned to Contact'));
    $this->addElement('checkbox', 'created_by', ts('Created by'));
    $this->setDefaults(['with_contact' => 1]);
    $this->addDefaultButtons(ts('Continue'));
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(['CRM_Activity_Form_Task_PickOption', 'formRule']);
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
    if (!isset($fields['with_contact']) &&
      !isset($fields['assigned_to']) &&
      !isset($fields['created_by'])
    ) {
      return ['with_contact' => ts('You must select at least one email recipient type.')];
    }
    return TRUE;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    // Clear any formRule errors from Email form in case they came back here via Cancel button
    $this->controller->resetPage('Email');
    $params = $this->exportValues();
    $this->_contacts = [];

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);
    // Get assignee contacts.
    if (!empty($params['assigned_to'])) {
      foreach ($this->_activityHolderIds as $key => $id) {
        $ids = array_keys(CRM_Activity_BAO_ActivityContact::getNames($id, $assigneeID));
        $this->_contacts = array_merge($this->_contacts, $ids);
      }
    }
    // Get target contacts.
    if (!empty($params['with_contact'])) {
      foreach ($this->_activityHolderIds as $key => $id) {
        $ids = array_keys(CRM_Activity_BAO_ActivityContact::getNames($id, $targetID));
        $this->_contacts = array_merge($this->_contacts, $ids);
      }
    }
    // Get 'Added by' contacts.
    if (!empty($params['created_by'])) {
      parent::setContactIDs();
      if (!empty($this->_contactIds)) {
        $this->_contacts = array_merge($this->_contacts, $this->_contactIds);
      }
    }
    $this->_contacts = array_unique($this->_contacts);

    // Bounce to pick option if no contacts to send to.
    if (empty($this->_contacts)) {
      $urlParams = "_qf_PickOption_display=true&qfKey={$params['qfKey']}";
      $urlRedirect = CRM_Utils_System::url('civicrm/activity/search', $urlParams);
      CRM_Core_Error::statusBounce(
        ts('It appears you have no contacts with email addresses from the selected recipients.'),
        $urlRedirect
      );
    }

    $this->set('contacts', $this->_contacts);
  }

}
