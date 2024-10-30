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
class CRM_Case_Form_Activity_ChangeCaseStartDate {

  /**
   * @param CRM_Core_Form $form
   *
   * @throws Exception
   */
  public static function preProcess(&$form) {
    if (!isset($form->_caseId)) {
      CRM_Core_Error::statusBounce(ts('Case Id not found.'));
    }
    if (count($form->_caseId) != 1) {
      CRM_Core_Error::statusBounce(ts('Expected one case-type'));
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

    $openCaseActivityType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
    $caseId = CRM_Utils_Array::first($form->_caseId);
    $openCaseParams = ['activity_type_id' => $openCaseActivityType];
    $openCaseInfo = CRM_Case_BAO_Case::getCaseActivityDates($caseId, $openCaseParams, TRUE);
    if (empty($openCaseInfo)) {
      $defaults['start_date'] = date('Y-m-d H:i:s');
    }
    else {
      // We know there can only be one result
      $openCaseInfo = current($openCaseInfo);

      // store activity id for updating it later
      $form->setOpenCaseActivityId($openCaseInfo['id']);

      $defaults['start_date'] = $openCaseInfo['activity_date'];
    }
    return $defaults;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function buildQuickForm(&$form) {
    $form->removeElement('status_id');
    $form->removeElement('priority_id');
    $caseId = CRM_Utils_Array::first($form->_caseId);

    $currentStartDate = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseId, 'start_date');
    $form->assign('current_start_date', $currentStartDate);
    $form->add('datepicker', 'start_date', ts('New Start Date'), [], TRUE);
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
    $caseType = CRM_Utils_Array::first($form->_caseType);
    $caseId = CRM_Utils_Array::first($form->_caseId);

    if (!$caseType && $caseId) {
      $caseType = CRM_Case_BAO_Case::getCaseType($caseId, 'title');
    }

    if (!$form->_currentlyViewedContactId ||
      !$form->_currentUserId ||
      !$caseId ||
      !$caseType
    ) {
      CRM_Core_Error::statusBounce(ts('Required parameter missing for ChangeCaseType - end post processing'));
    }

    $config = CRM_Core_Config::singleton();

    $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $activity->status_id = $params['status_id'];
    $params['priority_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'priority_id', 'Normal');
    $activity->priority_id = $params['priority_id'];

    // 1. save activity subject with new start date
    $currentStartDate = CRM_Utils_Date::customFormat(CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case',
      $caseId, 'start_date'
    ), $config->dateformatFull);
    $newStartDate = CRM_Utils_Date::customFormat($params['start_date'], $config->dateformatFull);
    $subject = 'Change Case Start Date from ' . $currentStartDate . ' to ' . $newStartDate;
    $activity->subject = $subject;
    $activity->save();
    // 2. initiate xml processor
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $xmlProcessorParams = [
      'clientID' => $form->_currentlyViewedContactId,
      'creatorID' => $form->_currentUserId,
      'standardTimeline' => 0,
      'activity_date_time' => $params['start_date'],
      'caseID' => $caseId,
      'caseType' => $caseType,
      'activityTypeName' => 'Change Case Start Date',
      'activitySetName' => 'standard_timeline',
      'resetTimeline' => 1,
    ];

    $xmlProcessor->run($caseType, $xmlProcessorParams);

    // 2.5 Update open case activity date
    // @todo Since revisioning code has been removed this can be refactored more
    if ($form->getOpenCaseActivityId()) {

      $abao = new CRM_Activity_BAO_Activity();
      $oldParams = ['id' => $form->getOpenCaseActivityId()];
      $oldActivityDefaults = [];
      $oldActivity = $abao->retrieve($oldParams, $oldActivityDefaults);

      // save the old values
      require_once 'api/v3/utils.php';
      $openCaseParams = [];
      //@todo calling api functions directly is not supported
      _civicrm_api3_object_to_array($oldActivity, $openCaseParams);

      // change some params for the activity update
      $openCaseParams['activity_date_time'] = $params['start_date'];
      $openCaseParams['target_contact_id'] = $oldActivityDefaults['target_contact'];
      $openCaseParams['assignee_contact_id'] = $oldActivityDefaults['assignee_contact'];
      $session = CRM_Core_Session::singleton();
      $openCaseParams['source_contact_id'] = $session->get('userID');

      // @todo This can go eventually but is still needed to keep them linked together if there is an existing revision. Just focusing right now on not creating new revisions.
      // original_id always refers to the first activity, so if it's null or missing, then it means no previous revisions and we can keep it null.
      $openCaseParams['original_id'] ??= NULL;

      $newActivity = CRM_Activity_BAO_Activity::create($openCaseParams);
      if (is_a($newActivity, 'CRM_Core_Error')) {
        CRM_Core_Error::statusBounce(ts('Unable to update Open Case activity'));
      }
    }

    // 3.status msg
    $params['statusMsg'] = ts('Case Start Date changed successfully.');
  }

}
