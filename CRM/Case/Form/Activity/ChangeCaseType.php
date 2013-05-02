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
 * This class generates form components for OpenCase Activity
 *
 */
class CRM_Case_Form_Activity_ChangeCaseType {

  static function preProcess(&$form) {
    if (!isset($form->_caseId)) {
      CRM_Core_Error::fatal(ts('Case Id not found.'));
    }
  }

  /**
   * This function sets the default values for the form. For edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  static function setDefaultValues(&$form) {
    $defaults = array();

    $defaults['is_reset_timeline'] = 1;

    $defaults['reset_date_time'] = array();
    list($defaults['reset_date_time'], $defaults['reset_date_time_time']) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
    $defaults['case_type_id'] = $form->_caseTypeId;

    return $defaults;
  }

  static function buildQuickForm(&$form) {
    $form->removeElement('status_id');
    $form->removeElement('priority_id');

    $form->_caseType = CRM_Case_PseudoConstant::caseType();
    $caseTypeId = explode(CRM_Case_BAO_Case::VALUE_SEPARATOR, CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case',
        $form->_caseId,
        'case_type_id'
      ));
    $form->_caseTypeId = $caseTypeId[1];
    if (!in_array($form->_caseTypeId, $form->_caseType)) {
      $form->_caseType[$form->_caseTypeId] = CRM_Core_OptionGroup::getLabel('case_type', $form->_caseTypeId, FALSE);
    }

    $form->add('select', 'case_type_id', ts('New Case Type'),
      $form->_caseType, TRUE
    );

    // timeline
    $form->addYesNo('is_reset_timeline', ts('Reset Case Timeline?'), NULL, TRUE, array('onclick' => "return showHideByValue('is_reset_timeline','','resetTimeline','table-row','radio',false);"));
    $form->addDateTime('reset_date_time', ts('Reset Start Date'), FALSE, array('formatType' => 'activityDateTime'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $values posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($values, $files, $form) {
    return TRUE;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  static function beginPostProcess(&$form, &$params) {
    if ($form->_context == 'case') {
      $params['id'] = $form->_id;
    }

    if (CRM_Utils_Array::value('is_reset_timeline', $params) == 0) {
      unset($params['reset_date_time']);
    }
    else {
      // store the date with proper format
      $params['reset_date_time'] = CRM_Utils_Date::processDate($params['reset_date_time'], $params['reset_date_time_time']);
    }
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  static function endPostProcess(&$form, &$params, $activity) {
    if (!$form->_caseId) {
      // always expecting a change, so case-id is a must.
      return;
    }

    $caseTypes = CRM_Case_PseudoConstant::caseType('name');
    $allCaseTypes = CRM_Case_PseudoConstant::caseType('label', FALSE);

    if (CRM_Utils_Array::value($params['case_type_id'], $caseTypes)) {
      $caseType = $caseTypes[$params['case_type_id']];
    }

    if (!$form->_currentlyViewedContactId ||
      !$form->_currentUserId ||
      !$params['case_type_id'] ||
      !$caseType
    ) {
      CRM_Core_Error::fatal('Required parameter missing for ChangeCaseType - end post processing');
    }

    $params['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
    $activity->status_id = $params['status_id'];
    $params['priority_id'] = CRM_Core_OptionGroup::getValue('priority', 'Normal', 'name');
    $activity->priority_id = $params['priority_id'];

    if ($activity->subject == 'null') {
      $activity->subject = ts('Case type changed from %1 to %2',
        array(1 => CRM_Utils_Array::value($form->_defaults['case_type_id'], $allCaseTypes),
          2 => CRM_Utils_Array::value($params['case_type_id'], $allCaseTypes),
        )
      );
      $activity->save();
    }

    // 1. initiate xml processor
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $xmlProcessorParams = array(
      'clientID' => $form->_currentlyViewedContactId,
      'creatorID' => $form->_currentUserId,
      'standardTimeline' => 1,
      'activityTypeName' => 'Change Case Type',
      'activity_date_time' => CRM_Utils_Array::value('reset_date_time', $params),
      'caseID' => $form->_caseId,
      'resetTimeline' => CRM_Utils_Array::value('is_reset_timeline', $params),
    );

    $xmlProcessor->run($caseType, $xmlProcessorParams);
    // status msg
    $params['statusMsg'] = ts('Case Type changed successfully.');
  }
}

