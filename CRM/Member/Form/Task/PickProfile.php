<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * This class provides the functionality for batch profile update for membership
 */
class CRM_Member_Form_Task_PickProfile extends CRM_Member_Form_Task {

  /**
   * The title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * Maximum members that should be allowed to update
   */
  protected $_maxMembers = 100;

  /**
   * Variable to store redirect path
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

    CRM_Utils_System::setTitle(ts('Update multiple memberships'));

    $validate = FALSE;
    //validations
    if (count($this->_memberIds) > $this->_maxMembers) {
      CRM_Core_Session::setStatus(ts("The maximum number of members you can select for Update multiple memberships is %1. You have selected %2. Please select fewer members from your search results and try again.", array(
            1 => $this->_maxMembers,
            2 => count($this->_memberIds),
          )), ts('Update multiple records error'), 'error');
      $validate = TRUE;
    }

    // than redirect
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
    $types = array('Membership');
    $profiles = CRM_Core_BAO_UFGroup::getProfiles($types, TRUE);

    if (empty($profiles)) {
      CRM_Core_Session::setStatus(ts("You will need to create a Profile containing the %1 fields you want to edit before you can use Update multiple memberships. Navigate to Administer CiviCRM >> CiviCRM Profile to configure a Profile. Consult the online Administrator documentation for more information.", array(1 => $types[0])), ts('Update multiple records error'), 'error');
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
   *
   *
   * @return void
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Member_Form_Task_PickProfile', 'formRule'));
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
