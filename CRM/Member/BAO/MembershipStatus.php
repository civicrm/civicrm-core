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
class CRM_Member_BAO_MembershipStatus extends CRM_Member_DAO_MembershipStatus implements \Civi\Core\HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Member_DAO_MembershipStatus', $id, 'is_active', $is_active);
  }

  /**
   * Takes an associative array and creates a membership status object.
   *
   * @param array $params
   *   Array of name/value pairs.
   *
   * @throws CRM_Core_Exception
   * @return CRM_Member_DAO_MembershipStatus
   */
  public static function create($params) {
    if (empty($params['id'])) {
      //don't allow duplicate names - if id not set
      $status = new CRM_Member_DAO_MembershipStatus();
      $status->name = $params['name'];
      if ($status->find(TRUE)) {
        throw new CRM_Core_Exception('A membership status with this name already exists.');
      }
    }
    return self::add($params);
  }

  /**
   * Add the membership status.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   * @param array $ids
   *   Array contains the id - this param is deprecated.
   *
   * @return CRM_Member_DAO_MembershipStatus
   */
  public static function add(&$params, $ids = []) {
    if (!empty($ids)) {
      CRM_Core_Error::deprecatedFunctionWarning('ids is a deprecated parameter');
    }
    $id = $params['id'] ?? $ids['membershipStatus'] ?? NULL;
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
      CRM_Core_DAO::executeQuery($query);
    }

    // action is taken depending upon the mode
    $membershipStatus = new CRM_Member_DAO_MembershipStatus();
    $membershipStatus->copyValues($params);

    $membershipStatus->id = $id;

    $membershipStatus->save();
    CRM_Member_PseudoConstant::flush('membershipStatus');
    Civi::cache('metadata')->clear();
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
   * Get membership status.
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
   * Delete membership status.
   *
   * @param int $membershipStatusId
   * @deprecated
   * @throws CRM_Core_Exception
   */
  public static function del($membershipStatusId) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    static::deleteRecord(['id' => $membershipStatusId]);
  }

  /**
   * Callback for hook_civicrm_pre().
   * @param \Civi\Core\Event\PreEvent $event
   * @throws CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if ($event->action === 'delete') {
      // Check if any membership records are assigned this membership status
      $dependency = ['Membership'];
      foreach ($dependency as $name) {
        $baoString = 'CRM_Member_BAO_' . $name;
        $dao = new $baoString();
        $dao->status_id = $event->id;
        if ($dao->find(TRUE)) {
          throw new CRM_Core_Exception(ts('This membership status cannot be deleted. Memberships exist with this status.'));
        }
      }
      CRM_Utils_Weight::delWeight('CRM_Member_DAO_MembershipStatus', $event->id);
      CRM_Member_PseudoConstant::flush('membershipStatus');
    }
  }

  /**
   * Find the membership status based on start date, end date, join date & status date.
   *
   * Loop through all the membership status definitions, ordered by their
   * weight. For each, we loop through all possible variations of the given
   * start, end, and join dates and adjust the starts and ends based on that
   * membership status's rules, where the last computed set of adjusted start
   * and end becomes a candidate. Then we compare that candidate to either
   * "today" or some other given date, and if it falls between the adjusted
   * start and end we have a match and we stop looping through status
   * definitions. Then we call a hook in case that wasn't enough loops.
   *
   * @param string $startDate
   *   Start date of the member whose membership status is to be calculated.
   * @param string $endDate
   *   End date of the member whose membership status is to be calculated.
   * @param string $joinDate
   *   Join date of the member whose membership status is to be calculated.
   * @param string $statusDate
   *   Either the string "today" or a date against which we compare the adjusted start and end based on the status rules.
   * @param bool $excludeIsAdmin
   *   Exclude the statuses having is_admin = 1.
   * @param int $membershipTypeID
   *   Not used directly but gets passed to the hook.
   * @param array $membership
   *   Membership params as available to calling function - not used directly but passed to the hook.
   *
   * @return array
   */
  public static function getMembershipStatusByDate(
    $startDate, $endDate, $joinDate,
    $statusDate = 'now', $excludeIsAdmin = FALSE, $membershipTypeID = NULL, $membership = []
  ) {
    $membershipDetails = [];

    if (!$statusDate || $statusDate === 'today') {
      $statusDate = 'now';
      CRM_Core_Error::deprecatedFunctionWarning('pass now rather than today in');
    }

    $statusDate = date('Ymd', CRM_Utils_Time::strtotime($statusDate));

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

    $dates = [
      'start' => ($startDate && $startDate !== 'null') ? date('Ymd', CRM_Utils_Time::strtotime($startDate)) : '',
      'end' => ($endDate && $endDate !== 'null') ? date('Ymd', CRM_Utils_Time::strtotime($endDate)) : '',
      'join' => ($joinDate && $joinDate !== 'null') ? date('Ymd', CRM_Utils_Time::strtotime($joinDate)) : '',
    ];

    while ($membershipStatus->fetch()) {
      $startEvent = NULL;
      $endEvent = NULL;
      foreach (['start', 'end'] as $eve) {
        foreach ($dates as $dat => $date) {
          // calculate start-event/date and end-event/date
          if (($membershipStatus->{$eve . '_event'} === $dat . '_date') &&
            $date
          ) {
            if ($membershipStatus->{$eve . '_event_adjust_unit'} &&
              $membershipStatus->{$eve . '_event_adjust_interval'}
            ) {
              $month = date('m', CRM_Utils_Time::strtotime($date));
              $day = date('d', CRM_Utils_Time::strtotime($date));
              $year = date('Y', CRM_Utils_Time::strtotime($date));
              // add in months
              if ($membershipStatus->{$eve . '_event_adjust_unit'} === 'month') {
                ${$eve . 'Event'} = date('Ymd', mktime(0, 0, 0,
                  $month + $membershipStatus->{$eve . '_event_adjust_interval'},
                  $day,
                  $year
                ));
              }
              // add in days
              if ($membershipStatus->{$eve . '_event_adjust_unit'} === 'day') {
                ${$eve . 'Event'} = date('Ymd', mktime(0, 0, 0,
                  $month,
                  $day + $membershipStatus->{$eve . '_event_adjust_interval'},
                  $year
                ));
              }
              // add in years
              if ($membershipStatus->{$eve . '_event_adjust_unit'} === 'year') {
                ${$eve . 'Event'} = date('Ymd', mktime(0, 0, 0,
                  $month,
                  $day,
                  $year + $membershipStatus->{$eve . '_event_adjust_interval'}
                ));
              }
              // if no interval and unit, present
            }
            else {
              ${$eve . 'Event'} = $date;
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

  /**
   * Get the id of the status to be used for new memberships.
   *
   * @return int
   * @throws \CRM_Core_Exception
   */
  public static function getNewMembershipTypeID(): int {
    $cacheKey = __CLASS__ . __FUNCTION__;
    if (!isset(\Civi::$statics[$cacheKey])) {
      \Civi::$statics[$cacheKey] = (bool) CRM_Core_DAO::singleValueQuery(
        'SELECT id FROM civicrm_membership_status
        WHERE start_event = "join_date"
        AND start_event_adjust_unit IS NULL
        ORDER BY weight LIMIT 1'
      );
    }
    return \Civi::$statics[$cacheKey];
  }

}
