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
 * This class provides the functionality to email a group of contacts
 */
class CRM_Activity_Form_Task_FileOnCase extends CRM_Activity_Form_Task {

  /**
   * the title of the group
   *
   * @var string
   */
  protected $_title;

  /**
   * variable to store redirect path
   *
   */
  protected $_userContext;

  /**
   * variable to store contact Ids
   *
   */
  public $_contacts;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */ function preProcess() {
    /*
         * initialize the task and row fields
         */

    parent::preProcess();
    $session = CRM_Core_Session::singleton();
    $this->_userContext = $session->readUserContext();

    CRM_Utils_System::setTitle(ts('File on Case'));

    $validationFailed = FALSE;

    // insert validations here

    // then redirect
    if ($validationFailed) {
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
    $this->addElement('text', 'unclosed_cases', ts('Select Case'));
    $this->add('hidden', 'unclosed_case_id', '', array('id' => 'unclosed_case_id'));
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
    $this->addFormRule(array('CRM_Activity_Form_Task_FileOnCase', 'formRule'));
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
  static
  function formRule($fields) {
    $errors = array();
    if (empty($fields['unclosed_case_id'])) {
      $errors['unclosed_case_id'] = ts('Case is a required field.');
    }
    return $errors;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */

  public function postProcess() {
    $formparams      = $this->exportValues();
    $caseId          = $formparams['unclosed_case_id'];
    $filedActivities = 0;
    foreach ($this->_activityHolderIds as $key => $id) {
      $targetContactValues = $defaults = array();
      $params = array('id' => $id);
      CRM_Activity_BAO_Activity::retrieve($params, $defaults);
      if (CRM_Case_BAO_Case::checkPermission($id, 'File On Case', $defaults['activity_type_id'])) {

        if (!CRM_Utils_Array::crmIsEmptyArray($defaults['target_contact'])) {
          $targetContactValues = array_combine(array_unique($defaults['target_contact']),
            explode(';', trim($defaults['target_contact_value']))
          );
          $targetContactValues = implode(',', array_keys($targetContactValues));
        }

        $params = array(
          'caseID' => $caseId,
          'activityID' => $id,
          'newSubject' => empty($defaults['subject']) ? '' : $defaults['subject'],
          'targetContactIds' => $targetContactValues,
          'mode' => 'file',
        );

        $error_msg = CRM_Activity_Page_AJAX::_convertToCaseActivity($params);
        if (empty($error_msg['error_msg'])) {
          $filedActivities++;
        }
        else {
          CRM_Core_Session::setStatus($error_msg['error_msg'], ts("Error"), "error");
        }
      }
      else {
        CRM_Core_Session::setStatus(ts('Not permitted to file activity %1 %2.', array(
          1 => empty($defaults['subject']) ? '' : $defaults['subject'],
          2 => $defaults['activity_date_time'])), 
          ts("Error"), "error");
      }
    }

    CRM_Core_Session::setStatus($filedActivities, ts("Filed Activities"), "success");
    CRM_Core_Session::setStatus("", ts('Total Selected Activities: %1', array(1 => count($this->_activityHolderIds))), "info");
  }
}

