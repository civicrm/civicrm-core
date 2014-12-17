<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 *
 */

/**
 * This class contains all case related functions that are called using AJAX (jQuery)
 */
class CRM_Case_Page_AJAX {

  /**
   * Retrieve unclosed cases.
   */
  static function unclosedCases() {
    $params = array(
      'limit' => CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'search_autocomplete_count', NULL, 10),
      'sort_name' => CRM_Utils_Type::escape(CRM_Utils_Array::value('term', $_GET, ''), 'String'),
    );

    $excludeCaseIds = array();
    if (!empty($_GET['excludeCaseIds'])) {
      $excludeCaseIds = explode(',', CRM_Utils_Type::escape($_GET['excludeCaseIds'], 'String'));
    }
    $unclosedCases = CRM_Case_BAO_Case::getUnclosedCases($params, $excludeCaseIds, TRUE, TRUE);
    $results = array();
    foreach ($unclosedCases as $caseId => $details) {
      $results[] = array(
        'id' => $caseId,
        'label' => $details['sort_name'] . ' - ' . $details['case_type'] . ($details['end_date'] ? ' (' . ts('closed') . ')' : ''),
        'label_class' => $details['end_date'] ? 'strikethrough' : '',
        'description' => array($details['case_subject'] . ' (' . $details['case_status'] . ')'),
        'extra' => $details,
      );
    }
    CRM_Utils_JSON::output($results);
  }

  function processCaseTags() {

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

    if (!empty($tagIds)) {
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
    }

    if (!empty($tagList)) {
      CRM_Core_Form_Tag::postProcess($tagList, $caseId, 'civicrm_case', CRM_Core_DAO::$_nullObject);
    }

    $session = CRM_Core_Session::singleton();

    $activityParams = array();
    $activityParams['source_contact_id'] = $session->get('userID');
    $activityParams['activity_type_id'] = CRM_Core_OptionGroup::getValue('activity_type', 'Change Case Tags', 'name');
    $activityParams['activity_date_time'] = date('YmdHis');
    $activityParams['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
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

  function caseDetails() {
    $caseId = CRM_Utils_Type::escape($_GET['caseId'], 'Positive');

    if (!CRM_Case_BAO_Case::accessCase($caseId, FALSE)) {
      CRM_Utils_System::permissionDenied();
    }

    $sql       = "SELECT civicrm_case.*, civicrm_case_type.title as case_type
        FROM civicrm_case
        INNER JOIN civicrm_case_type ON civicrm_case.case_type_id = civicrm_case_type.id
        WHERE civicrm_case.id = %1";
    $dao       = CRM_Core_DAO::executeQuery($sql, array(1 => array($caseId, 'Integer')));

    if ($dao->fetch()) {
      $caseStatuses = CRM_Case_PseudoConstant::caseStatus();
      $cs           = $caseStatuses[$dao->status_id];
      $caseDetails  = "<table><tr><td>" . ts('Case Subject') . "</td><td>{$dao->subject}</td></tr>
                                    <tr><td>" . ts('Case Type') . "</td><td>{$dao->case_type}</td></tr>
                                    <tr><td>" . ts('Case Status') . "</td><td>{$cs}</td></tr>
                                    <tr><td>" . ts('Case Start Date') . "</td><td>" . CRM_Utils_Date::customFormat($dao->start_date) . "</td></tr>
                                    <tr><td>" . ts('Case End Date') . "</td><td></td></tr>" . CRM_Utils_Date::customFormat($dao->end_date) . "</table>";
      echo $caseDetails;
    }
    else {
      echo ts('Could not find valid Case!');
    }
    CRM_Utils_System::civiExit();
  }

  function addClient() {
    $caseId = CRM_Utils_Type::escape($_POST['caseID'], 'Positive');
    $contactId = CRM_Utils_Type::escape($_POST['contactID'], 'Positive');

    if (!$contactId || !CRM_Case_BAO_Case::accessCase($caseId)) {
      CRM_Utils_System::permissionDenied();
    }

    $params = array(
      'case_id' => $caseId,
      'contact_id' => $contactId,
    );

    CRM_Case_BAO_Case::addCaseToContact($params);

    // add case relationships
    CRM_Case_BAO_Case::addCaseRelationships($caseId, $contactId);

    $session = CRM_Core_Session::singleton();

    $activityParams = array();
    $activityParams['source_contact_id'] = $session->get('userID');
    $activityParams['activity_type_id'] = CRM_Core_OptionGroup::getValue('activity_type', 'Add Client To Case', 'name');
    $activityParams['activity_date_time'] = date('YmdHis');
    $activityParams['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
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
   * Function to delete relationships specific to case and relationship type
   */
  static function deleteCaseRoles() {
    $caseId  = CRM_Utils_Type::escape($_POST['case_id'], 'Positive');
    $relType = CRM_Utils_Type::escape($_POST['rel_type'], 'Positive');

    if (!$relType || !CRM_Case_BAO_Case::accessCase($caseId)) {
      CRM_Utils_System::permissionDenied();
    }

    $sql = "DELETE FROM civicrm_relationship WHERE case_id={$caseId} AND relationship_type_id={$relType}";
    CRM_Core_DAO::executeQuery($sql);

    CRM_Utils_System::civiExit();
  }
}

