<?php

namespace Civi\Test;

/**
 * Detect sloppy tests.
 *
 * GOALS
 *
 * Suppose you have these tests:
 *
 *   function setupHeadless() { Civi\Test::headless()->apply(); }
 *   function test1() { doStuff(); }
 *   function test2() { doOtherStuff(); }
 *   function test3() { doMoreStuff(); }
 *
 * These tests are all based on the same headless configuration.
 * Ideally, we only want to initialize the database once and quickly run
 * through all the test functions.
 *
 * However, some tests might be sloppy -- perhaps `test2()` destroys
 * an important setting, which causes `test3()` to behave erratically.
 * (`test3()` works on its own but fails in combination with `test2()`.)
 *
 * The symptoms will present on `test3()`, but the blame lies with `test2()`.
 * This class does some sanity-checks and works to point the finger at `test2()`.
 *
 * USAGE
 *
 * This feature is opt-in. (Detecting sloppy tests requires extra computation...
 * How much? Not sure yet... Let's get some data before we consider making it mandatory...)
 *
 * You may enable by setting one of these environment variables:
 *
 * - CIVICRM_SLOPPY_TEST=STDERR             Print warnings to STDERR
 * - CIVICRM_SLOPPY_TEST=Exception          Throw an exception
 * - CIVICRM_SLOPPY_TEST=@/tmp/sloppy.log   Append to a log file
 */
class SloppyTestChecker {

  public static function isActive(): bool {
    return !empty(getenv('CIVICRM_SLOPPY_TEST'));
  }

  protected static function emit(string $message): void {
    $mode = getenv('CIVICRM_SLOPPY_TEST');

    if ($mode[0] === '@') {
      $file = substr($mode, 1);
      $now = date('Y-m-d H:i:s P');
      file_put_contents($file, "[$now] $message", FILE_APPEND);
      return;
    }

    switch ($mode) {
      case 'Exception':
        throw new \LogicException(__CLASS__ . ': ' . $message);

      case 'STDERR':
      default:
        fwrite(STDERR, "\n$message\n");
        break;
    }
  }

  /**
   * Determine if $startSnapshot and $endSnapshot match. If they don't,
   * complain about it and blame the $lastParty/$currentParty.
   *
   * @param array $startSnapshot
   * @param array $endSnapshot
   * @param string $lastParty
   *   Ex: 'FooBarTest::testOne()'
   * @param string $currentParty
   *   Ex: 'FooBarTest::testTwo()'
   */
  public static function doComparison(array $startSnapshot, array $endSnapshot, string $lastParty, string $currentParty): void {
    $buf = [];

    $buckets = array_keys($startSnapshot); /* Defined programmatically to be the same */
    foreach ($buckets as $bucket) {
      $startBucket = $startSnapshot[$bucket];
      $endBucket = $endSnapshot[$bucket];

      $removedKeys = array_diff(array_keys($startBucket), array_keys($endBucket));
      $addedKeys = array_diff(array_keys($endBucket), array_keys($startBucket));
      $commonKeys = array_intersect(array_keys($startBucket), array_keys($endBucket));

      foreach ($commonKeys as $key) {
        if (is_array($startBucket[$key])) {
          ksort($startBucket[$key]);
        }
        if (is_array($endBucket[$key])) {
          ksort($endBucket[$key]);
        }
        if ($startBucket[$key] !== $endBucket[$key]) {
          $buf[] = sprintf("- [%s] Baseline data has changed for \"%s\":\n    OLD: %s\n    NEW: %s", $bucket, $key,
            json_encode($startBucket[$key], JSON_UNESCAPED_SLASHES),
            json_encode($endBucket[$key], JSON_UNESCAPED_SLASHES));
        }
      }
      foreach ($removedKeys as $key) {
        $buf[] = sprintf("- [%s] Baseline data has gone missing for \"%s\"", $bucket, $key);
      }
      foreach ($addedKeys as $key) {
        $buf[] = sprintf("- [%s] Found unexpected left-over item \"%s\": %s", $bucket, $key, json_encode($endBucket[$key]));
      }
    }

    if ($buf) {
      // The current environment is dirty. Not suitable for re-use.
      $query = sprintf('DELETE FROM %s.civitest_revs', \Civi\Test::dsn('database'));
      \Civi\Test::execute($query);

      array_unshift($buf, sprintf("- Environment last configured for '%s'", $lastParty));
      $buf[] = sprintf("- Now preparing environment for '%s'", $currentParty);
      $buf[] = sprintf("- Reinitialization will be forced", $currentParty);
      $buf[] = sprintf("- Recommendations:");
      $buf[] = sprintf("    - Fix the test-case to cleanup its mess, or...");
      $buf[] = sprintf("    - Mark the test-case environment as useOnce()");
      $problems = implode("\n", $buf);
      static::emit(sprintf("DETECTED SLOPPY TEST:\n%s\n", $problems));
    }
  }

  public static function createSnapshot(): array {
    $tables = \Civi\Test::schema()->getTables('BASE TABLE');
    $views = \Civi\Test::schema()->getTables('VIEW');

    $ignoreSettings = '("resCacheCode")';

    $snapshot = [];
    $snapshot['tables'] = array_combine($tables, $tables);
    $snapshot['view'] = array_combine($views, $views);
    $snapshot['civicrm_extension'] = static::fetchAll('SELECT full_name, is_active FROM %s.civicrm_extension ORDER BY full_name', ['full_name']);
    $snapshot['civicrm_component'] = static::fetchAll('SELECT id, name, namespace FROM %s.civicrm_component', ['name']);
    $snapshot['civicrm_custom_group'] = static::fetchAll('SELECT id, name FROM %s.civicrm_custom_group ORDER BY name', ['name']);
    $snapshot['civicrm_custom_field'] = static::fetchAll('SELECT id, name, custom_group_id FROM %s.civicrm_custom_field ORDER BY name', ['name']);
    $snapshot['civicrm_setting'] = static::fetchAll("SELECT name, domain_id, value FROM %s.civicrm_setting WHERE name NOT IN $ignoreSettings ORDER BY name, domain_id", ['name', 'domain_id']);
    $snapshot['civicrm_preferences_date'] = static::fetchAll('SELECT id, name, start, end, date_format, time_format FROM %s.civicrm_preferences_date ORDER BY name', ['name']);
    $snapshot['civicrm_domain'] = static::fetchAll('SELECT name, version, locales, locale_custom_strings FROM %s.civicrm_domain', ['name']);
    $snapshot['civicrm_membership_status'] = static::fetchAll('SELECT * FROM %s.civicrm_membership_status ORDER BY name', ['name']);

    $snapshot['civicrm_worldregion'] = static::fetchAll('SELECT id, name FROM %s.civicrm_worldregion ORDER BY name', ['name']);
    $snapshot['civicrm_timezone'] = static::fetchAll('SELECT name, abbreviation, gmt FROM %s.civicrm_timezone ORDER BY name', ['name']);
    $snapshot['civicrm_country'] = static::fetchAll('SELECT * FROM %s.civicrm_country ORDER BY name', ['name']);
    $snapshot['civicrm_currency'] = static::fetchAll('SELECT id, name, symbol, numeric_code FROM %s.civicrm_currency ORDER BY name', ['name']);

    // Maybe: civicrm_contact_type, civicrm_financial_type, civicrm_case_type, civicrm_dashboard, civicrm_dedupe_rule,
    // civicrm_mail_settings, civicrm_membership_type, civicrm_membership_status

    // These have a mix of reserved and bespoke data. Only reserved is tracked.
    $snapshot['civicrm_msg_template'] = static::fetchAll('SELECT id, msg_title, msg_subject, workflow_name, is_active, is_default FROM %s.civicrm_msg_template WHERE is_reserved = 1 ORDER BY id', ['id']);
    $snapshot['civicrm_location_type'] = static::fetchAll('SELECT * FROM %s.civicrm_location_type WHERE is_reserved = 1 ORDER BY name', ['name']);
    $snapshot['civicrm_option_group'] = static::fetchAll('SELECT * FROM %s.civicrm_option_group WHERE is_reserved = 1 ORDER BY name', ['name']);
    $snapshot['civicrm_option_value'] = static::fetchAll('SELECT * FROM %s.civicrm_option_value WHERE is_reserved = 1 ORDER BY option_group_id, name', ['option_group_id', 'name']);
    $snapshot['civicrm_relationship_type'] = static::fetchAll('SELECT * FROM %s.civicrm_relationship_type WHERE is_reserved = 1 ORDER BY name_a_b', ['name_a_b']);

    // Normalize various parts of snapshot to make it easier to compare

    // In "civicrm_domain", the locale_custom_strings may be stored as empty... in different ways...
    $domains = &$snapshot['civicrm_domain'];
    foreach ($domains as $key => $domain) {
      if (isset($domains[$key]['locale_custom_strings'])) {
        $domains[$key]['locale_custom_strings'] = \CRM_Utils_String::unserialize($domains[$key]['locale_custom_strings']);
        if (empty($domains[$key]['locale_custom_strings']['en_US'])) {
          unset($domains[$key]['locale_custom_strings']['en_US']);
        }
      }
    }

    // In "civicrm_setting", a value of NULL is equivalent to a non-existent value.
    $settings = &$snapshot['civicrm_setting'];
    foreach (array_keys($settings) as $key) {
      if ($settings[$key]['value'] === NULL) {
        unset($settings[$key]);
      }
      else {
        $settings[$key]['value'] = \CRM_Utils_String::unserialize($settings[$key]['value']);
      }
    }

    return $snapshot;
  }

  protected static function fetchAll(string $query, array $index): array {
    $query = sprintf($query, \Civi\Test::dsn('database'));
    $result = [];
    foreach (\Civi\Test::pdo()->query($query, \PDO::FETCH_ASSOC)->fetchAll() as $item) {
      $key = [];
      foreach ($index as $indexPart) {
        $key[] = $item[$indexPart] ?? 'NULL';
      }
      unset($item['modified_date'], $item['created_date']);
      $result[implode(' ', $key)] = $item;
    }
    return $result;
  }

}
