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
 * This class generates form components for building activity to a case
 *
 */
class CRM_Case_Form_ActivityToCase extends CRM_Core_Form {

  /**
   * build all the data structures needed to build the form.
   *
   * @return None
   * @access public
   */
  function preProcess() {
    $this->_activityId = CRM_Utils_Request::retrieve('activityId', 'Positive', CRM_Core_DAO::$_nullObject);
    if (!$this->_activityId) {
      CRM_Core_Error::fatal('required activity id is missing.');
    }

    $this->_currentCaseId = CRM_Utils_Request::retrieve('caseId', 'Positive', CRM_Core_DAO::$_nullObject);
    $this->assign('currentCaseId', $this->_currentCaseId);
    $this->assign('buildCaseActivityForm', TRUE);
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $targetContactValues = $defaults = array();
    $params = array('id' => $this->_activityId);

    CRM_Activity_BAO_Activity::retrieve($params, $defaults);
    $defaults['case_activity_subject'] = $defaults['subject'];
    if (!CRM_Utils_Array::crmIsEmptyArray($defaults['target_contact'])) {
      $targetContactValues = array_combine(array_unique($defaults['target_contact']),
        explode(';', trim($defaults['target_contact_value']))
      );
    }
    $this->assign('targetContactValues', empty($targetContactValues) ? FALSE : $targetContactValues);

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    // tokeninput url
    $tokenUrl = CRM_Utils_System::url("civicrm/ajax/checkemail", "noemail=1", FALSE, NULL, FALSE);
    $this->assign('tokenUrl', $tokenUrl);

    $this->add('text', 'unclosed_cases', ts('Select Case'));
    $this->add('hidden', 'unclosed_case_id', '', array('id' => 'open_case_id'));
    $this->add('text', 'target_contact_id', ts('With Contact(s)'));
    $this->add('text', 'case_activity_subject', ts('Subject'), array('size' => 50));
  }
}

