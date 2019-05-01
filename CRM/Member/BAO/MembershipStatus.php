<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */
class CRM_Member_BAO_MembershipStatus extends CRM_Member_DAO_MembershipStatus {

  /**
   * Static holder for the default LT.
   * @var int
   */
  public static $_defaultMembershipStatus = NULL;

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
   * @return CRM_Member_BAO_MembershipStatus
   */
  public static function retrieve(&$params, &$defaults) {
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->copyValues($params);
    if ($membershipStatus->find(TRUE)) {
      CRM_Core_DAO::storeValues($membershipStatus, $defaults);
      return $membershipStatus;
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
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Member_DAO_MembershipStatus', $id, 'is_active', $is_active);
  }

  /**
   * Takes an associative array and creates a membership Status object.
   * See http://wiki.civicrm.org/confluence/display/CRM/Database+layer
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @throws Exception
   * @return CRM_Member_BAO_MembershipStatus
   */
  public static function create($params) {
    $ids = [];
    if (!empty($params['id'])) {
      $ids['membershipStatus'] = $params['id'];
    }
    else {
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
   * Add the membership types.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Array contains the id - this param is deprecated.
   *
   *
   * @return object
   */
  public static function add(&$params, $ids = []) {
    $id = CRM_Utils_Array::value('id', $params, CRM_Utils_Array::value('membershipStatus', $ids));
    if (!$id) {
      CRM_Core_DAO::setCreateDefaults($params, self::getDefaults());
      //copy name to label when not passed.
      if (empty($params['label']) && !empty($params['name'])) {
        $params['label'] = $params['name'];
      }

      if (empty($params['name']) && !empty($params['label'])) {
        $params['name'] = $params['label'];
      }
    }

    // set all other defaults to false.
    if (!empty($params['is_default'])) {
      $query = "UPDATE civicrm_membership_status SET is_default = 0";
      CRM_Core_DAO::executeQuery($query,
        CRM_Core_DAO::$_nullArray
      );
    }

    // action is taken depending upon the mode
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->copyValues($params);

    $membershipStatus->id = $id;

    $membershipStatus->save();
    CRM_Member_PseudoConstant::flush('membershipStatus');
    return $membershipStatus;
  }

  /**
   * Get defaults for new entity.
   * @return array
   */
  public static function getDefaults() {
    return [
      'is_active' => FALSE,
      'is_current_member' => FALSE,
      'is_admin' => FALSE,
      'is_default' => FALSE,
    ];
  }

  /**
   * Get  membership status.
   *
   * @param int $membershipStatusId
   *
   * @return array
   */
  public static function getMembershipStatus($membershipStatusId) {
    $statusDetails = [];
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->id = $membershipStatusId;
    if ($membershipStatus->find(TRUE)) {
      CRM_Core_DAO::storeValues($membershipStatus, $statusDetails);
    }
    return $statusDetails;
  }

  /**
   * Delete membership Types.
   *
   * @param int $membershipStatusId
   *
   * @throws CRM_Core_Exception
   */
  public static function del($membershipStatusId) {
    //check dependencies
    //checking if membership status is present in some other table
    $check = FALSE;

    $dependency = ['Membership', 'MembershipLog'];
    foreach ($dependency as $name) {
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
    if (!$membershipStatus->find()) {
      throw new CRM_Core_Exception(ts('Cannot delete membership status ' . $membershipStatusId));
    }
    $membershipStatus->delete();
    CRM_Member_PseudoConstant::flush('membershipStatus');
  }

  /**
   * Find the membership status based on start date, end date, join date & status date.
   *
   * @param string $startDate
   *   Start date of the member whose membership status is to be calculated.
   * @param string $endDate
   *   End date of the member whose membership status is to be calculated.
   * @param string $joinDate
   *   Join date of the member whose membership status is to be calculated.
   * @param \date|string $statusDate status date of the member whose membership status is to be calculated.
   * @param bool $excludeIsAdmin the statuses those having is_admin = 1.
   *   Exclude the statuses those having is_admin = 1.
   * @param int $membershipTypeID
   * @param array $membership
   *   Membership params as available to calling function - passed to the hook.
   *
   * @return array
   */
  public static function getMembershipStatusByDate(
    $startDate, $endDate, $joinDate,
    $statusDate = 'today', $excludeIsAdmin = FALSE, $membershipTypeID, $membership = []
  ) {
    $membershipDetails = [];

    if (!$statusDate || $statusDate == 'today') {
      $statusDate = getdate();
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

    $dates = ['start', 'end', 'join'];
    $events = ['start', 'end'];

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

    $membershipStatus = CRM_Core_DAO::executeQuery($query);
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

    //we bundle the arguments into an array as we can't pass 8 variables to the hook otherwise
    // the membership array might contain the pre-altered settings so we don't want to merge this
    $arguments = [
      'start_date' => $startDate,
      'end_date' => $endDate,
      'join_date' => $joinDate,
      'status_date' => $statusDate,
      'exclude_is_admin' => $endDate,
      'membership_type_id' => $membershipTypeID,
      'start_event' => $startEvent,
      'end_event' => $endEvent,
    ];
    CRM_Utils_Hook::alterCalculatedMembershipStatus($membershipDetails, $arguments, $membership);
    return $membershipDetails;
  }

  /**
   * Function that return the status ids whose is_current_member is set.
   *
   * @return array
   */
  public static function getMembershipStatusCurrent() {
    $statusIds = [];
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->is_current_member = 1;
    $membershipStatus->find();
    $membershipStatus->selectAdd();
    $membershipStatus->selectAdd('id');
    while ($membershipStatus->fetch()) {
      $statusIds[] = $membershipStatus->id;
    }
    return $statusIds;
  }

}
