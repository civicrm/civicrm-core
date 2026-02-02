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

use Civi\API\Exception\UnauthorizedException;
use Civi\Api4\Membership;
use Civi\Api4\MembershipType;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Member_BAO_Membership extends CRM_Member_DAO_Membership {

  /**
   * Static field for all the membership information that we can potentially import.
   *
   * @var array
   */
  public static $_importableFields = NULL;

  public static $_renewalActType = NULL;

  public static $_signupActType = NULL;

  /**
   * Takes an associative array and creates a membership object.
   *
   * the function extracts all the params it needs to initialize the created
   * membership object. The params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Member_BAO_Membership
   * @throws \CRM_Core_Exception
   */
  public static function add(&$params) {
    $oldStatus = $oldType = NULL;
    if ($params['id']) {
      CRM_Utils_Hook::pre('edit', 'Membership', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Membership', NULL, $params);
    }
    $id = $params['id'];
    // we do this after the hooks are called in case it has been altered
    if ($id) {
      $membershipObj = new CRM_Member_DAO_Membership();
      $membershipObj->id = $id;
      $membershipObj->find();
      while ($membershipObj->fetch()) {
        $oldStatus = $membershipObj->status_id;
        $oldType = $membershipObj->membership_type_id;
      }
    }

    if (array_key_exists('is_override', $params) && !$params['is_override']) {
      $params['is_override'] = 'null';
    }

    $membership = new CRM_Member_BAO_Membership();
    $membership->copyValues($params);
    $membership->id = $id;

    $membership->save();

    if (empty($membership->contact_id) || empty($membership->status_id)) {
      // this means we are in renewal mode and are just updating the membership
      // record or this is an API update call and all fields are not present in the update record
      // however the hooks don't care and want all data CRM-7784
      $tempMembership = new CRM_Member_DAO_Membership();
      $tempMembership->id = $membership->id;
      $tempMembership->find(TRUE);
      $membership = $tempMembership;
    }

    //get the log start date.
    //it is set during renewal of membership.
    $logStartDate = $params['log_start_date'] ?? NULL;
    $logStartDate = ($logStartDate) ? CRM_Utils_Date::isoToMysql($logStartDate) : CRM_Utils_Date::isoToMysql($membership->start_date);
    $membershipTypeID = (int) self::getStatusANDTypeValues($membership->id)[$membership->id]['membership_type_id'];
    $membershipLog = self::createMembershipLog($membership, $logStartDate, $membershipTypeID, $params['modified_id'] ?? NULL);

    // reset the group contact cache since smart groups might be affected due to this
    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    $allStatus = CRM_Member_BAO_Membership::buildOptions('status_id', 'get');
    $activityParams = [
      'status_id' => $params['membership_activity_status'] ?? 'Completed',
    ];
    if (in_array($allStatus[$membership->status_id], ['Pending', 'Grace'])) {
      $activityParams['status_id'] = 'Scheduled';
    }
    $activityParams['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', $activityParams['status_id']);

    $targetContactID = $membership->contact_id;
    if (!empty($params['is_for_organization'])) {
      // @todo - deprecate is_for_organization, require modified_id
      $targetContactID = $params['modified_id'] ?? NULL;
    }

    // add custom field values
    if (!empty($params['custom']) && is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_membership', $membership->id);
    }

    if ($id) {
      if ($membership->status_id != $oldStatus) {
        self::createChangeMembershipStatusActivity($membership, $allStatus[$oldStatus], $allStatus[$membership->status_id], $membershipLog['modified_id']);
      }
      if (isset($membership->membership_type_id) && $membership->membership_type_id != $oldType) {
        $membershipTypes = CRM_Member_BAO_Membership::buildOptions('membership_type_id', 'get');
        CRM_Activity_BAO_Activity::addActivity($membership,
          'Change Membership Type',
          NULL,
          [
            'subject' => "Type changed from {$membershipTypes[$oldType]} to {$membershipTypes[$membership->membership_type_id]}",
            'source_contact_id' => $membershipLog['modified_id'],
            'priority_id' => 'Normal',
          ]
        );
      }

      foreach (['Membership Signup', 'Membership Renewal'] as $activityType) {
        $activityParams['id'] = \Civi\Api4\Activity::get(FALSE)
          ->addSelect('id')
          ->addWhere('source_record_id', '=', $membership->id)
          ->addWhere('activity_type_id:name', '=', $activityType)
          ->addWhere('status_id:name', '=', 'Scheduled')
          ->execute()
          ->first()['id'] ?? NULL;
        // 1. Update Schedule Membership Signup/Renwal activity to completed on successful payment of pending membership
        // 2. OR Create renewal activity scheduled if its membership renewal will be paid later
        if (!empty($params['membership_activity_status']) && (!empty($activityParams['id']) || $activityType == 'Membership Renewal')) {
          CRM_Activity_BAO_Activity::addActivity($membership, $activityType, $targetContactID, $activityParams);
          break;
        }
      }

      CRM_Utils_Hook::post('edit', 'Membership', $membership->id, $membership, $params);
    }
    else {
      CRM_Activity_BAO_Activity::addActivity($membership, 'Membership Signup', $targetContactID, $activityParams);
      CRM_Utils_Hook::post('create', 'Membership', $membership->id, $membership, $params);
    }

    return $membership;
  }

  /**
   * Fetch the object and store the values in the values array.
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   * @param bool $active
   *   Return only memberships with an 'is_current_member' status.
   *
   * @return CRM_Member_BAO_Membership[]|null
   */
  public static function getValues($params, &$values, $active = FALSE) {
    if (empty($params)) {
      return NULL;
    }
    $membership = new CRM_Member_BAO_Membership();

    $membership->copyValues($params);
    $membership->find();
    $memberships = [];
    while ($membership->fetch()) {
      if ($active &&
        (!CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
          $membership->status_id,
          'is_current_member'
        ))
      ) {
        continue;
      }

      CRM_Core_DAO::storeValues($membership, $values[$membership->id]);
      $memberships[$membership->id] = $membership;
    }

    return $memberships;
  }

  /**
   * Takes an associative array and creates a membership object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $ids
   *   Deprecated parameter The array that holds all the db ids.
   *
   * @return CRM_Member_BAO_Membership|CRM_Core_Error
   * @throws \CRM_Core_Exception
   *
   * @throws CRM_Core_Exception
   */
  public static function create(&$params, $ids = []) {
    $isLifeTime = FALSE;
    if (!empty($params['membership_type_id'])) {
      $memTypeDetails = CRM_Member_BAO_MembershipType::getMembershipType($params['membership_type_id']);
      $isLifeTime = $memTypeDetails['duration_unit'] === 'lifetime' ? TRUE : FALSE;
    }
    // always calculate status if is_override/skipStatusCal is not true.
    // giving respect to is_override during import.  CRM-4012

    // To skip status calculation we should use 'skipStatusCal'.
    // eg pay later membership, membership update cron CRM-3984

    if (empty($params['skipStatusCal'])) {
      $fieldsToLoad = [];
      foreach (['start_date', 'end_date', 'join_date'] as $dateField) {
        if (!empty($params[$dateField]) && $params[$dateField] !== 'null' && !str_starts_with($params[$dateField], date('Ymd', CRM_Utils_Time::strtotime(trim($params[$dateField]))))) {
          $params[$dateField] = date('Ymd', CRM_Utils_Time::strtotime(trim($params[$dateField])));
          // @todo enable this once core is using the api.
          // CRM_Core_Error::deprecatedWarning('Relying on the BAO to clean up dates is deprecated. Call membership create via the api');
        }
        if (!empty($params['id']) && empty($params[$dateField]) && !($isLifeTime && $dateField == 'end_date')) {
          $fieldsToLoad[] = $dateField;
        }
      }
      if (!empty($fieldsToLoad)) {
        $membership = civicrm_api3('Membership', 'getsingle', ['id' => $params['id'], 'return' => $fieldsToLoad]);
        foreach ($fieldsToLoad as $fieldToLoad) {
          $params[$fieldToLoad] = $membership[$fieldToLoad];
        }
      }
      if (empty($params['id'])
        && (empty($params['start_date']) || empty($params['join_date']) || (empty($params['end_date']) && !$isLifeTime))) {
        // This is a new membership, calculate the membership dates.
        $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType(
          $params['membership_type_id'],
          $params['join_date'] ?? NULL,
          $params['start_date'] ?? NULL,
          $params['end_date'] ?? NULL,
          $params['num_terms'] ?? 1
        );
      }
      else {
        $calcDates = [];
      }
      $params['start_date'] = empty($params['start_date']) ? ($calcDates['start_date'] ?? 'null') : $params['start_date'];
      $params['end_date'] = empty($params['end_date']) ? ($calcDates['end_date'] ?? 'null') : $params['end_date'];
      $params['join_date'] = empty($params['join_date']) ? ($calcDates['join_date'] ?? 'null') : $params['join_date'];

      //fix for CRM-3570, during import exclude the statuses those having is_admin = 1
      $excludeIsAdmin = $params['exclude_is_admin'] ?? FALSE;

      //CRM-3724 always skip is_admin if is_override != true.
      if (!$excludeIsAdmin && empty($params['is_override'])) {
        $excludeIsAdmin = TRUE;
      }

      if (empty($params['status_id']) && empty($params['is_override'])) {
        $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($params['start_date'], $params['end_date'], $params['join_date'],
          'now', $excludeIsAdmin, $params['membership_type_id'] ?? NULL, $params
        );
        if (empty($calcStatus)) {
          throw new CRM_Core_Exception(ts("The membership cannot be saved because the status cannot be calculated for start_date: %1, end_date: %2, join_date: %3. Current time is: %4.", [1 => $params['start_date'], 2 => $params['end_date'], 3 => $params['join_date'], 4 => CRM_Utils_Time::date('Y-m-d H:i:s')]));
        }
        $params['status_id'] = $calcStatus['id'];
      }
    }

    // data cleanup only: all verifications on number of related memberships are done upstream in:
    // CRM_Member_BAO_Membership::createRelatedMemberships()
    // CRM_Contact_BAO_Relationship::relatedMemberships()
    if (!empty($params['owner_membership_id'])) {
      unset($params['max_related']);
    }
    else {
      // if membership allows related, default max_related to value in membership_type
      if (!array_key_exists('max_related', $params) && !empty($params['membership_type_id'])) {
        $membershipType = CRM_Member_BAO_MembershipType::getMembershipType($params['membership_type_id']);
        if (isset($membershipType['relationship_type_id'])) {
          $params['max_related'] = $membershipType['max_related'] ?? NULL;
        }
      }
    }

    $transaction = new CRM_Core_Transaction();

    $params['id'] ??= $ids['membership'] ?? NULL;
    $membership = self::add($params);

    $params['membership_id'] = $membership->id;
    // For api v4 we skip all of this stuff. There is an expectation that v4 users either use
    // the order api, or handle any financial / related processing themselves.
    // Note that the processing below is fairly intertwined with core usage and in some places
    // problematic or to be removed.
    // Note the choice of 'version' as a parameter is to make it
    // unavailable through apiv3.
    // once we are rid of direct calls to the BAO::create from core
    // we will deprecate this stuff into the v3 api.
    if (($params['version'] ?? 0) !== 4) {
      if (isset($ids['membership'])) {
        $latestContributionID = CRM_Member_BAO_MembershipPayment::getLatestContributionIDFromLineitemAndFallbackToMembershipPayment($membership->id);
        if (empty($params['contribution_id']) && !empty($latestContributionID)) {
          $params['contribution_id'] = $latestContributionID;
        }
      }

      // This code ensures a line item is created but it is recommended you pass in 'skipLineItem' or 'line_item'
      if (empty($params['line_item']) && !empty($params['membership_type_id']) && empty($params['skipLineItem'])) {
        CRM_Price_BAO_LineItem::getLineItemArray($params, NULL, 'membership', $params['membership_type_id']);
      }
      $params['skipLineItem'] = TRUE;

      // Record contribution for this membership and create a MembershipPayment
      // @todo deprecate this.
      if (!empty($params['contribution_status_id'])) {
        CRM_Core_Error::deprecatedWarning('creating a contribution via membership BAO is deprecated');
        $memInfo = array_merge($params, ['membership_id' => $membership->id]);
        $params['contribution'] = self::recordMembershipContribution($memInfo);
      }

      // If the membership has no associated contribution then we ensure
      // the line items are 'correct' here. This is a lazy legacy
      // hack whereby they are deleted and recreated
      if (empty($latestContributionID)) {
        if (!empty($params['lineItems'])) {
          CRM_Core_Error::deprecatedWarning('do not pass in lineItems');
          $params['line_item'] = $params['lineItems'];
        }
        // do cleanup line items if membership edit the Membership type.
        if (!empty($ids['membership'])) {
          CRM_Price_BAO_LineItem::deleteLineItems($ids['membership'], 'civicrm_membership');
        }
        // @todo - we should ONLY do the below if a contribution is created. Let's
        // get some deprecation notices in here & see where it's hit & work to eliminate.
        // This could happen if there is no contribution or we are in one of many
        // weird and wonderful flows. This is scary code. Keep adding tests.
        if (!empty($params['line_item']) && empty($params['contribution_id'])) {

          foreach ($params['line_item'] as $priceSetId => $lineItems) {
            foreach ($lineItems as $lineIndex => $lineItem) {
              $lineMembershipType = $lineItem['membership_type_id'] ?? NULL;
              if (!empty($params['contribution'])) {
                $params['line_item'][$priceSetId][$lineIndex]['contribution_id'] = $params['contribution']->id;
              }
              if ($lineMembershipType && $lineMembershipType == ($params['membership_type_id'] ?? NULL)) {
                $params['line_item'][$priceSetId][$lineIndex]['entity_id'] = $membership->id;
                $params['line_item'][$priceSetId][$lineIndex]['entity_table'] = 'civicrm_membership';
              }
              elseif (!$lineMembershipType && !empty($params['contribution'])) {
                $params['line_item'][$priceSetId][$lineIndex]['entity_id'] = $params['contribution']->id;
                $params['line_item'][$priceSetId][$lineIndex]['entity_table'] = 'civicrm_contribution';
              }
            }
          }
          CRM_Price_BAO_LineItem::processPriceSet(
            $membership->id,
            $params['line_item'],
            $params['contribution'] ?? NULL
          );
        }
      }
    }

    $transaction->commit();

    self::createRelatedMemberships($params, $membership);

    if (empty($params['skipRecentView'])) {
      self::addToRecentItems($membership);
    }

    return $membership;
  }

  /**
   * @param \CRM_Member_DAO_Membership $membership
   */
  private static function addToRecentItems($membership) {
    $url = CRM_Utils_System::url('civicrm/contact/view/membership',
      "action=view&reset=1&id={$membership->id}&cid={$membership->contact_id}&context=home"
    );
    if (empty($membership->membership_type_id)) {
      // ie in an update situation.
      $membership->find(TRUE);
    }
    $title = CRM_Contact_BAO_Contact::displayName($membership->contact_id) . ' - ' . ts('Membership Type:')
      . ' ' . CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'membership_type_id', $membership->membership_type_id);

    $recentOther = [];
    if (CRM_Core_Permission::checkActionPermission('CiviMember', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/membership',
        "action=update&reset=1&id={$membership->id}&cid={$membership->contact_id}&context=home"
      );
    }
    if (CRM_Core_Permission::checkActionPermission('CiviMember', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/membership',
        "action=delete&reset=1&id={$membership->id}&cid={$membership->contact_id}&context=home"
      );
    }

    // add the recently created Membership
    CRM_Utils_Recent::add($title,
      $url,
      $membership->id,
      'Membership',
      $membership->contact_id,
      NULL,
      $recentOther
    );
  }

  /**
   * Check the membership extended through relationship.
   *
   * @param int $membershipTypeID
   *   Membership type id.
   * @param int $contactId
   *   Contact id.
   *
   * @param int $action
   *
   * @return array
   *   array of contact_id of all related contacts.
   *
   * @throws \CRM_Core_Exception
   */
  public static function checkMembershipRelationship($membershipTypeID, $contactId, $action = CRM_Core_Action::ADD) {
    $contacts = [];

    $membershipType = CRM_Member_BAO_MembershipType::getMembershipType($membershipTypeID);

    $relationships = [];
    if (isset($membershipType['relationship_type_id'])) {
      $relationships = CRM_Contact_BAO_Relationship::getRelationship($contactId,
        CRM_Contact_BAO_Relationship::CURRENT
      );
      if ($action & CRM_Core_Action::UPDATE) {
        $pastRelationships = CRM_Contact_BAO_Relationship::getRelationship($contactId,
          CRM_Contact_BAO_Relationship::PAST
        );
        $relationships = array_merge($relationships, $pastRelationships);
      }
    }

    if (!empty($relationships)) {
      // check for each contact relationships
      foreach ($relationships as $values) {
        //get details of the relationship type
        $relType = ['id' => $values['civicrm_relationship_type_id']];
        $relValues = [];
        CRM_Contact_BAO_RelationshipType::retrieve($relType, $relValues);
        // Check if contact's relationship type exists in membership type
        $relTypeDirs = [];
        $bidirectional = FALSE;
        foreach ($membershipType['relationship_type_id'] as $key => $value) {
          $relTypeDirs[] = $value . '_' . $membershipType['relationship_direction'][$key];
          if (in_array($value, $relType) &&
            $relValues['name_a_b'] == $relValues['name_b_a']
          ) {
            $bidirectional = TRUE;
            break;
          }
        }
        $relTypeDir = $values['civicrm_relationship_type_id'] . '_' . $values['rtype'];
        if ($bidirectional || in_array($relTypeDir, $relTypeDirs)) {
          // $values['status'] is going to have value for
          // current or past relationships.
          $contacts[$values['cid']] = $values['status'];
        }
      }
    }

    // Sort by contact_id ascending
    ksort($contacts);
    return $contacts;
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the name / value pairs.
   *                        in a hierarchical manner
   *
   * @return CRM_Member_BAO_Membership
   */
  public static function retrieve(&$params, &$defaults) {
    $membership = new CRM_Member_DAO_Membership();

    $membership->copyValues($params);

    if ($membership->find(TRUE)) {
      CRM_Core_DAO::storeValues($membership, $defaults);

      //get the membership status and type values.
      $statusANDType = self::getStatusANDTypeValues($membership->id);
      foreach (['status', 'membership_type'] as $fld) {
        $defaults[$fld] = $statusANDType[$membership->id][$fld] ?? NULL;
      }
      if (!empty($statusANDType[$membership->id]['is_current_member'])) {
        $defaults['active'] = TRUE;
      }

      return $membership;
    }

    return NULL;
  }

  /**
   * Get membership status and membership type values.
   *
   * @param int $membershipId
   *   Membership id of values to return.
   *
   * @return array
   *   Array of key value pairs
   */
  public static function getStatusANDTypeValues($membershipId) {
    $values = [];
    if (!$membershipId) {
      return $values;
    }
    $sql = '
    SELECT  membership.id as id,
            status.id as status_id,
            status.label as status,
            status.is_current_member as is_current_member,
            type.id as membership_type_id,
            type.name as membership_type,
            type.relationship_type_id as relationship_type_id
      FROM  civicrm_membership membership
INNER JOIN  civicrm_membership_status status ON ( status.id = membership.status_id )
INNER JOIN  civicrm_membership_type type ON ( type.id = membership.membership_type_id )
     WHERE  membership.id = %1';
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$membershipId, 'Positive']]);
    $properties = [
      'status',
      'status_id',
      'membership_type',
      'membership_type_id',
      'is_current_member',
      'relationship_type_id',
    ];
    while ($dao->fetch()) {
      foreach ($properties as $property) {
        $values[$dao->id][$property] = $dao->$property;
      }
    }

    return $values;
  }

  /**
   * Delete membership.
   *
   * Wrapper for most delete calls. Use this unless you JUST want to delete related memberships w/o deleting the parent.
   *
   * @param int $membershipId
   *   Membership id that needs to be deleted.
   * @param bool $preserveContrib
   *
   * @return int
   *   Id of deleted Membership on success, false otherwise.
   */
  public static function del($membershipId, $preserveContrib = FALSE) {
    //delete related first and then delete parent.
    self::deleteRelatedMemberships($membershipId);
    return self::deleteMembership($membershipId, $preserveContrib);
  }

  /**
   * Delete membership.
   *
   * @param int $membershipId
   *   Membership id that needs to be deleted.
   * @param bool $preserveContrib
   *
   * @return int
   *   Id of deleted Membership on success, false otherwise.
   */
  public static function deleteMembership($membershipId, $preserveContrib = FALSE) {
    // CRM-12147, retrieve membership data before we delete it for hooks
    $params = ['id' => $membershipId];
    $memValues = [];
    $memberships = self::getValues($params, $memValues);

    $membership = $memberships[$membershipId];

    CRM_Utils_Hook::pre('delete', 'Membership', $membershipId, $memValues);

    $transaction = new CRM_Core_Transaction();

    //delete activity record
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');

    $params = [];
    $deleteActivity = FALSE;
    $membershipActivities = [
      'Membership Signup',
      'Membership Renewal',
      'Change Membership Status',
      'Change Membership Type',
      'Membership Renewal Reminder',
    ];
    foreach ($membershipActivities as $membershipActivity) {
      $activityId = array_search($membershipActivity, $activityTypes);
      if ($activityId) {
        $params['activity_type_id'][] = $activityId;
        $deleteActivity = TRUE;
      }
    }
    if ($deleteActivity) {
      $params['source_record_id'] = $membershipId;
      CRM_Activity_BAO_Activity::deleteActivity($params);
    }
    CRM_Member_BAO_MembershipPayment::deleteMembershipPayment($membershipId, $preserveContrib);
    CRM_Price_BAO_LineItem::deleteLineItems($membershipId, 'civicrm_membership');

    $results = $membership->delete();
    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Membership', $membership->id, $membership);

    return $results;
  }

  /**
   * Delete related memberships.
   *
   * @param int $ownerMembershipId
   * @param int $contactId
   *
   * @return void
   */
  public static function deleteRelatedMemberships($ownerMembershipId, $contactId = NULL) {
    if (!$ownerMembershipId && !$contactId) {
      return;
    }

    $membership = new CRM_Member_DAO_Membership();
    $membership->owner_membership_id = $ownerMembershipId;

    if ($contactId) {
      $membership->contact_id = $contactId;
    }

    $membership->find();
    while ($membership->fetch()) {
      //delete related first and then delete parent.
      self::deleteRelatedMemberships($membership->id);
      self::deleteMembership($membership->id);
    }
  }

  /**
   * Obtain active/inactive memberships from the list of memberships passed to it.
   *
   * @param array $memberships
   *   Membership records.
   * @param string $status
   *   Active or inactive.
   *
   * @return array|null
   *   array of memberships based on status
   */
  public static function activeMembers($memberships, $status = 'active') {
    $actives = [];
    if ($status == 'active') {
      foreach ($memberships as $f => $v) {
        if (!empty($v['active'])) {
          $actives[$f] = $v;
        }
      }
      return $actives;
    }
    elseif ($status == 'inactive') {
      foreach ($memberships as $f => $v) {
        if (empty($v['active'])) {
          $actives[$f] = $v;
        }
      }
      return $actives;
    }
    return NULL;
  }

  /**
   * Return Membership Block info in Contribution Pages.
   *
   * @param int $pageID
   *   Contribution page id.
   *
   * @return array|null
   */
  public static function getMembershipBlock($pageID) {
    $membershipBlock = [];
    $dao = new CRM_Member_DAO_MembershipBlock();
    $dao->entity_table = 'civicrm_contribution_page';

    $dao->entity_id = $pageID;
    $dao->is_active = 1;
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $membershipBlock);
      if (!empty($membershipBlock['membership_types'])) {
        $membershipTypes = CRM_Utils_String::unserialize($membershipBlock['membership_types']);
        if (!is_array($membershipTypes)) {
          return $membershipBlock;
        }
        $memTypes = [];
        foreach ($membershipTypes as $key => $value) {
          $membershipBlock['auto_renew'][$key] = $value;
          $memTypes[$key] = $key;
        }
        $membershipBlock['membership_types'] = implode(',', $memTypes);
      }
    }
    else {
      return NULL;
    }

    return $membershipBlock;
  }

  /**
   * Return a current membership of given contact.
   *
   * NB: if more than one membership meets criteria, a randomly selected one is returned.
   *
   * @param int $contactID
   *   Contact id.
   * @param int $memType
   *   Membership type, null to retrieve all types.
   * @param int $isTest
   * @param int $membershipId
   *   If provided, then determine if it is current.
   * @param bool $onlySameParentOrg
   *   True if only Memberships with same parent org as the $memType wanted, false otherwise.
   *
   * @return array|bool
   * @throws \CRM_Core_Exception
   */
  public static function getContactMembership($contactID, $memType, $isTest, $membershipId = NULL, $onlySameParentOrg = FALSE) {
    //check for owner membership id, if it exists update that membership instead: CRM-15992
    if ($membershipId) {
      CRM_Core_Error::deprecatedWarning('passing in membership ID is deprecated');
      $ownerMemberId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
        $membershipId,
        'owner_membership_id', 'id'
      );
      if ($ownerMemberId) {
        $membershipId = $ownerMemberId;
        $contactID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
          $membershipId,
          'contact_id', 'id'
        );
      }
    }

    $dao = new CRM_Member_DAO_Membership();
    if ($membershipId) {
      $dao->id = $membershipId;
    }
    $dao->contact_id = $contactID;
    $dao->membership_type_id = $memType;

    //fetch proper membership record.
    if ($isTest) {
      $dao->is_test = $isTest;
    }
    else {
      $dao->whereAdd('is_test = 0');
    }

    //avoid pending membership as current membership: CRM-3027
    $statusIds = [array_search('Pending', CRM_Member_PseudoConstant::membershipStatus())];
    if (!$membershipId) {
      // CRM-15475
      $statusIds[] = array_search(
        'Cancelled',
        CRM_Member_PseudoConstant::membershipStatus(
          NULL,
          " name = 'Cancelled' ",
          'name',
          FALSE,
          TRUE
        )
      );
    }
    $dao->whereAdd('status_id NOT IN ( ' . implode(',', $statusIds) . ')');

    // order by start date to find most recent membership first, CRM-4545
    $dao->orderBy('start_date DESC');

    // CRM-8141
    if ($onlySameParentOrg && $memType) {
      // require the same parent org as the $memType
      $membershipType = MembershipType::get(FALSE)
        ->addSelect('member_of_contact_id')
        ->addWhere('id', '=', $memType)
        ->execute()
        ->first();
      if (!empty($membershipType)) {
        $membershipTypesSameParentOrg = MembershipType::get(FALSE)
          ->addSelect('id')
          ->addWhere('member_of_contact_id', '=', $membershipType['member_of_contact_id'])
          ->execute()
          ->column('id', 'id');
        $memberTypesSameParentOrgList = implode(',', $membershipTypesSameParentOrg);
        $dao->whereAdd('membership_type_id IN (' . $memberTypesSameParentOrgList . ')');
      }
    }

    if ($dao->find(TRUE)) {
      $membership = [];
      CRM_Core_DAO::storeValues($dao, $membership);
      $membership['is_current_member'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
        $membership['status_id'],
        'is_current_member', 'id'
      );
      $ownerMemberId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
        $membership['id'],
        'owner_membership_id', 'id'
      );
      if ($ownerMemberId) {
        $membership['id'] = $membership['membership_id'] = $ownerMemberId;
        $membership['membership_contact_id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
          $membership['id'],
          'contact_id', 'id'
        );
      }
      return $membership;
    }

    // CRM-8141
    if ($onlySameParentOrg && $memType) {
      // see if there is a membership that has same parent as $memType but different parent than $membershipID
      unset($dao->id);

      unset($dao->membership_type_id);
      if ($dao->find(TRUE)) {
        $membership = [];
        CRM_Core_DAO::storeValues($dao, $membership);
        $membership['is_current_member'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
          $membership['status_id'],
          'is_current_member', 'id'
        );
        return $membership;
      }
    }
    return FALSE;
  }

  /**
   * Get all exportable fields.
   *
   * @return array return array of all exportable fields
   */
  public static function &exportableFields() {
    $expFieldMembership = CRM_Member_DAO_Membership::export();

    $expFieldsMemType = CRM_Member_DAO_MembershipType::export();
    $fields = array_merge($expFieldMembership, $expFieldsMemType);
    $fields = array_merge($fields, $expFieldMembership);
    $membershipStatus = [
      'membership_status' => [
        'title' => ts('Membership Status'),
        'name' => 'membership_status',
        'type' => CRM_Utils_Type::T_STRING,
        'where' => 'civicrm_membership_status.name',
      ],
    ];
    //CRM-6161 fix for customdata export
    $fields = array_merge($fields, $membershipStatus, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));
    $fields['membership_status_id'] = $membershipStatus['membership_status'];
    return $fields;
  }

  /**
   * Get membership joins/renewals for a specified membership type.
   *
   * Specifically, retrieves a count of memberships whose "Membership
   * Signup" or "Membership Renewal" activity falls in the given date range.
   * Dates match the pattern "yyyy-mm-dd".
   *
   * @param int $membershipTypeId
   *   Membership type id.
   * @param int $startDate
   *   Date on which to start counting.
   * @param int $endDate
   *   Date on which to end counting.
   * @param bool|int $isTest if true, membership is for a test site
   * @param bool|int $isOwner if true, only retrieve membership records for owners //LCD
   *
   * @return int
   *   the number of members of type $membershipTypeId whose
   *   start_date is between $startDate and $endDate
   */
  public static function getMembershipStarts($membershipTypeId, $startDate, $endDate, $isTest = 0, $isOwner = 0) {
    // Ensure that the dates that are passed to the query are in the format of yyyy-mm-dd
    $dates = ['startDate', 'endDate'];
    foreach ($dates as $date) {
      if (strlen($$date) === 8) {
        $$date = date('Y-m-d', CRM_Utils_Time::strtotime($$date));
      }
    }

    $testClause = 'membership.is_test = 1';
    if (!$isTest) {
      $testClause = '( membership.is_test = 0 )';
    }

    if (!self::$_signupActType || !self::$_renewalActType) {
      self::_getActTypes();
    }

    if (!self::$_signupActType || !self::$_renewalActType) {
      return 0;
    }

    $query = "
    SELECT  COUNT(DISTINCT membership.id) as member_count
      FROM  civicrm_membership membership
INNER JOIN civicrm_activity activity ON (activity.source_record_id = membership.id AND activity.activity_type_id in (%1, %2))
INNER JOIN  civicrm_membership_status status ON ( membership.status_id = status.id AND status.is_current_member = 1 )
INNER JOIN  civicrm_contact contact ON ( contact.id = membership.contact_id AND contact.is_deleted = 0 )
     WHERE  membership.membership_type_id = %3
       AND  activity.activity_date_time >= '$startDate' AND activity.activity_date_time <= '$endDate 23:59:59'
       AND  {$testClause}";

    $query .= ($isOwner) ? ' AND owner_membership_id IS NULL' : '';

    $params = [
      1 => [self::$_signupActType, 'Integer'],
      2 => [self::$_renewalActType, 'Integer'],
      3 => [$membershipTypeId, 'Integer'],
    ];

    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);
    return (int) $memberCount;
  }

  /**
   * Get a count of membership for a specified membership type, optionally for a specified date.
   *
   * The date must have the form yyyy-mm-dd.
   *
   * If $date is omitted, this function counts as a member anyone whose
   * membership status_id indicates they're a current member.
   * If $date is given, this function counts as a member anyone who:
   *  -- Has a start_date before $date and end_date after $date, or
   *  -- Has a start_date before $date and is currently a member, as indicated
   *     by the the membership's status_id.
   * The second condition takes care of records that have no end_date.  These
   * are assumed to be lifetime memberships.
   *
   * @param int $membershipTypeId
   *   Membership type id.
   * @param string $date
   *   The date for which to retrieve the count.
   * @param bool|int $isTest if true, membership is for a test site
   * @param bool|int $isOwner if true, only retrieve membership records for owners //LCD
   *
   * @return int
   *   the number of members of type $membershipTypeId as of $date.
   */
  public static function getMembershipCount($membershipTypeId, $date = NULL, $isTest = 0, $isOwner = 0) {
    if (!CRM_Utils_Rule::date($date)) {
      throw new CRM_Core_Exception(ts('Invalid date "%1" (must have form yyyy-mm-dd).', [1 => $date]));
    }

    $params = [
      1 => [$membershipTypeId, 'Integer'],
      2 => [$isTest, 'Boolean'],
    ];
    $query = "SELECT  count(civicrm_membership.id ) as member_count
FROM   civicrm_membership left join civicrm_membership_status on ( civicrm_membership.status_id = civicrm_membership_status.id  )
WHERE  civicrm_membership.membership_type_id = %1
AND civicrm_membership.contact_id NOT IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)
AND civicrm_membership.is_test = %2";
    if (!$date) {
      $query .= " AND civicrm_membership_status.is_current_member = 1";
    }
    else {
      $query .= " AND civicrm_membership.start_date <= '$date' AND civicrm_membership_status.is_current_member = 1";
    }
    // LCD
    $query .= ($isOwner) ? ' AND owner_membership_id IS NULL' : '';
    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);
    return (int) $memberCount;
  }

  /**
   * Function check the status of the membership before adding membership for a contact.
   *
   * @return int
   */
  public static function statusAvailabilty() {
    $membership = new CRM_Member_DAO_MembershipStatus();
    $membership->whereAdd('is_active=1');
    return $membership->count();
  }

  /**
   * @deprecated This is not used anywhere and should be removed soon!
   * Function for updating a membership record's contribution_recur_id.
   *
   * @param CRM_Member_DAO_Membership $membership
   * @param \CRM_Contribute_BAO_Contribution|\CRM_Contribute_DAO_Contribution $contribution
   */
  public static function updateRecurMembership(CRM_Member_DAO_Membership $membership, CRM_Contribute_BAO_Contribution $contribution) {
    CRM_Core_Error::deprecatedFunctionWarning('Use the api');

    if (empty($contribution->contribution_recur_id)) {
      return;
    }

    $params = [
      1 => [$contribution->contribution_recur_id, 'Integer'],
      2 => [$membership->id, 'Integer'],
    ];

    $sql = "UPDATE civicrm_membership SET contribution_recur_id = %1 WHERE id = %2";
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Method to fix membership status of stale membership.
   *
   * This method first checks if the membership is stale. If it is,
   * then status will be updated based on existing start and end
   * dates and log will be added for the status change.
   *
   * @param array $currentMembership
   *   Reference to the array.
   *   containing all values of
   *   the current membership
   * @param string|null $changeToday
   *   In case today needs
   *   to be customised, null otherwise
   *
   * @throws \CRM_Core_Exception
   */
  public static function fixMembershipStatusBeforeRenew(&$currentMembership, $changeToday = NULL) {
    $today = 'now';
    if ($changeToday) {
      $today = CRM_Utils_Date::processDate($changeToday, NULL, FALSE, 'Y-m-d');
    }

    $status = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(
      $currentMembership['start_date'] ?? NULL,
      $currentMembership['end_date'] ?? NULL,
      $currentMembership['join_date'] ?? NULL,
      $today,
      TRUE,
      $currentMembership['membership_type_id'],
      $currentMembership
    );

    if (empty($status) || empty($status['id'])) {
      throw new CRM_Core_Exception(ts('Oops, it looks like there is no valid membership status corresponding to the membership start and end dates for this membership. Contact the site administrator for assistance.'));
    }

    if ((int) $status['id'] !== (int) $currentMembership['status_id']) {
      $oldStatus = $currentMembership['status_id'];
      $memberDAO = new CRM_Member_DAO_Membership();
      $memberDAO->id = $currentMembership['id'];
      $memberDAO->find(TRUE);

      $memberDAO->status_id = $status['id'];
      $memberDAO->save();
      CRM_Core_DAO::storeValues($memberDAO, $currentMembership);

      $currentMembership['is_current_member'] = CRM_Core_DAO::getFieldValue(
        'CRM_Member_DAO_MembershipStatus',
        $currentMembership['status_id'],
        'is_current_member'
      );
      $format = '%Y%m%d';

      $logParams = [
        'membership_id' => $currentMembership['id'],
        'status_id' => $status['id'],
        'start_date' => CRM_Utils_Date::customFormat(
          $currentMembership['start_date'],
          $format
        ),
        'end_date' => CRM_Utils_Date::customFormat(
          $currentMembership['end_date'],
          $format
        ),
        'modified_date' => date('YmdHis', CRM_Utils_Time::strtotime($today)),
        'membership_type_id' => $currentMembership['membership_type_id'],
        'max_related' => $currentMembership['max_related'] ?? 0,
      ];

      $session = CRM_Core_Session::singleton();
      // If we have an authenticated session, set modified_id to that user's contact_id, else set to membership.contact_id
      if ($session->get('userID')) {
        $logParams['modified_id'] = $session->get('userID');
      }
      else {
        $logParams['modified_id'] = $currentMembership['contact_id'];
      }

      //Create activity for status change.
      $allStatus = CRM_Member_BAO_Membership::buildOptions('status_id', 'get');
      self::createChangeMembershipStatusActivity($memberDAO, $allStatus[$oldStatus], $allStatus[$status['id']], $logParams['modified_id']);

      CRM_Member_BAO_MembershipLog::add($logParams);
    }
  }

  /**
   * Get the contribution page id from the membership record.
   *
   * @param int $membershipID
   *
   * @return int
   *   contribution page id
   */
  public static function getContributionPageId($membershipID) {
    $query = "
SELECT c.contribution_page_id as pageID
  FROM civicrm_membership_payment mp, civicrm_contribution c
 WHERE mp.contribution_id = c.id
   AND c.contribution_page_id IS NOT NULL
   AND mp.membership_id = " . CRM_Utils_Type::escape($membershipID, 'Integer')
      . " ORDER BY mp.id DESC";

    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Updated related memberships.
   *
   * @param int $ownerMembershipId
   *   Owner Membership Id.
   * @param array $params
   *   Formatted array of key => value.
   */
  public static function updateRelatedMemberships($ownerMembershipId, $params) {
    $membership = new CRM_Member_DAO_Membership();
    $membership->owner_membership_id = $ownerMembershipId;
    $membership->find();

    while ($membership->fetch()) {
      $relatedMembership = new CRM_Member_DAO_Membership();
      $relatedMembership->id = $membership->id;
      $relatedMembership->copyValues($params);
      $relatedMembership->save();
    }

  }

  /**
   * Get list of membership fields for profile.
   *
   * For now we only allow custom membership fields to be in
   * profile
   *
   * @param null $mode
   *   FIXME: This param is ignored
   *
   * @return array
   *   the list of membership fields
   */
  public static function getMembershipFields($mode = NULL) {
    $fields = CRM_Member_DAO_Membership::export();

    unset($fields['membership_contact_id']);
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));

    $membershipType = CRM_Member_DAO_MembershipType::export();

    $membershipStatus = CRM_Member_DAO_MembershipStatus::export();

    $fields = array_merge($fields, $membershipType, $membershipStatus);

    return $fields;
  }

  /**
   * Get the sort name of a contact for a particular membership.
   *
   * @param int $id
   *   Id of the membership.
   *
   * @return null|string
   *   sort name of the contact if found
   */
  public static function sortName($id) {
    $id = CRM_Utils_Type::escape($id, 'Integer');

    $query = "
SELECT civicrm_contact.sort_name
FROM   civicrm_membership, civicrm_contact
WHERE  civicrm_membership.contact_id = civicrm_contact.id
  AND  civicrm_membership.id = {$id}
";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Create memberships for related contacts, taking into account the maximum related memberships.
   *
   * @param array $params
   *   Array of key - value pairs.
   * @param CRM_Core_DAO $dao
   *   Membership object.
   *
   * @throws \CRM_Core_Exception
   */
  public static function createRelatedMemberships($params, $dao) {
    unset($params['membership_id']);
    $membership = new CRM_Member_DAO_Membership();
    $membership->id = $dao->id;

    // required since create method doesn't return all the
    // parameters in the returned membership object
    if (!$membership->find(TRUE)) {
      return;
    }
    $deceasedStatusId = array_search('Deceased', CRM_Member_PseudoConstant::membershipStatus());
    // FIXME : While updating/ renewing the
    // membership, if the relationship is PAST then
    // the membership of the related contact must be
    // expired.
    // For that, getting Membership Status for which
    // is_current_member is 0. It works for the
    // generated data as there is only one membership
    // status having is_current_member = 0.
    // But this wont work exactly if there will be
    // more than one status having is_current_member = 0.
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->is_current_member = 0;
    if ($membershipStatus->find(TRUE)) {
      $expiredStatusId = $membershipStatus->id;
    }
    else {
      $expiredStatusId = array_search('Expired', CRM_Member_PseudoConstant::membershipStatus());
    }

    $relatedContacts = [];
    $allRelatedContacts = CRM_Member_BAO_Membership::checkMembershipRelationship($membership->membership_type_id,
      $membership->contact_id,
      $params['action'] ?? NULL
    );

    // CRM-4213, CRM-19735 check for loops, using static variable to record contacts already processed.
    // Remove repeated related contacts, which already inherited membership of this type$relatedContactIds[$membership->contact_id][$membership->membership_type_id] = TRUE;
    foreach ($allRelatedContacts as $cid => $status) {
      // relatedContactIDs is always empty now - will remove next roud because of whitespace readability.
      if (empty($relatedContactIds[$cid]) || empty($relatedContactIds[$cid][$membership->membership_type_id])) {
        $relatedContactIds[$cid][$membership->membership_type_id] = TRUE;

        //don't create membership again for owner contact.
        $nestedRelationship = FALSE;
        if ($membership->owner_membership_id) {
          $nestedRelMembership = new CRM_Member_DAO_Membership();
          $nestedRelMembership->id = $membership->owner_membership_id;
          $nestedRelMembership->contact_id = $cid;
          $nestedRelationship = $nestedRelMembership->find(TRUE);
        }
        if (!$nestedRelationship) {
          $relatedContacts[$cid] = $status;
        }
      }
    }

    //lets cleanup related membership if any.
    if (empty($relatedContacts)) {
      self::deleteRelatedMemberships($membership->id);
    }
    else {
      // Edit the params array
      unset($params['id']);
      // Reminder should be sent only to the direct membership
      unset($params['reminder_date']);
      // unset the custom value ids
      if (isset($params['custom']) && is_array($params['custom'])) {
        foreach ($params['custom'] as $k => $values) {
          foreach ($values as $i => $value) {
            unset($params['custom'][$k][$i]['id']);
          }
        }
      }
      if (!isset($params['membership_type_id'])) {
        $params['membership_type_id'] = $membership->membership_type_id;
      }

      // max_related should be set in the parent membership
      unset($params['max_related']);
      // Number of inherited memberships available - NULL is interpreted as unlimited, '0' as none
      $numRelatedAvailable = ($membership->max_related == NULL ? PHP_INT_MAX : $membership->max_related);
      // will be used to queue potential memberships to be created.
      $queue = [];

      foreach ($relatedContacts as $contactId => $relationshipStatus) {
        //use existing membership record.
        $relMembership = new CRM_Member_DAO_Membership();
        $relMembership->contact_id = $contactId;
        $relMembership->owner_membership_id = $membership->id;

        if ($relMembership->find(TRUE)) {
          $params['id'] = $relMembership->id;
        }
        else {
          unset($params['id']);
        }

        $params['contact_id'] = $contactId;
        $params['owner_membership_id'] = $membership->id;

        // set status_id as it might have been changed for
        // past relationship
        $params['status_id'] = $membership->status_id;

        if ($deceasedStatusId &&
          CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'is_deceased')
        ) {
          $params['status_id'] = $deceasedStatusId;
        }
        elseif ((($params['action'] ?? NULL) & CRM_Core_Action::UPDATE) &&
          ($relationshipStatus == CRM_Contact_BAO_Relationship::PAST)
        ) {
          $params['status_id'] = $expiredStatusId;
        }

        //don't calculate status again in create( );
        $params['skipStatusCal'] = TRUE;

        //do create activity if we changed status.
        if ($params['status_id'] != $relMembership->status_id) {
          $params['createActivity'] = TRUE;
        }

        //CRM-20707 - include start/end date
        $params['start_date'] = $membership->start_date;
        $params['end_date'] = $membership->end_date;

        // we should not create contribution record for related contacts, CRM-3371
        unset($params['contribution_status_id']);

        //CRM-16857: Do not create multiple line-items for inherited membership through priceset.
        unset($params['lineItems']);
        unset($params['line_item']);

        // CRM-20966: Do not create membership_payment record for inherited membership.
        unset($params['relate_contribution_id']);

        if (($params['status_id'] == $deceasedStatusId) || ($params['status_id'] == $expiredStatusId)) {
          // related membership is not active so does not count towards maximum
          if (!self::hasExistingInheritedMembership($params)) {
            civicrm_api3('Membership', 'create', $params);
          }
        }
        else {
          // related membership already exists, so this is just an update
          if (isset($params['id'])) {
            if ($numRelatedAvailable > 0) {
              CRM_Member_BAO_Membership::create($params);
              $numRelatedAvailable--;
            }
            else {
              // we have run out of inherited memberships, so delete extras
              self::deleteMembership($params['id']);
            }
            // we need to first check if there will remain inherited memberships, so queue it up
          }
          else {
            $queue[] = $params;
          }
        }
      }
      // now go over the queue and create any available related memberships
      foreach ($queue as $params) {
        if ($numRelatedAvailable <= 0) {
          break;
        }
        if (!self::hasExistingInheritedMembership($params)) {
          CRM_Member_BAO_Membership::create($params);
        }
        $numRelatedAvailable--;
      }
    }
  }

  /**
   * Build an array of available membership types in the current context.
   *
   * While core does not do anything context specific extensions may filter
   * or alter amounts based on user details.
   *
   * @param CRM_Core_Form $form
   * @param array $membershipTypeID
   * @param bool $activeOnly
   *   Do we only want active ones?
   *   (probably this should default to TRUE but as a newly added parameter we are leaving default b
   *   behaviour unchanged).
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function buildMembershipTypeValues($form, $membershipTypeID = [], $activeOnly = FALSE) {
    $membershipTypeIDS = (array) $membershipTypeID;
    $membershipTypeValues = CRM_Member_BAO_MembershipType::getAllMembershipTypes();

    // MembershipTypes are already filtered by domain, filter as appropriate by is_active & a passed in list of ids.
    foreach ($membershipTypeValues as $id => $type) {
      if (($activeOnly && empty($type['is_active']))
        || (!empty($membershipTypeIDS) && !in_array($id, $membershipTypeIDS, FALSE))
      ) {
        unset($membershipTypeValues[$id]);
      }
    }

    CRM_Utils_Hook::membershipTypeValues($form, $membershipTypeValues);
    return $membershipTypeValues;
  }

  /**
   * Get membership record count for a Contact.
   *
   * @param int $contactID
   * @param bool $activeOnly
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public static function getContactMembershipCount(int $contactID, $activeOnly = FALSE): int {
    try {
      $membershipTypes = MembershipType::get(TRUE)
        ->execute()
        ->column('name', 'id');
      $addWhere = " AND membership_type_id IN (0)";
      if (!empty($membershipTypes)) {
        $addWhere = " AND membership_type_id IN (" . implode(',', array_keys($membershipTypes)) . ")";
      }

      $select = "SELECT COUNT(*) FROM civicrm_membership ";
      $where = "WHERE civicrm_membership.contact_id = {$contactID} AND civicrm_membership.is_test = 0 ";

      // CRM-6627, all status below 3 (active, pending, grace) are considered active
      if ($activeOnly) {
        $select .= " INNER JOIN civicrm_membership_status ON civicrm_membership.status_id = civicrm_membership_status.id ";
        $where .= " and civicrm_membership_status.is_current_member = 1";
      }

      $query = $select . $where . $addWhere;
      return (int) CRM_Core_DAO::singleValueQuery($query);
    }
    catch (UnauthorizedException $e) {
      return 0;
    }
  }

  /**
   * Check whether payment processor supports cancellation of membership subscription.
   *
   * @param int $mid
   *   Membership id.
   *
   * @param bool $isNotCancelled
   *
   * @return bool
   */
  public static function isCancelSubscriptionSupported($mid, $isNotCancelled = TRUE) {
    $cacheKeyString = "$mid";
    $cacheKeyString .= $isNotCancelled ? '_1' : '_0';

    static $supportsCancel = [];

    if (!array_key_exists($cacheKeyString, $supportsCancel)) {
      $supportsCancel[$cacheKeyString] = FALSE;
      $isCancelled = FALSE;

      if ($isNotCancelled) {
        $isCancelled = self::isSubscriptionCancelled((int) $mid);
      }

      $paymentObject = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($mid, 'membership', 'obj');
      if (!empty($paymentObject)) {
        $supportsCancel[$cacheKeyString] = $paymentObject->supports('cancelRecurring') && !$isCancelled;
      }
    }
    return $supportsCancel[$cacheKeyString];
  }

  /**
   * Check whether subscription is already cancelled.
   *
   * @param int $membershipID
   *   Membership id.
   *
   * @return bool
   *   contribution status
   *
   * @throws \CRM_Core_Exception
   */
  public static function isSubscriptionCancelled(int $membershipID): bool {
    $isCiviContributeEnabled = CRM_Extension_System::singleton()
      ->getManager()
      ->isEnabled('civi_contribute');
    if ($isCiviContributeEnabled) {
      // Check permissions set to false 'in case' - ideally would check permissions are
      // correct & remove.
      return (bool) Membership::get(FALSE)
        ->addWhere('id', '=', $membershipID)
        ->addWhere('contribution_recur_id.contribution_status_id:name', '=', 'Cancelled')
        ->selectRowCount()->execute()->count();
    }
    return FALSE;
  }

  /**
   * Get membership joins for a specified membership type.
   *
   * Specifically, retrieves a count of still current memberships whose
   * join_date and start_date are within a specified date range.  Dates match
   * the pattern "yyyy-mm-dd".
   *
   * @param int $membershipTypeId
   *   Membership type id.
   * @param int $startDate
   *   Date on which to start counting.
   * @param int $endDate
   *   Date on which to end counting.
   * @param bool|int $isTest if true, membership is for a test site
   *
   * @return int
   *   the number of members of type $membershipTypeId
   *   whose join_date is between $startDate and $endDate and
   *   whose start_date is between $startDate and $endDate
   */
  public static function getMembershipJoins($membershipTypeId, $startDate, $endDate, $isTest = 0) {
    $testClause = 'membership.is_test = 1';
    if (!$isTest) {
      $testClause = '( membership.is_test = 0 )';
    }
    if (!self::$_signupActType) {
      self::_getActTypes();
    }

    if (!self::$_signupActType) {
      return 0;
    }

    $query = "
    SELECT  COUNT(DISTINCT membership.id) as member_count
      FROM  civicrm_membership membership
INNER JOIN civicrm_activity activity ON (activity.source_record_id = membership.id AND activity.activity_type_id = %1)
INNER JOIN  civicrm_membership_status status ON ( membership.status_id = status.id AND status.is_current_member = 1 )
INNER JOIN  civicrm_contact contact ON ( contact.id = membership.contact_id AND contact.is_deleted = 0 )
     WHERE  membership.membership_type_id = %2
       AND  activity.activity_date_time >= '$startDate' AND activity.activity_date_time <= '$endDate 23:59:59'
       AND  {$testClause}";

    $params = [
      1 => [self::$_signupActType, 'Integer'],
      2 => [$membershipTypeId, 'Integer'],
    ];

    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);

    return (int) $memberCount;
  }

  /**
   * Get membership renewals for a specified membership type.
   *
   * Specifically, retrieves a count of still current memberships
   * whose join_date is before and start_date is within a specified date
   * range.  Dates match the pattern "yyyy-mm-dd".
   *
   * @param int $membershipTypeId
   *   Membership type id.
   * @param int $startDate
   *   Date on which to start counting.
   * @param int $endDate
   *   Date on which to end counting.
   * @param bool|int $isTest if true, membership is for a test site
   *
   * @return int
   *   returns the number of members of type $membershipTypeId
   *   whose join_date is before $startDate and
   *   whose start_date is between $startDate and $endDate
   */
  public static function getMembershipRenewals($membershipTypeId, $startDate, $endDate, $isTest = 0) {
    $testClause = 'membership.is_test = 1';
    if (!$isTest) {
      $testClause = '( membership.is_test = 0 )';
    }
    if (!self::$_renewalActType) {
      self::_getActTypes();
    }

    if (!self::$_renewalActType) {
      return 0;
    }

    $query = "
    SELECT  COUNT(DISTINCT membership.id) as member_count
      FROM  civicrm_membership membership
INNER JOIN civicrm_activity activity ON (activity.source_record_id = membership.id AND activity.activity_type_id = %1)
INNER JOIN  civicrm_membership_status status ON ( membership.status_id = status.id AND status.is_current_member = 1 )
INNER JOIN  civicrm_contact contact ON ( contact.id = membership.contact_id AND contact.is_deleted = 0 )
     WHERE  membership.membership_type_id = %2
       AND  activity.activity_date_time >= '$startDate' AND activity.activity_date_time <= '$endDate 23:59:59'
       AND  {$testClause}";

    $params = [
      1 => [self::$_renewalActType, 'Integer'],
      2 => [$membershipTypeId, 'Integer'],
    ];
    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);

    return (int) $memberCount;
  }

  /**
   * Get line items representing the default price set.
   *
   * @param int $membershipOrg
   * @param int $membershipTypeID
   * @param float $total_amount
   * @param int $priceSetId
   *
   * @return array
   */
  public static function setQuickConfigMembershipParameters($membershipOrg, $membershipTypeID, $total_amount, $priceSetId) {
    $priceSets = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));

    // The name of the price field corresponds to the membership_type organization contact.
    $params = [
      'price_set_id' => $priceSetId,
      'name' => $membershipOrg,
    ];
    $results = [];
    CRM_Price_BAO_PriceField::retrieve($params, $results);

    if (!empty($results)) {
      $fields[$results['id']] = $priceSets['fields'][$results['id']];
      $fid = $results['id'];
      $editedFieldParams = [
        'price_field_id' => $results['id'],
        'membership_type_id' => $membershipTypeID,
      ];
      $results = [];
      CRM_Price_BAO_PriceFieldValue::retrieve($editedFieldParams, $results);
      $fields[$fid]['options'][$results['id']] = $priceSets['fields'][$fid]['options'][$results['id']];
      if (!empty($total_amount)) {
        $fields[$fid]['options'][$results['id']]['amount'] = $total_amount;
      }
    }

    $fieldID = key($fields);
    $returnParams = [
      'price_set_id' => $priceSetId,
      'price_sets' => $priceSets,
      'fields' => $fields,
      'price_fields' => [
        'price_' . $fieldID => $results['id'] ?? NULL,
      ],
    ];
    return $returnParams;
  }

  /**
   * Update the status of all deceased members to deceased.
   *
   * @return int
   *   Count of updated contacts.
   *
   * @throws \CRM_Core_Exception
   */
  protected static function updateDeceasedMembersStatuses() {
    $count = 0;

    $deceasedStatusId = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Deceased');

    // 'create' context for buildOptions returns only if enabled.
    $allStatus = self::buildOptions('status_id', 'create');
    if (array_key_exists($deceasedStatusId, $allStatus) === FALSE) {
      // Deceased status is an admin status & is required. We want to fail early if
      // it is not present or active.
      // We could make the case 'some databases just don't use deceased so we will check
      // for the presence of a deceased contact in the DB before rejecting.
      if (CRM_Core_DAO::singleValueQuery('
        SELECT count(*) FROM civicrm_contact WHERE is_deceased = 0'
      )) {
        throw new CRM_Core_Exception(
          ts("Deceased Membership status is missing or not active. <a href='%1'>Click here to check</a>.",
            [1 => CRM_Utils_System::url('civicrm/admin/member/membershipStatus', 'reset=1')]
          ));
      }
    }
    $deceasedDAO = CRM_Core_DAO::executeQuery(
      $baseQuery = "
       SELECT membership.id as membership_id
       FROM civicrm_membership membership
       INNER JOIN civicrm_contact ON membership.contact_id = civicrm_contact.id
       INNER JOIN civicrm_membership_type ON membership.membership_type_id = civicrm_membership_type.id
         AND civicrm_membership_type.is_active = 1
       WHERE membership.is_test = 0
         AND civicrm_contact.is_deceased = 1
         AND membership.status_id <> %1
      ",
      [1 => [$deceasedStatusId, 'Integer']]
    );
    while ($deceasedDAO->fetch()) {
      civicrm_api3('membership', 'create', [
        'id' => $deceasedDAO->membership_id,
        'status_id' => $deceasedStatusId,
        'createActivity' => TRUE,
        'skipStatusCal' => TRUE,
        'skipRecentView' => TRUE,
      ]);
      $count++;
    }
    return $count;
  }

  /**
   * Does the existing membership match the required membership.
   *
   * Check before updating that the params are not a match - this is part of avoiding
   * a loop if we have already updated.
   *
   * https://issues.civicrm.org/jira/browse/CRM-4213
   * @param array $params
   *
   * @param array $membership
   *
   * @return bool
   */
  protected static function matchesRequiredMembership($params, $membership) {
    foreach (['start_date', 'end_date'] as $date) {
      if (CRM_Utils_Time::strtotime($params[$date]) !== CRM_Utils_Time::strtotime($membership[$date])) {
        return FALSE;
      }
      if ((int) $params['status_id'] !== (int) $membership['status_id']) {
        return FALSE;
      }
      if ((int) $params['membership_type_id'] !== (int) $membership['membership_type_id']) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Params of new membership.
   *
   * @param array $params
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected static function hasExistingInheritedMembership($params) {
    $currentMemberships = \Civi\Api4\Membership::get(FALSE)
      ->addJoin('MembershipStatus AS membership_status', 'LEFT')
      ->addWhere('membership_status.is_current_member', '=', TRUE)
      ->addWhere('contact_id', '=', $params['contact_id'])
      ->execute();
    foreach ($currentMemberships as $membership) {
      if (!empty($membership['owner_membership_id'])
        && $membership['membership_type_id'] === $params['membership_type_id']
        && (int) $params['owner_membership_id'] !== (int) $membership['owner_membership_id']
      ) {
        // Inheriting it from another contact, don't update here.
        return TRUE;
      }
      if (self::matchesRequiredMembership($params, $membership)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Process price set and line items.
   *
   * @param int $membershipId
   * @param array $lineItem
   *
   * @throws \CRM_Core_Exception
   * @deprecated since 6.11 will be removed around 6.19
   */
  public function processPriceSet($membershipId, $lineItem) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    //FIXME : need to move this too
    if (!$membershipId || !is_array($lineItem)
      || CRM_Utils_System::isNull($lineItem)
    ) {
      return;
    }

    foreach ($lineItem as $priceSetId => $values) {
      if (!$priceSetId) {
        continue;
      }
      foreach ($values as $line) {
        $line['entity_table'] = 'civicrm_membership';
        $line['entity_id'] = $membershipId;
        CRM_Price_BAO_LineItem::create($line);
      }
    }
  }

  /**
   *
   * Retrieve the contribution id for the associated Membership id.
   *
   * @param int $membershipId
   *   Membership id.
   * @param bool $all
   *   if more than one payment associated with membership id need to be returned.
   *
   * @return int|int[]|null
   *   contribution id
   *
   * @deprecated
   */
  public static function getMembershipContributionId($membershipId, $all = FALSE) {
    CRM_Core_Error::deprecatedFunctionWarning('use LineItems');
    $membershipPayment = new CRM_Member_DAO_MembershipPayment();
    $membershipPayment->membership_id = $membershipId;
    if ($all && $membershipPayment->find()) {
      $contributionIds = [];
      while ($membershipPayment->fetch()) {
        $contributionIds[] = $membershipPayment->contribution_id;
      }
      return $contributionIds;
    }

    if ($membershipPayment->find(TRUE)) {
      return $membershipPayment->contribution_id;
    }
    return NULL;
  }

  /**
   * The function checks and updates the status of all membership records for a given domain using the
   * calc_membership_status and update_contact_membership APIs.
   *
   * IMPORTANT:
   * Sending renewal reminders has been migrated from this job to the Scheduled Reminders function as of 4.3.
   *
   * @param array $params
   *   only_active_membership_types, exclude_test_memberships, exclude_membership_status_ids
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function updateAllMembershipStatus($params = []) {
    // We want all of the statuses as id => name, even the disabled ones (cf.
    // CRM-15475), to identify which are Pending, Deceased, Cancelled, and
    // Expired.
    $allStatus = CRM_Member_BAO_Membership::buildOptions('status_id', 'validate');
    if (empty($params['exclude_membership_status_ids'])) {
      $params['exclude_membership_status_ids'] = [
        array_search('Pending', $allStatus),
        array_search('Cancelled', $allStatus),
        array_search('Expired', $allStatus) ?: 0,
        array_search('Deceased', $allStatus),
      ];
    }
    // Deceased is *always* excluded because it is has very specific processing below.
    elseif (!in_array(array_search('Deceased', $allStatus), $params['exclude_membership_status_ids'])) {
      $params['exclude_membership_status_ids'][] = array_search('Deceased', $allStatus);
    }

    for ($index = 0; $index < count($params['exclude_membership_status_ids']); $index++) {
      $queryParams[$index] = [$params['exclude_membership_status_ids'][$index], 'Integer'];
    }
    $membershipStatusClause = 'civicrm_membership.status_id NOT IN (%' . implode(', %', array_keys($queryParams)) . ')';

    // Tests for this function are in api_v3_JobTest. Please add tests for all updates.

    $updateCount = $processCount = self::updateDeceasedMembersStatuses();

    $whereClauses[] = 'civicrm_contact.is_deceased = 0';
    if ($params['exclude_test_memberships']) {
      $whereClauses[] = 'civicrm_membership.is_test = 0';
    }
    $whereClause = implode(' AND ', $whereClauses);
    $activeMembershipClause = '';
    if ($params['only_active_membership_types']) {
      $activeMembershipClause = ' AND civicrm_membership_type.is_active = 1';
    }

    // This query retrieves ALL memberships of active types.
    // Note: id, is_test, campaign_id expected by CRM_Activity_BAO_Activity::addActivity()
    //   called by createChangeMembershipStatusActivity().
    // max_related expected by createMembershipLog().
    $baseQuery = "
SELECT     civicrm_membership.id                    as membership_id,
           civicrm_membership.id                    as id,
           civicrm_membership.is_test               as is_test,
           civicrm_membership.campaign_id           as campaign_id,
           civicrm_membership.is_override           as is_override,
           civicrm_membership.status_override_end_date  as status_override_end_date,
           civicrm_membership.membership_type_id    as membership_type_id,
           civicrm_membership.status_id             as status_id,
           civicrm_membership.join_date             as join_date,
           civicrm_membership.start_date            as start_date,
           civicrm_membership.end_date              as end_date,
           civicrm_membership.source                as source,
           civicrm_membership.max_related           as max_related,
           civicrm_contact.id                       as contact_id,
           civicrm_membership.owner_membership_id   as owner_membership_id,
           civicrm_membership.contribution_recur_id as recur_id
FROM       civicrm_membership
INNER JOIN civicrm_contact ON ( civicrm_membership.contact_id = civicrm_contact.id )
INNER JOIN civicrm_membership_type ON
  (civicrm_membership.membership_type_id = civicrm_membership_type.id {$activeMembershipClause})
WHERE {$whereClause}";

    $query = $baseQuery . " AND civicrm_membership.is_override IS NOT NULL AND civicrm_membership.status_override_end_date IS NOT NULL";
    $dao1 = CRM_Core_DAO::executeQuery($query);
    while ($dao1->fetch()) {
      self::processOverriddenUntilDateMembership($dao1);
    }

    $query = $baseQuery . " AND civicrm_membership.is_override = 0
     AND {$membershipStatusClause}
     AND civicrm_membership.owner_membership_id IS NULL ";

    $dao2 = CRM_Core_DAO::executeQuery($query, $queryParams);

    while ($dao2->fetch()) {
      $processCount++;

      // CRM-7248: added excludeIsAdmin param to the following fn call to prevent moving to admin statuses
      //get the membership status as per id.
      $newStatus = civicrm_api3('membership_status', 'calc', [
        'membership_id' => $dao2->membership_id,
        'ignore_admin_only' => TRUE,
      ]);
      $newStatusId = $newStatus['id'] ?? NULL;

      // process only when status change.
      if ($newStatusId &&
        $newStatusId != $dao2->status_id
      ) {
        // Update the status on the membership.
        self::writeRecord([
          'id' => $dao2->membership_id,
          'status_id' => $newStatusId,
        ]);

        self::createRelatedMemberships(['action' => CRM_Core_Action::UPDATE], $dao2);
        // Now create the "Change Membership Status" activity
        $allStatusLabels = CRM_Member_BAO_Membership::buildOptions('status_id', 'get');
        $changedByContactID = CRM_Core_Session::getLoggedInContactID() ?? $dao2->contact_id;
        self::createChangeMembershipStatusActivity($dao2, $allStatusLabels[$dao2->status_id], $allStatusLabels[$newStatusId], $changedByContactID);
        $dao2->status_id = $newStatusId;
        self::createMembershipLog($dao2);
        $updateCount++;
      }
    }
    $result['is_error'] = 0;
    $result['messages'] = ts('Processed %1 membership records. Updated %2 records.', [
      1 => $processCount,
      2 => $updateCount,
    ]);
    return $result;
  }

  /**
   * Set is_override for the 'overridden until date' membership to
   * False and clears the 'until date' field in case the 'until date'
   * is equal or after today date.
   *
   * @param CRM_Core_DAO $membership
   *   The membership to be processed
   *
   * @throws \CRM_Core_Exception
   */
  private static function processOverriddenUntilDateMembership($membership) {
    $isOverriddenUntilDate = !empty($membership->is_override) && !empty($membership->status_override_end_date);
    if (!$isOverriddenUntilDate) {
      return;
    }

    $todayDate = new DateTime();
    $todayDate->setTime(0, 0);

    $overrideEndDate = new DateTime($membership->status_override_end_date);
    $overrideEndDate->setTime(0, 0);

    $datesDifference = $todayDate->diff($overrideEndDate);
    $daysDifference = (int) $datesDifference->format('%R%a');
    if ($daysDifference <= 0) {
      $params = [
        'id' => $membership->membership_id,
        'is_override' => FALSE,
        'status_override_end_date' => 'null',
      ];
      civicrm_api3('membership', 'create', $params);
    }
  }

  /**
   * Returns the membership types for a contact, optionally filtering to lifetime memberships only.
   *
   * @param int $contactID
   * @param bool $isTest
   * @param bool $onlyLifeTime
   *
   * @return array
   */
  public static function getAllContactMembership($contactID, $isTest = FALSE, $onlyLifeTime = FALSE) : array {
    if (!\CRM_Core_Component::isEnabled('CiviMember')) {
      return [];
    }
    $contactMembershipType = [];
    if (!$contactID) {
      return $contactMembershipType;
    }

    $membershipQuery = Membership::get(FALSE)
      ->addWhere('contact_id', '=', $contactID)
      ->addWhere('status_id:name', '<>', 'Pending')
      ->addWhere('is_test', '=', $isTest)
      //CRM-4297
      ->addOrderBy('end_date', 'DESC');

    if ($onlyLifeTime) {
      // membership#14 - use duration_unit for calculating lifetime, not join/end date.
      $membershipQuery->addWhere('membership_type_id.duration_unit', '=', 'lifetime');
    }
    $memberships = $membershipQuery->execute();
    foreach ($memberships as $membership) {
      $contactMembershipType[$membership['membership_type_id']] = $membership;
    }
    return $contactMembershipType;
  }

  /**
   * Record contribution record associated with membership.
   * This will update an existing contribution if $params['contribution_id'] is passed in.
   * This will create a MembershipPayment to link the contribution and membership
   *
   * @param array $params
   *   Array of submitted params.
   *
   * @return CRM_Contribute_BAO_Contribution
   * @throws \CRM_Core_Exception
   */
  public static function recordMembershipContribution(&$params) {
    $contributionParams = [];
    $config = CRM_Core_Config::singleton();
    $contributionParams['currency'] = $config->defaultCurrency;
    $contributionParams['receipt_date'] = !empty($params['receipt_date']) ? $params['receipt_date'] : 'null';
    $contributionParams['source'] = $params['contribution_source'] ?? NULL;
    $contributionParams['non_deductible_amount'] = 'null';
    $contributionParams['skipCleanMoney'] = TRUE;
    $contributionParams['payment_processor'] = $params['payment_processor_id'] ?? NULL;
    $contributionSoftParams = $params['soft_credit'] ?? NULL;
    $recordContribution = [
      'contact_id',
      'fee_amount',
      'total_amount',
      'receive_date',
      'financial_type_id',
      'payment_instrument_id',
      'trxn_id',
      'invoice_id',
      'is_test',
      'contribution_status_id',
      'check_number',
      'campaign_id',
      'is_pay_later',
      'membership_id',
      'tax_amount',
      'skipLineItem',
      'contribution_recur_id',
      'pan_truncation',
      'card_type_id',
    ];
    foreach ($recordContribution as $f) {
      $contributionParams[$f] = $params[$f] ?? NULL;
    }

    if (!empty($params['contribution_id'])) {
      $contributionParams['id'] = $params['contribution_id'];
    }
    // make entry in batch entity batch table
    if (!empty($params['batch_id'])) {
      $contributionParams['batch_id'] = $params['batch_id'];
    }

    if (!empty($params['contribution_contact_id'])) {
      // deal with possibility of a different person paying for contribution
      $contributionParams['contact_id'] = $params['contribution_contact_id'];
    }

    if (!empty($params['processPriceSet']) &&
      !empty($params['lineItems'])
    ) {
      $contributionParams['line_item'] = $params['lineItems'] ?? NULL;
    }

    $contribution = CRM_Contribute_BAO_Contribution::create($contributionParams);

    //CRM-13981, create new soft-credit record as to record payment from different person for this membership
    if (!empty($contributionSoftParams)) {
      if (!empty($params['batch_id'])) {
        foreach ($contributionSoftParams as $contributionSoft) {
          $contributionSoft['contribution_id'] = $contribution->id;
          $contributionSoft['currency'] = $contribution->currency;
          CRM_Contribute_BAO_ContributionSoft::add($contributionSoft);
        }
      }
      else {
        $contributionSoftParams['contribution_id'] = $contribution->id;
        $contributionSoftParams['currency'] = $contribution->currency;
        $contributionSoftParams['amount'] = $contribution->total_amount;
        CRM_Contribute_BAO_ContributionSoft::add($contributionSoftParams);
      }
    }

    // store contribution id
    $params['contribution_id'] = $contribution->id;

    return $contribution;
  }

  /**
   * @todo document me - I seem a bit out of date....
   */
  public static function _getActTypes() {
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    self::$_renewalActType = CRM_Utils_Array::key('Membership Renewal', $activityTypes);
    self::$_signupActType = CRM_Utils_Array::key('Membership Signup', $activityTypes);
  }

  /**
   * Get all Cancelled Membership(s) for a contact
   *
   * @param int $contactID
   *   Contact id.
   * @param bool $isTest
   *   Mode of payment.
   *
   * @return array
   *   Array of membership type
   */
  public static function getContactsCancelledMembership($contactID, $isTest = FALSE) {
    if (!$contactID) {
      return [];
    }
    $query = 'SELECT membership_type_id FROM civicrm_membership WHERE contact_id = %1 AND status_id = %2 AND is_test = %3';
    $queryParams = [
      1 => [$contactID, 'Integer'],
      2 => [
        // CRM-15475
        array_search(
          'Cancelled',
          CRM_Member_PseudoConstant::membershipStatus(
            NULL,
            " name = 'Cancelled' ",
            'name',
            FALSE,
            TRUE
          )
        ),
        'Integer',
      ],
      3 => [$isTest, 'Boolean'],
    ];

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $cancelledMembershipIds = [];
    while ($dao->fetch()) {
      $cancelledMembershipIds[] = $dao->membership_type_id;
    }
    return $cancelledMembershipIds;
  }

  /**
   * Merges the memberships from otherContactID to mainContactID.
   *
   * General idea is to merge memberships in regards to their type. We
   * move the other contact’s contributions to the main contact’s
   * membership which has the same type (if any) and then we update
   * membership to avoid loosing `join_date`, `end_date`, and
   * `status_id`. In this function, we don’t touch the contributions
   * directly (CRM_Dedupe_Merger::moveContactBelongings() takes care
   * of it).
   *
   * This function adds new SQL queries to the $sqlQueries parameter.
   *
   * @param int $mainContactID
   *   Contact id of main contact record.
   * @param int $otherContactID
   *   Contact id of record which is going to merge.
   * @param array $sqlQueries
   *   (reference) array of SQL queries to be executed.
   * @param array $tables
   *   List of tables that have to be merged.
   * @param array $tableOperations
   *   Special options/params for some tables to be merged.
   *
   * @see CRM_Dedupe_Merger::cpTables()
   */
  public static function mergeMemberships($mainContactID, $otherContactID, &$sqlQueries, $tables, $tableOperations) {
    /*
     * If the user requests not to merge memberships but to add them,
     * just attribute the `civicrm_membership` to the
     * `$mainContactID`. We have to do this here since the general
     * merge process is bypassed by this function.
     */
    if (array_key_exists("civicrm_membership", $tableOperations) && $tableOperations['civicrm_membership']['add']) {
      $sqlQueries[] = "UPDATE IGNORE civicrm_membership SET contact_id = $mainContactID WHERE contact_id = $otherContactID";
      return;
    }

    /*
     * Retrieve all memberships that belongs to each contacts and
     * keep track of each membership type.
     */
    $mainContactMemberships = [];
    $otherContactMemberships = [];

    $sql = "SELECT id, membership_type_id FROM civicrm_membership membership WHERE contact_id = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$mainContactID, "Integer"]]);
    while ($dao->fetch()) {
      $mainContactMemberships[$dao->id] = $dao->membership_type_id;
    }

    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$otherContactID, "Integer"]]);
    while ($dao->fetch()) {
      $otherContactMemberships[$dao->id] = $dao->membership_type_id;
    }

    /*
     * For each membership, move related contributions to the main
     * contact’s membership (by updating `membership_payments`). Then,
     * update membership’s `join_date` (if the other membership’s
     * join_date is older) and `end_date` (if the other membership’s
     * `end_date` is newer) and `status_id` (if the newly calculated
     * status is different).
     *
     * FIXME: what should we do if we have multiple memberships with
     * the same type (currently we only take the first one)?
     */
    $newSql = [];
    foreach ($otherContactMemberships as $otherMembershipId => $otherMembershipTypeId) {
      if ($newMembershipId = array_search($otherMembershipTypeId, $mainContactMemberships)) {

        /*
         * Move other membership’s contributions to the main one only
         * if user requested to merge contributions.
         */
        if (!empty($tables) && in_array('civicrm_contribution', $tables)) {
          $newSql[] = "UPDATE civicrm_membership_payment SET membership_id=$newMembershipId WHERE membership_id=$otherMembershipId";
        }

        $sql = "SELECT * FROM civicrm_membership membership WHERE id = %1";

        $newMembership = CRM_Member_DAO_Membership::findById($newMembershipId);
        $otherMembership = CRM_Member_DAO_Membership::findById($otherMembershipId);

        $updates = [];
        if (new DateTime($otherMembership->join_date) < new DateTime($newMembership->join_date)) {
          $updates["join_date"] = $otherMembership->join_date;
        }

        if (new DateTime($otherMembership->end_date) > new DateTime($newMembership->end_date)) {
          $updates["end_date"] = $otherMembership->end_date;
        }

        if (count($updates)) {

          /*
           * Update status
           */
          $status = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(
            $updates["start_date"] ?? $newMembership->start_date,
            $updates["end_date"] ?? $newMembership->end_date,
            $updates["join_date"] ?? $newMembership->join_date,
            'now',
            FALSE,
            $newMembershipId,
            (array) $newMembership
          );

          if (!empty($status['id']) and $status['id'] != $newMembership->status_id) {
            $updates['status_id'] = $status['id'];
          }

          $updates_sql = [];
          foreach ($updates as $k => $v) {
            $updates_sql[] = "$k = '{$v}'";
          }

          $newSql[] = sprintf("UPDATE civicrm_membership SET %s WHERE id=%s", implode(", ", $updates_sql), $newMembershipId);
          $newSql[] = sprintf("DELETE FROM civicrm_membership WHERE id=%s", $otherMembershipId);
        }

      }
    }

    $sqlQueries = array_merge($sqlQueries, $newSql);
  }

  /**
   * Update membership status to deceased.
   * function return the status message for updated membership.
   *
   * @param array $deceasedParams
   *  - contact id
   *  - is_deceased
   *  - deceased_date
   *
   * @return null|string
   *   $updateMembershipMsg string  status message for updated membership.
   */
  public static function updateMembershipStatus($deceasedParams) {
    $updateMembershipMsg = NULL;
    $contactId = $deceasedParams['contact_id'];
    $deceasedDate = $deceasedParams['deceased_date'];

    // process to set membership status to deceased for both active/inactive membership
    if ($contactId && !empty($deceasedParams['is_deceased'])) {
      $userId = CRM_Core_Session::getLoggedInContactID() ?: $contactId;

      // get deceased status id
      $allStatus = CRM_Member_PseudoConstant::membershipStatus();
      $deceasedStatusId = array_search('Deceased', $allStatus);
      if (!$deceasedStatusId) {
        return $updateMembershipMsg;
      }

      $today = CRM_Utils_Time::time();
      if ($deceasedDate && CRM_Utils_Time::strtotime($deceasedDate) > $today) {
        return $updateMembershipMsg;
      }

      // get non deceased membership
      $dao = new CRM_Member_DAO_Membership();
      $dao->contact_id = $contactId;
      $dao->whereAdd("status_id != $deceasedStatusId");
      $dao->find();
      $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
      $allStatus = CRM_Member_PseudoConstant::membershipStatus();
      $memCount = 0;
      while ($dao->fetch()) {
        // update status to deceased (for both active/inactive membership )
        CRM_Core_DAO::setFieldValue('CRM_Member_DAO_Membership', $dao->id,
          'status_id', $deceasedStatusId
        );

        // add membership log
        $membershipLog = [
          'membership_id' => $dao->id,
          'status_id' => $deceasedStatusId,
          'start_date' => CRM_Utils_Date::isoToMysql($dao->start_date),
          'end_date' => CRM_Utils_Date::isoToMysql($dao->end_date),
          'modified_id' => $userId,
          'modified_date' => CRM_Utils_Time::date('YmdHis'),
          'membership_type_id' => $dao->membership_type_id,
          'max_related' => $dao->max_related,
        ];

        CRM_Member_BAO_MembershipLog::add($membershipLog);

        //create activity when membership status is changed
        $activityParam = [
          'subject' => "Status changed from {$allStatus[$dao->status_id]} to {$allStatus[$deceasedStatusId]}",
          'source_contact_id' => $userId,
          'target_contact_id' => $dao->contact_id,
          'source_record_id' => $dao->id,
          'activity_type_id' => array_search('Change Membership Status', $activityTypes),
          'status_id' => 2,
          'priority_id' => 2,
          'activity_date_time' => CRM_Utils_Time::date('Y-m-d H:i:s'),
          'is_auto' => 0,
          'is_current_revision' => 1,
          'is_deleted' => 0,
        ];
        civicrm_api3('activity', 'create', $activityParam);

        $memCount++;
      }

      // set status msg
      if ($memCount) {
        CRM_Core_Session::setStatus(ts("%1 Current membership(s) for this contact have been set to 'Deceased' status.",
          [1 => $memCount]
        ));
      }
    }
    return $updateMembershipMsg;
  }

  /**
   * Create the "Change Membership Status" activity.
   * This was embedded deep in the ::add() function and various other places.
   * Extracted here to it's own function so we have a single place to create it.
   *
   * @param \CRM_Core_DAO|\CRM_Member_DAO_Membership $membership
   * @param string $oldStatusLabel
   * @param string $newStatusLabel
   * @param int $changedByContactID
   *
   * @return void
   * @throws \CRM_Core_Exception
   *
   * @internal Signature may change
   */
  private static function createChangeMembershipStatusActivity($membership, string $oldStatusLabel, string $newStatusLabel, int $changedByContactID) {
    CRM_Activity_BAO_Activity::addActivity($membership,
      'Change Membership Status',
      NULL,
      [
        'subject' => "Status changed from {$oldStatusLabel} to {$newStatusLabel}",
        'source_contact_id' => $changedByContactID,
        'priority_id' => 'Normal',
      ]
    );
  }

  /**
   *  Create the MembershipLog record.
   *  This was embedded deep in the ::add() function.
   *  Extracted here to it's own function so we have a single place to create it.
   *
   * @param CRM_Core_DAO $membership
   * @param string $logStartDate
   * @param ?int $membershipTypeID
   * @param ?int $modifiedContactID
   *
   * @return array
   *
   * @internal Signature may change
   */
  private static function createMembershipLog($membership, string $logStartDate = '', ?int $membershipTypeID = NULL, ?int $modifiedContactID = NULL): array {
    if (empty($logStartDate)) {
      $logStartDate = CRM_Utils_Date::isoToMysql($membership->start_date);
    }
    $membershipLog = [
      'membership_id' => $membership->id,
      'status_id' => $membership->status_id,
      'start_date' => $logStartDate,
      'end_date' => CRM_Utils_Date::isoToMysql($membership->end_date),
      'modified_date' => CRM_Utils_Time::date('YmdHis'),
      'membership_type_id' => $membershipTypeID ?? $membership->membership_type_id,
      'max_related' => $membership->max_related,
    ];

    if (!empty($modifiedContactID)) {
      $membershipLog['modified_id'] = $modifiedContactID;
    }
    // If we have an authenticated session, set modified_id to that user's contact_id, else set to membership.contact_id
    elseif (CRM_Core_Session::getLoggedInContactID()) {
      $membershipLog['modified_id'] = CRM_Core_Session::getLoggedInContactID();
    }
    else {
      $membershipLog['modified_id'] = $membership->contact_id;
    }
    // @todo maybe move this to an API4 call, or writeRecord()
    CRM_Member_BAO_MembershipLog::add($membershipLog);
    return $membershipLog;
  }

}
