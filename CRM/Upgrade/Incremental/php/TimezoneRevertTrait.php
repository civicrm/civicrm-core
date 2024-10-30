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
      $actions = getenv('CIVICRM_TZ_REVERT')
        ? explode(',', getenv('CIVICRM_TZ_REVERT'))
        : ['backup', 'revert', 'adapt'];
      if (in_array('backup', $actions)) {
        $this->addTask('Add temporary backup start_date to civicrm_event', 'addColumn', 'civicrm_event', 'start_date_ts_bak', "timestamp NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
        $this->addTask('Add temporary backup end_date to civicrm_event', 'addColumn', 'civicrm_event', 'end_date_ts_bak', "timestamp NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
        $this->addTask('Add temporary backup registration_start_date to civicrm_event', 'addColumn', 'civicrm_event', 'registration_start_date_ts_bak', "timestamp NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
        $this->addTask('Add temporary backup registration_end_date to civicrm_event', 'addColumn', 'civicrm_event', 'registration_end_date_ts_bak', "timestamp NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'");
        $this->addTask('Backup CiviEvent times', 'fillBackupEventDates');
      }
      if (in_array('revert', $actions)) {
        $this->addTask('Revert CiviEvent times', 'revertEventDates');
      }
      if (in_array('adapt', $actions)) {
        $this->addTask('Adapt CiviEvent times', 'convertModifiedEvents');
      }
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
    // don't try to localize since original was not localizable
    CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_event CHANGE COLUMN event_tz event_tz_bak text NULL DEFAULT NULL COMMENT 'For troubleshooting upgrades post 5.47. Can drop this column if no issues.'", [], TRUE, NULL, FALSE, FALSE);
    // need to rebuild since otherwise view is out of date
    $locales = CRM_Core_I18n::getMultilingual();
    if ($locales) {
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL, TRUE);
    }
    return TRUE;
  }

  /**
   * This is the straight-up opposite of the conversion done in `5.47.alpha1`.
   * It flips the `TIMESTAMP`s back to `DATETIME`s. This should be a clean/straight
   * revert - provided that the records have not changed.
   *
   * But some records may have changed. `convertModifiedEvents()` will address those.
   *
   * @param \CRM_Queue_TaskContext|null $ctx
   * @return bool
   */
  public static function revertEventDates(?CRM_Queue_TaskContext $ctx = NULL): bool {
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
   * If a user edited an `Event` in the UI while running 5.47.alpha1 - 5.47.2, then `revertEventDates`
   * won't be good enough. In particular:
   *
   * - It's likely to have activated the DST bug (based on the current-user's TZ).
   * - The user could have filled-in/corrected the values of `event_tz` and/or each `TIMESTAMP` column.
   *
   * The algorithm reads backup values (eg `start_date_ts_bak`) and rewrites live values (eg `start_date`)
   *
   * It uses a heuristic approach to cleaning DST error ("skew"). This requires a _representative_
   * timezone ("skewTz"). It should not be necessary to know the exact TZ of every edit -- as long as all TZs
   * have similar DST rules. For example:
   *
   *   - Most locales in US+CA have the same DST rules. (`America/Los_Angeles` and `America/Chicago` are equally representative.)
   *   - Most locales in Europe have the same DST rules. (`Europe/Helsinki` and `Europe/Berlin` are equally representative.)
   *   - Most locales in Australia have the same DST rules.
   *
   * By default, this will borrow the current user (sysadmin)'s timezone as the representative skewTz.
   * This can be overridden with env-var `CIVICRM_TZ_SKEW`.
   *
   * @param \CRM_Queue_TaskContext|null $ctx
   * @return bool
   */
  public static function convertModifiedEvents(?CRM_Queue_TaskContext $ctx = NULL): bool {
    $skewTz = self::pickSkewTz();
    $mysqlTz = '+0:00';
    $restoreMysqlTz = static::swapTz($mysqlTz);

    $columns = [
      'start_date' => 'start_date_ts_bak',
      'end_date' => 'end_date_ts_bak',
      'registration_start_date' => 'registration_start_date_ts_bak',
      'registration_end_date' => 'registration_end_date_ts_bak',
    ];

    [$lowLogId, $highLogId] = self::findLogRange(
      '5.47.alpha1',
      (static::class === CRM_Upgrade_Incremental_php_FiveFortySeven::class) ? '5.47.3' : '5.48.beta2'
    );

    $eventModTimes = CRM_Utils_SQL_Select::from('civicrm_log')
      ->select('entity_id, max(modified_date) as modified_date')
      ->where('entity_table = "civicrm_event"')
      ->where('id >= #lowLogId AND id <= #highLogId', ['lowLogId' => $lowLogId, 'highLogId' => $highLogId])
      ->groupBy('entity_table, entity_id')
      ->execute()
      ->fetchMap('entity_id', 'modified_date');
    if (empty($eventModTimes)) {
      return TRUE;
    }

    $events = CRM_Utils_SQL_Select::from('civicrm_event')
      ->select(['id', 'event_tz_bak'])
      ->select(array_values($columns))
      ->where('id in (#IDS)', ['IDS' => array_keys($eventModTimes)])
      ->execute()
      ->fetchAll();

    foreach ($events as $event) {
      $updates = [];
      foreach ($columns as $outColumn => $inColumn) {
        if (!empty($event[$inColumn])) {
          $dstError = ($skewTz === 'IGNORE') ? 0 : static::findDstError($eventModTimes[$event['id']], $event[$inColumn], $skewTz);
          // $event["{$inColumn}_err"] = $dstError;
          $newValue = static::addSeconds(static::convertTz($event[$inColumn], $mysqlTz, $event['event_tz_bak']), $dstError);
          $updates[] = $outColumn . ' = "' . CRM_Core_DAO::escapeString($newValue) . '"';
        }
        else {
          $updates[] = $outColumn . ' = NULL';
        }
      }
      $sql = sprintf('UPDATE civicrm_event SET %s WHERE id = %d', implode(',', $updates), (int) $event['id']);
      // printf("\n[UTC, skewTz=%s]\n%s\n%s\n", $skewTz, json_encode($event), $sql);
      CRM_Core_DAO::executeQuery($sql);
    }

    return TRUE;
  }

  public static function findDstError(string $modificationTime, string $targetValue, string $timeZone): int {
    $tzObj = new DateTimeZone($timeZone);
    $objA = new DateTime($modificationTime, $tzObj);
    $objB = new DateTime($targetValue, $tzObj);
    return $objA->getOffset() - $objB->getOffset();
  }

  public static function addSeconds(string $dateTime, int $skew): string {
    if (!$skew) {
      return $dateTime;
    }
    return date('Y-m-d H:i:s', strtotime($dateTime) + $skew);
  }

  public static function convertTz(string $dateTimeExpr, string $srcTz, string $destTz) {
    $datetime = new DateTime($dateTimeExpr, new DateTimeZone($srcTz));
    $datetime->setTimezone(new DateTimeZone($destTz));
    return $datetime->format('Y-m-d H:i:s');
  }

  protected static function swapTz($newTz): CRM_Utils_AutoClean {
    $startTz = CRM_Core_DAO::singleValueQuery('SELECT @@time_zone');
    CRM_Core_DAO::executeQuery('SET time_zone = %1', [1 => ['+0:00', 'String']]);
    return CRM_Utils_AutoClean::with(function() use ($startTz) {
      CRM_Core_DAO::executeQuery('SET time_zone = %1', [1 => [$startTz, 'String']]);
    });
  }

  /**
   * Choose a representative timezone for identifying DST errors.
   *
   * Preference will be given to ENV['CIVICRM_TZ_SKEW'] or the current user's TZ.
   *
   * An explict value of `IGNORE` will opt-out of skew correction.
   *
   * @return string
   */
  protected static function pickSkewTz(): string {
    $skewTz = getenv('CIVICRM_TZ_SKEW');
    if (!$skewTz) {
      $skewTz = CRM_Core_Config::singleton()->userSystem->getTimeZoneString();
    }
    if (!$skewTz) {
      return 'IGNORE';
    }
    return $skewTz;
  }

  /**
   * Find the slice of `civicrm_log` which occurred between version $X and version $Y.
   *
   * @param string $lowVersion
   * @param string $highVersion
   * @return array
   */
  protected static function findLogRange(string $lowVersion, string $highVersion): array {
    $lowLog = CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_log WHERE entity_table = "civicrm_domain" AND data LIKE %1 ORDER BY id LIMIT 1', [
      1 => ['upgrade%' . $lowVersion . '.upgrade', 'String'],
    ]);
    $lowLogId = $lowLog->fetch() ? $lowLog->id : 1;

    $highLog = CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_log WHERE entity_table = "civicrm_domain" AND data LIKE %1 ORDER BY id LIMIT 1', [
      1 => ['upgrade%' . $highVersion . '.upgrade', 'String'],
    ]);
    $highLogId = $highLog->fetch() ? $highLog->id : CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM civicrm_log');
    return [$lowLogId, $highLogId];
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
