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
 * $Id$
 *
 */
class CRM_Member_BAO_Membership extends CRM_Member_DAO_Membership {

  /**
   * static field for all the membership information that we can potentially import
   *
   * @var array
   * @static
   */
  static $_importableFields = NULL;

  static $_renewalActType = NULL;

  static $_signupActType = NULL;

  /**
   * class constructor
   *
   * @access public
   * @return \CRM_Member_DAO_Membership
   */
  /**
   *
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * takes an associative array and creates a membership object
   *
   * the function extracts all the params it needs to initialize the created
   * membership object. The params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   *
   * @return object CRM_Member_BAO_Membership object
   * @access public
   * @static
   */
  static function add(&$params, $ids = array()) {
    $oldStatus = $oldType = NULL;
     if (!empty($ids['membership'])) {
      CRM_Utils_Hook::pre('edit', 'Membership', $ids['membership'], $params);

      $membershipObj     = new CRM_Member_DAO_Membership();
      $membershipObj->id = $ids['membership'];
      $membershipObj->find();
      while ($membershipObj->fetch()) {
        $oldStatus = $membershipObj->status_id;
        $oldType = $membershipObj->membership_type_id;
      }
    }
    else {
      CRM_Utils_Hook::pre('create', 'Membership', NULL, $params);
    }

    if (array_key_exists('is_override', $params) && !$params['is_override']) {
      $params['is_override'] = 'null';
    }

    $membership = new CRM_Member_BAO_Membership();
    $membership->copyValues($params);
    $membership->id = CRM_Utils_Array::value('membership', $ids);

    $membership->save();
    $membership->free();

    if (empty($membership->contact_id) || empty($membership->status_id)) {
      // this means we are in renewal mode and are just updating the membership
      // record or this is an API update call and all fields are not present in the update record
      // however the hooks dont care and want all data CRM-7784
      $tempMembership = new CRM_Member_DAO_Membership();
      $tempMembership->id = $membership->id;
      $tempMembership->find(TRUE);
      $membership = $tempMembership;
    }

    //get the log start date.
    //it is set during renewal of membership.
    $logStartDate = CRM_Utils_Array::value('log_start_date', $params);
    $logStartDate = ($logStartDate) ? CRM_Utils_Date::isoToMysql($logStartDate) : CRM_Utils_Date::isoToMysql($membership->start_date);
    $values       = self::getStatusANDTypeValues($membership->id);

    $membershipLog = array(
      'membership_id' => $membership->id,
      'status_id' => $membership->status_id,
      'start_date' => $logStartDate,
      'end_date' => CRM_Utils_Date::isoToMysql($membership->end_date),
      'modified_date' => date('Ymd'),
      'membership_type_id' => $values[$membership->id]['membership_type_id'],
      'max_related' => $membership->max_related,
    );

    $session = CRM_Core_Session::singleton();
    // If we have an authenticated session, set modified_id to that user's contact_id, else set to membership.contact_id
    if ($session->get('userID')) {
      $membershipLog['modified_id'] = $session->get('userID');
    }
    elseif (!empty($ids['userId'])) {
      $membershipLog['modified_id'] = $ids['userId'];
    }
    else {
      $membershipLog['modified_id'] = $membership->contact_id;
    }

    CRM_Member_BAO_MembershipLog::add($membershipLog, CRM_Core_DAO::$_nullArray);

    // reset the group contact cache since smart groups might be affected due to this
    CRM_Contact_BAO_GroupContactCache::remove();

    if (!empty($ids['membership'])) {
      if ($membership->status_id != $oldStatus) {
        $allStatus = CRM_Member_BAO_Membership::buildOptions('status_id', 'get');
        $activityParam = array(
          'subject' => "Status changed from {$allStatus[$oldStatus]} to {$allStatus[$membership->status_id]}",
          'source_contact_id' => $membershipLog['modified_id'],
          'target_contact_id' => $membership->contact_id,
          'source_record_id' => $membership->id,
          'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Membership Status'),
          'status_id' => 2,
          'priority_id' => 2,
          'activity_date_time' => date('Y-m-d H:i:s'),
        );
        civicrm_api3('activity', 'create', $activityParam);
      }
      if (isset($membership->membership_type_id) && $membership->membership_type_id != $oldType) {
        $membershipTypes = CRM_Member_BAO_Membership::buildOptions('membership_type_id', 'get');
        $activityParam = array(
          'subject' => "Type changed from {$membershipTypes[$oldType]} to {$membershipTypes[$membership->membership_type_id]}",
          'source_contact_id' => $membershipLog['modified_id'],
          'target_contact_id' => $membership->contact_id,
          'source_record_id' => $membership->id,
          'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Change Membership Type'),
          'status_id' => 2,
          'priority_id' => 2,
          'activity_date_time' => date('Y-m-d H:i:s'),
        );
        civicrm_api3('activity', 'create', $activityParam);
      }
      CRM_Utils_Hook::post('edit', 'Membership', $membership->id, $membership);
    }
    else {
      CRM_Utils_Hook::post('create', 'Membership', $membership->id, $membership);
    }

    return $membership;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array   $params input parameters to find object
   * @param array   $values output values of the object
   * @param boolean $active do you want only active memberships to
   *                        be returned
   *
   * @return CRM_Member_BAO_Membership|null the found object or null
   * @access public
   * @static
   */
  static function &getValues(&$params, &$values, $active = FALSE) {
    if (empty($params)) {
      return NULL;
    }
    $membership = new CRM_Member_BAO_Membership();

    $membership->copyValues($params);
    $membership->find();
    $memberships = array();
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
   * takes an associative array and creates a membership object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $ids the array that holds all the db ids
   * @param bool $skipRedirect
   * @param string $activityType
   *
   * @throws CRM_Core_Exception
   * @internal param bool $callFromAPI Is this function called from API?
   *
   * @return CRM_Member_BAO_Membership object
   * @access public
   * @static
   */
  static function create(&$params, &$ids, $skipRedirect = FALSE, $activityType = 'Membership Signup') {
    // always calculate status if is_override/skipStatusCal is not true.
    // giving respect to is_override during import.  CRM-4012

    // To skip status calculation we should use 'skipStatusCal'.
    // eg pay later membership, membership update cron CRM-3984

    if (empty($params['is_override']) && empty($params['skipStatusCal'])) {
      $dates = array('start_date', 'end_date', 'join_date');
      $start_date = $end_date = $join_date = NULL; // declare these out of courtesy as IDEs don't pick up the setting of them below
      foreach ($dates as $date) {
        $$date = $params[$date] = CRM_Utils_Date::processDate(CRM_Utils_Array::value($date, $params), NULL, TRUE, 'Ymd');
      }

      //fix for CRM-3570, during import exclude the statuses those having is_admin = 1
      $excludeIsAdmin = CRM_Utils_Array::value('exclude_is_admin', $params, FALSE);

      //CRM-3724 always skip is_admin if is_override != true.
      if (!$excludeIsAdmin && empty($params['is_override'])) {
        $excludeIsAdmin = TRUE;
      }

      $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($start_date, $end_date, $join_date,
        'today', $excludeIsAdmin, CRM_Utils_Array::value('membership_type_id', $params), $params
      );
      if (empty($calcStatus)) {
        // Redirect the form in case of error
        // @todo this redirect in the BAO layer is really bad & should be moved to the form layer
        // however since we have no idea how (if) this is triggered we can't safely move / remove it
        // NB I tried really hard to trigger this error from backoffice membership form in order to test it
        // and am convinced form validation is complete on that form WRT this error.
        $errorParams = array(
          'message_title' => ts('No valid membership status for given dates.'),
          'legacy_redirect_path' => 'civicrm/contact/view',
          'legacy_redirect_query' => "reset=1&force=1&cid={$params['contact_id']}&selectedChild=member",
        );
        throw new CRM_Core_Exception(ts('The membership cannot be saved because the status cannot be calculated.'), 0, $errorParams);
      }
      $params['status_id'] = $calcStatus['id'];
    }

    // data cleanup only: all verifications on number of related memberships are done upstream in:
    //    CRM_Member_BAO_Membership::createRelatedMemberships()
    //    CRM_Contact_BAO_Relationship::relatedMemberships()
    if (isset($params['owner_membership_id'])) {
      unset($params['max_related']);
    }
    else {
      // if membership allows related, default max_related to value in membership_type
      if (!array_key_exists('max_related', $params) && !empty($params['membership_type_id'])) {
        $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($params['membership_type_id']);
        if (isset($membershipType['relationship_type_id'])) {
          $params['max_related'] = CRM_Utils_Array::value('max_related', $membershipType);
        }
      }
    }

    $transaction = new CRM_Core_Transaction();

    $membership = self::add($params, $ids);

    if (is_a($membership, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $membership;
    }

    // add custom field values
    if (!empty($params['custom']) && is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_membership', $membership->id);
    }

    $params['membership_id'] = $membership->id;
    if (isset($ids['membership'])) {
      $ids['contribution'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipPayment',
        $ids['membership'],
        'contribution_id',
        'membership_id'
      );
    }

    $params['skipLineItem'] = TRUE;

    //record contribution for this membership
    if (!empty($params['contribution_status_id']) && empty($params['relate_contribution_id'])) {
      $memInfo = array_merge($params, array('membership_id' => $membership->id));
      $params['contribution'] = self::recordMembershipContribution($memInfo, $ids);
    }

    if (!empty($params['lineItems'])) {
      $params['line_item'] = $params['lineItems'];
    }

    //do cleanup line  items if membership edit the Membership type.
    if (empty($ids['contribution']) && !empty($ids['membership'])) {
      CRM_Price_BAO_LineItem::deleteLineItems($ids['membership'], 'civicrm_membership');
    }

    if (!empty($params['line_item']) && empty($ids['contribution'])) {
      CRM_Price_BAO_LineItem::processPriceSet($membership->id, $params['line_item'], CRM_Utils_Array::value('contribution', $params));
    }

    //insert payment record for this membership
    if (!empty($params['relate_contribution_id'])) {
      CRM_Member_BAO_MembershipPayment::create(array('membership_id' => $membership->id, 'contribution_id' => $params['relate_contribution_id']));
    }

    // add activity record only during create mode and renew mode
    // also add activity if status changed CRM-3984 and CRM-2521
    if (empty($ids['membership']) ||
      $activityType == 'Membership Renewal' || !empty($params['createActivity'])) {
      if (!empty($ids['membership'])) {
        $data = array();
        CRM_Core_DAO::commonRetrieveAll('CRM_Member_DAO_Membership',
          'id',
          $membership->id,
          $data,
          array('contact_id', 'membership_type_id', 'source')
        );

        $membership->contact_id = $data[$membership->id]['contact_id'];
        $membership->membership_type_id = $data[$membership->id]['membership_type_id'];
        $membership->source = CRM_Utils_Array::value('source', $data[$membership->id]);
      }

      // since we are going to create activity record w/
      // individual contact as a target in case of on behalf signup,
      // so get the copy of organization id, CRM-5551
      $realMembershipContactId = $membership->contact_id;

      // create activity source = individual, target = org CRM-4027
      $targetContactID = NULL;
      if (!empty($params['is_for_organization'])) {
        $targetContactID = $membership->contact_id;
        $membership->contact_id = CRM_Utils_Array::value('userId', $ids);
      }

      if (empty($membership->contact_id) && (!empty($membership->owner_membership_id))) {
        $membership->contact_id = $realMembershipContactId;
      }

      if (!empty($ids['membership']) && $activityType != 'Membership Signup') {
        CRM_Activity_BAO_Activity::addActivity($membership, $activityType, $targetContactID);
      } elseif (empty($ids['membership'])) {
        CRM_Activity_BAO_Activity::addActivity($membership, $activityType, $targetContactID);
      }

      // we might created activity record w/ individual
      // contact as target so update membership object w/
      // original organization id, CRM-5551
      $membership->contact_id = $realMembershipContactId;
    }

    $transaction->commit();

    self::createRelatedMemberships($params, $membership);

    // do not add to recent items for import, CRM-4399
    if (empty($params['skipRecentView'])) {
      $url = CRM_Utils_System::url('civicrm/contact/view/membership',
        "action=view&reset=1&id={$membership->id}&cid={$membership->contact_id}&context=home"
      );
      if(empty($membership->membership_type_id)){// ie in an update situation
        $membership->find(TRUE);
      }
      $membershipTypes = CRM_Member_PseudoConstant::membershipType();
      $title = CRM_Contact_BAO_Contact::displayName($membership->contact_id) . ' - ' . ts('Membership Type:') . ' ' . $membershipTypes[$membership->membership_type_id];

      $recentOther = array();
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

    return $membership;
  }

  /**
   * Function to check the membership extended through relationship
   *
   * @param int $membershipId membership id
   * @param int $contactId contact id
   *
   * @param integer $action
   *
   * @return Array    array of contact_id of all related contacts.
   * @static
   */
  static function checkMembershipRelationship($membershipId, $contactId, $action = CRM_Core_Action::ADD) {
    $contacts = array();
    $membershipTypeID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $membershipId, 'membership_type_id');

    $membershipType = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($membershipTypeID);
    $relationships = array();
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
        $relType = array('id' => $values['civicrm_relationship_type_id']);
        $relValues = array();
        CRM_Contact_BAO_RelationshipType::retrieve($relType, $relValues);
        // Check if contact's relationship type exists in membership type
        $relTypeDirs   = array();
        $relTypeIds    = explode(CRM_Core_DAO::VALUE_SEPARATOR, $membershipType['relationship_type_id']);
        $relDirections = explode(CRM_Core_DAO::VALUE_SEPARATOR, $membershipType['relationship_direction']);
        $bidirectional = FALSE;
        foreach ($relTypeIds as $key => $value) {
          $relTypeDirs[] = $value . '_' . $relDirections[$key];
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
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. We'll tweak this function to be more
   * full featured over a period of time. This is the inverse function of
   * create.  It also stores all the retrieved values in the default array
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the name / value pairs
   *                        in a hierarchical manner
   *
   * @internal param array $ids (reference) the array that holds all the db ids
   *
   * @return object CRM_Member_BAO_Membership object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $membership = new CRM_Member_DAO_Membership();

    $membership->copyValues($params);

    if ($membership->find(TRUE)) {
      CRM_Core_DAO::storeValues($membership, $defaults);

      //get the membership status and type values.
      $statusANDType = self::getStatusANDTypeValues($membership->id);
      foreach (array(
        'status', 'membership_type') as $fld) {
        $defaults[$fld] = CRM_Utils_Array::value($fld, $statusANDType[$membership->id]);
      }
      if (!empty($statusANDType[$membership->id]['is_current_member'])) {
        $defaults['active'] = TRUE;
      }

      $membership->free();

      return $membership;
    }

    return NULL;
  }

  /**
   *
   * Function to get membership status and membership type values
   *
   * @param int $membershipId membership id of values to return
   *
   * @return array of key value pairs
   * @access public
   */
  static function getStatusANDTypeValues($membershipId) {
    $values = array();
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
    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($membershipId, 'Positive')));
    $properties = array('status', 'status_id', 'membership_type', 'membership_type_id', 'is_current_member', 'relationship_type_id');
    while ($dao->fetch()) {
      foreach ($properties as $property) {
        $values[$dao->id][$property] = $dao->$property;
      }
    }

    return $values;
  }

  /**
   * Function to delete membership.
   * Wrapper for most delete calls. Use this unless you JUST want to delete related memberships w/o deleting the parent.
   *
   * @param int $membershipId membership id that needs to be deleted
   *
   * @static
   *
   * @return $results   no of deleted Membership on success, false otherwise
   * @access public
   */
  static function del($membershipId) {
    //delete related first and then delete parent.
    self::deleteRelatedMemberships($membershipId);
    return self::deleteMembership($membershipId);
  }

  /**
   * Function to delete membership.
   *
   * @param int $membershipId membership id that needs to be deleted
   *
   * @static
   *
   * @return $results   no of deleted Membership on success, false otherwise
   * @access public
   */
  static function deleteMembership($membershipId) {
    // CRM-12147, retrieve membership data before we delete it for hooks
    $params = array('id' => $membershipId);
    $memValues = array();
    $memberships = self::getValues($params, $memValues);

    $membership = $memberships[$membershipId];

    CRM_Utils_Hook::pre('delete', 'Membership', $membershipId, $memValues);

    $transaction = new CRM_Core_Transaction();

    $results = NULL;
    //delete activity record
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');

    $params = array();
    $deleteActivity = false;
    $membershipActivities =  array(
      'Membership Signup',
      'Membership Renewal',
      'Change Membership Status',
      'Change Membership Type',
      'Membership Renewal Reminder'
    );
    foreach($membershipActivities as $membershipActivity) {
      $activityId = array_search($membershipActivity, $activityTypes);
      if ($activityId) {
        $params['activity_type_id'][] = $activityId;
        $deleteActivity = true;
      }
    }
    if ($deleteActivity) {
      $params['source_record_id'] = $membershipId;
      CRM_Activity_BAO_Activity::deleteActivity($params);
    }
    self::deleteMembershipPayment($membershipId);

    $results = $membership->delete();
    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Membership', $membership->id, $membership);

    // delete the recently created Membership
    $membershipRecent = array(
      'id' => $membershipId,
      'type' => 'Membership',
    );
    CRM_Utils_Recent::del($membershipRecent);

    return $results;
  }

  /**
   * Function to delete related memberships
   *
   * @param int $ownerMembershipId
   * @param int $contactId
   *
   * @return null
   * @static
   */
  static function deleteRelatedMemberships($ownerMembershipId, $contactId = NULL) {
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
    $membership->free();
  }

  /**
   * Function to obtain active/inactive memberships from the list of memberships passed to it.
   *
   * @param array  $memberships membership records
   * @param string $status      active or inactive
   *
   * @return array $actives array of memberships based on status
   * @static
   * @access public
   */
  static function activeMembers($memberships, $status = 'active') {
    $actives = array();
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
   * Function to build Membership  Block in Contribution Pages
   *
   * @param object $form form object
   * @param $pageID
   * @param $cid
   * @param boolean $formItems
   * @param int $selectedMembershipTypeID selected membership id
   * @param boolean $thankPage thank you page
   * @param null $isTest
   *
   * @return bool Is this a separate membership payment
   * @internal param int $pageId contribution page id
   * @internal param bool $memContactId contact who is to be
   * checked for having a current membership for a particular membership
   *
   * @static
   */
  static function buildMembershipBlock(&$form,
    $pageID,
    $cid,
    $formItems = FALSE,
    $selectedMembershipTypeID = NULL,
    $thankPage = FALSE,
    $isTest = NULL
  ) {

    $separateMembershipPayment = FALSE;
    if ($form->_membershipBlock) {
      $form->_currentMemberships = array();

      $membershipBlock    = $form->_membershipBlock;
      $membershipTypeIds  = $membershipTypes = $radio = array();
      $membershipPriceset = (!empty($form->_priceSetId) && $form->_useForMember) ? TRUE : FALSE;

      $allowAutoRenewMembership = $autoRenewOption = FALSE;
      $autoRenewMembershipTypeOptions = array();

      $paymentProcessor = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, 'is_recur = 1');

      $separateMembershipPayment = CRM_Utils_Array::value('is_separate_payment', $membershipBlock);

      if ($membershipPriceset) {
        foreach ($form->_priceSet['fields'] as $pField) {
          if (empty($pField['options'])) {
            continue;
          }
          foreach ($pField['options'] as $opId => $opValues) {
            if (empty($opValues['membership_type_id'])) {
              continue;
            }
            $membershipTypeIds[$opValues['membership_type_id']] = $opValues['membership_type_id'];
          }
        }
      }
      elseif (!empty($membershipBlock['membership_types'])) {
        $membershipTypeIds = explode(',', $membershipBlock['membership_types']);
      }

      if (!empty($membershipTypeIds)) {
        //set status message if wrong membershipType is included in membershipBlock
        if (isset($form->_mid) && !$membershipPriceset) {
          $membershipTypeID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
            $form->_mid,
            'membership_type_id'
          );
          if (!in_array($membershipTypeID, $membershipTypeIds)) {
            CRM_Core_Session::setStatus(ts("Oops. The membership you're trying to renew appears to be invalid. Contact your site administrator if you need assistance. If you continue, you will be issued a new membership."), ts('Invalid Membership'), 'error');
          }
        }

        $membershipTypeValues = self::buildMembershipTypeValues($form, $membershipTypeIds);
        $form->_membershipTypeValues = $membershipTypeValues;
        $endDate = NULL;
        foreach ($membershipTypeIds as $value) {
          $memType = $membershipTypeValues[$value];
          if ($selectedMembershipTypeID != NULL) {
            if ($memType['id'] == $selectedMembershipTypeID) {
              $form->assign('minimum_fee',
                CRM_Utils_Array::value('minimum_fee', $memType)
              );
              $form->assign('membership_name', $memType['name']);
              if (!$thankPage && $cid) {
                $membership = new CRM_Member_DAO_Membership();
                $membership->contact_id = $cid;
                $membership->membership_type_id = $memType['id'];
                if ($membership->find(TRUE)) {
                  $form->assign('renewal_mode', TRUE);
                  $memType['current_membership'] = $membership->end_date;
                  $form->_currentMemberships[$membership->membership_type_id] = $membership->membership_type_id;
                }
              }
              $membershipTypes[] = $memType;
            }
          }
          elseif ($memType['is_active']) {
            $javascriptMethod = NULL;
            $allowAutoRenewOpt = 1;
            if (is_array($form->_paymentProcessors)){
              foreach ($form->_paymentProcessors as $id => $val) {
                if (!$val['is_recur']) {
              $allowAutoRenewOpt = 0;
                  continue;
            }
              }
            }

            $javascriptMethod = array('onclick' => "return showHideAutoRenew( this.value );");
            $autoRenewMembershipTypeOptions["autoRenewMembershipType_{$value}"] = (int)$allowAutoRenewOpt * CRM_Utils_Array::value($value, CRM_Utils_Array::value('auto_renew', $form->_membershipBlock));;

            if ($allowAutoRenewOpt) {
              $allowAutoRenewMembership = TRUE;
            }

            //add membership type.
            $radio[$memType['id']] = $form->createElement('radio', NULL, NULL, NULL,
              $memType['id'], $javascriptMethod
            );
            if ($cid) {
              $membership = new CRM_Member_DAO_Membership();
              $membership->contact_id = $cid;
              $membership->membership_type_id = $memType['id'];

              //show current membership, skip pending and cancelled membership records,
              //because we take first membership record id for renewal
              $membership->whereAdd('status_id != 5 AND status_id !=6');

              if (!is_null($isTest)) {
                $membership->is_test = $isTest;
              }

              //CRM-4297
              $membership->orderBy('end_date DESC');

              if ($membership->find(TRUE)) {
                if (!$membership->end_date) {
                  unset($radio[$memType['id']]);
                  $form->assign('islifetime', TRUE);
                  continue;
                }
                $form->assign('renewal_mode', TRUE);
                $form->_currentMemberships[$membership->membership_type_id] = $membership->membership_type_id;
                $memType['current_membership'] = $membership->end_date;
                if (!$endDate) {
                  $endDate = $memType['current_membership'];
                  $form->_defaultMemTypeId = $memType['id'];
                }
                if ($memType['current_membership'] < $endDate) {
                  $endDate = $memType['current_membership'];
                  $form->_defaultMemTypeId = $memType['id'];
                }
              }
            }
            $membershipTypes[] = $memType;
          }
        }
      }

      $form->assign('showRadio', $formItems);
      if ($formItems) {
        if (!$membershipPriceset) {
          if (!$membershipBlock['is_required']) {
            $form->assign('showRadioNoThanks', TRUE);
            $radio[''] = $form->createElement('radio', NULL, NULL, NULL, 'no_thanks', NULL);
            $form->addGroup($radio, 'selectMembership', NULL);
          }
          elseif ($membershipBlock['is_required'] && count($radio) == 1) {
            $temp = array_keys($radio);
            $form->add('hidden', 'selectMembership', $temp[0], array('id' => 'selectMembership'));
            $form->assign('singleMembership', TRUE);
            $form->assign('showRadio', FALSE);
          }
          else {
            $form->addGroup($radio, 'selectMembership', NULL);
          }

          $form->addRule('selectMembership', ts('Please select one of the memberships.'), 'required');
        }
        else {
          $autoRenewOption = CRM_Price_BAO_PriceSet::checkAutoRenewForPriceSet($form->_priceSetId);
          $form->assign('autoRenewOption', $autoRenewOption);
        }

        if (!$form->_values['is_pay_later'] && is_array($form->_paymentProcessors) && ($allowAutoRenewMembership || $autoRenewOption)) {
          $form->addElement('checkbox', 'auto_renew', ts('Please renew my membership automatically.'));
        }

      }

      $form->assign('membershipBlock', $membershipBlock);
      $form->assign('membershipTypes', $membershipTypes);
      $form->assign('allowAutoRenewMembership', $allowAutoRenewMembership);
      $form->assign('autoRenewMembershipTypeOptions', json_encode($autoRenewMembershipTypeOptions));

      //give preference to user submitted auto_renew value.
      $takeUserSubmittedAutoRenew = (!empty($_POST) || $form->isSubmitted()) ? TRUE : FALSE;
      $form->assign('takeUserSubmittedAutoRenew', $takeUserSubmittedAutoRenew);
    }

    return $separateMembershipPayment;
  }

  /**
   * Function to return Membership Block info in Contribution Pages
   *
   * @param $pageID
   *
   * @return array|null
   * @internal param int $pageId contribution page id
   *
   * @static
   */
  static function getMembershipBlock($pageID) {
    $membershipBlock   = array();
    $dao               = new CRM_Member_DAO_MembershipBlock();
    $dao->entity_table = 'civicrm_contribution_page';

    $dao->entity_id = $pageID;
    $dao->is_active = 1;
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $membershipBlock);
      if (!empty($membershipBlock['membership_types'])) {
        $membershipTypes = unserialize($membershipBlock['membership_types']);
        if (!is_array($membershipTypes)) {
          return $membershipBlock;
        }
        $memTypes = array();
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
   * Function to return a current membership of given contact
   *        NB: if more than one membership meets criteria, a randomly selected one is returned.
   *
   * @param int $contactID contact id
   * @param int $memType membership type, null to retrieve all types
   * @param int $isTest
   * @param null $membershipId
   * @param boolean $onlySameParentOrg true if only Memberships with same parent org as the $memType wanted, false otherwise
   *
   * @return array|bool
   * @internal param int $membershipID if provided, then determine if it is current
   * @static
   */
  static function getContactMembership($contactID, $memType, $isTest, $membershipId = NULL, $onlySameParentOrg = FALSE) {
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
      $dao->whereAdd('is_test IS NULL OR is_test = 0');
    }

    //avoid pending membership as current membership: CRM-3027
    $statusIds[] = array_search('Pending', CRM_Member_PseudoConstant::membershipStatus());
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
    $dao->whereAdd('status_id NOT IN ( ' . implode(',',  $statusIds) . ')');

    // order by start date to find most recent membership first, CRM-4545
    $dao->orderBy('start_date DESC');

    // CRM-8141
    if ($onlySameParentOrg && $memType) {
      // require the same parent org as the $memType
      $params = array('id' => $memType);
      $membershipType = array();
      if (CRM_Member_BAO_MembershipType::retrieve($params, $membershipType)) {
        $memberTypesSameParentOrg = CRM_Member_BAO_MembershipType::getMembershipTypesByOrg($membershipType['member_of_contact_id']);
        $memberTypesSameParentOrgList = implode(',', array_keys($memberTypesSameParentOrg));
        $dao->whereAdd('membership_type_id IN (' . $memberTypesSameParentOrgList . ')');
      }
    }

    if ($dao->find(TRUE)) {
      $membership = array();
      CRM_Core_DAO::storeValues($dao, $membership);
      $membership['is_current_member'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
        $membership['status_id'],
        'is_current_member', 'id'
      );
      return $membership;
    }

    // CRM-8141
    if ($onlySameParentOrg && $memType) {
      // see if there is a membership that has same parent as $memType but different parent than $membershipID

            if ( $dao->id && CRM_Core_Permission::check( 'edit memberships' ) ) {
                // CRM-10016, This is probably a backend renewal, and make sure we return the same membership thats being renewed.
                $dao->whereAdd ( );
            } else {
      unset($dao->id);
            }

      unset($dao->membership_type_id);
      if ($dao->find(TRUE)) {
        $membership = array();
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
   * Combine all the importable fields from the lower levels object
   *
   * @param string  $contactType contact type
   * @param boolean $status
   *
   * @return array array of importable Fields
   * @access public
   * @static
   */
  static function &importableFields($contactType = 'Individual', $status = TRUE) {
    if (!self::$_importableFields) {
      if (!self::$_importableFields) {
        self::$_importableFields = array();
      }

      if (!$status) {
        $fields = array('' => array('title' => '- ' . ts('do not import') . ' -'));
      }
      else {
        $fields = array('' => array('title' => '- ' . ts('Membership Fields') . ' -'));
      }

      $tmpFields = CRM_Member_DAO_Membership::import();
      $contactFields = CRM_Contact_BAO_Contact::importableFields($contactType, NULL);

      // Using new Dedupe rule.
      $ruleParams = array(
        'contact_type' => $contactType,
        'used'         => 'Unsupervised',
      );
      $fieldsArray = CRM_Dedupe_BAO_Rule::dedupeRuleFields($ruleParams);

      $tmpContactField = array();
      if (is_array($fieldsArray)) {
        foreach ($fieldsArray as $value) {
          $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
            $value,
            'id',
            'column_name'
          );
          $value = $customFieldId ? 'custom_' . $customFieldId : $value;
          $tmpContactField[trim($value)] = CRM_Utils_Array::value(trim($value), $contactFields);
          if (!$status) {
            $title = $tmpContactField[trim($value)]['title'] . " " . ts('(match to contact)');
          }
          else {
            $title = $tmpContactField[trim($value)]['title'];
          }
          $tmpContactField[trim($value)]['title'] = $title;
        }
      }
      $tmpContactField['external_identifier'] = $contactFields['external_identifier'];
      $tmpContactField['external_identifier']['title'] = $contactFields['external_identifier']['title'] . " " . ts('(match to contact)');

      $tmpFields['membership_contact_id']['title'] = $tmpFields['membership_contact_id']['title'] . " " . ts('(match to contact)');;

      $fields = array_merge($fields, $tmpContactField);
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));
      self::$_importableFields = $fields;
    }
    return self::$_importableFields;
  }

  /**
   * function to get all exportable fields
   *
   * @retun array return array of all exportable fields
   * @static
   */
  static function &exportableFields() {
    $expFieldMembership = CRM_Member_DAO_Membership::export();

    $expFieldsMemType = CRM_Member_DAO_MembershipType::export();
    $fields           = array_merge($expFieldMembership, $expFieldsMemType);
    $fields           = array_merge($fields, $expFieldMembership);
    $membershipStatus = array(
      'membership_status' => array('title' => 'Membership Status',
        'name' => 'membership_status',
        'type' => CRM_Utils_Type::T_STRING,
        'where' => 'civicrm_membership_status.name',
      ));
    //CRM-6161 fix for customdata export
    $fields = array_merge($fields, $membershipStatus, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));
    return $fields;
  }

  /**
   * Function to get membership joins/renewals for a specified membership
   * type.  Specifically, retrieves a count of memberships whose "Membership
   * Signup" or "Membership Renewal" activity falls in the given date range.
   * Dates match the pattern "yyyy-mm-dd".
   *
   * @param int $membershipTypeId membership type id
   * @param int $startDate date on which to start counting
   * @param int $endDate date on which to end counting
   * @param bool|int $isTest if true, membership is for a test site
   * @param bool|int $isOwner if true, only retrieve membership records for owners //LCD
   *
   * @return integer the number of members of type $membershipTypeId whose
   *         start_date is between $startDate and $endDate
   */
  //LCD
  public static function getMembershipStarts($membershipTypeId, $startDate, $endDate, $isTest = 0, $isOwner = 0) {

    $testClause = 'membership.is_test = 1';
    if (!$isTest) {
      $testClause = '( membership.is_test IS NULL OR membership.is_test = 0 )';
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

    $params = array(
      1 => array(self::$_signupActType, 'Integer'),
      2 => array(self::$_renewalActType, 'Integer'),
      3 => array($membershipTypeId, 'Integer'),
    );

    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);
    return (int)$memberCount;
  }

  /**
   * Function to get a count of membership for a specified membership type,
   * optionally for a specified date.  The date must have the form yyyy-mm-dd.
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
   * @param int $membershipTypeId membership type id
   * @param string $date the date for which to retrieve the count
   * @param bool|int $isTest if true, membership is for a test site
   * @param bool|int $isOwner if true, only retrieve membership records for owners //LCD
   *
   * @return returns the number of members of type $membershipTypeId as of
   *         $date.
   */
  public static function getMembershipCount($membershipTypeId, $date = NULL, $isTest = 0, $isOwner = 0) {
    if (!CRM_Utils_Rule::date($date)) {
      CRM_Core_Error::fatal(ts('Invalid date "%1" (must have form yyyy-mm-dd).', array(1 => $date)));
    }

    $params = array(1 => array($membershipTypeId, 'Integer'),
      2 => array($isTest, 'Boolean'),
    );
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
    return (int)$memberCount;
  }

  /**
   * Function check the status of the membership before adding membership for a contact
   *
   * @param int $contactId contact id
   *
   * @return int
   */
  static function statusAvailabilty($contactId) {
    $membership = new CRM_Member_DAO_MembershipStatus();
    $membership->whereAdd('is_active=1');
    return $membership->count();
  }

  /**
   * Process the Memberships
   *
   * @param array $membershipParams array of membership fields
   * @param int $contactID contact id
   * @param CRM_Contribute_Form_Contribution_Confirm $form Confirmation form object
   *
   * @param $premiumParams
   * @param null $customFieldsFormatted
   * @param null $includeFieldTypes
   *
   * @param array $membershipDetails
   *
   * @param array $membershipTypeIDs
   *
   * @param bool $isPaidMembership
   * @param array $membershipID
   *
   * @param $isProcessSeparateMembershipTransaction
   *
   * @param $defaultContributionTypeID
   * @param array $membershipLineItems Line items specific to membership payment that is separate to contribution
   * @throws CRM_Core_Exception
   *
   * @return void
   * @access public
   */
  public static function postProcessMembership($membershipParams, $contactID, &$form, $premiumParams,
    $customFieldsFormatted = NULL, $includeFieldTypes = NULL, $membershipDetails, $membershipTypeIDs, $isPaidMembership, $membershipID,
    $isProcessSeparateMembershipTransaction, $defaultContributionTypeID, $membershipLineItems) {
    $result      = $membershipContribution = NULL;
    $isTest      = CRM_Utils_Array::value('is_test', $membershipParams, FALSE);
    $errors = $createdMemberships = array();

    if ($isPaidMembership) {
      $result = CRM_Contribute_BAO_Contribution_Utils::processConfirm($form, $membershipParams,
        $premiumParams, $contactID,
        $defaultContributionTypeID,
        'membership'
      );
      if (is_a($result[1], 'CRM_Core_Error')) {
        $errors[1] = CRM_Core_Error::getMessages($result[1]);
      }
      elseif (!empty($result[1])) {
        // Save the contribution ID so that I can be used in email receipts
        // For example, if you need to generate a tax receipt for the donation only.
        $form->_values['contribution_other_id'] = $result[1]->id;
        //note that this will be over-written if we are using a separate membership transaction. Otherwise there is only one
        $membershipContribution = $result[1];
      }
    }

    if ($isProcessSeparateMembershipTransaction) {
      try {
        $lineItems = $form->_lineItem = $membershipLineItems;
        $membershipContribution = self::processSecondaryFinancialTransaction($contactID, $form, $membershipParams, $isTest, $membershipLineItems, CRM_Utils_Array::value('minimum_fee', $membershipDetails, 0), CRM_Utils_Array::value('financial_type_id', $membershipDetails));
      }
      catch (CRM_Core_Exception $e) {
        $errors[2] = $e->getMessage();
        $membershipContribution = NULL;
      }
    }

    $membership = NULL;
    if (!empty($membershipContribution) && !is_a($membershipContribution, 'CRM_Core_Error')) {
      $membershipContributionID = $membershipContribution->id;
    }

    //@todo - why is this nested so deep? it seems like it could be just set on the calling function on the form layer
    if (isset($membershipParams['onbehalf']) && !empty($membershipParams['onbehalf']['member_campaign_id'])) {
      $form->_params['campaign_id'] = $membershipParams['onbehalf']['member_campaign_id'];
    }
    //@todo it should no longer be possible for it to get to this point & membership to not be an array
    if (is_array($membershipTypeIDs) && !empty($membershipContributionID)) {
      $typesTerms = CRM_Utils_Array::value('types_terms', $membershipParams, array());
      foreach ($membershipTypeIDs as $memType) {
        $numTerms = CRM_Utils_Array::value($memType, $typesTerms, 1);
        $createdMemberships[$memType] = self::createOrRenewMembership($membershipParams, $contactID, $customFieldsFormatted, $membershipID, $memType, $isTest, $numTerms, $membershipContribution, $form);
      }
      if ($form->_priceSetId && !empty($form->_useForMember) && !empty($form->_lineItem)) {
        foreach ($form->_lineItem[$form->_priceSetId] as & $priceFieldOp) {
          if (!empty($priceFieldOp['membership_type_id']) &&
            isset($createdMemberships[$priceFieldOp['membership_type_id']])
          ) {
            $membershipOb = $createdMemberships[$priceFieldOp['membership_type_id']];
            $priceFieldOp['start_date'] = $membershipOb->start_date ? CRM_Utils_Date::customFormat($membershipOb->start_date, '%B %E%f, %Y') : '-';
            $priceFieldOp['end_date'] = $membershipOb->end_date ? CRM_Utils_Date::customFormat($membershipOb->end_date, '%B %E%f, %Y') : '-';
          }
          else {
            $priceFieldOp['start_date'] = $priceFieldOp['end_date'] = 'N/A';
          }
        }
        $form->_values['lineItem'] = $form->_lineItem;
        $form->assign('lineItem', $form->_lineItem);
      }
    }

    if (!empty($errors)) {
      $message = self::compileErrorMessage($errors);
      throw new CRM_Core_Exception($message);
    }
    $form->_params['createdMembershipIDs'] = array();

    // CRM-7851 - Moved after processing Payment Errors
    //@todo - the reasoning for this being here seems a little outdated
    foreach ($createdMemberships as $createdMembership) {
      CRM_Core_BAO_CustomValueTable::postProcess(
        $form->_params,
        CRM_Core_DAO::$_nullArray,
        'civicrm_membership',
        $createdMembership->id,
        'Membership'
      );
      $form->_params['createdMembershipIDs'][] = $createdMembership->id;
    }
    if(count($createdMemberships) == 1) {
      //presumably this is only relevant for exactly 1 membership
      $form->_params['membershipID'] = $createdMembership->id;
    }

    //CRM-15232: Check if membership is created and on the basis of it use
    //membership reciept template to send payment reciept
    if (count($createdMemberships)) {
      $form->_values['isMembership'] = TRUE;
    }
    if ($form->_contributeMode == 'notify') {
      if ($form->_values['is_monetary'] && $form->_amount > 0.0 && !$form->_params['is_pay_later']) {
        // call postProcess hook before leaving
        $form->postProcessHook();
        // this does not return
        $payment = CRM_Core_Payment::singleton($form->_mode, $form->_paymentProcessor, $form);
        $payment->doTransferCheckout($form->_params, 'contribute');
      }
    }

    if (isset($membershipContributionID)) {
      $form->_values['contribution_id'] = $membershipContributionID;
    }

    // Do not send an email if Recurring transaction is done via Direct Mode
    // Email will we sent when the IPN is received.
    if (!empty($form->_params['is_recur']) && $form->_contributeMode == 'direct') {
      if (!empty($membershipContribution->trxn_id)) {
        try {
          civicrm_api3('contribution', 'completetransaction', array('id' => $membershipContribution->id, 'trxn_id' => $membershipContribution->trxn_id));
        }
        catch (CiviCRM_API3_Exception $e) {
          // if for any reason it is already completed this will fail - e.g extensions hacking around core not completing transactions prior to CRM-15296
          // so let's be gentle here
          CRM_Core_Error::debug_log_message('contribution ' . $membershipContribution->id . ' not completed with trxn_id ' . $membershipContribution->trxn_id . ' and message ' . $e->getMessage());
        }
      }
      return;
    }

    //finally send an email receipt
    CRM_Contribute_BAO_ContributionPage::sendMail($contactID,
      $form->_values,
      $isTest, FALSE,
      $includeFieldTypes
    );
  }

  /**
   * Function for updating a membership record's contribution_recur_id
   *
   * @param object CRM_Member_DAO_Membership $membership
   * @param \CRM_Contribute_BAO_Contribution|\CRM_Contribute_DAO_Contribution $contribution
   *
   * @return void
   * @static
   * @access public
   */
  static public function updateRecurMembership(CRM_Member_DAO_Membership $membership,
    CRM_Contribute_BAO_Contribution $contribution) {

    if (empty($contribution->contribution_recur_id)) {
      return;
    }

    $params = array(
      1 => array($contribution->contribution_recur_id, 'Integer'),
      2 => array($membership->id, 'Integer'),
    );

    $sql = "UPDATE civicrm_membership SET contribution_recur_id = %1 WHERE id = %2";
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * @deprecated
   * A wrapper for renewing memberships from a form - including the form in the membership processing adds complexity
   * as the forms are being forced to pretend similarity
   * Try to call the renewMembership directly
   * @todo - this form method needs to have the interaction with the form layer removed from it
   * as a BAO function. Note that the api now supports membership renewals & it is not clear this function does anything
   * not done by the membership.create api (with a lot less unit tests)
   *
   * This method will renew / create the membership depending on
   * whether the given contact has a membership or not. And will add
   * the modified dates for membership and in the log table.
   *
   * @param int $contactID id of the contact
   * @param int $membershipTypeID id of the new membership type
   * @param boolean $is_test if this is test contribution or live contribution
   * @param object $form form object
   * @param null $changeToday
   * @param int $modifiedID individual contact id in case of On Behalf signup (CRM-4027 )
   * @param null $customFieldsFormatted
   * @param int $numRenewTerms how many membership terms are being added to end date (default is 1)
   *
   * @param integer $membershipID Membership ID, this should always be passed in & optionality should be removed
   *
   * @throws CRM_Core_Exception
   * @internal param array $ipnParams array of name value pairs, to be used (for e.g source) when $form not present
   * @return CRM_Member_DAO_Membership $membership          object of membership
   *
   * @static
   * @access public
   */
  static function renewMembershipFormWrapper(
    $contactID,
    $membershipTypeID,
    $is_test,
    &$form,
    $changeToday = NULL,
    $modifiedID = NULL,
    $customFieldsFormatted = NULL,
    $numRenewTerms = 1,
    $membershipID = NULL
  ) {
    $statusFormat = '%Y-%m-%d';
    $format       = '%Y%m%d';
    $ids          = array();
    //@todo would be better to make $membershipID a compulsory function param & make form layer responsible for extracting it
    if(!$membershipID && isset($form->_membershipId)) {
      $membershipID = $form->_membershipId;
    }

    //get all active statuses of membership.
    $allStatus = CRM_Member_PseudoConstant::membershipStatus();

    $membershipTypeDetails = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($membershipTypeID);

    // check is it pending. - CRM-4555
    list($pending, $contributionRecurID, $changeToday, $membershipSource, $isPayLater, $campaignId) = self::extractFormValues($form, $changeToday, $membershipTypeDetails);
    list($membership, $renewalMode, $dates) = self::renewMembership($contactID, $membershipTypeID, $is_test, $changeToday, $modifiedID, $customFieldsFormatted, $numRenewTerms, $membershipID, $pending, $allStatus, $membershipTypeDetails, $contributionRecurID, $format, $membershipSource, $ids, $statusFormat, $isPayLater, $campaignId);
    $form->set('renewal_mode', $renewalMode);
    if (!empty($dates)) {
      $form->assign('mem_start_date',
        CRM_Utils_Date::customFormat($dates['start_date'], $format)
      );
      $form->assign('mem_end_date',
        CRM_Utils_Date::customFormat($dates['end_date'], $format)
      );
    }
    return $membership;

  }

  /**
   * Method to fix membership status of stale membership
   *
   * This method first checks if the membership is stale. If it is,
   * then status will be updated based on existing start and end
   * dates and log will be added for the status change.
   *
   * @param  array  $currentMembership   reference to the array
   *                                     containing all values of
   *                                     the current membership
   * @param  array  $changeToday         array of month, day, year
   *                                     values in case today needs
   *                                     to be customised, null otherwise
   *
   * @return void
   * @static
   */
  static function fixMembershipStatusBeforeRenew(&$currentMembership, $changeToday) {
    $today = NULL;
    if ($changeToday) {
      $today = CRM_Utils_Date::processDate($changeToday, NULL, FALSE, 'Y-m-d');
    }

    $status = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(
      CRM_Utils_Array::value('start_date', $currentMembership),
      CRM_Utils_Array::value('end_date', $currentMembership),
      CRM_Utils_Array::value('join_date', $currentMembership),
      $today,
      TRUE,
      $currentMembership['membership_type_id'],
      $currentMembership
    );

    if (empty($status) ||
      empty($status['id'])
    ) {
      CRM_Core_Error::fatal(ts('Oops, it looks like there is no valid membership status corresponding to the membership start and end dates for this membership. Contact the site administrator for assistance.'));
    }

    $currentMembership['today_date'] = $today;

    if ($status['id'] !== $currentMembership['status_id']) {
      $memberDAO = new CRM_Member_DAO_Membership();
      $memberDAO->id = $currentMembership['id'];
      $memberDAO->find(TRUE);

      $memberDAO->status_id = $status['id'];
      $memberDAO->join_date = CRM_Utils_Date::isoToMysql($memberDAO->join_date);
      $memberDAO->start_date = CRM_Utils_Date::isoToMysql($memberDAO->start_date);
      $memberDAO->end_date = CRM_Utils_Date::isoToMysql($memberDAO->end_date);
      $memberDAO->save();
      CRM_Core_DAO::storeValues($memberDAO, $currentMembership);
      $memberDAO->free();

      $currentMembership['is_current_member'] = CRM_Core_DAO::getFieldValue(
        'CRM_Member_DAO_MembershipStatus',
        $currentMembership['status_id'],
        'is_current_member'
      );
      $format = '%Y%m%d';

      $logParams = array(
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
        'modified_date' => CRM_Utils_Date::customFormat(
          $currentMembership['today_date'],
          $format
        ),
        'membership_type_id' => $currentMembership['membership_type_id'],
        'max_related' => $currentMembership['max_related'],
      );

      $session = CRM_Core_Session::singleton();
      // If we have an authenticated session, set modified_id to that user's contact_id, else set to membership.contact_id
      if ($session->get('userID')) {
        $logParams['modified_id'] = $session->get('userID');
      }
      else {
        $logParams['modified_id'] = $currentMembership['contact_id'];
      }
      CRM_Member_BAO_MembershipLog::add($logParams, CRM_Core_DAO::$_nullArray);
    }
  }

  /**
   * Function to get the contribution page id from the membership record
   *
   * @param int membershipId membership id
   *
   * @return int $contributionPageId contribution page id
   * @access public
   * @static
   */
  static function getContributionPageId($membershipID) {
    $query = "
SELECT c.contribution_page_id as pageID
  FROM civicrm_membership_payment mp, civicrm_contribution c
 WHERE mp.contribution_id = c.id
   AND c.contribution_page_id IS NOT NULL
   AND mp.membership_id = " . CRM_Utils_Type::escape($membershipID, 'Integer')
   . " ORDER BY mp.id DESC";

    return CRM_Core_DAO::singleValueQuery($query,
      CRM_Core_DAO::$_nullArray
    );
  }

  /**
   * Function to updated related memberships
   *
   * @param int   $ownerMembershipId owner Membership Id
   * @param array $params            formatted array of key => value..
   * @static
   */
  static function updateRelatedMemberships($ownerMembershipId, $params) {
    $membership = new CRM_Member_DAO_Membership();
    $membership->owner_membership_id = $ownerMembershipId;
    $membership->find();

    while ($membership->fetch()) {
      $relatedMembership = new CRM_Member_DAO_Membership();
      $relatedMembership->id = $membership->id;
      $relatedMembership->copyValues($params);
      $relatedMembership->save();
      $relatedMembership->free();
    }

    $membership->free();
  }

  /**
   * Function to get list of membership fields for profile
   * For now we only allow custom membership fields to be in
   * profile
   *
   * @param null $mode
   *
   * @return array the list of membership fields
   * @static
   * @access public
   */
  static function getMembershipFields($mode = NULL) {
    $fields = CRM_Member_DAO_Membership::export();

    unset($fields['membership_contact_id']);
    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Membership'));

    $membershipType = CRM_Member_DAO_MembershipType::export();

    $membershipStatus = CRM_Member_DAO_MembershipStatus::export();

    $fields = array_merge($fields, $membershipType, $membershipStatus);

    return $fields;
  }

  /**
   * function to get the sort name of a contact for a particular membership
   *
   * @param  int    $id      id of the membership
   *
   * @return null|string     sort name of the contact if found
   * @static
   * @access public
   */
  static function sortName($id) {
    $id = CRM_Utils_Type::escape($id, 'Integer');

    $query = "
SELECT civicrm_contact.sort_name
FROM   civicrm_membership, civicrm_contact
WHERE  civicrm_membership.contact_id = civicrm_contact.id
  AND  civicrm_membership.id = {$id}
";
    return CRM_Core_DAO::singleValueQuery($query, CRM_Core_DAO::$_nullArray);
  }

  /**
   * function to create memberships for related contacts
   * takes into account the maximum related memberships
   *
   * @param  array $params array of key - value pairs
   * @param $dao
   *
   * @internal param object $membership membership object
   *
   * @return null|relatedMembership     array of memberships if created
   * @static
   * @access public
   */
  static function createRelatedMemberships(&$params, &$dao) {
    static $relatedContactIds = array();

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
    } else {
      $expiredStatusId = array_search('Expired', CRM_Member_PseudoConstant::membershipStatus());
    }

    $allRelatedContacts = array();
    $relatedContacts = array();
    if (!is_a($membership, 'CRM_Core_Error')) {
      $allRelatedContacts = CRM_Member_BAO_Membership::checkMembershipRelationship($membership->id,
        $membership->contact_id,
        CRM_Utils_Array::value('action', $params)
      );
    }

    // check for loops. CRM-4213
    // remove repeated related contacts, which already inherited membership.
    $relatedContactIds[$membership->contact_id] = TRUE;
    foreach ($allRelatedContacts as $cid => $status) {
      if (empty($relatedContactIds[$cid])) {
        $relatedContactIds[$cid] = TRUE;

        //don't create membership again for owner contact.
        $nestedRelationship = FALSE;
        if ($membership->owner_membership_id) {
          $nestedRelMembership = new CRM_Member_DAO_Membership();
          $nestedRelMembership->id = $membership->owner_membership_id;
          $nestedRelMembership->contact_id = $cid;
          $nestedRelationship = $nestedRelMembership->find(TRUE);
          $nestedRelMembership->free();
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
      if (is_array(CRM_Utils_Array::value('custom', $params))) {
        foreach ($params['custom'] as $k => $v) {
          unset($params['custom'][$k]['id']);
        }
      }
      if (!isset($params['membership_type_id'])) {
        $params['membership_type_id'] = $membership->membership_type_id;
      }

      // max_related should be set in the parent membership
      unset($params['max_related']);
      // Number of inherited memberships available - NULL is interpreted as unlimited, '0' as none
      $available = ($membership->max_related == NULL ? PHP_INT_MAX : $membership->max_related);
      $queue = array(); // will be used to queue potential memberships to be created

      foreach ($relatedContacts as $contactId => $relationshipStatus) {
        //use existing membership record.
        $relMembership = new CRM_Member_DAO_Membership();
        $relMembership->contact_id = $contactId;
        $relMembership->owner_membership_id = $membership->id;
        $relMemIds = array();
        if ($relMembership->find(TRUE)) {
          $params['id'] = $relMemIds['membership'] = $relMembership->id;
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
        elseif ((CRM_Utils_Array::value('action', $params) & CRM_Core_Action::UPDATE) &&
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

        // we should not created contribution record for related contacts, CRM-3371
        unset($params['contribution_status_id']);

        if (($params['status_id'] == $deceasedStatusId) || ($params['status_id'] == $expiredStatusId)) {
          // related membership is not active so does not count towards maximum
          CRM_Member_BAO_Membership::create($params, $relMemIds);
        }
        else {
          // related membership already exists, so this is just an update
          if (isset($params['id'])) {
            if ($available > 0) {
        CRM_Member_BAO_Membership::create($params, $relMemIds);
              $available --;
            } else { // we have run out of inherited memberships, so delete extras
              self::deleteMembership($params['id']);
            }
          // we need to first check if there will remain inherited memberships, so queue it up
          } else {
            $queue[] = $params;
          }
        }
      }
      // now go over the queue and create any available related memberships
      reset($queue);
      while (($available > 0) && ($params = each($queue))) {
        CRM_Member_BAO_Membership::create($params['value'], $relMemIds);
        $available --;
      }
    }
  }

  /**
   * Delete the record that are associated with this Membership Payment
   *
   * @param  int  $membershipId  membsership id.
   *
   * @return boolean  true if deleted false otherwise
   * @access public
   */
  static function deleteMembershipPayment($membershipId) {

    $membesrshipPayment = new CRM_Member_DAO_MembershipPayment();
    $membesrshipPayment->membership_id = $membershipId;
    $membesrshipPayment->find();

    while ($membesrshipPayment->fetch()) {
      CRM_Contribute_BAO_Contribution::deleteContribution($membesrshipPayment->contribution_id);
      CRM_Utils_Hook::pre('delete', 'MembershipPayment', $membesrshipPayment->id, $membesrshipPayment);
      $membesrshipPayment->delete();
      CRM_Utils_Hook::post('delete', 'MembershipPayment', $membesrshipPayment->id, $membesrshipPayment);
    }
    return $membesrshipPayment;
  }

  /**
   * @param $form
   * @param null $membershipTypeID
   *
   * @return array
   */
  static function &buildMembershipTypeValues(&$form, $membershipTypeID = NULL) {
    $whereClause = " WHERE domain_id = ". CRM_Core_Config::domainID();

    if (is_array($membershipTypeID)) {
      $allIDs = implode(',', $membershipTypeID);
      $whereClause .= " AND id IN ( $allIDs )";
    }
    elseif (is_numeric($membershipTypeID) &&
      $membershipTypeID > 0
    ) {
      $whereClause .= " AND id = $membershipTypeID";
    }

    $query = "
SELECT *
FROM   civicrm_membership_type
       $whereClause;
";
    $dao = CRM_Core_DAO::executeQuery($query);

    $membershipTypeValues = array();
    $membershipTypeFields = array(
      'id', 'minimum_fee', 'name', 'is_active',
      'description', 'financial_type_id', 'auto_renew','member_of_contact_id',
      'relationship_type_id', 'relationship_direction', 'max_related',
    );

    while ($dao->fetch()) {
      $membershipTypeValues[$dao->id] = array();
      foreach ($membershipTypeFields as $mtField) {
        $membershipTypeValues[$dao->id][$mtField] = $dao->$mtField;
      }
    }
    $dao->free();

    CRM_Utils_Hook::membershipTypeValues($form, $membershipTypeValues);

    if (is_numeric($membershipTypeID) &&
      $membershipTypeID > 0
    ) {
      return $membershipTypeValues[$membershipTypeID];
    }
    else {
      return $membershipTypeValues;
    }
  }

  /**
   * Function to get membership record count for a Contact
   *
   * @param $contactID
   * @param boolean $activeOnly
   *
   * @internal param int $contactId Contact ID
   * @return int count of membership records
   * @access public
   * @static
   */
  static function getContactMembershipCount($contactID, $activeOnly = FALSE) {
    $select = "SELECT count(*) FROM civicrm_membership ";
    $where  = "WHERE civicrm_membership.contact_id = {$contactID} AND civicrm_membership.is_test = 0 ";

    // CRM-6627, all status below 3 (active, pending, grace) are considered active
    if ($activeOnly) {
      $select .= " INNER JOIN civicrm_membership_status ON civicrm_membership.status_id = civicrm_membership_status.id ";
      $where  .= " and civicrm_membership_status.is_current_member = 1";
    }

    $query = $select . $where;
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Function to check whether payment processor supports
   * cancellation of membership subscription
   *
   * @param int $mid membership id
   *
   * @param bool $isNotCancelled
   *
   * @return boolean
   * @access public
   * @static
   */
  static function isCancelSubscriptionSupported($mid, $isNotCancelled = TRUE) {
    $cacheKeyString = "$mid";
    $cacheKeyString .= $isNotCancelled ? '_1' : '_0';

    static $supportsCancel = array();

    if (!array_key_exists($cacheKeyString, $supportsCancel)) {
      $supportsCancel[$cacheKeyString] = FALSE;
      $isCancelled = FALSE;

      if ($isNotCancelled) {
        $isCancelled = self::isSubscriptionCancelled($mid);
      }

      $paymentObject = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($mid, 'membership', 'obj');
      if (!empty($paymentObject)) {
        $supportsCancel[$cacheKeyString] = $paymentObject->isSupported('cancelSubscription') && !$isCancelled;
      }
    }
    return $supportsCancel[$cacheKeyString];
  }

  /**
   * Function to check whether subscription is already cancelled
   *
   * @param int $mid membership id
   *
   * @return string $status contribution status
   * @access public
   * @static
   */
  static function isSubscriptionCancelled($mid) {
    $sql = "
   SELECT cr.contribution_status_id
     FROM civicrm_contribution_recur cr
LEFT JOIN civicrm_membership mem ON ( cr.id = mem.contribution_recur_id )
    WHERE mem.id = %1 LIMIT 1";
    $params   = array(1 => array($mid, 'Integer'));
    $statusId = CRM_Core_DAO::singleValueQuery($sql, $params);
    $status   = CRM_Contribute_PseudoConstant::contributionStatus($statusId, 'name');
    if ($status == 'Cancelled') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to get membership joins for a specified membership
   * type.  Specifically, retrieves a count of still current memberships whose
   * join_date and start_date are within a specified date range.  Dates match
   * the pattern "yyyy-mm-dd".
   *
   * @param int $membershipTypeId membership type id
   * @param int $startDate date on which to start counting
   * @param int $endDate date on which to end counting
   * @param bool|int $isTest if true, membership is for a test site
   *
   * @return returns the number of members of type $membershipTypeId
   *         whose join_date is between $startDate and $endDate and
   *         whose start_date is between $startDate and $endDate
   */
  static function getMembershipJoins($membershipTypeId, $startDate, $endDate, $isTest = 0) {
    $testClause = 'membership.is_test = 1';
    if (!$isTest) {
      $testClause = '( membership.is_test IS NULL OR membership.is_test = 0 )';
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

    $params = array(
      1 => array(self::$_signupActType, 'Integer'),
      2 => array($membershipTypeId, 'Integer'),
    );

    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);

    return (int)$memberCount;
  }

  /**
   * Function to get membership renewals for a specified membership
   * type.  Specifically, retrieves a count of still current memberships
   * whose join_date is before and start_date is within a specified date
   * range.  Dates match the pattern "yyyy-mm-dd".
   *
   * @param int $membershipTypeId membership type id
   * @param int $startDate date on which to start counting
   * @param int $endDate date on which to end counting
   * @param bool|int $isTest if true, membership is for a test site
   *
   * @return integer returns the number of members of type $membershipTypeId
   *         whose join_date is before $startDate and
   *         whose start_date is between $startDate and $endDate
   */
  static function getMembershipRenewals($membershipTypeId, $startDate, $endDate, $isTest = 0) {
    $testClause = 'membership.is_test = 1';
    if (!$isTest) {
      $testClause = '( membership.is_test IS NULL OR membership.is_test = 0 )';
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

    $params = array(
      1 => array(self::$_renewalActType, 'Integer'),
      2 => array($membershipTypeId, 'Integer'),
    );
    $memberCount = CRM_Core_DAO::singleValueQuery($query, $params);

    return (int)$memberCount;
  }

  /**
   * Where a second separate financial transaction is supported we will process it here
   *
   * @param $contactID
   * @param CRM_Contribute_Form_Contribution_Confirm $form
   * @param $tempParams
   * @param $isTest
   *
   * @param $lineItems
   * @param $minimumFee
   * @param $financialTypeID
   *
   * @throws CRM_Core_Exception
   * @throws Exception
   * @internal param $membershipDetails
   * @return CRM_Contribute_BAO_Contribution
   */
  public static function processSecondaryFinancialTransaction($contactID, &$form, $tempParams, $isTest, $lineItems, $minimumFee, $financialTypeID) {
    $contributionType = new CRM_Financial_DAO_FinancialType();
    $contributionType->id = $financialTypeID;
    if (!$contributionType->find(TRUE)) {
      CRM_Core_Error::fatal(ts("Could not find a system table"));
    }
    $tempParams['amount'] = $minimumFee;
    $tempParams['invoiceID'] = md5(uniqid(rand(), TRUE));

    $result = NULL;
    if ($form->_values['is_monetary'] && !$form->_params['is_pay_later'] && $minimumFee > 0.0) {
      $payment = CRM_Core_Payment::singleton($form->_mode, $form->_paymentProcessor, $form);

      if ($form->_contributeMode == 'express') {
        $result = $payment->doExpressCheckout($tempParams);
      }
      else {
        $result = $payment->doDirectPayment($tempParams);
      }
    }

    if (is_a($result, 'CRM_Core_Error')) {
      throw new CRM_Core_Exception(CRM_Core_Error::getMessages($result));
    }
    else {
      //assign receive date when separate membership payment
      //and contribution amount not selected.
      if ($form->_amount == 0) {
        $now = date('YmdHis');
        $form->_params['receive_date'] = $now;
        $receiveDate = CRM_Utils_Date::mysqlToIso($now);
        $form->set('params', $form->_params);
        $form->assign('receive_date', $receiveDate);
      }

      $form->set('membership_trx_id', $result['trxn_id']);
      $form->set('membership_amount', $minimumFee);

      $form->assign('membership_trx_id', $result['trxn_id']);
      $form->assign('membership_amount', $minimumFee);

      // we don't need to create the user twice, so lets disable cms_create_account
      // irrespective of the value, CRM-2888
      $tempParams['cms_create_account'] = 0;

      $pending = $form->_params['is_pay_later'] ? (($minimumFee > 0.0) ? TRUE : FALSE) : FALSE;

      //set this variable as we are not creating pledge for
      //separate membership payment contribution.
      //so for differentiating membership contribution from
      //main contribution.
      $form->_params['separate_membership_payment'] = 1;
      $membershipContribution = CRM_Contribute_Form_Contribution_Confirm::processContribution($form,
        $tempParams,
        $result,
        $contactID,
        $contributionType,
        $pending,
        TRUE,
        $isTest,
        $lineItems
      );
      return $membershipContribution;
    }
  }

  /**
   * Create linkages between membership & contribution - note this is the wrong place for this code but this is a
   * refactoring step. This should be BAO functionality
   * @param $membership
   * @param $membershipContribution
   */
  public static function linkMembershipPayment($membership, $membershipContribution) {
    CRM_Member_BAO_MembershipPayment::create(array('membership_id' => $membership->id, 'contribution_id' => $membershipContribution->id));
  }

  /**
   * @param $membershipParams
   * @param $contactID
   * @param $customFieldsFormatted
   * @param $membershipID
   * @param $memType
   * @param $isTest
   * @param $numTerms
   * @param $membershipContribution
   * @param $form
   *
   * @internal param $createdMemberships
   *
   * @return array
   */
  public static function createOrRenewMembership($membershipParams, $contactID, $customFieldsFormatted, $membershipID, $memType, $isTest, $numTerms, $membershipContribution,  &$form) {
    $membership = self::renewMembershipFormWrapper($contactID, $memType,
      $isTest, $form, NULL,
      CRM_Utils_Array::value('cms_contactID', $membershipParams),
      $customFieldsFormatted, $numTerms,
      $membershipID
    );

    if (!empty($membershipContribution)) {
      // update recurring id for membership record
      self::updateRecurMembership($membership, $membershipContribution);

      self::linkMembershipPayment($membership, $membershipContribution);
    }
    return $membership;
  }

  /**
   * Turn array of errors into message string
   *
   * @param array $errors
   *
   * @internal param $message
   *
   * @return string
   */
  public static function compileErrorMessage($errors)
  {
    foreach($errors as $error) {
      if (is_string($error)) {
        $message[] = $error;
      }
    }
    return ts('Payment Processor Error message') . ': ' . implode('<br/>', $message);
  }

  /**
   * Extract relevant values from the form so we can separate form logic from BAO logcis
   * @param $form
   * @param $changeToday
   * @param $membershipTypeDetails
   *
   * @return array
   */
  public static function extractFormValues($form, $changeToday, $membershipTypeDetails)
  {
    $pending = FALSE;
    //@todo this is a BAO function & should not inspect the form - the form should do this
    // & pass required params to the BAO
    if (CRM_Utils_Array::value('minimum_fee', $membershipTypeDetails) > 0.0) {
      if (((isset($form->_contributeMode) && $form->_contributeMode == 'notify') || !empty($form->_params['is_pay_later']) ||
          (!empty($form->_params['is_recur']) && $form->_contributeMode == 'direct'
          )
        ) &&
        (($form->_values['is_monetary'] && $form->_amount > 0.0) || !empty($form->_params['separate_membership_payment']) ||
          CRM_Utils_Array::value('record_contribution', $form->_params)
        )
      ) {
        $pending = TRUE;
      }
    }
    $contributionRecurID = isset($form->_params['contributionRecurID']) ? $form->_params['contributionRecurID'] : NULL;

    //we renew expired membership, CRM-6277
    if (!$changeToday) {
      if ($form->get('renewalDate')) {
        $changeToday = $form->get('renewalDate');
      }
      elseif (get_class($form) == 'CRM_Contribute_Form_Contribution_Confirm') {
        $changeToday = date('YmdHis');
      }
    }

    $membershipSource = NULL;
    if (!empty($form->_params['membership_source'])) {
      $membershipSource = $form->_params['membership_source'];
    }
    elseif (isset($form->_values['title']) && !empty($form->_values['title'])) {
      $membershipSource = ts('Online Contribution:') . ' ' . $form->_values['title'];
    }
    $isPayLater = NULL;
    if(isset($form->_params)) {
      $isPayLater = CRM_Utils_Array::value('is_pay_later', $form->_params);
    }
    $campaignId = NULL;
    if (isset($form->_values) && is_array($form->_values) && !empty($form->_values)) {
      $campaignId = CRM_Utils_Array::value('campaign_id', $form->_params);
      if (!array_key_exists('campaign_id', $form->_params)) {
        $campaignId = CRM_Utils_Array::value('campaign_id', $form->_values);
      }
    }
    return array($pending, $contributionRecurID, $changeToday, $membershipSource, $isPayLater, $campaignId);
  }

  /**
   * @param integer $contactID
   * @param $membershipTypeID
   * @param bool $is_test
   * @param $changeToday
   * @param integer $modifiedID
   * @param $customFieldsFormatted
   * @param $numRenewTerms
   * @param $membershipID
   * @param $pending
   * @param $allStatus
   * @param array $membershipTypeDetails
   * @param integer $contributionRecurID
   * @param $format
   * @param $membershipSource
   * @param $ids
   * @param $statusFormat
   * @param $isPayLater
   * @param integer $campaignId
   *
   * @throws CRM_Core_Exception
   * @return array
   */
  public static function renewMembership($contactID, $membershipTypeID, $is_test, $changeToday, $modifiedID, $customFieldsFormatted, $numRenewTerms, $membershipID, $pending, $allStatus, $membershipTypeDetails, $contributionRecurID, $format, $membershipSource, $ids, $statusFormat, $isPayLater, $campaignId) {
    $renewalMode = $updateStatusId = FALSE;
    $dates = array();
    // CRM-7297 - allow membership type to be be changed during renewal so long as the parent org of new membershipType
    // is the same as the parent org of an existing membership of the contact
    $currentMembership = CRM_Member_BAO_Membership::getContactMembership($contactID, $membershipTypeID,
      $is_test, $membershipID, TRUE
    );
    if ($currentMembership) {
      $activityType = 'Membership Renewal';
      $renewalMode = TRUE;

      // Do NOT do anything.
      //1. membership with status : PENDING/CANCELLED (CRM-2395)
      //2. Paylater/IPN renew. CRM-4556.
      if ($pending || in_array($currentMembership['status_id'], array(array_search('Pending', $allStatus),
          // CRM-15475
          array_search('Cancelled', CRM_Member_PseudoConstant::membershipStatus(NULL, " name = 'Cancelled' ", 'name', FALSE, TRUE)),
        ))
      ) {
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $currentMembership['id'];
        $membership->find(TRUE);

        // CRM-8141 create a membership_log entry so that we will know the membership_type_id to change to when payment completed
        $format = '%Y%m%d';
        // note that we are logging the requested new membership_type_id that may be different than current membership_type_id
        // it will be used when payment is received to update the membership_type_id to what was paid for
        $logParams = array(
          'membership_id' => $membership->id,
          'status_id' => $membership->status_id,
          'start_date' => CRM_Utils_Date::customFormat(
            $membership->start_date,
            $format
          ),
          'end_date' => CRM_Utils_Date::customFormat(
            $membership->end_date,
            $format
          ),
          'modified_date' => CRM_Utils_Date::customFormat(
            date('Ymd'),
            $format
          ),
          'membership_type_id' => $membershipTypeID,
          'max_related' => !empty($membershipTypeDetails['max_related']) ? $membershipTypeDetails['max_related'] : NULL,
        );
        $session = CRM_Core_Session::singleton();
        // If we have an authenticated session, set modified_id to that user's contact_id, else set to membership.contact_id
        if ($session->get('userID')) {
          $logParams['modified_id'] = $session->get('userID');
        }
        else {
          $logParams['modified_id'] = $membership->contact_id;
        }
        CRM_Member_BAO_MembershipLog::add($logParams, CRM_Core_DAO::$_nullArray);

        if (!empty($contributionRecurID)) {
          CRM_Core_DAO::setFieldValue('CRM_Member_DAO_Membership', $membership->id,
            'contribution_recur_id', $contributionRecurID
          );
        }

        return array($membership, $renewalMode, $dates);
      }

      // Check and fix the membership if it is STALE
      self::fixMembershipStatusBeforeRenew($currentMembership, $changeToday);

      // Now Renew the membership
      if (!$currentMembership['is_current_member']) {
        // membership is not CURRENT

        // CRM-7297 Membership Upsell - calculate dates based on new membership type
        $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($currentMembership['id'],
          $changeToday,
          $membershipTypeID,
          $numRenewTerms
        );

        $currentMembership['join_date'] = CRM_Utils_Date::customFormat($currentMembership['join_date'], $format);
        $currentMembership['start_date'] = CRM_Utils_Array::value('start_date', $dates);
        $currentMembership['end_date'] = CRM_Utils_Array::value('end_date', $dates);
        $currentMembership['is_test'] = $is_test;

        if (!empty($membershipSource)) {
          $currentMembership['source'] = $membershipSource;
        }
        else {
          $currentMembership['source'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
            $currentMembership['id'],
            'source'
          );
        }

        if (!empty($currentMembership['id'])) {
          $ids['membership'] = $currentMembership['id'];
        }
        $memParams = $currentMembership;
        $memParams['membership_type_id'] = $membershipTypeID;

        //set the log start date.
        $memParams['log_start_date'] = CRM_Utils_Date::customFormat($dates['log_start_date'], $format);
      }
      else {

        // CURRENT Membership
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $currentMembership['id'];
        $membership->find(TRUE);
        // CRM-7297 Membership Upsell - calculate dates based on new membership type
        $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership->id,
          $changeToday,
          $membershipTypeID,
          $numRenewTerms
        );

        // Insert renewed dates for CURRENT membership
        $memParams = array();
        $memParams['join_date'] = CRM_Utils_Date::isoToMysql($membership->join_date);
        $memParams['start_date'] = CRM_Utils_Date::isoToMysql($membership->start_date);
        $memParams['end_date'] = CRM_Utils_Array::value('end_date', $dates);
        $memParams['membership_type_id'] = $membershipTypeID;

        //set the log start date.
        $memParams['log_start_date'] = CRM_Utils_Date::customFormat($dates['log_start_date'], $format);
        if (empty($membership->source)) {
          if (!empty($membershipSource)) {
            $memParams['source'] = $membershipSource;
          }
          else {
            $memParams['source'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership',
              $currentMembership['id'],
              'source'
            );
          }
        }

        if (!empty($currentMembership['id'])) {
          $ids['membership'] = $currentMembership['id'];
        }
      }
      //CRM-4555
      if ($pending) {
        $updateStatusId = array_search('Pending', $allStatus);
      }
    }
    else {
      // NEW Membership

      $activityType = 'Membership Signup';
      $memParams = array(
        'contact_id' => $contactID,
        'membership_type_id' => $membershipTypeID,
      );

      if (!$pending) {
        $dates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($membershipTypeID, NULL, NULL, NULL, $numRenewTerms);

        $memParams['join_date'] = CRM_Utils_Array::value('join_date', $dates);
        $memParams['start_date'] = CRM_Utils_Array::value('start_date', $dates);
        $memParams['end_date'] = CRM_Utils_Array::value('end_date', $dates);

        $status = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(CRM_Utils_Date::customFormat($dates['start_date'],
            $statusFormat
          ),
          CRM_Utils_Date::customFormat($dates['end_date'],
            $statusFormat
          ),
          CRM_Utils_Date::customFormat($dates['join_date'],
            $statusFormat
          ),
          'today',
          TRUE,
          $membershipTypeID,
          $memParams
        );
        $updateStatusId = CRM_Utils_Array::value('id', $status);
      }
      else {
        // if IPN/Pay-Later set status to: PENDING
        $updateStatusId = array_search('Pending', $allStatus);
      }

      if (!empty($membershipSource)) {
        $memParams['source'] = $membershipSource;
      }
      $memParams['contribution_recur_id'] = $contributionRecurID;

      $memParams['is_test'] = $is_test;
      $memParams['is_pay_later'] = $isPayLater;
    }

    //CRM-4555
    //if we decided status here and want to skip status
    //calculation in create( ); then need to pass 'skipStatusCal'.
    if ($updateStatusId) {
      $memParams['status_id'] = $updateStatusId;
      $memParams['skipStatusCal'] = TRUE;
    }

    //since we are renewing,
    //make status override false.
    $memParams['is_override'] = FALSE;

    //CRM-4027, create log w/ individual contact.
    if ($modifiedID) {
      $ids['userId'] = $modifiedID;
      $memParams['is_for_organization'] = TRUE;
    }
    else {
      $ids['userId'] = $contactID;
    }

    //inherit campaign from contrib page.
    if (isset($campaignId)) {
      $memParams['campaign_id'] = $campaignId;
    }

    $memParams['custom'] = $customFieldsFormatted;
    $membership = self::create($memParams, $ids, FALSE, $activityType);

    // not sure why this statement is here, seems quite odd :( - Lobo: 12/26/2010
    // related to: http://forum.civicrm.org/index.php/topic,11416.msg49072.html#msg49072
    $membership->find(TRUE);

    return array($membership, $renewalMode, $dates);
  }

  /**
   * Function to process price set and line items.
   *
   * @access public
   *
   * @param $membershipId
   * @param $lineItem
   *
   * @return void
   */
  function processPriceSet($membershipId, $lineItem) {
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
   * retrieve the contribution id for the associated Membership id
   * @todo we should get this off the line item
   *
   * @param  int  $membershipId  membership id.
   *
   * @return integer contribution id
   * @access public
   */
  static function getMembershipContributionId($membershipId) {

    $membershipPayment = new CRM_Member_DAO_MembershipPayment();
    $membershipPayment->membership_id = $membershipId;
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
   * @return array $result
     * @access public
     */
  static function updateAllMembershipStatus() {

    //get all active statuses of membership, CRM-3984
    $allStatus     = CRM_Member_PseudoConstant::membershipStatus();
    $statusLabels  = CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label');
    $allTypes      = CRM_Member_PseudoConstant::membershipType();

    // get only memberships with active membership types
    $query = "
SELECT     civicrm_membership.id                    as membership_id,
           civicrm_membership.is_override           as is_override,
           civicrm_membership.membership_type_id    as membership_type_id,
           civicrm_membership.status_id             as status_id,
           civicrm_membership.join_date             as join_date,
           civicrm_membership.start_date            as start_date,
           civicrm_membership.end_date              as end_date,
           civicrm_membership.source                as source,
           civicrm_contact.id                       as contact_id,
           civicrm_contact.is_deceased              as is_deceased,
           civicrm_membership.owner_membership_id   as owner_membership_id,
           civicrm_membership.contribution_recur_id as recur_id
FROM       civicrm_membership
INNER JOIN civicrm_contact ON ( civicrm_membership.contact_id = civicrm_contact.id )
INNER JOIN civicrm_membership_type ON
  (civicrm_membership.membership_type_id = civicrm_membership_type.id AND civicrm_membership_type.is_active = 1)
WHERE      civicrm_membership.is_test = 0";

    $params = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $processCount  = 0;
    $updateCount   = 0;

    $smarty = CRM_Core_Smarty::singleton();

    while ($dao->fetch()) {
      // echo ".";
      $processCount++;

      // Put common parameters into array for easy access
      $memberParams = array(
        'id' => $dao->membership_id,
        'status_id' => $dao->status_id,
        'contact_id' => $dao->contact_id,
        'membership_type_id' => $dao->membership_type_id,
        'membership_type' => $allTypes[$dao->membership_type_id],
        'join_date' => $dao->join_date,
        'start_date' => $dao->start_date,
        'end_date' => $dao->end_date,
        'source' => $dao->source,
        'skipStatusCal' => TRUE,
        'skipRecentView' => TRUE,
      );

      $smarty->assign_by_ref('memberParams', $memberParams);

      //update membership record to Deceased if contact is deceased
      if ($dao->is_deceased) {
        // check for 'Deceased' membership status, CRM-5636
        $deceaseStatusId = array_search('Deceased', $allStatus);
        if (!$deceaseStatusId) {
          CRM_Core_Error::fatal(ts("Deceased Membership status is missing or not active. <a href='%1'>Click here to check</a>.", array(1 => CRM_Utils_System::url('civicrm/admin/member/membershipStatus', 'reset=1'))));
        }

        //process only when status change.
        if ($dao->status_id != $deceaseStatusId) {
          //take all params that need to save.
          $deceasedMembership = $memberParams;
          $deceasedMembership['status_id'] = $deceaseStatusId;
          $deceasedMembership['createActivity'] = TRUE;
          $deceasedMembership['version'] = 3;

          //since there is change in status.
          $statusChange = array('status_id' => $deceaseStatusId);
          $smarty->append_by_ref('memberParams', $statusChange, TRUE);
          unset(
            $deceasedMembership['contact_id'],
            $deceasedMembership['membership_type_id'],
            $deceasedMembership['membership_type'],
            $deceasedMembership['join_date'],
            $deceasedMembership['start_date'],
            $deceasedMembership['end_date'],
            $deceasedMembership['source']
          );

          //process membership record.
          civicrm_api('membership', 'create', $deceasedMembership);
        }
        continue;
      }

      //we fetch related, since we need to check for deceased
      //now further processing is handle w/ main membership record.
      if ($dao->owner_membership_id) {
        continue;
      }

      //update membership records where status is NOT - Pending OR Cancelled.
      //as well as membership is not override.
      //skipping Expired membership records -> reduced extra processing( kiran )
      if (!$dao->is_override &&
        !in_array($dao->status_id, array(array_search('Pending', $allStatus),
            // CRM-15475
            array_search(
              'Cancelled', 
              CRM_Member_PseudoConstant::membershipStatus(NULL, " name = 'Cancelled' ", 'name', FALSE, TRUE)
            ),
            array_search('Expired', $allStatus),
          ))
      ) {

        // CRM-7248: added excludeIsAdmin param to the following fn call to prevent moving to admin statuses
        //get the membership status as per id.
        $newStatus = civicrm_api('membership_status', 'calc',
          array(
            'membership_id' => $dao->membership_id, 'version' => 3, 'ignore_admin_only'=> FALSE), TRUE
        );
        $statusId = CRM_Utils_Array::value('id', $newStatus);

        //process only when status change.
        if ($statusId &&
          $statusId != $dao->status_id
        ) {
          //take all params that need to save.
          $memParams = $memberParams;
          $memParams['status_id'] = $statusId;
          $memParams['createActivity'] = TRUE;
          $memParams['version'] = 3;

          // Unset columns which should remain unchanged from their current saved
          // values. This avoids race condition in which these values may have
          // been changed by other processes.
          unset(
            $memParams['contact_id'],
            $memParams['membership_type_id'],
            $memParams['membership_type'],
            $memParams['join_date'],
            $memParams['start_date'],
            $memParams['end_date'],
            $memParams['source']
          );
          //since there is change in status.
          $statusChange = array('status_id' => $statusId);
          $smarty->append_by_ref('memberParams', $statusChange, TRUE);

          //process member record.
          civicrm_api('membership', 'create', $memParams);
          $updateCount++;
        }
      }
      // CRM_Core_Error::debug( 'fEnd', count( $GLOBALS['_DB_DATAOBJECT']['RESULTS'] ) );
    }
    $result['is_error'] = 0;
    $result['messages'] = ts('Processed %1 membership records. Updated %2 records.', array(1 => $processCount, 2 => $updateCount));
    return $result;
  }

  /**
   * The function returns the membershiptypes for a particular contact
   * who has lifetime membership without end date.
   *
   * @param $contactID
   * @param bool $isTest
   * @param bool $onlyLifeTime
   *
   * @return array
   * @internal param array $contactMembershipType array of allMembershipTypes Key - value pairs
   */

  static function getAllContactMembership($contactID, $isTest = FALSE, $onlyLifeTime = FALSE) {
    $contactMembershipType = array();
    if (!$contactID) {
      return $contactMembershipType;
    }

    $dao             = new CRM_Member_DAO_Membership();
    $dao->contact_id = $contactID;
    $pendingStatusId = array_search('Pending', CRM_Member_PseudoConstant::membershipStatus());
    $dao->whereAdd("status_id != $pendingStatusId");

    if ($isTest) {
      $dao->is_test = $isTest;
    }
    else {
      $dao->whereAdd('is_test IS NULL OR is_test = 0');
    }

    if ($onlyLifeTime) {
      $dao->whereAdd('end_date IS NULL');
    }

    $dao->find();
    while ($dao->fetch()) {
      $membership = array();
      CRM_Core_DAO::storeValues($dao, $membership);
      $contactMembershipType[$dao->membership_type_id] = $membership;
    }
    return $contactMembershipType;
  }

  /**
   * Function to record contribution record associated with membership
   *
   * @param array  $params array of submitted params
   * @param array  $ids (param in process of being removed - try to use params)   array of ids
   *
   * @return CRM_Contribute_BAO_Contribution
   * @static
   */
  static function recordMembershipContribution( &$params, $ids = array()) {
    $membershipId = $params['membership_id'];
    $contributionParams = array();
    $config = CRM_Core_Config::singleton();
    $contributionParams['currency'] = $config->defaultCurrency;
    $contributionParams['receipt_date'] = (CRM_Utils_Array::value('receipt_date', $params)) ? $params['receipt_date'] : 'null';
    $contributionParams['source'] = CRM_Utils_Array::value('contribution_source', $params);
    $contributionParams['non_deductible_amount'] = 'null';
    $contributionParams['payment_processor'] = CRM_Utils_Array::value('payment_processor_id', $params);
    $contributionSoftParams = CRM_Utils_Array::value('soft_credit', $params);
    $recordContribution = array(
      'contact_id', 'total_amount', 'receive_date', 'financial_type_id',
      'payment_instrument_id', 'trxn_id', 'invoice_id', 'is_test',
      'contribution_status_id', 'check_number', 'campaign_id', 'is_pay_later',
      'membership_id', 'skipLineItem'
    );
    foreach ($recordContribution as $f) {
      $contributionParams[$f] = CRM_Utils_Array::value($f, $params);
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
      $contributionParams['line_item'] = CRM_Utils_Array::value('lineItems', $params, NULL);
    }

    $contribution = CRM_Contribute_BAO_Contribution::create($contributionParams, $ids);

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


    //insert payment record for this membership
    if (empty($ids['contribution']) || !empty($params['is_recur'])) {
      CRM_Member_BAO_MembershipPayment::create(array('membership_id' => $membershipId, 'contribution_id' => $contribution->id));
    }
    return $contribution;
  }

  /**
   * Function to record line items for default membership
   *
   * @param $qf object
   *
   * @param $membershipType array with membership type and organization
   *
   * @param $priceSetId
   *
   * @internal param $ $ priceSetId priceset id
   * @access public
   * @static
   */
  static function createLineItems(&$qf, $membershipType, &$priceSetId) {
    $qf->_priceSetId = $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', 'default_membership_type_amount', 'id', 'name');
    if ($priceSetId) {
      $qf->_priceSet = $priceSets = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
    }
    $editedFieldParams = array(
      'price_set_id' => $priceSetId,
      'name' => $membershipType[0],
    );
    $editedResults = array();
    CRM_Price_BAO_PriceField::retrieve($editedFieldParams, $editedResults);

    if (!empty($editedResults)) {
      unset($qf->_priceSet['fields']);
      $qf->_priceSet['fields'][$editedResults['id']] = $priceSets['fields'][$editedResults['id']];
      unset($qf->_priceSet['fields'][$editedResults['id']]['options']);
      $fid = $editedResults['id'];
      $editedFieldParams = array(
        'price_field_id' => $editedResults['id'],
        'membership_type_id' => $membershipType[1],
      );
      $editedResults = array();
      CRM_Price_BAO_PriceFieldValue::retrieve($editedFieldParams, $editedResults);
      $qf->_priceSet['fields'][$fid]['options'][$editedResults['id']] = $priceSets['fields'][$fid]['options'][$editedResults['id']];
      if (!empty($qf->_params['total_amount'])) {
        $qf->_priceSet['fields'][$fid]['options'][$editedResults['id']]['amount'] = $qf->_params['total_amount'];
      }
    }

    $fieldID = key($qf->_priceSet['fields']);
    $qf->_params['price_' . $fieldID] = CRM_Utils_Array::value('id', $editedResults);
  }

  /**
   * @todo document me - I seem a bit out of date....
   */
  static function _getActTypes() {
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    self::$_renewalActType = CRM_Utils_Array::key('Membership Renewal', $activityTypes);
    self::$_signupActType = CRM_Utils_Array::key('Membership Signup', $activityTypes);
  }

  /**
   * Get all Cancelled Membership(s) for a contact
   *
   * @param int    $contactID   contact id
   * @param boolean  $isTest    mode of payment
   *
   * @return array of membership type
   * @static
   * @access public
   */
  static function getContactsCancelledMembership($contactID, $isTest = FALSE) {
    if (!$contactID) {
      return array();
    }
    $query = 'SELECT membership_type_id FROM civicrm_membership WHERE contact_id = %1 AND status_id = %2 AND is_test = %3';
    $queryParams = array(
      1 => array($contactID, 'Integer'),
      2 => array(
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
        'Integer'
      ),
      3 => array($isTest, 'Boolean'),
    );

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $cancelledMembershipIds = array();
    while ($dao->fetch()) {
      $cancelledMembershipIds[] = $dao->membership_type_id;
    }
    return $cancelledMembershipIds;
  }
}
