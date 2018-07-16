<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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
      CRM_Core_Error::fatal(ts('Case Id not found.'));
    }
    if (count($form->_caseId) != 1) {
      CRM_Core_Resources::fatal(ts('Expected one case-type'));
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

    $openCaseActivityType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Open Case');
    $caseId = CRM_Utils_Array::first($form->_caseId);
    $openCaseParams = array('activity_type_id' => $openCaseActivityType);
    $openCaseInfo = CRM_Case_BAO_Case::getCaseActivityDates($caseId, $openCaseParams, TRUE);
    if (empty($openCaseInfo)) {
      list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults();
    }
    else {
      // We know there can only be one result
      $openCaseInfo = current($openCaseInfo);

      // store activity id for updating it later
      $form->openCaseActivityId = $openCaseInfo['id'];

      list($defaults['start_date'], $defaults['start_date_time']) = CRM_Utils_Date::setDateDefaults($openCaseInfo['activity_date'], 'activityDateTime');
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
    $form->addDate('start_date', ts('New Start Date'), FALSE, array('formatType' => 'activityDateTime'));
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
    if (!empty($params['start_date'])) {
      $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    }

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
      CRM_Core_Error::fatal('Required parameter missing for ChangeCaseType - end post processing');
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
    $newStartDate = CRM_Utils_Date::customFormat(CRM_Utils_Date::mysqlToIso($params['start_date']), $config->dateformatFull);
    $subject = 'Change Case Start Date from ' . $currentStartDate . ' to ' . $newStartDate;
    $activity->subject = $subject;
    $activity->save();
    // 2. initiate xml processor
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $xmlProcessorParams = array(
      'clientID' => $form->_currentlyViewedContactId,
      'creatorID' => $form->_currentUserId,
      'standardTimeline' => 0,
      'activity_date_time' => $params['start_date'],
      'caseID' => $caseId,
      'caseType' => $caseType,
      'activityTypeName' => 'Change Case Start Date',
      'activitySetName' => 'standard_timeline',
      'resetTimeline' => 1,
    );

    $xmlProcessor->run($caseType, $xmlProcessorParams);

    // 2.5 Update open case activity date
    // Multiple steps since revisioned
    if ($form->openCaseActivityId) {

      $abao = new CRM_Activity_BAO_Activity();
      $oldParams = array('id' => $form->openCaseActivityId);
      $oldActivityDefaults = array();
      $oldActivity = $abao->retrieve($oldParams, $oldActivityDefaults);

      // save the old values
      require_once 'api/v3/utils.php';
      $openCaseParams = array();
      //@todo calling api functions directly is not supported
      _civicrm_api3_object_to_array($oldActivity, $openCaseParams);

      // update existing revision
      $oldParams = array(
        'id' => $form->openCaseActivityId,
        'is_current_revision' => 0,
      );
      $oldActivity = new CRM_Activity_DAO_Activity();
      $oldActivity->copyValues($oldParams);
      $oldActivity->save();

      // change some params for the new one
      unset($openCaseParams['id']);
      $openCaseParams['activity_date_time'] = $params['start_date'];
      $openCaseParams['target_contact_id'] = $oldActivityDefaults['target_contact'];
      $openCaseParams['assignee_contact_id'] = $oldActivityDefaults['assignee_contact'];
      $session = CRM_Core_Session::singleton();
      $openCaseParams['source_contact_id'] = $session->get('userID');

      // original_id always refers to the first activity, so only update if null (i.e. this is the second revision)
      $openCaseParams['original_id'] = $openCaseParams['original_id'] ? $openCaseParams['original_id'] : $form->openCaseActivityId;

      $newActivity = CRM_Activity_BAO_Activity::create($openCaseParams);
      if (is_a($newActivity, 'CRM_Core_Error')) {
        CRM_Core_Error::fatal('Unable to update Open Case activity');
      }
      else {
        // Create linkage to case
        $caseActivityParams = array(
          'activity_id' => $newActivity->id,
          'case_id' => $caseId,
        );

        CRM_Case_BAO_Case::processCaseActivity($caseActivityParams);

        $caseActivityParams = array(
          'activityID' => $form->openCaseActivityId,
          'mainActivityId' => $newActivity->id,
        );
        CRM_Activity_BAO_Activity::copyExtendedActivityData($caseActivityParams);
      }
    }

    // 3.status msg
    $params['statusMsg'] = ts('Case Start Date changed successfully.');
  }

}
