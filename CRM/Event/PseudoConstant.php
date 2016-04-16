<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This class holds all the Pseudo constants that are specific to Event. This avoids
 * polluting the core class and isolates the Event
 */
class CRM_Event_PseudoConstant extends CRM_Core_PseudoConstant {

  /**
   * Event
   *
   * @var array
   */
  private static $event;

  /**
   * Participant Status
   *
   * @var array
   */
  private static $participantStatus;

  /**
   * Participant Role
   *
   * @var array
   */
  private static $participantRole;

  /**
   * Participant Listing
   *
   * @var array
   */
  private static $participantListing;

  /**
   * Event Type.
   *
   * @var array
   */
  private static $eventType;

  /**
   * Event template titles
   * @var array
   */
  private static $eventTemplates;

  /**
   * Personal campaign pages
   * @var array
   */
  private static $pcPage;

  /**
   * Get all the n events
   *
   *
   * @param int $id
   * @param bool $all
   * @param null $condition
   *
   * @return array
   *   array of all events if any
   */
  public static function event($id = NULL, $all = FALSE, $condition = NULL) {
    $key = "{$id}_{$all}_{$condition}";

    if (!isset(self::$event[$key])) {
      self::$event[$key] = array();
    }

    if (!self::$event[$key]) {
      CRM_Core_PseudoConstant::populate(self::$event[$key],
        'CRM_Event_DAO_Event',
        $all, 'title', 'is_active', $condition, NULL
      );
    }

    if ($id) {
      if (array_key_exists($id, self::$event[$key])) {
        return self::$event[$key][$id];
      }
      else {
        return NULL;
      }
    }
    return self::$event[$key];
  }

  /**
   * Get all the n participant statuses.
   *
   *
   * @param int $id
   * @param null $cond
   * @param string $retColumn
   *   Tells populate() whether to return 'name' (default) or 'label' values.
   *
   * @return array
   *   array reference of all participant statuses if any
   */
  public static function &participantStatus($id = NULL, $cond = NULL, $retColumn = 'name') {
    if (self::$participantStatus === NULL) {
      self::$participantStatus = array();
    }

    $index = $cond ? $cond : 'No Condition';
    $index = "{$index}_{$retColumn}";
    if (!CRM_Utils_Array::value($index, self::$participantStatus)) {
      self::$participantStatus[$index] = array();
      CRM_Core_PseudoConstant::populate(self::$participantStatus[$index],
        'CRM_Event_DAO_ParticipantStatusType',
        FALSE, $retColumn, 'is_active', $cond, 'weight'
      );
    }

    if ($id) {
      return self::$participantStatus[$index][$id];
    }

    return self::$participantStatus[$index];
  }

  /**
   * Get participant status class options.
   *
   * @return array
   */
  public static function participantStatusClassOptions() {
    return array(
      'Positive' => ts('Positive'),
      'Pending' => ts('Pending'),
      'Waiting' => ts('Waiting'),
      'Negative' => ts('Negative'),
    );
  }

  /**
   * Return a status-type-keyed array of status classes
   *
   * @return array
   *   Array of status classes, keyed by status type
   */
  public static function &participantStatusClass() {
    static $statusClasses = NULL;

    if ($statusClasses === NULL) {
      self::populate($statusClasses, 'CRM_Event_DAO_ParticipantStatusType', TRUE, 'class');
    }

    return $statusClasses;
  }

  /**
   * Get all the n participant roles.
   *
   *
   * @param int $id
   * @param null $cond
   *
   * @return array
   *   array reference of all participant roles if any
   */
  public static function &participantRole($id = NULL, $cond = NULL) {
    $index = $cond ? $cond : 'No Condition';
    if (!CRM_Utils_Array::value($index, self::$participantRole)) {
      self::$participantRole[$index] = array();

      $condition = NULL;

      if ($cond) {
        $condition = "AND $cond";
      }

      self::$participantRole[$index] = CRM_Core_OptionGroup::values('participant_role', FALSE, FALSE,
        FALSE, $condition
      );
    }

    if ($id) {
      return self::$participantRole[$index][$id];
    }
    return self::$participantRole[$index];
  }

  /**
   * Get all the participant listings.
   *
   *
   * @param int $id
   *
   * @return array
   *   array reference of all participant listings if any
   */
  public static function &participantListing($id = NULL) {
    if (!self::$participantListing) {
      self::$participantListing = array();
      self::$participantListing = CRM_Core_OptionGroup::values('participant_listing');
    }

    if ($id) {
      return self::$participantListing[$id];
    }

    return self::$participantListing;
  }

  /**
   * Get all  event types.
   *
   *
   * @param int $id
   * @return array
   *   array reference of all event types.
   */
  public static function &eventType($id = NULL) {
    if (!self::$eventType) {
      self::$eventType = array();
      self::$eventType = CRM_Core_OptionGroup::values('event_type');
    }

    if ($id) {
      return self::$eventType[$id];
    }

    return self::$eventType;
  }

  /**
   * Get event template titles.
   *
   * @param int $id
   *
   * @return array
   *   Array of event id → template title pairs
   */
  public static function &eventTemplates($id = NULL) {
    if (!self::$eventTemplates) {
      CRM_Core_PseudoConstant::populate(self::$eventTemplates,
        'CRM_Event_DAO_Event',
        FALSE,
        'template_title',
        'is_active',
        'is_template = 1'
      );
    }
    if ($id) {
      return self::$eventTemplates[$id];
    }
    return self::$eventTemplates;
  }

  /**
   * Flush given pseudoconstant so it can be reread from db
   * nex time it's requested.
   *
   *
   * @param bool|string $name pseudoconstant to be flushed
   */
  public static function flush($name = 'cache') {
    if (isset(self::$$name)) {
      self::$$name = NULL;
    }
  }

  /**
   * Get all the Personal campaign pages.
   *
   *
   * @param int $id
   * @return array
   *   array reference of all pcp if any
   */
  public static function &pcPage($id = NULL) {
    if (!self::$pcPage) {
      CRM_Core_PseudoConstant::populate(self::$pcPage,
        'CRM_PCP_DAO_PCP',
        FALSE, 'title'
      );
    }
    if ($id) {
      return CRM_Utils_Array::value($id, self::$pcPage);
    }
    return self::$pcPage;
  }

}
