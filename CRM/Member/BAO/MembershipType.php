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
 * $Id$
 *
 */
class CRM_Member_BAO_MembershipType extends CRM_Member_DAO_MembershipType {

  /**
   * Static holder for the default Membership Type.
   */
  static $_defaultMembershipType = NULL;

  static $_membershipTypeInfo = array();

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Member_BAO_MembershipType
   */
  public static function retrieve(&$params, &$defaults) {
    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->copyValues($params);
    if ($membershipType->find(TRUE)) {
      CRM_Core_DAO::storeValues($membershipType, $defaults);
      return $membershipType;
    }
    return NULL;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Member_DAO_MembershipType', $id, 'is_active', $is_active);
  }

  /**
   * Add the membership types.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Array contains the id (deprecated).
   *
   * @return \CRM_Member_DAO_MembershipType
   * @throws \CiviCRM_API3_Exception
   */
  public static function add(&$params, $ids = array()) {
    // DEPRECATED Check if membershipType ID was passed in via $ids
    if (empty($params['id'])) {
      if (isset($ids['membershipType'])) {
        Civi::log()->warning('Deprecated: Passing membershipType by $ids array in CRM_Member_BAO_MembershipType::add');
      }
      $params['id'] = CRM_Utils_Array::value('membershipType', $ids);
    }

    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'MembershipType', CRM_Utils_Array::value('id', $params), $params);

    $membershipTypeId = CRM_Utils_Array::value('id', $params);

    if (!$membershipTypeId) {
      if (!isset($params['is_active'])) {
        // do we need this?
        $params['is_active'] = FALSE;
      }
      if (!isset($params['domain_id'])) {
        $params['domain_id'] = CRM_Core_Config::domainID();
      }
    }

    // $previousID is the old organization id for membership type i.e 'member_of_contact_id'. This is used when an organization is changed.
    $previousID = NULL;
    if ($membershipTypeId) {
      $previousID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $membershipTypeId, 'member_of_contact_id');
    }

    // action is taken depending upon the mode
    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->copyValues($params);
    $membershipType->save();

    if ($membershipTypeId) {
      // on update we may need to retrieve some details for the price field function - otherwise we get e-notices on attempts to retrieve
      // name etc - the presence of previous id tells us this is an update
      $params = array_merge(civicrm_api3('membership_type', 'getsingle', array('id' => $membershipType->id)), $params);
    }
    self::createMembershipPriceField($params, $previousID, $membershipType->id);
    // update all price field value for quick config when membership type is set CRM-11718
    if ($membershipTypeId) {
      self::updateAllPriceFieldValue($membershipTypeId, $params);
    }

    CRM_Utils_Hook::post($hook, 'MembershipType', $membershipType->id, $membershipType);

    self::flush();
    return $membershipType;
  }

  /**
   * Flush anywhere that membership types might be cached.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function flush() {
    CRM_Member_PseudoConstant::membershipType(NULL, TRUE);
    civicrm_api3('membership', 'getfields', array('cache_clear' => 1, 'fieldname' => 'membership_type_id'));
    civicrm_api3('profile', 'getfields', array('action' => 'submit', 'cache_clear' => 1));
  }

  /**
   * Delete membership Types.
   *
   * @param int $membershipTypeId
   *
   * @throws CRM_Core_Exception
   * @return bool|mixed
   */
  public static function del($membershipTypeId) {
    // Check dependencies.
    $check = FALSE;
    $status = array();
    $dependency = array(
      'Membership' => 'membership_type_id',
      'MembershipBlock' => 'membership_type_default',
    );

    foreach ($dependency as $name => $field) {
      $baoString = 'CRM_Member_BAO_' . $name;
      $dao = new $baoString();
      $dao->$field = $membershipTypeId;
      /** @noinspection PhpUndefinedMethodInspection */
      if ($dao->find(TRUE)) {
        $check = TRUE;
        $status[] = $name;
      }
    }
    if ($check) {
      $cnt = 1;
      $message = ts('This membership type cannot be deleted due to following reason(s):');
      if (in_array('Membership', $status)) {
        $findMembersURL = CRM_Utils_System::url('civicrm/member/search', 'reset=1');
        $deleteURL = CRM_Utils_System::url('civicrm/contact/search/advanced', 'reset=1');
        $message .= '<br/>' . ts('%3. There are some contacts who have this membership type assigned to them. Search for contacts with this membership type from <a href=\'%1\'>Find Members</a>. If you are still getting this message after deleting these memberships, there may be contacts in the Trash (deleted) with this membership type. Try using <a href="%2">Advanced Search</a> and checking "Search in Trash".', array(
          1 => $findMembersURL,
          2 => $deleteURL,
          3 => $cnt,
        ));
        $cnt++;
      }

      if (in_array('MembershipBlock', $status)) {
        $deleteURL = CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1');
        $message .= ts('%2. This Membership Type is used in an <a href=\'%1\'>Online Contribution page</a>. Uncheck this membership type in the Memberships tab.', array(
          1 => $deleteURL,
          2 => $cnt,
        ));
        throw new CRM_Core_Exception($message);
      }
    }
    CRM_Utils_Weight::delWeight('CRM_Member_DAO_MembershipType', $membershipTypeId);
    //delete from membership Type table
    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->id = $membershipTypeId;

    //fix for membership type delete api
    $result = FALSE;
    if ($membershipType->find(TRUE)) {
      return $membershipType->delete();
    }

    return $result;
  }

  /**
   * Convert membership type's 'start day' & 'rollover day' to human readable formats.
   *
   * @param array $membershipType
   *   An array of membershipType-details.
   */
  public static function convertDayFormat(&$membershipType) {
    $periodDays = array(
      'fixed_period_start_day',
      'fixed_period_rollover_day',
    );
    foreach ($membershipType as $id => $details) {
      foreach ($periodDays as $pDay) {
        if (!empty($details[$pDay])) {
          if ($details[$pDay] > 31) {
            $month = substr($details[$pDay], 0, strlen($details[$pDay]) - 2);
            $day = substr($details[$pDay], -2);
            $monthMap = array(
              '1' => 'Jan',
              '2' => 'Feb',
              '3' => 'Mar',
              '4' => 'Apr',
              '5' => 'May',
              '6' => 'Jun',
              '7' => 'Jul',
              '8' => 'Aug',
              '9' => 'Sep',
              '10' => 'Oct',
              '11' => 'Nov',
              '12' => 'Dec',
            );
            $membershipType[$id][$pDay] = $monthMap[$month] . ' ' . $day;
          }
          else {
            $membershipType[$id][$pDay] = $details[$pDay];
          }
        }
      }
    }
  }

  /**
   * Get membership Types.
   *
   * @param bool $public
   *
   * @return array
   */
  public static function getMembershipTypes($public = TRUE) {
    $membershipTypes = array();
    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->is_active = 1;
    if ($public) {
      $membershipType->visibility = 'Public';
    }
    $membershipType->orderBy(' weight');
    $membershipType->find();
    while ($membershipType->fetch()) {
      $membershipTypes[$membershipType->id] = $membershipType->name;
    }
    $membershipType->free();
    return $membershipTypes;
  }

  /**
   * Get membership Type Details.
   *
   * @param int $membershipTypeId
   *
   * @return array|null
   */
  public static function getMembershipTypeDetails($membershipTypeId) {
    $membershipTypeDetails = array();

    $membershipType = new CRM_Member_DAO_MembershipType();
    $membershipType->is_active = 1;
    $membershipType->id = $membershipTypeId;
    if ($membershipType->find(TRUE)) {
      CRM_Core_DAO::storeValues($membershipType, $membershipTypeDetails);
      $membershipType->free();
      return $membershipTypeDetails;
    }
    else {
      return NULL;
    }
  }

  /**
   * Calculate start date and end date for new membership.
   *
   * @param int $membershipTypeId
   *   Membership type id.
   * @param string $joinDate
   *   Member since ( in mysql date format ).
   * @param string $startDate
   *   Start date ( in mysql date format ).
   * @param null $endDate
   * @param int $numRenewTerms
   *   How many membership terms are being added to end date (default is 1).
   *
   * @return array
   *   associated array with  start date, end date and join date for the membership
   */
  public static function getDatesForMembershipType($membershipTypeId, $joinDate = NULL, $startDate = NULL, $endDate = NULL, $numRenewTerms = 1) {
    $membershipTypeDetails = self::getMembershipTypeDetails($membershipTypeId);

    // Convert all dates to 'Y-m-d' format.
    foreach (array(
      'joinDate',
      'startDate',
      'endDate',
    ) as $dateParam) {
      if (!empty($$dateParam)) {
        $$dateParam = CRM_Utils_Date::processDate($$dateParam, NULL, FALSE, 'Y-m-d');
      }
    }
    if (!$joinDate) {
      $joinDate = date('Y-m-d');
    }
    $actualStartDate = $joinDate;
    if ($startDate) {
      $actualStartDate = $startDate;
    }

    $fixed_period_rollover = FALSE;
    if (CRM_Utils_Array::value('period_type', $membershipTypeDetails) == 'rolling') {
      if (!$startDate) {
        $startDate = $joinDate;
      }
      $actualStartDate = $startDate;
    }
    elseif (CRM_Utils_Array::value('period_type', $membershipTypeDetails) == 'fixed') {
      // calculate start date
      // if !$startDate then use $joinDate
      $toDay = explode('-', (empty($startDate) ? $joinDate : $startDate));
      $year = $toDay[0];
      $month = $toDay[1];
      $day = $toDay[2];

      if ($membershipTypeDetails['duration_unit'] == 'year') {

        //get start fixed day
        $startMonth = substr($membershipTypeDetails['fixed_period_start_day'], 0,
          strlen($membershipTypeDetails['fixed_period_start_day']) - 2
        );
        $startDay = substr($membershipTypeDetails['fixed_period_start_day'], -2);

        if (date('Y-m-d', mktime(0, 0, 0, $startMonth, $startDay, $year)) <= date('Y-m-d', mktime(0, 0, 0, $month, $day, $year))) {
          $actualStartDate = date('Y-m-d', mktime(0, 0, 0, $startMonth, $startDay, $year));
        }
        else {
          $actualStartDate = date('Y-m-d', mktime(0, 0, 0, $startMonth, $startDay, $year - 1));
        }

        $fixed_period_rollover = self::isDuringFixedAnnualRolloverPeriod($joinDate, $membershipTypeDetails, $year, $actualStartDate);

        if (!$startDate) {
          $startDate = $actualStartDate;
        }
      }
      elseif ($membershipTypeDetails['duration_unit'] == 'month') {
        // Check if we are on or after rollover day of the month - CRM-10585
        // If so, set fixed_period_rollover TRUE so we increment end_date month below.
        $dateParts = explode('-', $actualStartDate);
        if ($dateParts[2] >= $membershipTypeDetails['fixed_period_rollover_day']) {
          $fixed_period_rollover = TRUE;
        }

        // Start date is always first day of actualStartDate month
        if (!$startDate) {
          $actualStartDate = $startDate = $year . '-' . $month . '-01';
        }
      }
    }

    // Calculate end date if it is not passed by user.
    if (!$endDate) {
      //end date calculation
      $date = explode('-', $actualStartDate);
      $year = $date[0];
      $month = $date[1];
      $day = $date[2];

      switch ($membershipTypeDetails['duration_unit']) {
        case 'year':
          $year = $year + ($numRenewTerms * $membershipTypeDetails['duration_interval']);
          //extend membership date by duration interval.
          if ($fixed_period_rollover) {
            $year += 1;
          }
          break;

        case 'month':
          $month = $month + ($numRenewTerms * $membershipTypeDetails['duration_interval']);
          //duration interval is month
          if ($fixed_period_rollover) {
            //CRM-10585
            $month += 1;
          }
          break;

        case 'day':
          $day = $day + ($numRenewTerms * $membershipTypeDetails['duration_interval']);

          if ($fixed_period_rollover) {
            //Fix Me: Currently we don't allow rollover if
            //duration interval is day
          }
          break;
      }

      if ($membershipTypeDetails['duration_unit'] == 'lifetime') {
        $endDate = NULL;
      }
      else {
        $endDate = date('Y-m-d', mktime(0, 0, 0, $month, $day - 1, $year));
      }
    }

    $membershipDates = array(
      'start_date' => CRM_Utils_Date::customFormat($startDate, '%Y%m%d'),
      'end_date' => CRM_Utils_Date::customFormat($endDate, '%Y%m%d'),
      'join_date' => CRM_Utils_Date::customFormat($joinDate, '%Y%m%d'),
    );

    return $membershipDates;
  }

  /**
   * Does this membership start between the rollover date and the start of the next period.
   * (in which case they will get an extra membership period)
   *  ie if annual memberships run June - May & the rollover is in May memberships between
   * May and June will return TRUE and between June and May will return FALSE
   *
   * @param string $startDate start date of current membership period
   * @param array $membershipTypeDetails
   * @param int $year
   * @param string $actualStartDate
   * @return bool is this in the window where the membership gets an extra part-period added
   */
  public static function isDuringFixedAnnualRolloverPeriod($startDate, $membershipTypeDetails, $year, $actualStartDate) {

    $rolloverMonth = substr($membershipTypeDetails['fixed_period_rollover_day'], 0,
      strlen($membershipTypeDetails['fixed_period_rollover_day']) - 2
    );
    $rolloverDay = substr($membershipTypeDetails['fixed_period_rollover_day'], -2);

    $calculatedRolloverDate = date('Y-m-d', mktime(0, 0, 0, $rolloverMonth, $rolloverDay, $year));

    //CRM-7825 -membership date rules are :
    //1. Membership should not be start in future.
    //2. rollover window should be subset of membership window.

    //get the fixed end date here.
    $dateParts = explode('-', $actualStartDate);
    $endDateOfFirstYearMembershipPeriod = date('Y-m-d', mktime(0, 0, 0,
      $dateParts[1],
      $dateParts[2] - 1,
      $dateParts[0] + 1
    ));

    //we know the month and day of the rollover date but not the year (we're just
    //using the start date year at the moment. So if it's after the end
    // of the first year of membership then it's the next period & we'll adjust back a year. If it's
    // before the start_date then it's too early & we'll adjust forward.
    if ($endDateOfFirstYearMembershipPeriod < $calculatedRolloverDate) {
      $calculatedRolloverDate = date('Y-m-d', mktime(0, 0, 0, $rolloverMonth, $rolloverDay, $year - 1));
    }
    if ($calculatedRolloverDate < $actualStartDate) {
      $calculatedRolloverDate = date('Y-m-d', mktime(0, 0, 0, $rolloverMonth, $rolloverDay, $year + 1));
    }

    if ($calculatedRolloverDate <= $startDate) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Calculate start date and end date for membership updates.
   *
   * Note that this function is called by the api for any membership update although it was
   * originally written for renewal (which feels a bit fragile but hey....).
   *
   * @param int $membershipId
   * @param $changeToday
   * @param int $membershipTypeID
   *   If provided, overrides the membership type of the $membershipID membership.
   * @param int $numRenewTerms
   *   How many membership terms are being added to end date (default is 1).
   *
   * CRM-7297 Membership Upsell - Added $membershipTypeID param to facilitate calculations of dates when membership type changes
   *
   * @return array
   *   array fo the start date, end date and join date of the membership
   */
  public static function getRenewalDatesForMembershipType($membershipId, $changeToday = NULL, $membershipTypeID = NULL, $numRenewTerms = 1) {
    $params = array('id' => $membershipId);
    $membershipDetails = CRM_Member_BAO_Membership::getValues($params, $values);
    $membershipDetails = $membershipDetails[$membershipId];
    $statusID = $membershipDetails->status_id;
    $membershipDates = array();
    if (!empty($membershipDetails->join_date)) {
      $membershipDates['join_date'] = CRM_Utils_Date::customFormat($membershipDetails->join_date, '%Y%m%d');
    }

    $oldPeriodType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $membershipId, 'membership_type_id'), 'period_type');

    // CRM-7297 Membership Upsell
    if (is_null($membershipTypeID)) {
      $membershipTypeDetails = self::getMembershipTypeDetails($membershipDetails->membership_type_id);
    }
    else {
      $membershipTypeDetails = self::getMembershipTypeDetails($membershipTypeID);
    }
    $statusDetails = CRM_Member_BAO_MembershipStatus::getMembershipStatus($statusID);

    if ($statusDetails['is_current_member'] == 1) {
      $startDate = $membershipDetails->start_date;
      // CRM=7297 Membership Upsell: we need to handle null end_date in case we are switching
      // from a lifetime to a different membership type
      if (is_null($membershipDetails->end_date)) {
        $date = date('Y-m-d');
      }
      else {
        $date = $membershipDetails->end_date;
      }
      $date = explode('-', $date);
      // We have to add 1 day first in case it's the end of the month, then subtract afterwards
      // eg. 2018-02-28 should renew to 2018-03-31, if we just added 1 month we'd get 2018-03-28
      $logStartDate = date('Y-m-d', mktime(0, 0, 0,
        (double) $date[1],
        (double) ($date[2] + 1),
        (double) $date[0]
      ));

      $date = explode('-', $logStartDate);
      $year = $date[0];
      $month = $date[1];
      $day = $date[2];

      switch ($membershipTypeDetails['duration_unit']) {
        case 'year':
          //need to check if the upsell is from rolling to fixed and adjust accordingly
          if ($membershipTypeDetails['period_type'] == 'fixed' && $oldPeriodType == 'rolling') {
            $month = substr($membershipTypeDetails['fixed_period_start_day'], 0, strlen($membershipTypeDetails['fixed_period_start_day']) - 2);
            $day = substr($membershipTypeDetails['fixed_period_start_day'], -2);
            $year += 1;
          }
          else {
            $year = $year + ($numRenewTerms * $membershipTypeDetails['duration_interval']);
          }
          break;

        case 'month':
          $month = $month + ($numRenewTerms * $membershipTypeDetails['duration_interval']);
          break;

        case 'day':
          $day = $day + ($numRenewTerms * $membershipTypeDetails['duration_interval']);
          break;
      }
      if ($membershipTypeDetails['duration_unit'] == 'lifetime') {
        $endDate = NULL;
      }
      else {
        $endDate = date('Y-m-d', mktime(0, 0, 0,
          $month,
          $day - 1,
          $year
        ));
      }
      $today = date('Y-m-d');
      $membershipDates['today'] = CRM_Utils_Date::customFormat($today, '%Y%m%d');
      $membershipDates['start_date'] = CRM_Utils_Date::customFormat($startDate, '%Y%m%d');
      $membershipDates['end_date'] = CRM_Utils_Date::customFormat($endDate, '%Y%m%d');
      $membershipDates['log_start_date'] = CRM_Utils_Date::customFormat($logStartDate, '%Y%m%d');
    }
    else {
      $today = date('Y-m-d');
      if ($changeToday) {
        $today = CRM_Utils_Date::processDate($changeToday, NULL, FALSE, 'Y-m-d');
      }
      // Calculate new start/end dates when join date is today
      $renewalDates = self::getDatesForMembershipType($membershipTypeDetails['id'],
        $today, NULL, NULL, $numRenewTerms
      );
      $membershipDates['today'] = CRM_Utils_Date::customFormat($today, '%Y%m%d');
      $membershipDates['start_date'] = $renewalDates['start_date'];
      $membershipDates['end_date'] = $renewalDates['end_date'];
      $membershipDates['log_start_date'] = $renewalDates['start_date'];
      // CRM-18503 - set join_date as today in case the membership type is fixed.
      if ($membershipTypeDetails['period_type'] == 'fixed' && !isset($membershipDates['join_date'])) {
        $membershipDates['join_date'] = $renewalDates['join_date'];
      }
    }
    if (!isset($membershipDates['join_date'])) {
      $membershipDates['join_date'] = $membershipDates['start_date'];
    }

    return $membershipDates;
  }

  /**
   * @deprecated Please use the Membership API
   * Retrieve all Membership Types associated with an Organization
   *
   * @param int $orgID
   *   Id of Organization.
   *
   * @return array
   *   array of the details of membership types
   */
  public static function getMembershipTypesByOrg($orgID) {
    CRM_Core_Error::deprecatedFunctionWarning('membership_type api');
    $memberTypesSameParentOrg = civicrm_api3('MembershipType', 'get', array(
      'member_of_contact_id' => $orgID,
      'options' => array(
        'limit' => 0,
      ),
    ));
    return CRM_Utils_Array::value('values', $memberTypesSameParentOrg, array());
  }

  /**
   * Retrieve all Membership Types with Member of Contact id.
   *
   * @param array $membershipTypes
   *   array of membership type ids
   * @return array
   *   array of the details of membership types with Member of Contact id
   */
  public static function getMemberOfContactByMemTypes($membershipTypes) {
    $memTypeOrganizations = array();
    if (empty($membershipTypes)) {
      return $memTypeOrganizations;
    }

    $result = CRM_Core_DAO::executeQuery("SELECT id, member_of_contact_id FROM civicrm_membership_type WHERE id IN (" . implode(',', $membershipTypes) . ")");
    while ($result->fetch()) {
      $memTypeOrganizations[$result->id] = $result->member_of_contact_id;
    }

    return $memTypeOrganizations;
  }

  /**
   * The function returns all the Organization for  all membershipTypes .
   *
   * @param int $membershipTypeId
   *
   * @return array
   */
  public static function getMembershipTypeOrganization($membershipTypeId = NULL) {
    $allMembershipTypes = array();

    $membershipType = new CRM_Member_DAO_MembershipType();

    if (isset($membershipTypeId)) {
      $membershipType->id = $membershipTypeId;
    }
    $membershipType->find();

    while ($membershipType->fetch()) {
      $allMembershipTypes[$membershipType->id] = $membershipType->member_of_contact_id;
    }
    return $allMembershipTypes;
  }

  /**
   * Function to retrieve organization and associated membership
   * types
   *
   * @return array
   *   arrays of organization and membership types
   *
   */
  public static function getMembershipTypeInfo() {
    if (!self::$_membershipTypeInfo) {
      $orgs = $types = array();

      $query = 'SELECT memType.id, memType.name, memType.member_of_contact_id, c.sort_name
        FROM civicrm_membership_type memType INNER JOIN civicrm_contact c ON c.id = memType.member_of_contact_id
        WHERE memType.is_active = 1 ';
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $orgs[$dao->member_of_contact_id] = $dao->sort_name;
        $types[$dao->member_of_contact_id][$dao->id] = $dao->name;
      }

      self::$_membershipTypeInfo = array($orgs, $types);
    }
    return self::$_membershipTypeInfo;
  }


  /**
   * @param array $params
   * @param int $previousID
   * @param int $membershipTypeId
   */
  public static function createMembershipPriceField($params, $previousID, $membershipTypeId) {

    $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', 'default_membership_type_amount', 'id', 'name');

    if (!empty($params['member_of_contact_id'])) {
      $fieldName = $params['member_of_contact_id'];
    }
    else {
      $fieldName = $previousID;
    }
    $fieldLabel = 'Membership Amount';
    $optionsIds = NULL;
    $fieldParams = array(
      'price_set_id ' => $priceSetId,
      'name' => $fieldName,
    );
    $results = array();
    CRM_Price_BAO_PriceField::retrieve($fieldParams, $results);
    if (empty($results)) {
      $fieldParams = array();
      $fieldParams['label'] = $fieldLabel;
      $fieldParams['name'] = $fieldName;
      $fieldParams['price_set_id'] = $priceSetId;
      $fieldParams['html_type'] = 'Radio';
      $fieldParams['is_display_amounts'] = $fieldParams['is_required'] = 0;
      $fieldParams['weight'] = $fieldParams['option_weight'][1] = 1;
      $fieldParams['option_label'][1] = $params['name'];
      $fieldParams['option_description'][1] = CRM_Utils_Array::value('description', $params);

      $fieldParams['membership_type_id'][1] = $membershipTypeId;
      $fieldParams['option_amount'][1] = empty($params['minimum_fee']) ? 0 : $params['minimum_fee'];
      $fieldParams['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $params);

      if ($previousID) {
        CRM_Member_Form_MembershipType::checkPreviousPriceField($previousID, $priceSetId, $membershipTypeId, $optionsIds);
        $fieldParams['option_id'] = CRM_Utils_Array::value('option_id', $optionsIds);
      }
      CRM_Price_BAO_PriceField::create($fieldParams);
    }
    else {
      $fieldID = $results['id'];
      $fieldValueParams = array(
        'price_field_id' => $fieldID,
        'membership_type_id' => $membershipTypeId,
      );
      $results = array();
      CRM_Price_BAO_PriceFieldValue::retrieve($fieldValueParams, $results);
      if (!empty($results)) {
        $results['label'] = $results['name'] = $params['name'];
        $results['amount'] = empty($params['minimum_fee']) ? 0 : $params['minimum_fee'];
        $optionsIds['id'] = $results['id'];
      }
      else {
        $results = array(
          'price_field_id' => $fieldID,
          'name' => $params['name'],
          'label' => $params['name'],
          'amount' => empty($params['minimum_fee']) ? 0 : $params['minimum_fee'],
          'membership_type_id' => $membershipTypeId,
          'is_active' => 1,
        );
      }

      if ($previousID) {
        CRM_Member_Form_MembershipType::checkPreviousPriceField($previousID, $priceSetId, $membershipTypeId, $optionsIds);
        if (!empty($optionsIds['option_id'])) {
          $optionsIds['id'] = current(CRM_Utils_Array::value('option_id', $optionsIds));
        }
      }
      $results['financial_type_id'] = CRM_Utils_Array::value('financial_type_id', $params);
      $results['description'] = CRM_Utils_Array::value('description', $params);
      CRM_Price_BAO_PriceFieldValue::add($results, $optionsIds);
    }
  }

  /**
   * This function updates all price field value for quick config
   * price set which has membership type
   *
   * @param int $membershipTypeId membership type id
   *
   * @param array $params
   */
  public static function updateAllPriceFieldValue($membershipTypeId, $params) {
    $defaults = array();
    $fieldsToUpdate = array(
      'financial_type_id' => 'financial_type_id',
      'name' => 'label',
      'minimum_fee' => 'amount',
      'description' => 'description',
      'visibility' => 'visibility_id',
    );
    $priceFieldValueBAO = new CRM_Price_BAO_PriceFieldValue();
    $priceFieldValueBAO->membership_type_id = $membershipTypeId;
    $priceFieldValueBAO->find();
    while ($priceFieldValueBAO->fetch()) {
      $updateParams = array(
        'id' => $priceFieldValueBAO->id,
        'price_field_id' => $priceFieldValueBAO->price_field_id,
      );
      //Get priceset details.
      $fieldParams = array('fid' => $priceFieldValueBAO->price_field_id);
      $setID = CRM_Price_BAO_PriceSet::getSetId($fieldParams);
      $setParams = array('id' => $setID);
      $setValues = CRM_Price_BAO_PriceSet::retrieve($setParams, $defaults);
      if (!empty($setValues->is_quick_config) && $setValues->name != 'default_membership_type_amount') {
        foreach ($fieldsToUpdate as $key => $value) {
          if ($value == 'visibility_id' && !empty($params['visibility'])) {
            $updateParams['visibility_id'] = CRM_Price_BAO_PriceField::getVisibilityOptionID(strtolower($params['visibility']));
          }
          else {
            $updateParams[$value] = CRM_Utils_Array::value($key, $params);
          }
        }
        CRM_Price_BAO_PriceFieldValue::add($updateParams);
      }
    }
  }

}
