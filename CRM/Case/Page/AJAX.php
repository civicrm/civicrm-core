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
 * This class contains all case related functions that are called using AJAX
 */
class CRM_Case_Page_AJAX {

  /**
   * @throws \CRM_Core_Exception
   */
  public static function processCaseTags() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();

    $caseId = CRM_Utils_Type::escape($_POST['case_id'], 'Positive');
    $tags = CRM_Utils_Type::escape($_POST['tag'], 'String');
    $tagList = $_POST['taglist'];

    if (!CRM_Case_BAO_Case::accessCase($caseId)) {
      CRM_Utils_System::permissionDenied();
    }

    $tagIds = [];
    if ($tags) {
      $tagIds = explode(',', $tags);
    }

    $params = [
      'entity_id' => $caseId,
      'entity_table' => 'civicrm_case',
    ];

    CRM_Core_BAO_EntityTag::del($params);

    foreach ($tagIds as $tagid) {
      if (is_numeric($tagid)) {
        $params['tag_id'] = $tagid;
        CRM_Core_BAO_EntityTag::add($params);
      }
    }

    if (!empty($tagList)) {
      CRM_Core_Form_Tag::postProcess($tagList, $caseId, 'civicrm_case');
    }

    $session = CRM_Core_Session::singleton();

    $activityParams = [];
    $activityParams['source_contact_id'] = $session->get('userID');
    $activityParams['activity_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Case Tags');
    $activityParams['activity_date_time'] = date('YmdHis');
    $activityParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $activityParams['case_id'] = $caseId;
    $activityParams['is_auto'] = 0;
    $activityParams['subject'] = ts('Change Case Tags');

    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    echo 'true';
    CRM_Utils_System::civiExit();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public static function caseDetails() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $caseId = CRM_Utils_Type::escape($_GET['caseId'], 'Positive');

    $case = civicrm_api3('Case', 'getsingle', [
      'id' => $caseId,
      'check_permissions' => TRUE,
      'return' => ['subject', 'case_type_id', 'status_id', 'start_date', 'end_date'],
    ]);

    $caseStatuses = CRM_Case_PseudoConstant::caseStatus();
    $caseTypes = CRM_Case_PseudoConstant::caseType('title', FALSE);
    $caseDetails = "<table><tr><td>" . ts('Case Subject') . "</td><td>{$case['subject']}</td></tr>
                                  <tr><td>" . ts('Case Type') . "</td><td>{$caseTypes[$case['case_type_id']]}</td></tr>
                                  <tr><td>" . ts('Case Status') . "</td><td>{$caseStatuses[$case['status_id']]}</td></tr>
                                  <tr><td>" . ts('Case Start Date') . "</td><td>" . CRM_Utils_Date::customFormat($case['start_date']) . "</td></tr>
                                  <tr><td>" . ts('Case End Date') . "</td><td>" . (isset($case['end_date']) ? CRM_Utils_Date::customFormat($case['end_date']) : '') . "</td></tr></table>";

    if (($_GET['snippet'] ?? NULL) == 'json') {
      CRM_Core_Page_AJAX::returnJsonResponse($caseDetails);
    }

    echo $caseDetails;
    CRM_Utils_System::civiExit();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public static function addClient() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $caseId = CRM_Utils_Type::escape($_POST['caseID'], 'Positive');
    $contactId = CRM_Utils_Type::escape($_POST['contactID'], 'Positive');

    if (!$contactId || !CRM_Case_BAO_Case::accessCase($caseId)) {
      CRM_Utils_System::permissionDenied();
    }

    $params = [
      'case_id' => $caseId,
      'contact_id' => $contactId,
    ];

    CRM_Case_BAO_CaseContact::writeRecord($params);

    // add case relationships
    CRM_Case_BAO_Case::addCaseRelationships($caseId, $contactId);

    $session = CRM_Core_Session::singleton();

    $activityParams = [];
    $activityParams['source_contact_id'] = $session->get('userID');
    $activityParams['activity_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Add Client To Case');
    $activityParams['activity_date_time'] = date('YmdHis');
    $activityParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $activityParams['case_id'] = $caseId;
    $activityParams['is_auto'] = 0;
    $activityParams['subject'] = ts('Client Added To Case');

    $activity = CRM_Activity_BAO_Activity::create($activityParams);
    CRM_Utils_JSON::output(TRUE);
  }

  /**
   * Delete relationships specific to case and relationship type.
   */
  public static function deleteCaseRoles() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $caseId = CRM_Utils_Type::escape($_POST['case_id'], 'Positive');
    $cid = CRM_Utils_Type::escape($_POST['cid'], 'Positive');
    $relType = CRM_Utils_Request::retrieve('rel_type', 'String', CRM_Core_DAO::$_nullObject, TRUE);

    if (!$cid || !CRM_Case_BAO_Case::accessCase($caseId)) {
      CRM_Utils_System::permissionDenied();
    }

    list($relTypeId, $a, $b) = explode('_', $relType);

    CRM_Case_BAO_Case::endCaseRole($caseId, $b, $cid, $relTypeId);
    CRM_Utils_System::civiExit();
  }

  public static function getCases() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $requiredParameters = [
      'type' => 'String',
    ];
    $optionalParameters = [
      'case_type_id' => 'CommaSeparatedIntegers',
      'status_id' => 'CommaSeparatedIntegers',
      'all' => 'Positive',
    ];
    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams($requiredParameters, $optionalParameters);

    $allCases = !empty($params['all']);

    if ($params['type'] === 'recent' && empty($params['sortBy'])) {
      $params['sortBy'] = 'date DESC';
    }

    $cases = CRM_Case_BAO_Case::getCases($allCases, $params);

    $casesDT = [
      'recordsFiltered' => $cases['total'],
      'recordsTotal' => $cases['total'],
    ];
    unset($cases['total']);
    $casesDT['data'] = array_values($cases);

    CRM_Utils_JSON::output($casesDT);
  }

}
