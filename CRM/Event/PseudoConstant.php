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
 * @deprecated functions. Use the API instead.
 */
class CRM_Event_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Get all events
   *
   * @param int|null $id
   * @param bool $all
   * @param string|null $condition
   *   Optional SQL where condition
   *
   * @return array|string|null
   *   array of all events if any
   */
  public static function event($id = NULL, $all = FALSE, $condition = NULL) {
    $options = [];
    CRM_Core_PseudoConstant::populate($options,
      'CRM_Event_DAO_Event',
      $all, 'title', 'is_active', $condition, NULL
    );

    if ($id) {
      return $options[$id] ?? NULL;
    }
    return $options;
  }

  /**
   * Get all the event participant statuses.
   *
   *
   * @param int|null $id
   *   Return the specified participant status, or null to return all
   * @param string|null $cond
   *   Optional SQL where condition
   * @param string $retColumn
   *   Tells populate() whether to return 'name' (default) or 'label' values.
   *
   * @return array|string
   *   array reference of all participant statuses if any, or single value if $id was passed
   */
  public static function participantStatus($id = NULL, $cond = NULL, $retColumn = 'name') {
    $statuses = [];
    CRM_Core_PseudoConstant::populate($statuses,
      'CRM_Event_DAO_ParticipantStatusType',
      FALSE, $retColumn, 'is_active', $cond, 'weight'
    );

    if ($id) {
      return $statuses[$id] ?? NULL;
    }
    return $statuses;
  }

  /**
   * Get participant status class options.
   *
   * @return array
   */
  public static function participantStatusClassOptions() {
    return [
      'Positive' => ts('Positive'),
      'Pending' => ts('Pending'),
      'Waiting' => ts('Waiting'),
      'Negative' => ts('Negative'),
    ];
  }

  /**
   * Return a status-type-keyed array of status classes
   *
   * @return array
   *   Array of status classes, keyed by status type
   */
  public static function participantStatusClass() {
    $statusClasses = [];
    self::populate($statusClasses, 'CRM_Event_DAO_ParticipantStatusType', TRUE, 'class');
    return $statusClasses;
  }

  /**
   * Get all the n participant roles.
   *
   *
   * @param int $id
   * @param string|null $cond
   *   Optional SQL where condition
   *
   * @return array|string
   *   array reference of all participant roles if any
   */
  public static function participantRole($id = NULL, $cond = NULL) {
    $condition = empty($cond) ? NULL : "AND $cond";
    $options = CRM_Core_OptionGroup::values('participant_role', FALSE, FALSE, FALSE, $condition);

    if ($id) {
      return $options[$id] ?? NULL;
    }
    return $options;
  }

  /**
   * Get all  event types.
   *
   *
   * @param int $id
   * @return array|string
   *   array reference of all event types.
   */
  public static function eventType($id = NULL) {
    $options = CRM_Core_OptionGroup::values('event_type');

    if ($id) {
      return $options[$id] ?? NULL;
    }
    return $options;
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * next time it's requested.
   *
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'cache') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
  }

}
