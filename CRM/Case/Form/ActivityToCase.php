<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class generates form components for building activity to a case.
 */
class CRM_Case_Form_ActivityToCase extends CRM_Core_Form {

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->_activityId = CRM_Utils_Request::retrieve('activityId', 'Positive', CRM_Core_DAO::$_nullObject);
    if (!$this->_activityId) {
      CRM_Core_Error::fatal('required activity id is missing.');
    }

    $this->_currentCaseId = CRM_Utils_Request::retrieve('caseId', 'Positive', CRM_Core_DAO::$_nullObject);
    $this->assign('currentCaseId', $this->_currentCaseId);
    $this->assign('buildCaseActivityForm', TRUE);
  }

  /**
   * Set default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    $params = array('id' => $this->_activityId);

    CRM_Activity_BAO_Activity::retrieve($params, $defaults);
    $defaults['file_on_case_activity_subject'] = $defaults['subject'];
    $defaults['file_on_case_target_contact_id'] = $defaults['target_contact'];

    // If this contact has an open case, supply it as a default
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    if ($cid) {
      $cases = civicrm_api3('CaseContact', 'get', array(
        'contact_id' => $cid,
        'case_id' => array('!=' => $this->_currentCaseId),
        'case_id.status_id' => array('!=' => "Closed"),
        'case_id.is_deleted' => 0,
        'case_id.end_date' => array('IS NULL' => 1),
        'options' => array('limit' => 1),
        'return' => 'case_id',
      ));
      foreach ($cases['values'] as $record) {
        $defaults['file_on_case_unclosed_case_id'] = $record['case_id'];
        break;
      }
    }
    return $defaults;
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    $this->addEntityRef('file_on_case_unclosed_case_id', ts('Select Case'), array(
      'entity' => 'Case',
      'api' => array(
        'extra' => array('contact_id'),
        'params' => array(
          'case_id' => array('!=' => $this->_currentCaseId),
          'case_id.is_deleted' => 0,
          'case_id.status_id' => array('!=' => "Closed"),
          'case_id.end_date' => array('IS NULL' => 1),
        ),
      ),
    ), TRUE);
    $this->addEntityRef('file_on_case_target_contact_id', ts('With Contact(s)'), array('multiple' => TRUE));
    $this->add('text', 'file_on_case_activity_subject', ts('Subject'), array('size' => 50));
  }

}
