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
 *
 */

/**
 * This class contains all case related functions that are called using AJAX
 */
class CRM_Case_Page_AJAX {

  /**
   * @throws \CRM_Core_Exception
   */
  public function processCaseTags() {

    $caseId = CRM_Utils_Type::escape($_POST['case_id'], 'Positive');
    $tags = CRM_Utils_Type::escape($_POST['tag'], 'String');
    $tagList = $_POST['taglist'];

    if (!CRM_Case_BAO_Case::accessCase($caseId)) {
      CRM_Utils_System::permissionDenied();
    }

    $tagIds = array();
    if ($tags) {
      $tagIds = explode(',', $tags);
    }

    $params = array(
      'entity_id' => $caseId,
      'entity_table' => 'civicrm_case',
    );

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

    $activityParams = array();
    $activityParams['source_contact_id'] = $session->get('userID');
    $activityParams['activity_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Case Tags');
    $activityParams['activity_date_time'] = date('YmdHis');
    $activityParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $activityParams['case_id'] = $caseId;
    $activityParams['is_auto'] = 0;
    $activityParams['subject'] = 'Change Case Tags';

    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    $caseParams = array(
      'activity_id' => $activity->id,
      'case_id' => $caseId,
    );

    CRM_Case_BAO_Case::processCaseActivity($caseParams);

    echo 'true';
    CRM_Utils_System::civiExit();
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function caseDetails() {
    $caseId = CRM_Utils_Type::escape($_GET['caseId'], 'Positive');

    $case = civicrm_api3('Case', 'getsingle', array(
      'id' => $caseId,
      'check_permissions' => TRUE,
      'return' => array('subject', 'case_type_id', 'status_id', 'start_date', 'end_date'))
    );

    $caseStatuses = CRM_Case_PseudoConstant::caseStatus();
    $caseTypes = CRM_Case_PseudoConstant::caseType('title', FALSE);
    $caseDetails = "<table><tr><td>" . ts('Case Subject') . "</td><td>{$case['subject']}</td></tr>
                                  <tr><td>" . ts('Case Type') . "</td><td>{$caseTypes[$case['case_type_id']]}</td></tr>
                                  <tr><td>" . ts('Case Status') . "</td><td>{$caseStatuses[$case['status_id']]}</td></tr>
                                  <tr><td>" . ts('Case Start Date') . "</td><td>" . CRM_Utils_Date::customFormat($case['start_date']) . "</td></tr>
                                  <tr><td>" . ts('Case End Date') . "</td><td></td></tr>" . CRM_Utils_Date::customFormat($case['end_date']) . "</table>";

    if (CRM_Utils_Array::value('snippet', $_GET) == 'json') {
      CRM_Core_Page_AJAX::returnJsonResponse($caseDetails);
    }

    echo $caseDetails;
    CRM_Utils_System::civiExit();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function addClient() {
    $caseId = CRM_Utils_Type::escape($_POST['caseID'], 'Positive');
    $contactId = CRM_Utils_Type::escape($_POST['contactID'], 'Positive');

    if (!$contactId || !CRM_Case_BAO_Case::accessCase($caseId)) {
      CRM_Utils_System::permissionDenied();
    }

    $params = array(
      'case_id' => $caseId,
      'contact_id' => $contactId,
    );

    CRM_Case_BAO_CaseContact::create($params);

    // add case relationships
    CRM_Case_BAO_Case::addCaseRelationships($caseId, $contactId);

    $session = CRM_Core_Session::singleton();

    $activityParams = array();
    $activityParams['source_contact_id'] = $session->get('userID');
    $activityParams['activity_type_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Add Client To Case');
    $activityParams['activity_date_time'] = date('YmdHis');
    $activityParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed');
    $activityParams['case_id'] = $caseId;
    $activityParams['is_auto'] = 0;
    $activityParams['subject'] = 'Client Added To Case';

    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    $caseParams = array(
      'activity_id' => $activity->id,
      'case_id' => $caseId,
    );

    CRM_Case_BAO_Case::processCaseActivity($caseParams);
    CRM_Utils_JSON::output(TRUE);
  }

  /**
   * Delete relationships specific to case and relationship type.
   */
  public static function deleteCaseRoles() {
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
    $requiredParameters = array(
      'type' => 'String',
    );
    $optionalParameters = array(
      'case_type_id' => 'CommaSeparatedIntegers',
      'status_id' => 'CommaSeparatedIntegers',
      'all' => 'Positive',
    );
    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams($requiredParameters, $optionalParameters);

    $allCases = (bool) $params['all'];

    $cases = CRM_Case_BAO_Case::getCases($allCases, $params);

    $casesDT = array(
      'recordsFiltered' => $cases['total'],
      'recordsTotal' => $cases['total'],
    );
    unset($cases['total']);
    $casesDT['data'] = $cases;

    CRM_Utils_JSON::output($casesDT);
  }

}
