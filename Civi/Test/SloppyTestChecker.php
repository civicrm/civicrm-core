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
    \CRM_Utils_Array::flatten($startSnapshot, $flatStart, '', ': ');
    \CRM_Utils_Array::flatten($endSnapshot, $flatEnd, '', ': ');
    $removed = array_diff($flatStart, $flatEnd);
    $added = array_diff($flatEnd, $flatStart);

    $buf = [];
    foreach (array_keys($removed) as $key) {
      if (isset($added[$key])) {
        $buf[] = sprintf("- Baseline data has changed (%s: %s ==> %s)", $key, json_encode($removed[$key]), json_encode($added[$key]));
        unset($removed[$key]);
        unset($added[$key]);
      }
    }
    foreach ($removed as $key => $value) {
      $buf[] = sprintf("- Baseline data has gone missing (%s=%s)", $key, json_encode($value));
    }
    foreach ($added as $key => $value) {
      $buf[] = sprintf("- Found unexpected left-overs (%s=%s)", $key, json_encode($value));
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

    $snapshot = [];
    $snapshot['tables'] = array_combine($tables, $tables);
    $snapshot['view'] = array_combine($views, $views);
    $snapshot['extensions'] = static::fetchAll('SELECT full_name, is_active FROM %s.civicrm_extension ORDER BY full_name', ['full_name']);
    $snapshot['custom_groups'] = static::fetchAll('SELECT id, name FROM %s.civicrm_custom_group ORDER BY name', ['name']);
    $snapshot['custom_fields'] = static::fetchAll('SELECT id, name, custom_group_id FROM %s.civicrm_custom_field ORDER BY name', ['name']);
    $snapshot['settings'] = static::fetchAll('SELECT name, domain_id, value FROM %s.civicrm_setting WHERE name NOT IN ("resCacheCode") ORDER BY name, domain_id', ['name', 'domain_id']);
    // $snapshot['settings'] = static::fetchAll('SELECT name, domain_id, value FROM %s.civicrm_setting ORDER BY name, domain_id', ['name', 'domain_id']);
    $snapshot['contacts'] = static::fetchAll('SELECT id, display_name FROM %s.civicrm_contact', ['display_name']);
    $snapshot['events'] = static::fetchAll('SELECT id, title FROM %s.civicrm_event', ['title']);
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
      $result[implode(' ', $key)] = $item;
    }
    return $result;
  }

}
