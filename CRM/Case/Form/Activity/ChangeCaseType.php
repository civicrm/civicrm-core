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
 */

/**
 * This class generates form components for OpenCase Activity.
 */
class CRM_Case_Form_Activity_ChangeCaseType {

  /**
   * @param CRM_Core_Form $form
   *
   * @throws Exception
   */
  public static function preProcess(&$form) {
    if (!isset($form->_caseId)) {
      CRM_Core_Error::fatal(ts('Case Id not found.'));
    }
  }

  /**
   * Set default values for the form.
   *
   * For edit/view mode the default values are retrieved from the database.
   *
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function setDefaultValues(&$form) {
    $defaults = array();

    $defaults['is_reset_timeline'] = 1;

    $defaults['reset_date_time'] = array();
    list($defaults['reset_date_time'], $defaults['reset_date_time_time']) = CRM_Utils_Date::setDateDefaults(NULL, 'activityDateTime');
    $defaults['case_type_id'] = $form->_caseTypeId;

    return $defaults;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    $form->removeElement('status_id');
    $form->removeElement('priority_id');

    $caseId = CRM_Utils_Array::first($form->_caseId);
    $form->_caseType = CRM_Case_BAO_Case::buildOptions('case_type_id', 'create');
    $form->_caseTypeId = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case',
      $caseId,
      'case_type_id'
    );
    if (!in_array($form->_caseTypeId, $form->_caseType)) {
      $form->_caseType[$form->_caseTypeId] = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $form->_caseTypeId, 'title');
    }

    $form->addField('case_type_id', array('context' => 'create', 'entity' => 'Case'));

    // timeline
    $form->addYesNo('is_reset_timeline', ts('Reset Case Timeline?'), NULL, TRUE, array('onclick' => "return showHideByValue('is_reset_timeline','','resetTimeline','table-row','radio',false);"));
    $form->addDateTime('reset_date_time', ts('Reset Start Date'), FALSE, array('formatType' => 'activityDateTime'));
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $values
   *   Posted values of the form.
   *
   * @param $files
   * @param CRM_Core_Form $form
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($values, $files, $form) {
    return TRUE;
  }

  /**
   * Process the form submission.
   *
   *
   * @param CRM_Core_Form $form
   * @param array $params
   */
  public static function beginPostProcess(&$form, &$params) {
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
   * Process the form submission.
   *
   *
   * @param CRM_Core_Form $form
   * @param array $params
   * @param $activity
   */
  public static function endPostProcess(&$form, &$params, $activity) {
    if (!$form->_caseId) {
      // always expecting a change, so case-id is a must.
      return;
    }

    $caseTypes = CRM_Case_PseudoConstant::caseType('name');
    $allCaseTypes = CRM_Case_PseudoConstant::caseType('title', FALSE);

    if (!empty($caseTypes[$params['case_type_id']])) {
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
        array(
          1 => CRM_Utils_Array::value($form->_defaults['case_type_id'], $allCaseTypes),
          2 => CRM_Utils_Array::value($params['case_type_id'], $allCaseTypes),
        )
      );
      $activity->save();
    }

    // 1. initiate xml processor
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $caseId = CRM_Utils_Array::first($form->_caseId);
    $xmlProcessorParams = array(
      'clientID' => $form->_currentlyViewedContactId,
      'creatorID' => $form->_currentUserId,
      'standardTimeline' => 1,
      'activityTypeName' => 'Change Case Type',
      'activity_date_time' => CRM_Utils_Array::value('reset_date_time', $params),
      'caseID' => $caseId,
      'resetTimeline' => CRM_Utils_Array::value('is_reset_timeline', $params),
    );

    $xmlProcessor->run($caseType, $xmlProcessorParams);
    // status msg
    $params['statusMsg'] = ts('Case Type changed successfully.');
  }

}
