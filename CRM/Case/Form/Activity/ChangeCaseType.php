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
      CRM_Core_Error::statusBounce(ts('Case Id not found.'));
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
    $defaults = [];

    $defaults['is_reset_timeline'] = 1;

    $defaults['reset_date_time'] = date('Y-m-d H:i:s');
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

    $form->addField('case_type_id', ['context' => 'create', 'entity' => 'Case'], TRUE);

    // timeline
    $form->addYesNo('is_reset_timeline', ts('Reset Case Timeline?'), NULL, TRUE);
    $form->add('datepicker', 'reset_date_time', ts('Reset Start Date'), NULL, FALSE, ['allowClear' => FALSE]);
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

    if (empty($params['is_reset_timeline'])) {
      unset($params['reset_date_time']);
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
      CRM_Core_Error::statusBounce(ts('Required parameter missing for ChangeCaseType - end post processing'));
    }

    $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $activity->status_id = $params['status_id'];
    $params['priority_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'priority_id', 'Normal');
    $activity->priority_id = $params['priority_id'];

    if ($activity->subject == 'null') {
      $activity->subject = ts('Case type changed from %1 to %2',
        [
          1 => $allCaseTypes[$form->_defaults['case_type_id']] ?? NULL,
          2 => $allCaseTypes[$params['case_type_id']] ?? NULL,
        ]
      );
      $activity->save();
    }

    // 1. initiate xml processor
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $caseId = CRM_Utils_Array::first($form->_caseId);
    $xmlProcessorParams = [
      'clientID' => $form->_currentlyViewedContactId,
      'creatorID' => $form->_currentUserId,
      'standardTimeline' => 1,
      'activityTypeName' => 'Change Case Type',
      'activity_date_time' => $params['reset_date_time'] ?? NULL,
      'caseID' => $caseId,
      'resetTimeline' => $params['is_reset_timeline'] ?? NULL,
    ];

    $xmlProcessor->run($caseType, $xmlProcessorParams);
    // status msg
    $params['statusMsg'] = ts('Case Type changed successfully.');
  }

}
