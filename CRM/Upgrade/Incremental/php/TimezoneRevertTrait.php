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
 * Circa v5.47.x-5.48.x, the `civicrm_event.event_tz` columns were introduced. But there were still
 * significant issues.
 *
 * This rollsback the schema changes. The same logic should be used for various upgrade-paths
 * involving 5.47.x or 5.48.x, so it helps to use a trait shared by them.
 */
trait CRM_Upgrade_Incremental_php_TimezoneRevertTrait {

  public function createEventTzPreUpgradeMessage(): string {
    if (self::areEventsUsingTimestamp() && self::areThereAnyCiviEvents()) {
      $timezoneStats = $this->getTimezoneStats();
      return '<div><span>' . ts('CiviEvent Timezone Rollback') . '</span><ul><li>'
        . ts('CiviEvent v5.47.0 briefly introduced new timezone functionality. This will be removed, and times will be converted back to their old format.')
        . '</li><li>'
        . ts('Unfortunately, <em>CiviEvent times may be inaccurate</em>. To prevent or fix inaccuracies, please review <a %1>CiviEvent v5.47 Timezone Notice</a>.', [
          1 => 'target="_blank" href="https://civicrm.org/redirect/event-timezone-5.47"',
        ])
        . '</li><li>'
        . ts('The conversion will be performed with your active timezone (<strong><code>%1</code></strong>, <strong><code>%2</code></strong>).', [
          1 => CRM_Core_Config::singleton()->userSystem->getTimeZoneString(),
          2 => CRM_Core_Config::singleton()->userSystem->getTimeZoneOffset(),
        ])
        . '</li><li>'
        . ts('The database has %1 events which all use the same timezone (<strong><code>%2</code></strong>).', [
          1 => array_sum(array_column($timezoneStats, 'count')),
          2 => $timezoneStats[0]['name'],
          3 => count($timezoneStats),
          4 => implode(', ', array_map(
            function($stat) {
              return sprintf('<strong><code>%s</code></strong> [%dx]', htmlentities($stat['name']), $stat['count']);
            },
            $timezoneStats
          )),
          'plural' => 'The database has %1 events with %3 timezones (%4).',
          'count' => count($timezoneStats),
        ])
        . '</li></ul></div>';
    }
    return '';
  }

  public function createEventTzPostUpgradeMessage(): string {
    // Note that setPostUpgradeMessage is called at the start of the step,
    // before its queued tasks run, so we are examining the database
    // before updating the fields.
    if (self::areThereAnyCiviEvents() && self::areEventsUsingTimestamp()) {
      return '<div><span>' . ts('CiviEvent Timezones') . '</span><ul><li>' . ts('Please check your CiviEvents for the correct time. <a %1>(Learn more...)</a>', [1 => 'target="_blank" href="https://civicrm.org/redirect/event-timezone-5.47"']) . '</li></ul></div>';
    }
    return '';
  }

  public function addEventTzTasks(): void {
    if (self::areEventsUsingTimestamp()) {
      $this->addTask('Add temporary backup start_date to civicrm_event', 'addColumn', 'civicrm_event', 'start_date_ts_bak', "timestamp NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
      $this->addTask('Add temporary backup end_date to civicrm_event', 'addColumn', 'civicrm_event', 'end_date_ts_bak', "timestamp NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
      $this->addTask('Add temporary backup registration_start_date to civicrm_event', 'addColumn', 'civicrm_event', 'registration_start_date_ts_bak', "timestamp NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
      $this->addTask('Add temporary backup registration_end_date to civicrm_event', 'addColumn', 'civicrm_event', 'registration_end_date_ts_bak', "timestamp NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
      $this->addTask('Fill Backup Event Dates', 'fillBackupEventDates');
      $this->addTask('Revert Event Dates', 'revertEventDates');
    }
  }

  /**
   * dev/core#2122 - keep a copy of converted dates
   * In theory we could skip this step if logging is enabled, but (a) people
   * might turn off logging before running upgrades, and (b) there may not be a
   * complete record anyway. People can drop the new column if they don't need
   * it.
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function fillBackupEventDates(CRM_Queue_TaskContext $ctx): bool {
    // We only run if the field is timestamp, so don't need to check about that.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_event SET start_date_ts_bak = start_date, end_date_ts_bak = end_date, registration_start_date_ts_bak = registration_start_date, registration_end_date_ts_bak = registration_end_date');
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_event CHANGE COLUMN event_tz event_tz_bak text NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
    return TRUE;
  }

  /**
   * dev/core#2122 - undo timestamp conversion from 5.47
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function revertEventDates(CRM_Queue_TaskContext $ctx): bool {
    // We only run if the field is timestamp, so don't need to check about that.

    // The original 5.47.alpha1 upgrade was executed with SQL helpers in CRM_Utils_File, which use
    // a separate DSN/session. We need to use the same interface so that the `@@time_zone` is consistent
    // with the prior update.

    $sql = "ALTER TABLE `civicrm_event`
      MODIFY COLUMN `start_date` datetime DEFAULT NULL COMMENT 'Date and time that event starts.',
      MODIFY COLUMN `end_date` datetime DEFAULT NULL COMMENT 'Date and time that event ends. May be NULL if no defined end date/time',
      MODIFY COLUMN `registration_start_date` datetime DEFAULT NULL COMMENT 'Date and time that online registration starts.',
      MODIFY COLUMN `registration_end_date` datetime DEFAULT NULL COMMENT 'Date and time that online registration ends.';";

    $upgrade = new CRM_Upgrade_Form();
    $upgrade->source($sql, TRUE);

    return TRUE;
  }

  /**
   * Check if civicrm_event start_date is a timestamp.
   * @return bool
   */
  private static function areEventsUsingTimestamp(): bool {
    $dao = CRM_Core_DAO::executeQuery("SHOW COLUMNS FROM civicrm_event LIKE 'start_date'");
    if ($dao->fetch()) {
      return (strtolower($dao->Type) === 'timestamp');
    }
    return FALSE;
  }

  /**
   * Are there any events in the system?
   * @return bool
   */
  private static function areThereAnyCiviEvents(): bool {
    return (bool) CRM_Core_DAO::singleValueQuery('SELECT COUNT(id) FROM civicrm_event');
  }

  private function getTimezoneStats(): array {
    $dao = CRM_Core_DAO::executeQuery('SELECT event_tz, COUNT(*) AS `count` FROM civicrm_event GROUP BY event_tz ORDER BY COUNT(event_tz) DESC');
    $r = [];
    while ($dao->fetch()) {
      $r[] = ['name' => $dao->event_tz ?: ts('Empty'), 'count' => $dao->count];
    }
    return $r;
  }

}
