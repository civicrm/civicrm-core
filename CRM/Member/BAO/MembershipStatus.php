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
class CRM_Member_BAO_MembershipStatus extends CRM_Member_DAO_MembershipStatus {

  /**
   * static holder for the default LT
   */
  static $_defaultMembershipStatus = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Member_BAO_MembershipStatus object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->copyValues($params);
    if ($membershipStatus->find(TRUE)) {
      CRM_Core_DAO::storeValues($membershipStatus, $defaults);
      return $membershipStatus;
    }
    return NULL;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Member_DAO_MembershipStatus', $id, 'is_active', $is_active);
  }

  /**
   * Takes an associative array and creates a membership Status object
   * See http://wiki.civicrm.org/confluence/display/CRM/Database+layer
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @throws Exception
   * @return object CRM_Member_BAO_MembershipStatus object
   * @access public
   * @static
   */
  static function create($params){
    $ids = array();
    if(!empty($params['id'])){
      $ids['membershipStatus']  = $params['id'];
    }
    else{
      //don't allow duplicate names - if id not set
      $status = new CRM_Member_DAO_MembershipStatus();
      $status->name = $params['name'];
      if ($status->find(TRUE)) {
        throw new Exception('A membership status with this name already exists.');
      }
    }
    $membershipStatusBAO = CRM_Member_BAO_MembershipStatus::add($params, $ids);
    return $membershipStatusBAO;
  }
  /**
   * function to add the membership types
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids array contains the id - this param is deprecated
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, $ids = array()) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_current_member'] = CRM_Utils_Array::value('is_current_member', $params, FALSE);
    $params['is_admin'] = CRM_Utils_Array::value('is_admin', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

    // set all other defaults to false.
    if ($params['is_default']) {
      $query = "UPDATE civicrm_membership_status SET is_default = 0";
      CRM_Core_DAO::executeQuery($query,
        CRM_Core_DAO::$_nullArray
      );
    }

    //copy name to label when not passed.
    if (empty($params['label']) && !empty($params['name'])) {
      $params['label'] = $params['name'];
    }

    //for add mode, copy label to name.
    $statusId = !empty($params['id']) ? $params['id'] : CRM_Utils_Array::value('membershipStatus', $ids);
    if (!$statusId && !empty($params['label']) && empty($params['name'])) {
      $params['name'] = $params['label'];
    }

    // action is taken depending upon the mode
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->copyValues($params);

    $membershipStatus->id = $statusId;

    $membershipStatus->save();
    return $membershipStatus;
  }

  /**
   * Function to get  membership status
   *
   * @param int $membershipStatusId
   *
   * @return array
   * @static
   */
  public static function getMembershipStatus($membershipStatusId) {
    $statusDetails = array();
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->id = $membershipStatusId;
    if ($membershipStatus->find(TRUE)) {
      CRM_Core_DAO::storeValues($membershipStatus, $statusDetails);
    }
    return $statusDetails;
  }

  /**
   * Function to delete membership Types
   *
   * @param int $membershipStatusId
   *
   * @throws CRM_Core_Exception
   * @internal param $
   * @static
   */
  static function del($membershipStatusId) {
    //check dependencies
    //checking if membership status is present in some other table
    $check = FALSE;

    $dependancy = array('Membership', 'MembershipLog');
    foreach ($dependancy as $name) {
      $baoString = 'CRM_Member_BAO_' . $name;
      $dao = new $baoString();
      $dao->status_id = $membershipStatusId;
      if ($dao->find(TRUE)) {
        throw new CRM_Core_Exception(ts('This membership status cannot be deleted as memberships exist with this status'));
      }
    }
    CRM_Utils_Weight::delWeight('CRM_Member_DAO_MembershipStatus', $membershipStatusId);
    //delete from membership Type table
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->id = $membershipStatusId;
    $membershipStatus->delete();
    $membershipStatus->free();
  }

  /**
   * Function to find the membership status based on start date, end date, join date & status date.
   *
   * @param  string $startDate start date of the member whose membership status is to be calculated.
   * @param  string $endDate end date of the member whose membership status is to be calculated.
   * @param  string $joinDate join date of the member whose membership status is to be calculated.
   * @param \date|string $statusDate status date of the member whose membership status is to be calculated.
   * @param  boolean $excludeIsAdmin exclude the statuses those having is_admin = 1
   * @param $membershipTypeID
   * @param array $membership membership params as available to calling function - passed to the hook
   *
   * @internal param int $membershipType membership type id - passed to the hook
   * @return array
  @static
   */
  static function getMembershipStatusByDate($startDate, $endDate, $joinDate,
    $statusDate = 'today', $excludeIsAdmin = FALSE, $membershipTypeID, $membership = array()
  ) {
    $membershipDetails = array();

    if (!$statusDate || $statusDate == 'today') {
      $statusDate = getDate();
      $statusDate = date('Ymd',
        mktime($statusDate['hours'],
          $statusDate['minutes'],
          $statusDate['seconds'],
          $statusDate['mon'],
          $statusDate['mday'],
          $statusDate['year']
        )
      );
    }
    else {
      $statusDate = CRM_Utils_Date::customFormat($statusDate, '%Y%m%d');
    }

    $dates = array('start', 'end', 'join');
    $events = array('start', 'end');

    foreach ($dates as $dat) {
      if (${$dat . 'Date'} && ${$dat . 'Date'} != "null") {
        ${$dat . 'Date'} = CRM_Utils_Date::customFormat(${$dat . 'Date'}, '%Y%m%d');

        ${$dat . 'Year'} = substr(${$dat . 'Date'}, 0, 4);

        ${$dat . 'Month'} = substr(${$dat . 'Date'}, 4, 2);

        ${$dat . 'Day'} = substr(${$dat . 'Date'}, 6, 2);
      }
      else {
        ${$dat . 'Date'} = '';
      }
    }

    //fix for CRM-3570, if we have statuses with is_admin=1,
    //exclude these statuses from calculatation during import.
    $where = "is_active = 1";
    if ($excludeIsAdmin) {
      $where .= " AND is_admin != 1";
    }

    $query = "
 SELECT   *
 FROM     civicrm_membership_status
 WHERE    {$where}
 ORDER BY weight ASC";

    $membershipStatus = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    $hour = $minute = $second = 0;

    while ($membershipStatus->fetch()) {
      $startEvent = NULL;
      $endEvent = NULL;
      foreach ($events as $eve) {
        foreach ($dates as $dat) {
          // calculate start-event/date and end-event/date
          if (($membershipStatus->{$eve . '_event'} == $dat . '_date') &&
            ${$dat . 'Date'}
          ) {
            if ($membershipStatus->{$eve . '_event_adjust_unit'} &&
              $membershipStatus->{$eve . '_event_adjust_interval'}
            ) {
              // add in months
              if ($membershipStatus->{$eve . '_event_adjust_unit'} == 'month') {
                ${$eve . 'Event'} = date('Ymd', mktime($hour, $minute, $second,
                    ${$dat . 'Month'} + $membershipStatus->{$eve . '_event_adjust_interval'},
                    ${$dat . 'Day'},
                    ${$dat . 'Year'}
                  ));
              }
              // add in days
              if ($membershipStatus->{$eve . '_event_adjust_unit'} == 'day') {
                ${$eve . 'Event'} = date('Ymd', mktime($hour, $minute, $second,
                    ${$dat . 'Month'},
                    ${$dat . 'Day'} + $membershipStatus->{$eve . '_event_adjust_interval'},
                    ${$dat . 'Year'}
                  ));
              }
              // add in years
              if ($membershipStatus->{$eve . '_event_adjust_unit'} == 'year') {
                ${$eve . 'Event'} = date('Ymd', mktime($hour, $minute, $second,
                    ${$dat . 'Month'},
                    ${$dat . 'Day'},
                    ${$dat . 'Year'} + $membershipStatus->{$eve . '_event_adjust_interval'}
                  ));
              }
              // if no interval and unit, present
            }
            else {
              ${$eve . 'Event'} = ${$dat . 'Date'};
            }
          }
        }
      }

      // check if statusDate is in the range of start & end events.
      if ($startEvent && $endEvent) {
        if (($statusDate >= $startEvent) && ($statusDate <= $endEvent)) {
          $membershipDetails['id'] = $membershipStatus->id;
          $membershipDetails['name'] = $membershipStatus->name;
        }
      }
      elseif ($startEvent) {
        if ($statusDate >= $startEvent) {
          $membershipDetails['id'] = $membershipStatus->id;
          $membershipDetails['name'] = $membershipStatus->name;
        }
      }
      elseif ($endEvent) {
        if ($statusDate <= $endEvent) {
          $membershipDetails['id'] = $membershipStatus->id;
          $membershipDetails['name'] = $membershipStatus->name;
        }
      }

      // returns FIRST status record for which status_date is in range.
      if ($membershipDetails) {
        break;
      }
    }
    //end fetch

    $membershipStatus->free();

    //we bundle the arguments into an array as we can't pass 8 variables to the hook otherwise
    // the membership array might contain the pre-altered settings so we don't want to merge this
    $arguments = array(
      'start_date' => $startDate,
      'end_date' => $endDate,
      'join_date' => $joinDate,
      'status_date' => $statusDate,
      'exclude_is_admin' => $endDate,
      'membership_type_id' => $membershipTypeID,
      'start_event' => $startEvent,
      'end_event' => $endEvent,
    );
    CRM_Utils_Hook::alterCalculatedMembershipStatus($membershipDetails, $arguments, $membership);
    return $membershipDetails;
  }

  /**
   * Function that return the status ids whose is_current_member is set
   *
   * @return array
  @static
   */
  public static function getMembershipStatusCurrent() {
    $statusIds = array();
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->is_current_member = 1;
    $membershipStatus->find();
    $membershipStatus->selectAdd();
    $membershipStatus->selectAdd('id');
    while ($membershipStatus->fetch()) {
      $statusIds[] = $membershipStatus->id;
    }
    $membershipStatus->free();
    return $statusIds;
  }
}

