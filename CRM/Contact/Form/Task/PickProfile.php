<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class provides the functionality for batch profile update
 */
class CRM_Contact_Form_Task_PickProfile extends CRM_Contact_Form_Task {

  /**
   * the title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * maximum contacts that should be allowed to update
   *
   */
  protected $_maxContacts = 100;

  /**
   * maximum profile fields that will be displayed
   *
   */
  protected $_maxFields = 9;

  /**
   * variable to store redirect path
   *
   */
  protected $_userContext;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    /*
     * initialize the task and row fields
     */
    parent::preProcess();

    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();

    $validate = FALSE;
    //validations
    if (count($this->_contactIds) > $this->_maxContacts) {
      CRM_Core_Session::setStatus(ts("The maximum number of contacts you can select for Batch Update is %1. You have selected %2. Please select fewer contacts from your search results and try again.", array(1 => $this->_maxContacts, 2 => count($this->_contactIds))), ts('Maximum Exceeded'), 'error');
      $validate = TRUE;
    }

    if (CRM_Contact_BAO_Contact_Utils::checkContactType($this->_contactIds)) {
      CRM_Core_Session::setStatus(ts("Batch update requires that all selected contacts be the same basic type (e.g. all Individuals OR all Organizations...). Please modify your selection and try again."), ts('Contact Type Mismatch'), 'error');
      $validate = TRUE;
    }

    // than redirect
    if ($validate) {
      CRM_Utils_System::redirect($this->_userContext);
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Batch Profile Update for Contact'));

    foreach ($this->_contactIds as $id) {
      $this->_contactTypes = CRM_Contact_BAO_Contact::getContactTypes($id);
    }

    //add Contact type profiles
    $this->_contactTypes[] = 'Contact';

    $profiles = CRM_Core_BAO_UFGroup::getProfiles($this->_contactTypes);

    if (empty($profiles)) {
      $types = implode(' ' . ts('or') . ' ', $this->_contactTypes);
      CRM_Core_Session::setStatus(ts("The contact type selected for Batch Update does not have a corresponding profile. Please set up a profile for %1s and try again.", array(1 => $types)), ts('No Profile Available'), 'error');
      CRM_Utils_System::redirect($this->_userContext);
    }
    $ufGroupElement = $this->add('select', 'uf_group_id', ts('Select Profile'), array('' => ts('- select profile -')) + $profiles, TRUE);

    $this->addDefaultButtons(ts('Continue >>'));
  }

  /**
   * Add local and global form rules
   *
   * @access protected
   *
   * @return void
   */
  function addRules() {
    $this->addFormRule(array('CRM_Contact_Form_Task_PickProfile', 'formRule'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields) {
    if (CRM_Core_BAO_UFField::checkProfileType($fields['uf_group_id'])) {
      $errorMsg['uf_group_id'] = "You cannot select mix profile for batch update.";
    }

    if (!empty($errorMsg)) {
      return $errorMsg;
    }

    return TRUE;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->exportValues();

    $this->set('ufGroupId', $params['uf_group_id']);

    // also reset the batch page so it gets new values from the db
    $this->controller->resetPage('Batch');
  }
  //end of function
}

