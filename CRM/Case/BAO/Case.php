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
 * This class contains the functions for Case Management.
 */
class CRM_Case_BAO_Case extends CRM_Case_DAO_Case {

  /**
   * Static field for all the case information that we can potentially export.
   *
   * @var array
   */
  static $_exportableFields = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Is CiviCase enabled?
   *
   * @return bool
   */
  public static function enabled() {
    $config = CRM_Core_Config::singleton();
    return in_array('CiviCase', $config->enableComponents);
  }

  /**
   * Create a case object.
   *
   * The function extracts all the params it needs to initialize the create a
   * case object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Case_BAO_Case
   */
  public static function add(&$params) {
    $caseDAO = new CRM_Case_DAO_Case();
    $caseDAO->copyValues($params);
    return $caseDAO->save();
  }

  /**
   * Takes an associative array and creates a case object.
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Case_BAO_Case
   */
  public static function &create(&$params) {
    $transaction = new CRM_Core_Transaction();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'Case', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Case', NULL, $params);
    }

    $case = self::add($params);

    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_case', $case->id);
    }

    if (is_a($case, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $case;
    }

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'Case', $case->id, $case);
    }
    else {
      CRM_Utils_Hook::post('create', 'Case', $case->id, $case);
    }
    $transaction->commit();

    //we are not creating log for case
    //since case log can be tracked using log for activity.
    return $case;
  }

  /**
   * Process case activity add/delete
   * takes an associative array and
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   */
  public static function processCaseActivity(&$params) {
    $caseActivityDAO = new CRM_Case_DAO_CaseActivity();
    $caseActivityDAO->activity_id = $params['activity_id'];
    $caseActivityDAO->case_id = $params['case_id'];

    $caseActivityDAO->find(TRUE);
    $caseActivityDAO->save();
  }

  /**
   * Get the case subject for Activity.
   *
   * @param int $activityId
   *   Activity id.
   *
   * @return string|null
   */
  public static function getCaseSubject($activityId) {
    $caseActivity = new CRM_Case_DAO_CaseActivity();
    $caseActivity->activity_id = $activityId;
    if ($caseActivity->find(TRUE)) {
      return CRM_Core_DAO::getFieldValue('CRM_Case_BAO_Case', $caseActivity->case_id, 'subject');
    }
    return NULL;
  }

  /**
   * Get the case type.
   *
   * @param int $caseId
   * @param string $colName
   *
   * @return string
   *   case type
   */
  public static function getCaseType($caseId, $colName = 'title') {
    $query = "
SELECT  civicrm_case_type.{$colName} FROM civicrm_case
LEFT JOIN civicrm_case_type ON
  civicrm_case.case_type_id = civicrm_case_type.id
WHERE civicrm_case.id = %1";

    $queryParams = array(1 => array($caseId, 'Integer'));

    return CRM_Core_DAO::singleValueQuery($query, $queryParams);
  }

  /**
   * Delete the record that are associated with this case.
   * record are deleted from case
   *
   * @param int $caseId
   *   Id of the case to delete.
   *
   * @param bool $moveToTrash
   *
   * @return bool
   *   is successful
   */
  public static function deleteCase($caseId, $moveToTrash = FALSE) {
    CRM_Utils_Hook::pre('delete', 'Case', $caseId, CRM_Core_DAO::$_nullArray);

    //delete activities
    $activities = self::getCaseActivityDates($caseId);
    if ($activities) {
      foreach ($activities as $value) {
        CRM_Activity_BAO_Activity::deleteActivity($value, $moveToTrash);
      }
    }

    if (!$moveToTrash) {
      $transaction = new CRM_Core_Transaction();
    }
    $case = new CRM_Case_DAO_Case();
    $case->id = $caseId;
    if (!$moveToTrash) {
      $result = $case->delete();
      $transaction->commit();
    }
    else {
      $result = $case->is_deleted = 1;
      $case->save();
    }

    if ($result) {
      // CRM-7364, disable relationships
      self::enableDisableCaseRelationships($caseId, FALSE);

      CRM_Utils_Hook::post('delete', 'Case', $caseId, $case);

      // remove case from recent items.
      $caseRecent = array(
        'id' => $caseId,
        'type' => 'Case',
      );
      CRM_Utils_Recent::del($caseRecent);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Enable disable case related relationships.
   *
   * @param int $caseId
   *   Case id.
   * @param bool $enable
   *   Action.
   */
  public static function enableDisableCaseRelationships($caseId, $enable) {
    $contactIds = self::retrieveContactIdsByCaseId($caseId);
    if (!empty($contactIds)) {
      foreach ($contactIds as $cid) {
        $roles = self::getCaseRoles($cid, $caseId);
        if (!empty($roles)) {
          $relationshipIds = implode(',', array_keys($roles));
          $enable = (int) $enable;
          $query = "UPDATE civicrm_relationship SET is_active = {$enable}
                        WHERE id IN ( {$relationshipIds} )";
          CRM_Core_DAO::executeQuery($query);
        }
      }
    }
  }

  /**
   * Retrieve contact_id by case_id.
   *
   * @param int $caseId
   *   ID of the case.
   *
   * @param int $contactID
   *
   * @return array
   */
  public static function retrieveContactIdsByCaseId($caseId, $contactID = NULL) {
    $caseContact = new CRM_Case_DAO_CaseContact();
    $caseContact->case_id = $caseId;
    $caseContact->find();
    $contactArray = array();
    $count = 1;
    while ($caseContact->fetch()) {
      if ($contactID != $caseContact->contact_id) {
        $contactArray[$count] = $caseContact->contact_id;
        $count++;
      }
    }

    return $contactArray;
  }

  /**
   * Look up a case using an activity ID.
   *
   * @param int $activityId
   *
   * @return int, case ID
   */
  public static function getCaseIdByActivityId($activityId) {
    $originalId = CRM_Core_DAO::singleValueQuery(
      'SELECT original_id FROM civicrm_activity WHERE id = %1',
      array('1' => array($activityId, 'Integer'))
    );
    $caseId = CRM_Core_DAO::singleValueQuery(
      'SELECT case_id FROM civicrm_case_activity WHERE activity_id in (%1,%2)',
      array(
        '1' => array($activityId, 'Integer'),
        '2' => array($originalId ? $originalId : $activityId, 'Integer'),
      )
    );
    return $caseId;
  }

  /**
   * Retrieve contact names by caseId.
   *
   * @param int $caseId
   *   ID of the case.
   *
   * @return array
   */
  public static function getContactNames($caseId) {
    $contactNames = array();
    if (!$caseId) {
      return $contactNames;
    }

    $query = "
    SELECT  contact_a.sort_name name,
            contact_a.display_name as display_name,
            contact_a.id cid,
            contact_a.birth_date as birth_date,
            ce.email as email,
            cp.phone as phone
      FROM  civicrm_contact contact_a
 LEFT JOIN  civicrm_case_contact ON civicrm_case_contact.contact_id = contact_a.id
 LEFT JOIN  civicrm_email ce ON ( ce.contact_id = contact_a.id AND ce.is_primary = 1)
 LEFT JOIN  civicrm_phone cp ON ( cp.contact_id = contact_a.id AND cp.is_primary = 1)
     WHERE  civicrm_case_contact.case_id = %1";

    $dao = CRM_Core_DAO::executeQuery($query,
      array(1 => array($caseId, 'Integer'))
    );
    while ($dao->fetch()) {
      $contactNames[$dao->cid]['contact_id'] = $dao->cid;
      $contactNames[$dao->cid]['sort_name'] = $dao->name;
      $contactNames[$dao->cid]['display_name'] = $dao->display_name;
      $contactNames[$dao->cid]['email'] = $dao->email;
      $contactNames[$dao->cid]['phone'] = $dao->phone;
      $contactNames[$dao->cid]['birth_date'] = $dao->birth_date;
      $contactNames[$dao->cid]['role'] = ts('Client');
    }

    return $contactNames;
  }

  /**
   * Retrieve case_id by contact_id.
   *
   * @param int $contactID
   * @param bool $includeDeleted
   *   Include the deleted cases in result.
   * @param null $caseType
   *
   * @return array
   */
  public static function retrieveCaseIdsByContactId($contactID, $includeDeleted = FALSE, $caseType = NULL) {
    $query = "
SELECT ca.id as id
FROM civicrm_case_contact cc
INNER JOIN civicrm_case ca ON cc.case_id = ca.id
";
    if (isset($caseType)) {
      $query .=
        "INNER JOIN civicrm_case_type ON civicrm_case_type.id = ca.case_type_id
WHERE cc.contact_id = %1 AND civicrm_case_type.name = '{$caseType}'";
    }
    if (!isset($caseType)) {
      $query .= "WHERE cc.contact_id = %1";
    }
    if (!$includeDeleted) {
      $query .= " AND ca.is_deleted = 0";
    }

    $params = array(1 => array($contactID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $caseArray = array();
    while ($dao->fetch()) {
      $caseArray[] = $dao->id;
    }

    $dao->free();
    return $caseArray;
  }

  /**
   * @param string $type
   * @param int $userID
   * @param string $condition
   *
   * @return string
   */
  public static function getCaseActivityQuery($type = 'upcoming', $userID = NULL, $condition = NULL) {
    if (!$userID) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    $query = "SELECT
civicrm_case.id as case_id,
civicrm_case.subject as case_subject,
civicrm_contact.id as contact_id,
civicrm_contact.sort_name as sort_name,
civicrm_phone.phone as phone,
civicrm_contact.contact_type as contact_type,
civicrm_contact.contact_sub_type as contact_sub_type,
t_act.activity_type_id,
c_type.title as case_type,
civicrm_case.case_type_id as case_type_id,
cov_status.label as case_status,
cov_status.label as case_status_name,
t_act.status_id,
civicrm_case.start_date as case_start_date,
case_relation_type.label_b_a as case_role, ";

    if ($type == 'upcoming') {
      $query .= "
t_act.desired_date as case_scheduled_activity_date,
t_act.id as case_scheduled_activity_id,
t_act.act_type_name as case_scheduled_activity_type_name,
t_act.act_type AS case_scheduled_activity_type ";
    }
    elseif ($type == 'recent') {
      $query .= "
t_act.desired_date as case_recent_activity_date,
t_act.id as case_recent_activity_id,
t_act.act_type_name as case_recent_activity_type_name,
t_act.act_type AS case_recent_activity_type ";
    }
    elseif ($type == 'any') {
      $query .= "
t_act.desired_date as case_activity_date,
t_act.id as case_activity_id,
t_act.act_type_name as case_activity_type_name,
t_act.act_type AS case_activity_type ";
    }

    $query .= " FROM civicrm_case
                  INNER JOIN civicrm_case_contact ON civicrm_case.id = civicrm_case_contact.case_id
                  INNER JOIN civicrm_contact ON civicrm_case_contact.contact_id = civicrm_contact.id ";

    if ($type == 'upcoming') {
      // This gets the earliest activity per case that's scheduled within 14 days from now.
      // Note we have an inner select to get the min activity id in order to remove duplicates in case there are two with the same datetime.
      // In this case we don't really care which one, so min(id) works.
      // optimized in CRM-11837
      $query .= " INNER JOIN
(
  SELECT case_id, act.id, activity_date_time AS desired_date, activity_type_id, status_id, aov.name AS act_type_name, aov.label AS act_type
  FROM (
    SELECT *
    FROM (
      SELECT *
      FROM civicrm_view_case_activity_upcoming
      ORDER BY activity_date_time ASC, id ASC
      ) AS upcomingOrdered
    GROUP BY case_id
    ) AS act
  LEFT JOIN civicrm_option_group aog ON aog.name='activity_type'
  LEFT JOIN civicrm_option_value aov ON ( aov.option_group_id = aog.id AND aov.value = act.activity_type_id )
) AS t_act
";
    }
    elseif ($type == 'recent') {
      // Similarly, the most recent activity in the past 14 days, and exclude scheduled.
      //improve query performance - CRM-10598
      $query .= " INNER JOIN
(
  SELECT case_id, act.id, activity_date_time AS desired_date, activity_type_id, status_id, aov.name AS act_type_name, aov.label AS act_type
  FROM (
    SELECT *
    FROM (
      SELECT *
      FROM civicrm_view_case_activity_recent
      ORDER BY activity_date_time DESC, id ASC
      ) AS recentOrdered
    GROUP BY case_id
    ) AS act
LEFT JOIN civicrm_option_group aog ON aog.name='activity_type'
  LEFT JOIN civicrm_option_value aov ON ( aov.option_group_id = aog.id AND aov.value = act.activity_type_id )
) AS t_act ";
    }
    elseif ($type == 'any') {
      $query .= " LEFT JOIN
(
  SELECT ca4.case_id, act4.id AS id, act4.activity_date_time AS desired_date, act4.activity_type_id, act4.status_id, aov.name AS act_type_name, aov.label AS act_type
  FROM civicrm_activity act4
  LEFT JOIN civicrm_case_activity ca4
    ON ca4.activity_id = act4.id
    AND act4.is_current_revision = 1
  LEFT JOIN civicrm_option_group aog
    ON aog.name='activity_type'
  LEFT JOIN civicrm_option_value aov
    ON aov.option_group_id = aog.id
    AND aov.value = act4.activity_type_id
) AS t_act";
    }

    $query .= "
        ON t_act.case_id = civicrm_case.id
 LEFT JOIN civicrm_phone ON (civicrm_phone.contact_id = civicrm_contact.id AND civicrm_phone.is_primary=1)
 LEFT JOIN civicrm_relationship case_relationship
 ON ( case_relationship.contact_id_a = civicrm_case_contact.contact_id AND case_relationship.contact_id_b = {$userID}
      AND case_relationship.case_id = civicrm_case.id )

 LEFT JOIN civicrm_relationship_type case_relation_type
 ON ( case_relation_type.id = case_relationship.relationship_type_id
      AND case_relation_type.id = case_relationship.relationship_type_id )

 LEFT JOIN civicrm_case_type c_type
 ON civicrm_case.case_type_id = c_type.id

 LEFT JOIN civicrm_option_group cog_status
 ON cog_status.name = 'case_status'

 LEFT JOIN civicrm_option_value cov_status
 ON ( civicrm_case.status_id = cov_status.value
      AND cog_status.id = cov_status.option_group_id )
";

    if ($condition) {
      // CRM-8749 backwards compatibility - callers of this function expect to start $condition with "AND"
      $query .= " WHERE (1) $condition ";
    }

    if ($type == 'upcoming') {
      $query .= " ORDER BY case_scheduled_activity_date ASC ";
    }
    elseif ($type == 'recent') {
      $query .= " ORDER BY case_recent_activity_date ASC ";
    }
    elseif ($type == 'any') {
      $query .= " ORDER BY case_activity_date ASC ";
    }

    return $query;
  }

  /**
   * Retrieve cases related to particular contact or whole contact used in Dashboard and Tab.
   *
   * @param bool $allCases
   *
   * @param int $userID
   *
   * @param string $type
   *   /upcoming,recent,all/.
   *
   * @param string $context
   *
   * @return array
   *   Array of Cases
   */
  public static function getCases($allCases = TRUE, $userID = NULL, $type = 'upcoming', $context = 'dashboard') {
    $condition = NULL;
    $casesList = array();

    //validate access for own cases.
    if (!self::accessCiviCase()) {
      return $casesList;
    }

    if (!$userID) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    //validate access for all cases.
    if ($allCases && !CRM_Core_Permission::check('access all cases and activities')) {
      $allCases = FALSE;
    }

    $condition = " AND civicrm_case.is_deleted = 0 AND civicrm_contact.is_deleted <> 1";

    if (!$allCases) {
      $condition .= " AND case_relationship.contact_id_b = {$userID} ";
    }
    if ($type == 'upcoming' || $type == 'any') {
      $closedId = CRM_Core_OptionGroup::getValue('case_status', 'Closed', 'name');
      $condition .= "
AND civicrm_case.status_id != $closedId";
    }

    $query = self::getCaseActivityQuery($type, $userID, $condition);

    $queryParams = array();
    $result = CRM_Core_DAO::executeQuery($query,
      $queryParams
    );

    $caseStatus = CRM_Core_OptionGroup::values('case_status', FALSE, FALSE, FALSE, " AND v.name = 'Urgent' ");

    $resultFields = array(
      'contact_id',
      'contact_type',
      'sort_name',
      'phone',
      'case_id',
      'case_subject',
      'case_type',
      'case_type_id',
      'status_id',
      'case_status',
      'case_status_name',
      'activity_type_id',
      'case_start_date',
      'case_role',
    );

    if ($type == 'upcoming') {
      $resultFields[] = 'case_scheduled_activity_date';
      $resultFields[] = 'case_scheduled_activity_type_name';
      $resultFields[] = 'case_scheduled_activity_type';
      $resultFields[] = 'case_scheduled_activity_id';
    }
    elseif ($type == 'recent') {
      $resultFields[] = 'case_recent_activity_date';
      $resultFields[] = 'case_recent_activity_type_name';
      $resultFields[] = 'case_recent_activity_type';
      $resultFields[] = 'case_recent_activity_id';
    }
    elseif ($type == 'any') {
      $resultFields[] = 'case_activity_date';
      $resultFields[] = 'case_activity_type_name';
      $resultFields[] = 'case_activity_type';
      $resultFields[] = 'case_activity_id';
    }

    // we're going to use the usual actions, so doesn't make sense to duplicate definitions
    $actions = CRM_Case_Selector_Search::links();

    // check is the user has view/edit signer permission
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('access all cases and activities') ||
      (!$allCases && CRM_Core_Permission::check('access my cases and activities'))
    ) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviCase')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);

    while ($result->fetch()) {
      foreach ($resultFields as $donCare => $field) {
        $casesList[$result->case_id][$field] = $result->$field;
        if ($field == 'contact_type') {
          $casesList[$result->case_id]['contact_type_icon'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ? $result->contact_sub_type : $result->contact_type
          );
          $casesList[$result->case_id]['action'] = CRM_Core_Action::formLink($actions['primaryActions'], $mask,
            array(
              'id' => $result->case_id,
              'cid' => $result->contact_id,
              'cxt' => $context,
            ),
            ts('more'),
            FALSE,
            'case.actions.primary',
            'Case',
            $result->case_id
          );
          $casesList[$result->case_id]['moreActions'] = CRM_Core_Action::formLink($actions['moreActions'],
            $mask,
            array(
              'id' => $result->case_id,
              'cid' => $result->contact_id,
              'cxt' => $context,
            ),
            ts('more'),
            TRUE,
            'case.actions.more',
            'Case',
            $result->case_id
          );
        }
        elseif ($field == 'case_status') {
          if (in_array($result->$field, $caseStatus)) {
            $casesList[$result->case_id]['class'] = "status-urgent";
          }
          else {
            $casesList[$result->case_id]['class'] = "status-normal";
          }
        }
      }
      //CRM-4510.
      $caseTypes = CRM_Case_PseudoConstant::caseType('name');
      $caseManagerContact = self::getCaseManagerContact($caseTypes[$result->case_type_id], $result->case_id);
      if (!empty($caseManagerContact)) {
        $casesList[$result->case_id]['casemanager_id'] = CRM_Utils_Array::value('casemanager_id', $caseManagerContact);
        $casesList[$result->case_id]['casemanager'] = CRM_Utils_Array::value('casemanager', $caseManagerContact);
      }

      //do check user permissions for edit/view activity.
      if (($actId = CRM_Utils_Array::value('case_scheduled_activity_id', $casesList[$result->case_id])) ||
        ($actId = CRM_Utils_Array::value('case_recent_activity_id', $casesList[$result->case_id]))
      ) {
        $casesList[$result->case_id]["case_{$type}_activity_editable"] = self::checkPermission($actId,
          'edit',
          $casesList[$result->case_id]['activity_type_id'], $userID
        );
        $casesList[$result->case_id]["case_{$type}_activity_viewable"] = self::checkPermission($actId,
          'view',
          $casesList[$result->case_id]['activity_type_id'], $userID
        );
      }
    }

    return $casesList;
  }

  /**
   * Get the summary of cases counts by type and status.
   *
   * @param bool $allCases
   * @param int $userID
   * @return array
   */
  public static function getCasesSummary($allCases = TRUE, $userID) {
    $caseSummary = array();

    //validate access for civicase.
    if (!self::accessCiviCase()) {
      return $caseSummary;
    }

    //validate access for all cases.
    if ($allCases && !CRM_Core_Permission::check('access all cases and activities')) {
      $allCases = FALSE;
    }

    $caseTypes = CRM_Case_PseudoConstant::caseType();
    $caseStatuses = CRM_Case_PseudoConstant::caseStatus();
    $caseTypes = array_flip($caseTypes);

    // get statuses as headers for the table
    $url = CRM_Utils_System::url('civicrm/case/search', "reset=1&force=1&all=1&status=");
    foreach ($caseStatuses as $key => $name) {
      $caseSummary['headers'][$key]['status'] = $name;
      $caseSummary['headers'][$key]['url'] = $url . $key;
    }

    // build rows with actual data
    $rows = array();
    $myGroupByClause = $mySelectClause = $myCaseFromClause = $myCaseWhereClause = '';

    if ($allCases) {
      $userID = 'null';
      $all = 1;
      $case_owner = 1;
      $myGroupByClause = ' GROUP BY civicrm_case.id';
    }
    else {
      $all = 0;
      $case_owner = 2;
      $myCaseWhereClause = " AND case_relationship.contact_id_b = {$userID}";
      $myGroupByClause = " GROUP BY CONCAT(case_relationship.case_id,'-',case_relationship.contact_id_b)";
    }

    // FIXME: This query could be a lot more efficient if it used COUNT() instead of returning all rows and then counting them with php
    $query = "
SELECT case_status.label AS case_status, status_id, civicrm_case_type.title AS case_type,
 case_type_id, case_relationship.contact_id_b
 FROM civicrm_case
 INNER JOIN civicrm_case_contact cc on cc.case_id = civicrm_case.id
 LEFT JOIN civicrm_case_type ON civicrm_case.case_type_id = civicrm_case_type.id
 LEFT JOIN civicrm_option_group option_group_case_status ON ( option_group_case_status.name = 'case_status' )
 LEFT JOIN civicrm_option_value case_status ON ( civicrm_case.status_id = case_status.value
 AND option_group_case_status.id = case_status.option_group_id )
 LEFT JOIN civicrm_relationship case_relationship ON ( case_relationship.case_id  = civicrm_case.id
 AND case_relationship.contact_id_b = {$userID})
 WHERE is_deleted = 0 AND cc.contact_id IN (SELECT id FROM civicrm_contact WHERE is_deleted <> 1)
{$myCaseWhereClause} {$myGroupByClause}";

    $res = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    while ($res->fetch()) {
      if (!empty($rows[$res->case_type]) && !empty($rows[$res->case_type][$res->case_status])) {
        $rows[$res->case_type][$res->case_status]['count'] = $rows[$res->case_type][$res->case_status]['count'] + 1;
      }
      else {
        $rows[$res->case_type][$res->case_status] = array(
          'count' => 1,
          'url' => CRM_Utils_System::url('civicrm/case/search',
            "reset=1&force=1&status={$res->status_id}&type={$res->case_type_id}&case_owner={$case_owner}"
          ),
        );
      }
    }
    $caseSummary['rows'] = array_merge($caseTypes, $rows);

    return $caseSummary;
  }

  /**
   * Get Case roles.
   *
   * @param int $contactID
   *   Contact id.
   * @param int $caseID
   *   Case id.
   * @param int $relationshipID
   *
   * @return array
   *   case role / relationships
   *
   */
  public static function getCaseRoles($contactID, $caseID, $relationshipID = NULL) {
    $query = '
    SELECT  rel.id as civicrm_relationship_id,
            con.sort_name as sort_name,
            civicrm_email.email as email,
            civicrm_phone.phone as phone,
            con.id as civicrm_contact_id,
            IF(rel.contact_id_a = %1, civicrm_relationship_type.label_a_b, civicrm_relationship_type.label_b_a) as relation,
            civicrm_relationship_type.id as relation_type,
            IF(rel.contact_id_a = %1, "a_b", "b_a") as relationship_direction
      FROM  civicrm_relationship rel
 INNER JOIN  civicrm_relationship_type ON rel.relationship_type_id = civicrm_relationship_type.id
 INNER JOIN  civicrm_contact con ON ((con.id <> %1 AND con.id IN (rel.contact_id_a, rel.contact_id_b)) OR (con.id = %1 AND rel.contact_id_b = rel.contact_id_a AND rel.contact_id_a = %1))
 LEFT JOIN  civicrm_phone ON (civicrm_phone.contact_id = con.id AND civicrm_phone.is_primary = 1)
 LEFT JOIN  civicrm_email ON (civicrm_email.contact_id = con.id AND civicrm_email.is_primary = 1)
     WHERE  (rel.contact_id_a = %1 OR rel.contact_id_b = %1) AND rel.case_id = %2
       AND  rel.is_active = 1 AND (rel.end_date IS NULL OR rel.end_date > NOW())';

    $params = array(
      1 => array($contactID, 'Positive'),
      2 => array($caseID, 'Positive'),
    );

    if ($relationshipID) {
      $query .= ' AND civicrm_relationship.id = %3 ';
      $params[3] = array($relationshipID, 'Integer');
    }
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $values = array();
    while ($dao->fetch()) {
      $rid = $dao->civicrm_relationship_id;
      $values[$rid]['cid'] = $dao->civicrm_contact_id;
      $values[$rid]['relation'] = $dao->relation;
      $values[$rid]['name'] = $dao->sort_name;
      $values[$rid]['email'] = $dao->email;
      $values[$rid]['phone'] = $dao->phone;
      $values[$rid]['relation_type'] = $dao->relation_type;
      $values[$rid]['rel_id'] = $dao->civicrm_relationship_id;
      $values[$rid]['client_id'] = $contactID;
      $values[$rid]['relationship_direction'] = $dao->relationship_direction;
    }

    $dao->free();
    return $values;
  }

  /**
   * Get Case Activities.
   *
   * @param int $caseID
   *   Case id.
   * @param array $params
   *   Posted params.
   * @param int $contactID
   *   Contact id.
   *
   * @param null $context
   * @param int $userID
   * @param null $type
   *
   * @return array
   *   Array of case activities
   *
   */
  public static function getCaseActivity($caseID, &$params, $contactID, $context = NULL, $userID = NULL, $type = NULL) {
    $values = array();

    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // CRM-5081 - formatting the dates to omit seconds.
    // Note the 00 in the date format string is needed otherwise later on it thinks scheduled ones are overdue.
    $select = "
           SELECT COUNT(ca.id) AS ismultiple,
                  ca.id AS id,
                  ca.activity_type_id AS type,
                  ca.activity_type_id AS activity_type_id,
                  tcc.sort_name AS target_contact_name,
                  tcc.id AS target_contact_id,
                  scc.sort_name AS source_contact_name,
                  scc.id AS source_contact_id,
                  acc.sort_name AS assignee_contact_name,
                  acc.id AS assignee_contact_id,
                  DATE_FORMAT(
                    IF(ca.activity_date_time < NOW() AND ca.status_id=ov.value,
                      ca.activity_date_time,
                      DATE_ADD(NOW(), INTERVAL 1 YEAR)
                    ), '%Y%m%d%H%i00') AS overdue_date,
                  DATE_FORMAT(ca.activity_date_time, '%Y%m%d%H%i00') AS display_date,
                  ca.status_id AS status,
                  ca.subject AS subject,
                  ca.is_deleted AS deleted,
                  ca.priority_id AS priority,
                  ca.weight AS weight,
                  GROUP_CONCAT(ef.file_id) AS attachment_ids ";

    $from = "
             FROM civicrm_case_activity cca
       INNER JOIN civicrm_activity ca
               ON ca.id = cca.activity_id
       INNER JOIN civicrm_activity_contact cas
               ON cas.activity_id = ca.id
              AND cas.record_type_id = {$sourceID}
       INNER JOIN civicrm_contact scc
               ON scc.id = cas.contact_id
        LEFT JOIN civicrm_activity_contact caa
               ON caa.activity_id = ca.id
              AND caa.record_type_id = {$assigneeID}
        LEFT JOIN civicrm_contact acc
               ON acc.id = caa.contact_id
        LEFT JOIN civicrm_activity_contact cat
               ON cat.activity_id = ca.id
              AND cat.record_type_id = {$targetID}
        LEFT JOIN civicrm_contact tcc
               ON tcc.id = cat.contact_id
       INNER JOIN civicrm_option_group cog
               ON cog.name = 'activity_type'
       INNER JOIN civicrm_option_value cov
               ON cov.option_group_id = cog.id
              AND cov.value = ca.activity_type_id
              AND cov.is_active = 1
        LEFT JOIN civicrm_entity_file ef
               ON ef.entity_table = 'civicrm_activity'
              AND ef.entity_id = ca.id
  LEFT OUTER JOIN civicrm_option_group og
               ON og.name = 'activity_status'
  LEFT OUTER JOIN civicrm_option_value ov
               ON ov.option_group_id=og.id
              AND ov.name = 'Scheduled'";

    $where = '
            WHERE cca.case_id= %1
              AND ca.is_current_revision = 1';

    if (!empty($params['source_contact_id'])) {
      $where .= "
              AND cas.contact_id = " . CRM_Utils_Type::escape($params['source_contact_id'], 'Integer');
    }

    if (!empty($params['status_id'])) {
      $where .= "
              AND ca.status_id = " . CRM_Utils_Type::escape($params['status_id'], 'Integer');
    }

    if (!empty($params['activity_deleted'])) {
      $where .= "
              AND ca.is_deleted = 1";
    }
    else {
      $where .= "
              AND ca.is_deleted = 0";
    }

    if (!empty($params['activity_type_id'])) {
      $where .= "
              AND ca.activity_type_id = " . CRM_Utils_Type::escape($params['activity_type_id'], 'Integer');
    }

    if (!empty($params['activity_date_low'])) {
      $fromActivityDate = CRM_Utils_Type::escape(CRM_Utils_Date::processDate($params['activity_date_low']), 'Date');
    }
    if (!empty($fromActivityDate)) {
      $where .= "
              AND ca.activity_date_time >= '{$fromActivityDate}'";
    }

    if (!empty($params['activity_date_high'])) {
      $toActivityDate = CRM_Utils_Type::escape(CRM_Utils_Date::processDate($params['activity_date_high']), 'Date');
      $toActivityDate = $toActivityDate ? $toActivityDate + 235959 : NULL;
    }
    if (!empty($toActivityDate)) {
      $where .= "
              AND ca.activity_date_time <= '{$toActivityDate}'";
    }

    $groupBy = "
         GROUP BY ca.id ";

    $sortBy = CRM_Utils_Array::value('sortBy', $params);
    if (!$sortBy) {
      // CRM-5081 - added id to act like creation date
      $orderBy = "
         ORDER BY overdue_date ASC, display_date DESC, weight DESC";
    }
    else {
      $sortBy = CRM_Utils_Type::escape($sortBy, 'String');
      $orderBy = " ORDER BY $sortBy ";
    }

    $page = CRM_Utils_Array::value('page', $params);
    $rp = CRM_Utils_Array::value('rp', $params);

    if (!$page) {
      $page = 1;
    }
    if (!$rp) {
      $rp = 10;
    }
    $start = (($page - 1) * $rp);
    $limit = " LIMIT $start, $rp";

    $query = $select . $from . $where . $groupBy . $orderBy . $limit;
    $queryParams = array(1 => array($caseID, 'Integer'));

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);

    $activityTypes = CRM_Case_PseudoConstant::caseActivityType(FALSE, TRUE);
    $activityStatuses = CRM_Core_PseudoConstant::activityStatus();

    $url = CRM_Utils_System::url("civicrm/case/activity",
      "reset=1&cid={$contactID}&caseid={$caseID}", FALSE, NULL, FALSE
    );

    $contextUrl = '';
    if ($context == 'fulltext') {
      $contextUrl = "&context={$context}";
    }
    $editUrl = "{$url}&action=update{$contextUrl}";
    $deleteUrl = "{$url}&action=delete{$contextUrl}";
    $restoreUrl = "{$url}&action=renew{$contextUrl}";
    $viewTitle = ts('View activity');
    $statusTitle = ts('Edit Status');

    $emailActivityTypeIDs = array(
      'Email' => CRM_Core_OptionGroup::getValue('activity_type',
        'Email',
        'name'
      ),
      'Inbound Email' => CRM_Core_OptionGroup::getValue('activity_type',
        'Inbound Email',
        'name'
      ),
    );

    $emailActivityTypeIDs = array(
      'Email' => CRM_Core_OptionGroup::getValue('activity_type',
        'Email',
        'name'
      ),
      'Inbound Email' => CRM_Core_OptionGroup::getValue('activity_type',
        'Inbound Email',
        'name'
      ),
    );

    $caseDeleted = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseID, 'is_deleted');

    // define statuses which are handled like Completed status (others are assumed to be handled like Scheduled status)
    $compStatusValues = array();
    $compStatusNames = array('Completed', 'Left Message', 'Cancelled', 'Unreachable', 'Not Required');
    foreach ($compStatusNames as $name) {
      $compStatusValues[] = CRM_Core_OptionGroup::getValue('activity_status', $name, 'name');
    }

    $contactViewUrl = CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid=", FALSE, NULL, FALSE);
    $hasViewContact = CRM_Core_Permission::giveMeAllACLs();
    $clientIds = self::retrieveContactIdsByCaseId($caseID);

    if (!$userID) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    $caseActivities = array();
    $caseCount = 0;

    while ($dao->fetch()) {
      $caseCount++;
      $caseActivity = array();
      $caseActivityId = $dao->id;

      $allowView = self::checkPermission($caseActivityId, 'view', $dao->activity_type_id, $userID);
      $allowEdit = self::checkPermission($caseActivityId, 'edit', $dao->activity_type_id, $userID);
      $allowDelete = self::checkPermission($caseActivityId, 'delete', $dao->activity_type_id, $userID);

      //do not have sufficient permission
      //to access given case activity record.
      if (!$allowView && !$allowEdit && !$allowDelete) {
        continue;
      }

      $caseActivity['DT_RowId'] = $caseActivityId;
      //Add classes to the row, via DataTables syntax
      $caseActivity['DT_RowClass'] = "crm-entity";

      if (CRM_Utils_Array::crmInArray($dao->status, $compStatusValues)) {
        $caseActivity['DT_RowClass'] .= " status-completed";
      }
      else {
        if (CRM_Utils_Date::overdue($dao->display_date)) {
          $caseActivity['DT_RowClass'] .= " status-overdue";
        }
        else {
          $caseActivity['DT_RowClass'] .= " status-scheduled";
        }
      }

      if (!empty($dao->priority)) {
        if ($dao->priority == CRM_Core_OptionGroup::getValue('priority', 'Urgent', 'name')) {
          $caseActivity['DT_RowClass'] .= " priority-urgent ";
        }
        elseif ($dao->priority == CRM_Core_OptionGroup::getValue('priority', 'Low', 'name')) {
          $caseActivity['DT_RowClass'] .= " priority-low ";
        }
      }

      //Add data to the row for inline editing, via DataTable syntax
      $caseActivity['DT_RowAttr'] = array();
      $caseActivity['DT_RowAttr']['data-entity'] = 'activity';
      $caseActivity['DT_RowAttr']['data-id'] = $caseActivityId;

      //Activity Date and Time
      $caseActivity['activity_date_time'] = CRM_Utils_Date::customFormat($dao->display_date);

      //Activity Subject
      $caseActivity['subject'] = $dao->subject;

      //Activity Type
      $caseActivity['type'] = $activityTypes[$dao->type]['label'];

      //Activity Target (With)
      $targetContact = '';
      if (isset($dao->target_contact_id)) {
        $targetContact = $dao->target_contact_name;
        if ($hasViewContact) {
          $targetContact = '<a href="' . $contactViewUrl . $dao->target_contact_id . '">' . $dao->target_contact_name . '</a>';
        }
      }
      $caseActivity['target_contact_name'] = $targetContact;

      //Activity Source Contact (Reporter)
      $sourceContact = $dao->source_contact_name;
      if ($hasViewContact) {
        $sourceContact = '<a href="' . $contactViewUrl . $dao->source_contact_id . '">' . $dao->source_contact_name . '</a>';
      }
      $caseActivity['source_contact_name'] = $sourceContact;

      //Activity Assignee. CRM-4485.
      $assigneeContact = '';
      if (isset($dao->assignee_contact_id)) {
        $assigneeContact = $dao->assignee_contact_name;
        if ($hasViewContact) {
          $assigneeContact = '<a href="' . $contactViewUrl . $dao->assignee_contact_id . '">' . $dao->assignee_contact_name . '</a>';
        }
      }
      $caseActivity['assignee_contact_name'] = $assigneeContact;

      //Activity Status
      $caseActivity['status_id'] = $activityStatuses[$dao->status];

      // FIXME: Why are we not using CRM_Core_Action for these links? This is too much manual work and likely to get out-of-sync with core markup.
      $url = "";
      $css = 'class="action-item crm-hover-button"';
      if ($allowView) {
        $viewUrl = CRM_Utils_System::url('civicrm/case/activity/view', array('cid' => $contactID, 'aid' => $caseActivityId));
        $url = '<a ' . str_replace('action-item', 'action-item medium-pop-up', $css) . 'href="' . $viewUrl . '" title="' . $viewTitle . '">' . ts('View') . '</a>';
      }
      $additionalUrl = "&id={$caseActivityId}";
      if (!$dao->deleted) {
        //hide edit link of activity type email.CRM-4530.
        if (!in_array($dao->type, $emailActivityTypeIDs)) {
          //hide Edit link if activity type is NOT editable (special case activities).CRM-5871
          if ($allowEdit) {
            $url .= '<a ' . $css . ' href="' . $editUrl . $additionalUrl . '">' . ts('Edit') . '</a> ';
          }
        }
        if ($allowDelete) {
          $url .= ' <a ' . str_replace('action-item', 'action-item small-popup', $css) . ' href="' . $deleteUrl . $additionalUrl . '">' . ts('Delete') . '</a>';
        }
      }
      elseif (!$caseDeleted) {
        $url = ' <a ' . $css . ' href="' . $restoreUrl . $additionalUrl . '">' . ts('Restore') . '</a>';
        $caseActivity['status_id'] = $caseActivity['status_id'] . '<br /> (deleted)';
      }

      //check for operations.
      if (self::checkPermission($caseActivityId, 'Move To Case', $dao->activity_type_id)) {
        $url .= ' <a ' . $css . ' href="#" onClick="Javascript:fileOnCase( \'move\',' . $caseActivityId . ', ' . $caseID . ', this ); return false;">' . ts('Move To Case') . '</a> ';
      }
      if (self::checkPermission($caseActivityId, 'Copy To Case', $dao->activity_type_id)) {
        $url .= ' <a ' . $css . ' href="#" onClick="Javascript:fileOnCase( \'copy\',' . $caseActivityId . ',' . $caseID . ', this ); return false;">' . ts('Copy To Case') . '</a> ';
      }
      // if there are file attachments we will return how many and, if only one, add a link to it
      if (!empty($dao->attachment_ids)) {
        $attachmentIDs = explode(',', $dao->attachment_ids);
        $caseActivity['no_attachments'] = count($attachmentIDs);
        if ($caseActivity['no_attachments'] == 1) {
          // if there is only one it's easy to do a link - otherwise just flag it
          $attachmentViewUrl = CRM_Utils_System::url(
            "civicrm/file",
            "reset=1&eid=" . $caseActivityId . "&id=" . $dao->attachment_ids,
            FALSE,
            NULL,
            FALSE
          );
          $url .= " <a href='$attachmentViewUrl' ><i class='crm-i fa-paperclip'></i></a>";
        }
      }

      $caseActivity['links'] = $url;

      array_push($caseActivities, $caseActivity);
    }
    $dao->free();

    $caseActivitiesDT = array();
    $caseActivitiesDT['data'] = $caseActivities;
    $caseActivitiesDT['recordsTotal'] = $caseCount;
    $caseActivitiesDT['recordsFiltered'] = $caseCount;

    return $caseActivitiesDT;
  }

  /**
   * Get Case Related Contacts.
   *
   * @param int $caseID
   *   Case id.
   * @param bool $skipDetails
   *   If true include details of contacts.
   *
   * @return array
   *   array of return properties
   *
   */
  public static function getRelatedContacts($caseID, $skipDetails = FALSE) {
    $values = array();
    $query = '
      SELECT cc.display_name as name, cc.sort_name as sort_name, cc.id, crt.label_b_a as role, ce.email
      FROM civicrm_relationship cr
      LEFT JOIN civicrm_relationship_type crt
        ON crt.id = cr.relationship_type_id
      LEFT JOIN civicrm_contact cc
        ON cc.id = cr.contact_id_b
      LEFT JOIN civicrm_email ce
        ON ce.contact_id = cc.id
        AND ce.is_primary= 1
      WHERE cr.case_id =  %1
      GROUP BY cc.id';

    $params = array(1 => array($caseID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    while ($dao->fetch()) {
      if ($skipDetails) {
        $values[$dao->id] = 1;
      }
      else {
        $values[] = array(
          'contact_id' => $dao->id,
          'display_name' => $dao->name,
          'sort_name' => $dao->sort_name,
          'role' => $dao->role,
          'email' => $dao->email,
        );
      }
    }
    $dao->free();

    return $values;
  }

  /**
   * Send e-mail copy of activity
   *
   * @param int $clientId
   * @param int $activityId
   *   Activity Id.
   * @param array $contacts
   *   Array of related contact.
   *
   * @param null $attachments
   * @param int $caseId
   *
   * @return bool |array
   */
  public static function sendActivityCopy($clientId, $activityId, $contacts, $attachments = NULL, $caseId) {
    if (!$activityId) {
      return FALSE;
    }

    $tplParams = $activityInfo = array();
    //if its a case activity
    if ($caseId) {
      $activityTypeId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $activityId, 'activity_type_id');
      $nonCaseActivityTypes = CRM_Core_PseudoConstant::activityType();
      if (!empty($nonCaseActivityTypes[$activityTypeId])) {
        $anyActivity = TRUE;
      }
      else {
        $anyActivity = FALSE;
      }
      $tplParams['isCaseActivity'] = 1;
      $tplParams['client_id'] = $clientId;
    }
    else {
      $anyActivity = TRUE;
    }

    $xmlProcessorProcess = new CRM_Case_XMLProcessor_Process();
    $isRedact = $xmlProcessorProcess->getRedactActivityEmail();

    $xmlProcessorReport = new CRM_Case_XMLProcessor_Report();

    $activityInfo = $xmlProcessorReport->getActivityInfo($clientId, $activityId, $anyActivity, $isRedact);
    if ($caseId) {
      $activityInfo['fields'][] = array('label' => 'Case ID', 'type' => 'String', 'value' => $caseId);
    }
    $tplParams['activity'] = $activityInfo;
    foreach ($tplParams['activity']['fields'] as $k => $val) {
      if (CRM_Utils_Array::value('label', $val) == ts('Subject')) {
        $activitySubject = $val['value'];
        break;
      }
    }
    $session = CRM_Core_Session::singleton();
    // CRM-8926 If user is not logged in, use the activity creator as userID
    if (!($userID = $session->get('userID'))) {
      $userID = CRM_Activity_BAO_Activity::getSourceContactID($activityId);
    }

    //also create activities simultaneously of this copy.
    $activityParams = array();

    $activityParams['source_record_id'] = $activityId;
    $activityParams['source_contact_id'] = $userID;
    $activityParams['activity_type_id'] = CRM_Core_OptionGroup::getValue('activity_type', 'Email', 'name');
    $activityParams['activity_date_time'] = date('YmdHis');
    $activityParams['status_id'] = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
    $activityParams['medium_id'] = CRM_Core_OptionGroup::getValue('encounter_medium', 'email', 'name');
    $activityParams['case_id'] = $caseId;
    $activityParams['is_auto'] = 0;
    $activityParams['target_id'] = $clientId;

    $tplParams['activitySubject'] = $activitySubject;

    // if it’s a case activity, add hashed id to the template (CRM-5916)
    if ($caseId) {
      $tplParams['idHash'] = substr(sha1(CIVICRM_SITE_KEY . $caseId), 0, 7);
    }

    $result = array();
    list($name, $address) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);

    $receiptFrom = "$name <$address>";

    $recordedActivityParams = array();

    foreach ($contacts as $mail => $info) {
      $tplParams['contact'] = $info;
      self::buildPermissionLinks($tplParams, $activityParams);

      $displayName = CRM_Utils_Array::value('display_name', $info);

      list($result[CRM_Utils_Array::value('contact_id', $info)], $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate(
        array(
          'groupName' => 'msg_tpl_workflow_case',
          'valueName' => 'case_activity',
          'contactId' => CRM_Utils_Array::value('contact_id', $info),
          'tplParams' => $tplParams,
          'from' => $receiptFrom,
          'toName' => $displayName,
          'toEmail' => $mail,
          'attachments' => $attachments,
        )
      );

      $activityParams['subject'] = $activitySubject . ' - copy sent to ' . $displayName;
      $activityParams['details'] = $message;

      if (!empty($result[$info['contact_id']])) {
        /*
         * Really only need to record one activity with all the targets combined.
         * Originally the template was going to possibly have different content, e.g. depending on permissions,
         * but it's always the same content at the moment.
         */
        if (empty($recordedActivityParams)) {
          $recordedActivityParams = $activityParams;
        }
        else {
          $recordedActivityParams['subject'] .= "; $displayName";
        }
        $recordedActivityParams['target_contact_id'][] = $info['contact_id'];
      }
      else {
        unset($result[CRM_Utils_Array::value('contact_id', $info)]);
      }
    }

    if (!empty($recordedActivityParams)) {
      $activity = CRM_Activity_BAO_Activity::create($recordedActivityParams);

      //create case_activity record if its case activity.
      if ($caseId) {
        $caseParams = array(
          'activity_id' => $activity->id,
          'case_id' => $caseId,
        );
        self::processCaseActivity($caseParams);
      }
    }

    return $result;
  }

  /**
   * Retrieve count of activities having a particular type, and
   * associated with a particular case.
   *
   * @param int $caseId
   *   ID of the case.
   * @param int $activityTypeId
   *   ID of the activity type.
   *
   * @return array
   */
  public static function getCaseActivityCount($caseId, $activityTypeId) {
    $queryParam = array(
      1 => array($caseId, 'Integer'),
      2 => array($activityTypeId, 'Integer'),
    );
    $query = "SELECT count(ca.id) as countact
 FROM       civicrm_activity ca
 INNER JOIN civicrm_case_activity cca ON ca.id = cca.activity_id
 WHERE      ca.activity_type_id = %2
 AND       cca.case_id = %1
 AND        ca.is_deleted = 0";

    $dao = CRM_Core_DAO::executeQuery($query, $queryParam);
    if ($dao->fetch()) {
      return $dao->countact;
    }

    return FALSE;
  }

  /**
   * Create an activity for a case via email.
   *
   * @param int $file
   *   Email sent.
   *
   * @return array|void
   *   $activity object of newly creted activity via email
   */
  public static function recordActivityViaEmail($file) {
    if (!file_exists($file) ||
      !is_readable($file)
    ) {
      return CRM_Core_Error::fatal(ts('File %1 does not exist or is not readable',
        array(1 => $file)
      ));
    }

    $result = CRM_Utils_Mail_Incoming::parse($file);
    if ($result['is_error']) {
      return $result;
    }

    foreach ($result['to'] as $to) {
      $caseId = NULL;

      $emailPattern = '/^([A-Z0-9._%+-]+)\+([\d]+)@[A-Z0-9.-]+\.[A-Z]{2,4}$/i';
      $replacement = preg_replace($emailPattern, '$2', $to['email']);

      if ($replacement !== $to['email']) {
        $caseId = $replacement;
        //if caseId is invalid, return as error file
        if (!CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $caseId, 'id')) {
          return CRM_Core_Error::createAPIError(ts('Invalid case ID ( %1 ) in TO: field.',
            array(1 => $caseId)
          ));
        }
      }
      else {
        continue;
      }

      // TODO: May want to replace this with a call to getRelatedAndGlobalContacts() when this feature is revisited.
      // (Or for efficiency call the global one outside the loop and then union with this each time.)
      $contactDetails = self::getRelatedContacts($caseId, TRUE);

      if (!empty($contactDetails[$result['from']['id']])) {
        $params = array();
        $params['subject'] = $result['subject'];
        $params['activity_date_time'] = $result['date'];
        $params['details'] = $result['body'];
        $params['source_contact_id'] = $result['from']['id'];
        $params['status_id'] = CRM_Core_OptionGroup::getValue('activity_status',
          'Completed',
          'name'
        );

        $details = CRM_Case_PseudoConstant::caseActivityType();
        $matches = array();
        preg_match('/^\W+([a-zA-Z0-9_ ]+)(\W+)?\n/i',
          $result['body'], $matches
        );

        if (!empty($matches) && isset($matches[1])) {
          $activityType = trim($matches[1]);
          if (isset($details[$activityType])) {
            $params['activity_type_id'] = $details[$activityType]['id'];
          }
        }
        if (!isset($params['activity_type_id'])) {
          $params['activity_type_id'] = CRM_Core_OptionGroup::getValue('activity_type', 'Inbound Email', 'name');
        }

        // create activity
        $activity = CRM_Activity_BAO_Activity::create($params);

        $caseParams = array(
          'activity_id' => $activity->id,
          'case_id' => $caseId,
        );
        self::processCaseActivity($caseParams);
      }
      else {
        return CRM_Core_Error::createAPIError(ts('FROM email contact %1 doesn\'t have a relationship to the referenced case.',
          array(1 => $result['from']['email'])
        ));
      }
    }
  }

  /**
   * Retrieve the scheduled activity type and date.
   *
   * @param array $cases
   *   Array of contact and case id.
   *
   * @param string $type
   *
   * @return array
   *   Array of scheduled activity type and date
   *
   *
   */
  public static function getNextScheduledActivity($cases, $type = 'upcoming') {
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    $caseID = implode(',', $cases['case_id']);
    $contactID = implode(',', $cases['contact_id']);

    $condition = "
 AND civicrm_case_contact.contact_id IN( {$contactID} )
 AND civicrm_case.id IN( {$caseID})
 AND civicrm_case.is_deleted     = {$cases['case_deleted']}";

    $query = self::getCaseActivityQuery($type, $userID, $condition, $cases['case_deleted']);

    $res = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    $activityInfo = array();
    while ($res->fetch()) {
      if ($type == 'upcoming') {
        $activityInfo[$res->case_id]['date'] = $res->case_scheduled_activity_date;
        $activityInfo[$res->case_id]['type'] = $res->case_scheduled_activity_type;
      }
      else {
        $activityInfo[$res->case_id]['date'] = $res->case_recent_activity_date;
        $activityInfo[$res->case_id]['type'] = $res->case_recent_activity_type;
      }
    }

    return $activityInfo;
  }

  /**
   * Combine all the exportable fields from the lower levels object.
   *
   * @return array
   *   array of exportable Fields
   */
  public static function &exportableFields() {
    if (!self::$_exportableFields) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = array();
      }

      $fields = CRM_Case_DAO_Case::export();
      $fields['case_role'] = array('title' => ts('Role in Case'));
      $fields['case_type'] = array(
        'title' => ts('Case Type'),
        'name' => 'case_type',
      );
      $fields['case_status'] = array(
        'title' => ts('Case Status'),
        'name' => 'case_status',
      );

      // add custom data for cases
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Case'));

      self::$_exportableFields = $fields;
    }
    return self::$_exportableFields;
  }

  /**
   * Restore the record that are associated with this case.
   *
   * @param int $caseId
   *   Id of the case to restore.
   *
   * @return bool
   */
  public static function restoreCase($caseId) {
    //restore activities
    $activities = self::getCaseActivityDates($caseId);
    if ($activities) {
      foreach ($activities as $value) {
        CRM_Activity_BAO_Activity::restoreActivity($value);
      }
    }
    //restore case
    $case = new CRM_Case_DAO_Case();
    $case->id = $caseId;
    $case->is_deleted = 0;
    $case->save();

    //CRM-7364, enable relationships
    self::enableDisableCaseRelationships($caseId, TRUE);
    return TRUE;
  }

  /**
   * @param $groupInfo
   * @param null $sort
   * @param null $showLinks
   * @param bool $returnOnlyCount
   * @param int $offset
   * @param int $rowCount
   *
   * @return array
   */
  public static function getGlobalContacts(&$groupInfo, $sort = NULL, $showLinks = NULL, $returnOnlyCount = FALSE, $offset = 0, $rowCount = 25) {
    $globalContacts = array();

    $settingsProcessor = new CRM_Case_XMLProcessor_Settings();
    $settings = $settingsProcessor->run();
    if (!empty($settings)) {
      $groupInfo['name'] = $settings['groupname'];
      if ($groupInfo['name']) {
        $searchParams = array('name' => $groupInfo['name']);
        $results = array();
        CRM_Contact_BAO_Group::retrieve($searchParams, $results);
        if ($results) {
          $groupInfo['id'] = $results['id'];
          $groupInfo['title'] = $results['title'];
          $params = array(array('group', '=', $groupInfo['id'], 0, 0));
          $return = array('contact_id' => 1, 'sort_name' => 1, 'display_name' => 1, 'email' => 1, 'phone' => 1);
          list($globalContacts) = CRM_Contact_BAO_Query::apiQuery($params, $return, NULL, $sort, $offset, $rowCount, TRUE, $returnOnlyCount);

          if ($returnOnlyCount) {
            return $globalContacts;
          }

          if ($showLinks) {
            foreach ($globalContacts as $idx => $contact) {
              $globalContacts[$idx]['sort_name'] = '<a href="' . CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$contact['contact_id']}") . '">' . $contact['sort_name'] . '</a>';
            }
          }
        }
      }
    }
    return $globalContacts;
  }

  /**
   * Convenience function to get both case contacts and global in one array.
   * @param int $caseId
   *
   * @return array
   */
  public static function getRelatedAndGlobalContacts($caseId) {
    $relatedContacts = self::getRelatedContacts($caseId);

    $groupInfo = array();
    $globalContacts = self::getGlobalContacts($groupInfo);

    //unset values which are not required.
    foreach ($globalContacts as $k => & $v) {
      unset($v['email_id']);
      unset($v['group_contact_id']);
      unset($v['status']);
      unset($v['phone']);
      $v['role'] = $groupInfo['title'];
    }
    //include multiple listings for the same contact/different roles.
    $relatedGlobalContacts = array_merge($relatedContacts, $globalContacts);
    return $relatedGlobalContacts;
  }

  /**
   * Get Case ActivitiesDueDates with given criteria.
   *
   * @param int $caseID
   *   Case id.
   * @param array $criteriaParams
   *   Given criteria.
   * @param bool $latestDate
   *   If set newest or oldest date is selected.
   *
   * @return array
   *   case activities due dates
   *
   */
  public static function getCaseActivityDates($caseID, $criteriaParams = array(), $latestDate = FALSE) {
    $values = array();
    $selectDate = " ca.activity_date_time";
    $where = $groupBy = ' ';

    if (!$caseID) {
      return NULL;
    }

    if ($latestDate) {
      if (!empty($criteriaParams['activity_type_id'])) {
        $where .= " AND ca.activity_type_id    = " . CRM_Utils_Type::escape($criteriaParams['activity_type_id'], 'Integer');
        $where .= " AND ca.is_current_revision = 1";
        $groupBy .= " GROUP BY ca.activity_type_id";
      }

      if (!empty($criteriaParams['newest'])) {
        $selectDate = " max(ca.activity_date_time) ";
      }
      else {
        $selectDate = " min(ca.activity_date_time) ";
      }
    }

    $query = "SELECT ca.id, {$selectDate} as activity_date
                  FROM civicrm_activity ca
                  LEFT JOIN civicrm_case_activity cca ON cca.activity_id = ca.id LEFT JOIN civicrm_case cc ON cc.id = cca.case_id
                  WHERE cc.id = %1 {$where} {$groupBy}";

    $params = array(1 => array($caseID, 'Integer'));
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    while ($dao->fetch()) {
      $values[$dao->id]['id'] = $dao->id;
      $values[$dao->id]['activity_date'] = $dao->activity_date;
    }
    $dao->free();
    return $values;
  }

  /**
   * Create activities when Case or Other roles assigned/modified/deleted.
   *
   * @param int $caseId
   * @param int $relationshipId
   *   Relationship id.
   * @param int $relContactId
   *   Case role assignee contactId.
   * @param int $contactId
   */
  public static function createCaseRoleActivity($caseId, $relationshipId, $relContactId = NULL, $contactId = NULL) {
    if (!$caseId || !$relationshipId || empty($relationshipId)) {
      return;
    }

    $queryParam = array();
    if (is_array($relationshipId)) {
      $relationshipId = implode(',', $relationshipId);
      $relationshipClause = " civicrm_relationship.id IN ($relationshipId)";
    }
    else {
      $relationshipClause = " civicrm_relationship.id = %1";
      $queryParam[1] = array($relationshipId, 'Positive');
    }

    $query = "
   SELECT  cc.display_name as clientName,
           cca.display_name as  assigneeContactName,
           civicrm_relationship.case_id as caseId,
           civicrm_relationship_type.label_a_b as relation_a_b,
           civicrm_relationship_type.label_b_a as relation_b_a,
           civicrm_relationship.contact_id_b as rel_contact_id,
           civicrm_relationship.contact_id_a as assign_contact_id
     FROM  civicrm_relationship_type,  civicrm_relationship
 LEFT JOIN  civicrm_contact cc  ON cc.id  = civicrm_relationship.contact_id_b
 LEFT JOIN  civicrm_contact cca ON cca.id = civicrm_relationship.contact_id_a
    WHERE  civicrm_relationship.relationship_type_id = civicrm_relationship_type.id AND {$relationshipClause}";

    $dao = CRM_Core_DAO::executeQuery($query, $queryParam);

    while ($dao->fetch()) {
      //to get valid assignee contact(s).
      if (isset($dao->caseId) || $dao->rel_contact_id != $contactId) {
        $caseRelationship = $dao->relation_a_b;
        $assigneContactName = $dao->clientName;
        $assigneContactIds[$dao->rel_contact_id] = $dao->rel_contact_id;
      }
      else {
        $caseRelationship = $dao->relation_b_a;
        $assigneContactName = $dao->assigneeContactName;
        $assigneContactIds[$dao->assign_contact_id] = $dao->assign_contact_id;
      }
    }

    $session = CRM_Core_Session::singleton();
    $activityParams = array(
      'source_contact_id' => $session->get('userID'),
      'subject' => $caseRelationship . ' : ' . $assigneContactName,
      'activity_date_time' => date('YmdHis'),
      'status_id' => CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name'),
    );

    //if $relContactId is passed, role is added or modified.
    if (!empty($relContactId)) {
      $activityParams['assignee_contact_id'] = $assigneContactIds;

      $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
        'Assign Case Role',
        'name'
      );
    }
    else {
      $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
        'Remove Case Role',
        'name'
      );
    }

    $activityParams['activity_type_id'] = $activityTypeID;

    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    //create case_activity record.
    $caseParams = array(
      'activity_id' => $activity->id,
      'case_id' => $caseId,
    );

    CRM_Case_BAO_Case::processCaseActivity($caseParams);
  }

  /**
   * Get case manger
   * contact which is assigned a case role of case manager.
   *
   * @param int $caseType
   *   Case type.
   * @param int $caseId
   *   Case id.
   *
   * @return array
   *   array of contact on success otherwise empty
   *
   */
  public static function getCaseManagerContact($caseType, $caseId) {
    if (!$caseType || !$caseId) {
      return NULL;
    }

    $caseManagerContact = array();
    $xmlProcessor = new CRM_Case_XMLProcessor_Process();

    $managerRoleId = $xmlProcessor->getCaseManagerRoleId($caseType);

    if (!empty($managerRoleId)) {
      $managerRoleQuery = "
SELECT civicrm_contact.id as casemanager_id,
       civicrm_contact.sort_name as casemanager
 FROM civicrm_contact
 LEFT JOIN civicrm_relationship ON (civicrm_relationship.contact_id_b = civicrm_contact.id AND civicrm_relationship.relationship_type_id = %1)
 LEFT JOIN civicrm_case ON civicrm_case.id = civicrm_relationship.case_id
 WHERE civicrm_case.id = %2";

      $managerRoleParams = array(
        1 => array($managerRoleId, 'Integer'),
        2 => array($caseId, 'Integer'),
      );

      $dao = CRM_Core_DAO::executeQuery($managerRoleQuery, $managerRoleParams);
      if ($dao->fetch()) {
        $caseManagerContact['casemanager_id'] = $dao->casemanager_id;
        $caseManagerContact['casemanager'] = $dao->casemanager;
      }
    }

    return $caseManagerContact;
  }

  /**
   * @param int $contactId
   * @param bool $excludeDeleted
   *
   * @return null|string
   */
  public static function caseCount($contactId = NULL, $excludeDeleted = TRUE) {
    $params = array('check_permissions' => TRUE);
    if ($excludeDeleted) {
      $params['is_deleted'] = 0;
    }
    if ($contactId) {
      $params['contact_id'] = $contactId;
    }
    return civicrm_api3('Case', 'getcount', $params);
  }

  /**
   * Retrieve related cases for give case.
   *
   * @param int $mainCaseId
   *   Id of main case.
   * @param int $contactId
   *   Id of contact.
   * @param bool $excludeDeleted
   *   Do not include deleted cases.
   *
   * @return array
   */
  public static function getRelatedCases($mainCaseId, $contactId, $excludeDeleted = TRUE) {
    //FIXME : do check for permissions.

    $relatedCases = array();
    if (!$mainCaseId || !$contactId) {
      return $relatedCases;
    }

    $linkActType = array_search('Link Cases',
      CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name')
    );
    if (!$linkActType) {
      return $relatedCases;
    }

    $whereClause = "mainCase.id = %2";
    if ($excludeDeleted) {
      $whereClause .= " AND ( relAct.is_deleted = 0 OR relAct.is_deleted IS NULL )";
    }

    //1. first fetch related case ids.
    $query = "
    SELECT  relCaseAct.case_id
      FROM  civicrm_case mainCase
 INNER JOIN  civicrm_case_activity mainCaseAct ON (mainCaseAct.case_id = mainCase.id)
 INNER JOIN  civicrm_activity mainAct          ON (mainCaseAct.activity_id = mainAct.id AND mainAct.activity_type_id = %1)
 INNER JOIN  civicrm_case_activity relCaseAct  ON (relCaseAct.activity_id = mainAct.id AND mainCaseAct.id !=  relCaseAct.id)
 INNER JOIN  civicrm_activity relAct           ON (relCaseAct.activity_id = relAct.id  AND relAct.activity_type_id = %1)
     WHERE  $whereClause";

    $dao = CRM_Core_DAO::executeQuery($query, array(
      1 => array($linkActType, 'Integer'),
      2 => array($mainCaseId, 'Integer'),
    ));
    $relatedCaseIds = array();
    while ($dao->fetch()) {
      $relatedCaseIds[$dao->case_id] = $dao->case_id;
    }
    $dao->free();

    // there are no related cases.
    if (empty($relatedCaseIds)) {
      return $relatedCases;
    }

    $whereClause = 'relCase.id IN ( ' . implode(',', $relatedCaseIds) . ' )';
    if ($excludeDeleted) {
      $whereClause .= " AND ( relCase.is_deleted = 0 OR relCase.is_deleted IS NULL )";
    }

    //filter for permissioned cases.
    $filterCases = array();
    $doFilterCases = FALSE;
    if (!CRM_Core_Permission::check('access all cases and activities')) {
      $doFilterCases = TRUE;
      $session = CRM_Core_Session::singleton();
      $filterCases = CRM_Case_BAO_Case::getCases(FALSE, $session->get('userID'));
    }

    //2. fetch the details of related cases.
    $query = "
    SELECT  relCase.id as id,
            civicrm_case_type.title as case_type,
            client.display_name as client_name,
            client.id as client_id
      FROM  civicrm_case relCase
 INNER JOIN  civicrm_case_contact relCaseContact ON ( relCase.id = relCaseContact.case_id )
 INNER JOIN  civicrm_contact      client         ON ( client.id = relCaseContact.contact_id )
 LEFT JOIN  civicrm_case_type ON relCase.case_type_id = civicrm_case_type.id
     WHERE  {$whereClause}";

    $dao = CRM_Core_DAO::executeQuery($query);
    $contactViewUrl = CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid=");
    $hasViewContact = CRM_Core_Permission::giveMeAllACLs();

    while ($dao->fetch()) {
      $caseView = NULL;
      if (!$doFilterCases || array_key_exists($dao->id, $filterCases)) {
        $caseViewStr = "reset=1&id={$dao->id}&cid={$dao->client_id}&action=view&context=case&selectedChild=case";
        $caseViewUrl = CRM_Utils_System::url("civicrm/contact/view/case", $caseViewStr);
        $caseView = "<a class='action-item no-popup crm-hover-button' href='{$caseViewUrl}'>" . ts('View Case') . "</a>";
      }
      $clientView = $dao->client_name;
      if ($hasViewContact) {
        $clientView = "<a href='{$contactViewUrl}{$dao->client_id}'>$dao->client_name</a>";
      }

      $relatedCases[$dao->id] = array(
        'case_id' => $dao->id,
        'case_type' => $dao->case_type,
        'client_name' => $clientView,
        'links' => $caseView,
      );
    }
    $dao->free();

    return $relatedCases;
  }

  /**
   * Merge two duplicate contacts' cases - follow CRM-5758 rules.
   *
   * @see CRM_Dedupe_Merger::cpTables()
   *
   * TODO: use the 3rd $sqls param to append sql statements rather than executing them here
   *
   * @param int $mainContactId
   * @param int $otherContactId
   */
  public static function mergeContacts($mainContactId, $otherContactId) {
    self::mergeCases($mainContactId, NULL, $otherContactId);
  }

  /**
   * Function perform two task.
   * 1. Merge two duplicate contacts cases - follow CRM-5758 rules.
   * 2. Merge two cases of same contact - follow CRM-5598 rules.
   *
   * @param int $mainContactId
   *   Contact id of main contact record.
   * @param int $mainCaseId
   *   Case id of main case record.
   * @param int $otherContactId
   *   Contact id of record which is going to merge.
   * @param int $otherCaseId
   *   Case id of record which is going to merge.
   *
   * @param bool $changeClient
   *
   * @return int|NULL
   */
  public static function mergeCases(
    $mainContactId, $mainCaseId = NULL, $otherContactId = NULL,
    $otherCaseId = NULL, $changeClient = FALSE) {
    $moveToTrash = TRUE;

    $duplicateContacts = FALSE;
    if ($mainContactId && $otherContactId &&
      $mainContactId != $otherContactId
    ) {
      $duplicateContacts = TRUE;
    }

    $duplicateCases = FALSE;
    if ($mainCaseId && $otherCaseId &&
      $mainCaseId != $otherCaseId
    ) {
      $duplicateCases = TRUE;
    }

    $mainCaseIds = array();
    if (!$duplicateContacts && !$duplicateCases) {
      return $mainCaseIds;
    }

    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name');
    $activityStatuses = CRM_Core_PseudoConstant::activityStatus('name');
    $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    $processCaseIds = array($otherCaseId);
    if ($duplicateContacts && !$duplicateCases) {
      if ($changeClient) {
        $processCaseIds = array($mainCaseId);
      }
      else {
        //get all case ids for other contact.
        $processCaseIds = self::retrieveCaseIdsByContactId($otherContactId, TRUE);
      }
      if (!is_array($processCaseIds)) {
        return;
      }
    }

    $session = CRM_Core_Session::singleton();
    $currentUserId = $session->get('userID');

    CRM_Utils_Hook::pre_case_merge($mainContactId, $mainCaseId, $otherContactId, $otherCaseId, $changeClient);

    // copy all cases and connect to main contact id.
    foreach ($processCaseIds as $otherCaseId) {
      if ($duplicateContacts) {
        $mainCase = CRM_Core_DAO::copyGeneric('CRM_Case_DAO_Case', array('id' => $otherCaseId));
        $mainCaseId = $mainCase->id;
        if (!$mainCaseId) {
          continue;
        }

        // CRM-11662 Copy Case custom data
        $extends = array('case');
        $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, NULL, $extends);
        if ($groupTree) {
          foreach ($groupTree as $groupID => $group) {
            $table[$groupTree[$groupID]['table_name']] = array('entity_id');
            foreach ($group['fields'] as $fieldID => $field) {
              $table[$groupTree[$groupID]['table_name']][] = $groupTree[$groupID]['fields'][$fieldID]['column_name'];
            }
          }

          foreach ($table as $tableName => $tableColumns) {
            $insert = 'INSERT INTO ' . $tableName . ' (' . implode(', ', $tableColumns) . ') ';
            $tableColumns[0] = $mainCaseId;
            $select = 'SELECT ' . implode(', ', $tableColumns);
            $from = ' FROM ' . $tableName;
            $where = " WHERE {$tableName}.entity_id = {$otherCaseId}";
            $query = $insert . $select . $from . $where;
            $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
          }
        }

        $mainCase->free();

        $mainCaseIds[] = $mainCaseId;
        //insert record for case contact.
        $otherCaseContact = new CRM_Case_DAO_CaseContact();
        $otherCaseContact->case_id = $otherCaseId;
        $otherCaseContact->find();
        while ($otherCaseContact->fetch()) {
          $mainCaseContact = new CRM_Case_DAO_CaseContact();
          $mainCaseContact->case_id = $mainCaseId;
          $mainCaseContact->contact_id = $otherCaseContact->contact_id;
          if ($mainCaseContact->contact_id == $otherContactId) {
            $mainCaseContact->contact_id = $mainContactId;
          }
          //avoid duplicate object.
          if (!$mainCaseContact->find(TRUE)) {
            $mainCaseContact->save();
          }
          $mainCaseContact->free();
        }
        $otherCaseContact->free();
      }
      elseif (!$otherContactId) {
        $otherContactId = $mainContactId;
      }

      if (!$mainCaseId || !$otherCaseId ||
        !$mainContactId || !$otherContactId
      ) {
        continue;
      }

      // get all activities for other case.
      $otherCaseActivities = array();
      CRM_Core_DAO::commonRetrieveAll('CRM_Case_DAO_CaseActivity', 'case_id', $otherCaseId, $otherCaseActivities);

      //for duplicate cases do not process singleton activities.
      $otherActivityIds = $singletonActivityIds = array();
      foreach ($otherCaseActivities as $caseActivityId => $otherIds) {
        $otherActId = CRM_Utils_Array::value('activity_id', $otherIds);
        if (!$otherActId || in_array($otherActId, $otherActivityIds)) {
          continue;
        }
        $otherActivityIds[] = $otherActId;
      }
      if ($duplicateCases) {
        if ($openCaseType = array_search('Open Case', $activityTypes)) {
          $sql = "
SELECT  id
  FROM  civicrm_activity
 WHERE  activity_type_id = $openCaseType
   AND  id IN ( " . implode(',', array_values($otherActivityIds)) . ');';
          $dao = CRM_Core_DAO::executeQuery($sql);
          while ($dao->fetch()) {
            $singletonActivityIds[] = $dao->id;
          }
          $dao->free();
        }
      }

      // migrate all activities and connect to main contact.
      $copiedActivityIds = $activityMappingIds = array();
      sort($otherActivityIds);
      foreach ($otherActivityIds as $otherActivityId) {

        //for duplicate cases -
        //do not migrate singleton activities.
        if (!$otherActivityId || in_array($otherActivityId, $singletonActivityIds)) {
          continue;
        }

        //migrate activity record.
        $otherActivity = new CRM_Activity_DAO_Activity();
        $otherActivity->id = $otherActivityId;
        if (!$otherActivity->find(TRUE)) {
          continue;
        }

        $mainActVals = array();
        $mainActivity = new CRM_Activity_DAO_Activity();
        CRM_Core_DAO::storeValues($otherActivity, $mainActVals);
        $mainActivity->copyValues($mainActVals);
        $mainActivity->id = NULL;
        $mainActivity->activity_date_time = CRM_Utils_Date::isoToMysql($otherActivity->activity_date_time);
        $mainActivity->source_record_id = CRM_Utils_Array::value($mainActivity->source_record_id,
          $activityMappingIds
        );

        $mainActivity->original_id = CRM_Utils_Array::value($mainActivity->original_id,
          $activityMappingIds
        );

        $mainActivity->parent_id = CRM_Utils_Array::value($mainActivity->parent_id,
          $activityMappingIds
        );
        $mainActivity->save();
        $mainActivityId = $mainActivity->id;
        if (!$mainActivityId) {
          continue;
        }

        $activityMappingIds[$otherActivityId] = $mainActivityId;
        // insert log of all activities
        CRM_Activity_BAO_Activity::logActivityAction($mainActivity);

        $otherActivity->free();
        $mainActivity->free();
        $copiedActivityIds[] = $otherActivityId;

        //create case activity record.
        $mainCaseActivity = new CRM_Case_DAO_CaseActivity();
        $mainCaseActivity->case_id = $mainCaseId;
        $mainCaseActivity->activity_id = $mainActivityId;
        $mainCaseActivity->save();
        $mainCaseActivity->free();

        //migrate source activity.
        $otherSourceActivity = new CRM_Activity_DAO_ActivityContact();
        $otherSourceActivity->activity_id = $otherActivityId;
        $otherSourceActivity->record_type_id = $sourceID;
        $otherSourceActivity->find();
        while ($otherSourceActivity->fetch()) {
          $mainActivitySource = new CRM_Activity_DAO_ActivityContact();
          $mainActivitySource->record_type_id = $sourceID;
          $mainActivitySource->activity_id = $mainActivityId;
          $mainActivitySource->contact_id = $otherSourceActivity->contact_id;
          if ($mainActivitySource->contact_id == $otherContactId) {
            $mainActivitySource->contact_id = $mainContactId;
          }
          //avoid duplicate object.
          if (!$mainActivitySource->find(TRUE)) {
            $mainActivitySource->save();
          }
          $mainActivitySource->free();
        }
        $otherSourceActivity->free();

        //migrate target activities.
        $otherTargetActivity = new CRM_Activity_DAO_ActivityContact();
        $otherTargetActivity->activity_id = $otherActivityId;
        $otherTargetActivity->record_type_id = $targetID;
        $otherTargetActivity->find();
        while ($otherTargetActivity->fetch()) {
          $mainActivityTarget = new CRM_Activity_DAO_ActivityContact();
          $mainActivityTarget->record_type_id = $targetID;
          $mainActivityTarget->activity_id = $mainActivityId;
          $mainActivityTarget->contact_id = $otherTargetActivity->contact_id;
          if ($mainActivityTarget->contact_id == $otherContactId) {
            $mainActivityTarget->contact_id = $mainContactId;
          }
          //avoid duplicate object.
          if (!$mainActivityTarget->find(TRUE)) {
            $mainActivityTarget->save();
          }
          $mainActivityTarget->free();
        }
        $otherTargetActivity->free();

        //migrate assignee activities.
        $otherAssigneeActivity = new CRM_Activity_DAO_ActivityContact();
        $otherAssigneeActivity->activity_id = $otherActivityId;
        $otherAssigneeActivity->record_type_id = $assigneeID;
        $otherAssigneeActivity->find();
        while ($otherAssigneeActivity->fetch()) {
          $mainAssigneeActivity = new CRM_Activity_DAO_ActivityContact();
          $mainAssigneeActivity->activity_id = $mainActivityId;
          $mainAssigneeActivity->record_type_id = $assigneeID;
          $mainAssigneeActivity->contact_id = $otherAssigneeActivity->contact_id;
          if ($mainAssigneeActivity->contact_id == $otherContactId) {
            $mainAssigneeActivity->contact_id = $mainContactId;
          }
          //avoid duplicate object.
          if (!$mainAssigneeActivity->find(TRUE)) {
            $mainAssigneeActivity->save();
          }
          $mainAssigneeActivity->free();
        }
        $otherAssigneeActivity->free();

        // copy custom fields and attachments
        $aparams = array(
          'activityID' => $otherActivityId,
          'mainActivityId' => $mainActivityId,
        );
        CRM_Activity_BAO_Activity::copyExtendedActivityData($aparams);
      }

      //copy case relationship.
      if ($duplicateContacts) {
        //migrate relationship records.
        $otherRelationship = new CRM_Contact_DAO_Relationship();
        $otherRelationship->case_id = $otherCaseId;
        $otherRelationship->find();
        $otherRelationshipIds = array();
        while ($otherRelationship->fetch()) {
          $otherRelVals = array();
          $updateOtherRel = FALSE;
          CRM_Core_DAO::storeValues($otherRelationship, $otherRelVals);

          $mainRelationship = new CRM_Contact_DAO_Relationship();
          $mainRelationship->copyValues($otherRelVals);
          $mainRelationship->id = NULL;
          $mainRelationship->case_id = $mainCaseId;
          if ($mainRelationship->contact_id_a == $otherContactId) {
            $updateOtherRel = TRUE;
            $mainRelationship->contact_id_a = $mainContactId;
          }

          //case creator change only when we merge user contact.
          if ($mainRelationship->contact_id_b == $otherContactId) {
            //do not change creator for change client.
            if (!$changeClient) {
              $updateOtherRel = TRUE;
              $mainRelationship->contact_id_b = ($currentUserId) ? $currentUserId : $mainContactId;
            }
          }
          $mainRelationship->end_date = CRM_Utils_Date::isoToMysql($otherRelationship->end_date);
          $mainRelationship->start_date = CRM_Utils_Date::isoToMysql($otherRelationship->start_date);

          //avoid duplicate object.
          if (!$mainRelationship->find(TRUE)) {
            $mainRelationship->save();
          }
          $mainRelationship->free();

          //get the other relationship ids to update end date.
          if ($updateOtherRel) {
            $otherRelationshipIds[$otherRelationship->id] = $otherRelationship->id;
          }
        }
        $otherRelationship->free();

        //update other relationships end dates
        if (!empty($otherRelationshipIds)) {
          $sql = 'UPDATE  civicrm_relationship
                               SET  end_date = CURDATE()
                             WHERE  id IN ( ' . implode(',', $otherRelationshipIds) . ')';
          CRM_Core_DAO::executeQuery($sql);
        }
      }

      //move other case to trash.
      $mergeCase = self::deleteCase($otherCaseId, $moveToTrash);
      if (!$mergeCase) {
        continue;
      }

      $mergeActSubject = $mergeActSubjectDetails = $mergeActType = '';
      if ($changeClient) {
        $mainContactDisplayName = CRM_Contact_BAO_Contact::displayName($mainContactId);
        $otherContactDisplayName = CRM_Contact_BAO_Contact::displayName($otherContactId);

        $mergeActType = array_search('Reassigned Case', $activityTypes);
        $mergeActSubject = ts("Case %1 reassigned client from %2 to %3. New Case ID is %4.",
          array(
            1 => $otherCaseId,
            2 => $otherContactDisplayName,
            3 => $mainContactDisplayName,
            4 => $mainCaseId,
          )
        );
      }
      elseif ($duplicateContacts) {
        $mergeActType = array_search('Merge Case', $activityTypes);
        $mergeActSubject = ts("Case %1 copied from contact id %2 to contact id %3 via merge. New Case ID is %4.",
          array(
            1 => $otherCaseId,
            2 => $otherContactId,
            3 => $mainContactId,
            4 => $mainCaseId,
          )
        );
      }
      else {
        $mergeActType = array_search('Merge Case', $activityTypes);
        $mergeActSubject = ts("Case %1 merged into case %2", array(1 => $otherCaseId, 2 => $mainCaseId));
        if (!empty($copiedActivityIds)) {
          $sql = '
SELECT id, subject, activity_date_time, activity_type_id
FROM civicrm_activity
WHERE id IN (' . implode(',', $copiedActivityIds) . ')';
          $dao = CRM_Core_DAO::executeQuery($sql);
          while ($dao->fetch()) {
            $mergeActSubjectDetails .= "{$dao->activity_date_time} :: {$activityTypes[$dao->activity_type_id]}";
            if ($dao->subject) {
              $mergeActSubjectDetails .= " :: {$dao->subject}";
            }
            $mergeActSubjectDetails .= "<br />";
          }
        }
      }

      //Create merge activity record. Source for merge activity is the logged in user's contact ID ($currentUserId).
      $activityParams = array(
        'subject' => $mergeActSubject,
        'details' => $mergeActSubjectDetails,
        'status_id' => array_search('Completed', $activityStatuses),
        'activity_type_id' => $mergeActType,
        'source_contact_id' => $currentUserId,
        'activity_date_time' => date('YmdHis'),
      );

      $mergeActivity = CRM_Activity_BAO_Activity::create($activityParams);
      $mergeActivityId = $mergeActivity->id;
      if (!$mergeActivityId) {
        continue;
      }
      $mergeActivity->free();

      //connect merge activity to case.
      $mergeCaseAct = array(
        'case_id' => $mainCaseId,
        'activity_id' => $mergeActivityId,
      );

      self::processCaseActivity($mergeCaseAct);
    }

    CRM_Utils_Hook::post_case_merge($mainContactId, $mainCaseId, $otherContactId, $otherCaseId, $changeClient);

    return $mainCaseIds;
  }

  /**
   * Validate contact permission for
   * edit/view on activity record and build links.
   *
   * @param array $tplParams
   *   Params to be sent to template for sending email.
   * @param array $activityParams
   *   Info of the activity.
   */
  public static function buildPermissionLinks(&$tplParams, $activityParams) {
    $activityTypeId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $activityParams['source_record_id'],
      'activity_type_id', 'id'
    );

    if (!empty($tplParams['isCaseActivity'])) {
      $tplParams['editActURL'] = CRM_Utils_System::url('civicrm/case/activity',
        "reset=1&cid={$activityParams['target_id']}&caseid={$activityParams['case_id']}&action=update&id={$activityParams['source_record_id']}", TRUE
      );

      $tplParams['viewActURL'] = CRM_Utils_System::url('civicrm/case/activity/view',
        "reset=1&aid={$activityParams['source_record_id']}&cid={$activityParams['target_id']}&caseID={$activityParams['case_id']}", TRUE
      );

      $tplParams['manageCaseURL'] = CRM_Utils_System::url('civicrm/contact/view/case',
        "reset=1&id={$activityParams['case_id']}&cid={$activityParams['target_id']}&action=view&context=home", TRUE
      );
    }
    else {
      $tplParams['editActURL'] = CRM_Utils_System::url('civicrm/contact/view/activity',
        "atype=$activityTypeId&action=update&reset=1&id={$activityParams['source_record_id']}&cid={$tplParams['contact']['contact_id']}&context=activity", TRUE
      );

      $tplParams['viewActURL'] = CRM_Utils_System::url('civicrm/contact/view/activity',
        "atype=$activityTypeId&action=view&reset=1&id={$activityParams['source_record_id']}&cid={$tplParams['contact']['contact_id']}&context=activity", TRUE
      );
    }
  }

  /**
   * Validate contact permission for
   * given operation on activity record.
   *
   * @param int $activityId
   *   Activity record id.
   * @param string $operation
   *   User operation.
   * @param int $actTypeId
   *   Activity type id.
   * @param int $contactId
   *   Contact id/if not pass consider logged in.
   * @param bool $checkComponent
   *   Do we need to check component enabled.
   *
   * @return bool
   */
  public static function checkPermission($activityId, $operation, $actTypeId = NULL, $contactId = NULL, $checkComponent = TRUE) {
    $allow = FALSE;
    if (!$actTypeId && $activityId) {
      $actTypeId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $activityId, 'activity_type_id');
    }

    if (!$activityId || !$operation || !$actTypeId) {
      return $allow;
    }

    //do check for civicase component enabled.
    if ($checkComponent && !self::enabled()) {
      return $allow;
    }

    //do check for cases.
    $caseActOperations = array(
      'File On Case',
      'Link Cases',
      'Move To Case',
      'Copy To Case',
    );

    if (in_array($operation, $caseActOperations)) {
      static $caseCount;
      if (!isset($caseCount)) {
        $caseCount = civicrm_api3('Case', 'getcount', array(
          'check_permissions' => TRUE,
          'status_id' => array('!=' => 'Closed'),
          'is_deleted' => 0,
          'end_date' => array('IS NULL' => 1),
        ));
      }
      if ($operation == 'File On Case') {
        $allow = !empty($caseCount);
      }
      else {
        $allow = ($caseCount > 1);
      }
    }

    $actionOperations = array('view', 'edit', 'delete');
    if (in_array($operation, $actionOperations)) {

      //do cache when user has non/supper permission.
      static $allowOperations;

      if (!is_array($allowOperations) ||
        !array_key_exists($operation, $allowOperations)
      ) {

        if (!$contactId) {
          $session = CRM_Core_Session::singleton();
          $contactId = $session->get('userID');
        }

        //check for permissions.
        $permissions = array(
          'view' => array(
            'access my cases and activities',
            'access all cases and activities',
          ),
          'edit' => array(
            'access my cases and activities',
            'access all cases and activities',
          ),
          'delete' => array('delete activities'),
        );

        //check for core permission.
        $hasPermissions = array();
        $checkPermissions = CRM_Utils_Array::value($operation, $permissions);
        if (is_array($checkPermissions)) {
          foreach ($checkPermissions as $per) {
            if (CRM_Core_Permission::check($per)) {
              $hasPermissions[$operation][] = $per;
            }
          }
        }

        //has permissions.
        if (!empty($hasPermissions)) {
          //need to check activity object specific.
          if (in_array($operation, array(
            'view',
            'edit',
          ))
          ) {
            //do we have supper permission.
            if (in_array('access all cases and activities', $hasPermissions[$operation])) {
              $allowOperations[$operation] = $allow = TRUE;
            }
            else {
              //user has only access to my cases and activity.
              //here object specific permmions come in picture.

              //edit - contact must be source or assignee
              //view - contact must be source/assignee/target
              $isTarget = $isAssignee = $isSource = FALSE;
              $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
              $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
              $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
              $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

              $target = new CRM_Activity_DAO_ActivityContact();
              $target->record_type_id = $targetID;
              $target->activity_id = $activityId;
              $target->contact_id = $contactId;
              if ($target->find(TRUE)) {
                $isTarget = TRUE;
              }

              $assignee = new CRM_Activity_DAO_ActivityContact();
              $assignee->activity_id = $activityId;
              $assignee->record_type_id = $assigneeID;
              $assignee->contact_id = $contactId;
              if ($assignee->find(TRUE)) {
                $isAssignee = TRUE;
              }

              $source = new CRM_Activity_DAO_ActivityContact();
              $source->activity_id = $activityId;
              $source->record_type_id = $sourceID;
              $source->contact_id = $contactId;
              if ($source->find(TRUE)) {
                $isSource = TRUE;
              }

              if ($operation == 'edit') {
                if ($isAssignee || $isSource) {
                  $allow = TRUE;
                }
              }
              if ($operation == 'view') {
                if ($isTarget || $isAssignee || $isSource) {
                  $allow = TRUE;
                }
              }
            }
          }
          elseif (is_array($hasPermissions[$operation])) {
            $allowOperations[$operation] = $allow = TRUE;
          }
        }
        else {
          //contact do not have permission.
          $allowOperations[$operation] = FALSE;
        }
      }
      else {
        //use cache.
        //here contact might have supper/non permission.
        $allow = $allowOperations[$operation];
      }
    }

    //do further only when operation is granted.
    if ($allow) {
      $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'name');

      //get the activity type name.
      $actTypeName = CRM_Utils_Array::value($actTypeId, $activityTypes);

      //do not allow multiple copy / edit action.
      $singletonNames = array(
        'Open Case',
        'Reassigned Case',
        'Merge Case',
        'Link Cases',
        'Assign Case Role',
        'Email',
        'Inbound Email',
      );

      //do not allow to delete these activities, CRM-4543
      $doNotDeleteNames = array('Open Case', 'Change Case Type', 'Change Case Status', 'Change Case Start Date');

      //allow edit operation.
      $allowEditNames = array('Open Case');

      // do not allow File on Case
      $doNotFileNames = array(
        'Open Case',
        'Change Case Type',
        'Change Case Status',
        'Change Case Start Date',
        'Reassigned Case',
        'Merge Case',
        'Link Cases',
        'Assign Case Role',
      );

      if (in_array($actTypeName, $singletonNames)) {
        $allow = FALSE;
        if ($operation == 'File On Case') {
          $allow = (in_array($actTypeName, $doNotFileNames)) ? FALSE : TRUE;
        }
        if (in_array($operation, $actionOperations)) {
          $allow = TRUE;
          if ($operation == 'edit') {
            $allow = (in_array($actTypeName, $allowEditNames)) ? TRUE : FALSE;
          }
          elseif ($operation == 'delete') {
            $allow = (in_array($actTypeName, $doNotDeleteNames)) ? FALSE : TRUE;
          }
        }
      }
      if ($allow && ($operation == 'delete') &&
        in_array($actTypeName, $doNotDeleteNames)
      ) {
        $allow = FALSE;
      }

      if ($allow && ($operation == 'File On Case') &&
        in_array($actTypeName, $doNotFileNames)
      ) {
        $allow = FALSE;
      }

      //check settings file for masking actions
      //on the basis the activity types
      //hide Edit link if activity type is NOT editable
      //(special case activities).CRM-5871
      if ($allow && in_array($operation, $actionOperations)) {
        static $actionFilter = array();
        if (!array_key_exists($operation, $actionFilter)) {
          $xmlProcessor = new CRM_Case_XMLProcessor_Process();
          $actionFilter[$operation] = $xmlProcessor->get('Settings', 'ActivityTypes', FALSE, $operation);
        }
        if (array_key_exists($operation, $actionFilter[$operation]) &&
          in_array($actTypeId, $actionFilter[$operation][$operation])
        ) {
          $allow = FALSE;
        }
      }
    }

    return $allow;
  }

  /**
   * Since we drop 'access CiviCase', allow access
   * if user has 'access my cases and activities'
   * or 'access all cases and activities'
   */
  public static function accessCiviCase() {
    if (!self::enabled()) {
      return FALSE;
    }

    if (CRM_Core_Permission::check('access my cases and activities') ||
      CRM_Core_Permission::check('access all cases and activities')
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Verify user has permission to access a case.
   *
   * @param int $caseId
   * @param bool $denyClosed
   *   Set TRUE if one wants closed cases to be treated as inaccessible.
   *
   * @return bool
   */
  public static function accessCase($caseId, $denyClosed = TRUE) {
    if (!$caseId || !self::enabled()) {
      return FALSE;
    }

    $params = array('id' => $caseId, 'check_permissions' => TRUE);
    if ($denyClosed && !CRM_Core_Permission::check('access all cases and activities')) {
      $params['status_id'] = array('!=' => 'Closed');
    }
    return (bool) civicrm_api3('Case', 'getcount', $params);
  }

  /**
   * Check whether activity is a case Activity.
   *
   * @param int $activityID
   *   Activity id.
   *
   * @return bool
   */
  public static function isCaseActivity($activityID) {
    $isCaseActivity = FALSE;
    if ($activityID) {
      $params = array(1 => array($activityID, 'Integer'));
      $query = "SELECT id FROM civicrm_case_activity WHERE activity_id = %1";
      if (CRM_Core_DAO::singleValueQuery($query, $params)) {
        $isCaseActivity = TRUE;
      }
    }

    return $isCaseActivity;
  }

  /**
   * Get all the case type ids currently in use.
   *
   * @return array
   */
  public static function getUsedCaseType() {
    static $caseTypeIds;

    if (!is_array($caseTypeIds)) {
      $query = "SELECT DISTINCT( civicrm_case.case_type_id ) FROM civicrm_case";

      $dao = CRM_Core_DAO::executeQuery($query);
      $caseTypeIds = array();
      while ($dao->fetch()) {
        $typeId = explode(CRM_Core_DAO::VALUE_SEPARATOR,
          $dao->case_type_id
        );
        $caseTypeIds[] = $typeId[1];
      }
    }

    return $caseTypeIds;
  }

  /**
   * Get all the case status ids currently in use.
   *
   * @return array
   */
  public static function getUsedCaseStatuses() {
    static $caseStatusIds;

    if (!is_array($caseStatusIds)) {
      $query = "SELECT DISTINCT( civicrm_case.status_id ) FROM civicrm_case";

      $dao = CRM_Core_DAO::executeQuery($query);
      $caseStatusIds = array();
      while ($dao->fetch()) {
        $caseStatusIds[] = $dao->status_id;
      }
    }

    return $caseStatusIds;
  }

  /**
   * Get all the encounter medium ids currently in use.
   *
   * @return array
   */
  public static function getUsedEncounterMediums() {
    static $mediumIds;

    if (!is_array($mediumIds)) {
      $query = "SELECT DISTINCT( civicrm_activity.medium_id )  FROM civicrm_activity";

      $dao = CRM_Core_DAO::executeQuery($query);
      $mediumIds = array();
      while ($dao->fetch()) {
        $mediumIds[] = $dao->medium_id;
      }
    }

    return $mediumIds;
  }

  /**
   * Check case configuration.
   *
   * @param int $contactId
   *
   * @return array
   */
  public static function isCaseConfigured($contactId = NULL) {
    $configured = array_fill_keys(array('configured', 'allowToAddNewCase', 'redirectToCaseAdmin'), FALSE);

    //lets check for case configured.
    $allCasesCount = CRM_Case_BAO_Case::caseCount(NULL, FALSE);
    $configured['configured'] = ($allCasesCount) ? TRUE : FALSE;
    if (!$configured['configured']) {
      //do check for case type and case status.
      $caseTypes = CRM_Case_PseudoConstant::caseType('title', FALSE);
      if (!empty($caseTypes)) {
        $configured['configured'] = TRUE;
        if (!$configured['configured']) {
          $caseStatuses = CRM_Case_PseudoConstant::caseStatus('label', FALSE);
          if (!empty($caseStatuses)) {
            $configured['configured'] = TRUE;
          }
        }
      }
    }
    if ($configured['configured']) {
      //do check for active case type and case status.
      $caseTypes = CRM_Case_PseudoConstant::caseType();
      if (!empty($caseTypes)) {
        $caseStatuses = CRM_Case_PseudoConstant::caseStatus();
        if (!empty($caseStatuses)) {
          $configured['allowToAddNewCase'] = TRUE;
        }
      }

      //do we need to redirect user to case admin.
      if (!$configured['allowToAddNewCase'] && $contactId) {
        //check for current contact case count.
        $currentContatCasesCount = CRM_Case_BAO_Case::caseCount($contactId);
        //redirect user to case admin page.
        if (!$currentContatCasesCount) {
          $configured['redirectToCaseAdmin'] = TRUE;
        }
      }
    }

    return $configured;
  }

  /**
   * Used during case component enablement and during ugprade.
   *
   * @return bool
   */
  public static function createCaseViews() {
    $errorScope = CRM_Core_TemporaryErrorScope::ignoreException();
    $dao = new CRM_Core_DAO();

    $sql = self::createCaseViewsQuery('upcoming');
    $dao->query($sql);
    if (PEAR::getStaticProperty('DB_DataObject', 'lastError')) {
      return FALSE;
    }

    // Above error doesn't get caught?
    $doublecheck = $dao->singleValueQuery("SELECT count(id) FROM civicrm_view_case_activity_upcoming");
    if (is_null($doublecheck)) {
      return FALSE;
    }

    $sql = self::createCaseViewsQuery('recent');
    $dao->query($sql);
    if (PEAR::getStaticProperty('DB_DataObject', 'lastError')) {
      return FALSE;
    }

    // Above error doesn't get caught?
    $doublecheck = $dao->singleValueQuery("SELECT count(id) FROM civicrm_view_case_activity_recent");
    if (is_null($doublecheck)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Helper function, also used by the upgrade in case of error
   *
   * @param string $section
   *
   * @return string
   */
  public static function createCaseViewsQuery($section = 'upcoming') {
    $sql = "";
    $scheduled_id = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');
    switch ($section) {
      case 'upcoming':
        $sql = "CREATE OR REPLACE VIEW `civicrm_view_case_activity_upcoming`
 AS SELECT ca.case_id, a.id, a.activity_date_time, a.status_id, a.activity_type_id
 FROM civicrm_case_activity ca
 INNER JOIN civicrm_activity a ON ca.activity_id=a.id
 WHERE a.activity_date_time <= DATE_ADD( NOW(), INTERVAL 14 DAY )
 AND a.is_current_revision = 1 AND a.is_deleted=0 AND a.status_id = $scheduled_id";
        break;

      case 'recent':
        $sql = "CREATE OR REPLACE VIEW `civicrm_view_case_activity_recent`
 AS SELECT ca.case_id, a.id, a.activity_date_time, a.status_id, a.activity_type_id
 FROM civicrm_case_activity ca
 INNER JOIN civicrm_activity a ON ca.activity_id=a.id
 WHERE a.activity_date_time <= NOW()
 AND a.activity_date_time >= DATE_SUB( NOW(), INTERVAL 14 DAY )
 AND a.is_current_revision = 1 AND a.is_deleted=0 AND a.status_id <> $scheduled_id";
        break;
    }
    return $sql;
  }

  /**
   * Add/copy relationships, when new client is added for a case
   *
   * @param int $caseId
   *   Case id.
   * @param int $contactId
   *   Contact id / new client id.
   */
  public static function addCaseRelationships($caseId, $contactId) {
    // get the case role / relationships for the case
    $caseRelationships = new CRM_Contact_DAO_Relationship();
    $caseRelationships->case_id = $caseId;
    $caseRelationships->find();
    $relationshipTypes = array();

    // make sure we don't add duplicate relationships of same relationship type.
    while ($caseRelationships->fetch() && !in_array($caseRelationships->relationship_type_id, $relationshipTypes)) {
      $values = array();
      CRM_Core_DAO::storeValues($caseRelationships, $values);

      // add relationship for new client.
      $newRelationship = new CRM_Contact_DAO_Relationship();
      $newRelationship->copyValues($values);
      $newRelationship->id = NULL;
      $newRelationship->case_id = $caseId;
      $newRelationship->contact_id_a = $contactId;
      $newRelationship->end_date = CRM_Utils_Date::isoToMysql($caseRelationships->end_date);
      $newRelationship->start_date = CRM_Utils_Date::isoToMysql($caseRelationships->start_date);

      // another check to avoid duplicate relationship, in cases where client is removed and re-added again.
      if (!$newRelationship->find(TRUE)) {
        $newRelationship->save();
      }
      $newRelationship->free();

      // store relationship type of newly created relationship
      $relationshipTypes[] = $caseRelationships->relationship_type_id;
    }
  }

  /**
   * Get the list of clients for a case.
   *
   * @param int $caseId
   *
   * @return array
   *   associated array with client ids
   */
  public static function getCaseClients($caseId) {
    $clients = array();
    $caseContact = new CRM_Case_DAO_CaseContact();
    $caseContact->case_id = $caseId;
    $caseContact->find();

    while ($caseContact->fetch()) {
      $clients[] = $caseContact->contact_id;
    }

    return $clients;
  }

  /**
   * @param int $caseId
   * @param string $direction
   * @param int $cid
   * @param int $relTypeId
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function endCaseRole($caseId, $direction, $cid, $relTypeId) {
    // Validate inputs
    if ($direction !== 'a' && $direction !== 'b') {
      throw new CRM_Core_Exception('Invalid relationship direction');
    }

    // This case might have multiple clients, so we lookup by relationship instead of by id to get them all
    $sql = "SELECT id FROM civicrm_relationship WHERE case_id = %1 AND contact_id_{$direction} = %2 AND relationship_type_id = %3";
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      1 => array($caseId, 'Positive'),
      2 => array($cid, 'Positive'),
      3 => array($relTypeId, 'Positive'),
    ));
    while ($dao->fetch()) {
      civicrm_api3('relationship', 'create', array(
        'id' => $dao->id,
        'is_active' => 0,
        'end_date' => 'now',
      ));
    }
  }

  /**
   * Get options for a given case field.
   * @see CRM_Core_DAO::buildOptions
   *
   * @param string $fieldName
   * @param string $context
   * @see CRM_Core_DAO::buildOptionsContext
   * @param array $props
   *   Whatever is known about this dao object.
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    $className = __CLASS__;
    $params = array();
    switch ($fieldName) {
      // This field is not part of this object but the api supports it
      case 'medium_id':
        $className = 'CRM_Activity_BAO_Activity';
        break;
    }
    return CRM_Core_PseudoConstant::get($className, $fieldName, $params, $context);
  }

  /**
   * @inheritDoc
   */
  public function addSelectWhereClause() {
    // We always return an array with these keys, even if they are empty,
    // because this tells the query builder that we have considered these fields for acls
    $clauses = array(
      'id' => array(),
      // Only case admins can view deleted cases
      'is_deleted' => CRM_Core_Permission::check('administer CiviCase') ? array() : array("= 0"),
    );
    // Ensure the user has permission to view the case client
    $contactClause = CRM_Utils_SQL::mergeSubquery('Contact');
    if ($contactClause) {
      $contactClause = implode(' AND contact_id ', $contactClause);
      $clauses['id'][] = "IN (SELECT case_id FROM civicrm_case_contact WHERE contact_id $contactClause)";
    }
    // The api gatekeeper ensures the user has at least "access my cases and activities"
    // so if they do not have permission to see all cases we'll assume they can only access their own
    if (!CRM_Core_Permission::check('access all cases and activities')) {
      $user = (int) CRM_Core_Session::getLoggedInContactID();
      $clauses['id'][] = "IN (
        SELECT r.case_id FROM civicrm_relationship r, civicrm_case_contact cc WHERE r.is_active = 1 AND cc.case_id = r.case_id AND (
          (r.contact_id_a = cc.contact_id AND r.contact_id_b = $user) OR (r.contact_id_b = cc.contact_id AND r.contact_id_a = $user)
        )
      )";
    }
    CRM_Utils_Hook::selectWhereClause($this, $clauses);
    return $clauses;
  }

}
