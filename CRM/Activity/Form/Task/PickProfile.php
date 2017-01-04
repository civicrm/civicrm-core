<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 */

/**
 * This class provides the functionality for batch profile update for Activity.
 */
class CRM_Activity_Form_Task_PickProfile extends CRM_Activity_Form_Task {

  /**
   * The title of the group.
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum Activities that should be allowed to update.
   */
  protected $_maxActivities = 100;

  /**
   * Variable to store redirect path.
   */
  protected $_userContext;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {

    // Initialize the task and row fields.
    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();

    CRM_Utils_System::setTitle(ts('Update multiple activities'));

    $validate = FALSE;
    // Validations.
    if (count($this->_activityHolderIds) > $this->_maxActivities) {
      CRM_Core_Session::setStatus(ts("The maximum number of activities you can select for Update multiple activities is %1. You have selected %2. Please select fewer Activities from your search results and try again.", array(
        1 => $this->_maxActivities,
        2 => count($this->_activityHolderIds),
      )), ts('Maximum Exceeded'), 'error');
      $validate = TRUE;
    }

    // Then redirect.
    if ($validate) {
      CRM_Utils_System::redirect($this->_userContext);
    }
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $types = array('Activity');
    $profiles = CRM_Core_BAO_UFGroup::getProfiles($types, TRUE);

    $activityTypeIds = array_flip(CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name'));
    $nonEditableActivityTypeIds = array(
      $activityTypeIds['Email'],
      $activityTypeIds['Bulk Email'],
      $activityTypeIds['Contribution'],
      $activityTypeIds['Inbound Email'],
      $activityTypeIds['Pledge Reminder'],
      $activityTypeIds['Membership Signup'],
      $activityTypeIds['Membership Renewal'],
      $activityTypeIds['Event Registration'],
      $activityTypeIds['Pledge Acknowledgment'],
    );
    $notEditable = FALSE;
    foreach ($this->_activityHolderIds as $activityId) {
      $typeId = CRM_Core_DAO::getFieldValue("CRM_Activity_DAO_Activity", $activityId, 'activity_type_id');
      if (in_array($typeId, $nonEditableActivityTypeIds)) {
        $notEditable = TRUE;
        break;
      }
    }

    if (empty($profiles)) {
      CRM_Core_Session::setStatus(ts("You will need to create a Profile containing the %1 fields you want to edit before you can use Update multiple activities. Navigate to Administer > Customize Data and Screens > Profiles to configure a Profile. Consult the online Administrator documentation for more information.", array(1 => $types[0])), ts("No Profile Configured"), "alert");
      CRM_Utils_System::redirect($this->_userContext);
    }
    elseif ($notEditable) {
      CRM_Core_Session::setStatus("", ts("Some of the selected activities are not editable."), "alert");
      CRM_Utils_System::redirect($this->_userContext);
    }

    $ufGroupElement = $this->add('select', 'uf_group_id', ts('Select Profile'),
      array(
        '' => ts('- select profile -'),
      ) + $profiles, TRUE
    );
    $this->addDefaultButtons(ts('Continue'));
  }

  /**
   * Add local and global form rules.
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Activity_Form_Task_PickProfile', 'formRule'));
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
