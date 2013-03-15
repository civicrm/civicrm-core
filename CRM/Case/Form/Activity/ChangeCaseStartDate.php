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
class CRM_Case_Form_Activity_ChangeCaseStartDate {

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

    $openCaseActivityType = CRM_Core_OptionGroup::getValue('activity_type',
      'Open Case',
      'name'
    );
    $openCaseParams = array('activity_type_id' => $openCaseActivityType);
    $openCaseInfo = CRM_Case_BAO_Case::getCaseActivityDates($form->_caseId, $openCaseParams, TRUE);
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

  static function buildQuickForm(&$form) {
    $form->removeElement('status_id');
    $form->removeElement('priority_id');

    $currentStartDate = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $form->_caseId, 'start_date');
    $form->assign('current_start_date', $currentStartDate);
    $form->addDate('start_date', ts('New Start Date'), FALSE, array('formatType' => 'activityDateTime'));
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
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  static function endPostProcess(&$form, &$params, $activity) {
    if (CRM_Utils_Array::value('start_date', $params)) {
      $params['start_date'] = CRM_Utils_Date::processDate($params['start_date'], $params['start_date_time']);
    }

    $caseType = $form->_caseType;

    if (!$caseType && $form->_caseId) {

      $query = "
SELECT  cov_type.label as case_type FROM civicrm_case
LEFT JOIN  civicrm_option_group cog_type ON cog_type.name = 'case_type'
LEFT JOIN civicrm_option_value cov_type ON
( civicrm_case.case_type_id = cov_type.value AND cog_type.id = cov_type.option_group_id )
WHERE civicrm_case.id=  %1";

      $queryParams = array(1 => array($form->_caseId, 'Integer'));
      $caseType = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    }

    if (!$form->_currentlyViewedContactId ||
      !$form->_currentUserId ||
      !$form->_caseId ||
      !$caseType
    ) {
      CRM_Core_Error::fatal('Required parameter missing for ChangeCaseType - end post processing');
    }

    $config = CRM_Core_Config::singleton();

    $params['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
    $activity->status_id = $params['status_id'];
    $params['priority_id'] = CRM_Core_OptionGroup::getValue('priority', 'Normal', 'name');
    $activity->priority_id = $params['priority_id'];

    // 1. save activity subject with new start date
    $currentStartDate = CRM_Utils_Date::customFormat(CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case',
        $form->_caseId, 'start_date'
      ), $config->dateformatFull);
    $newStartDate      = CRM_Utils_Date::customFormat(CRM_Utils_Date::mysqlToIso($params['start_date']), $config->dateformatFull);
    $subject           = 'Change Case Start Date from ' . $currentStartDate . ' to ' . $newStartDate;
    $activity->subject = $subject;
    $activity->save();
    // 2. initiate xml processor
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();
    $xmlProcessorParams = array(
      'clientID' => $form->_currentlyViewedContactId,
      'creatorID' => $form->_currentUserId,
      'standardTimeline' => 0,
      'activity_date_time' => $params['start_date'],
      'caseID' => $form->_caseId,
      'caseType' => $caseType,
      'activityTypeName' => 'Change Case Start Date',
      'activitySetName' => 'standard_timeline',
      'resetTimeline' => 1,
    );

    $xmlProcessor->run($caseType, $xmlProcessorParams);

    // 2.5 Update open case activity date
    // Multiple steps since revisioned
    if ($form->openCaseActivityId) {

      $abao                = new CRM_Activity_BAO_Activity();
      $oldParams           = array('id' => $form->openCaseActivityId);
      $oldActivityDefaults = array();
      $oldActivity         = $abao->retrieve($oldParams, $oldActivityDefaults);

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
          'case_id' => $form->_caseId,
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

