<?php

namespace Civi\Test;

use RuntimeException;

/**
 * Class Schema
 *
 * Manage the entire database. This is useful for destroying or loading the schema.
 */
class Schema {

  /**
   * @param string $type
   *   'BASE TABLE' or 'VIEW'.
   * @return array
   */
  public function getTables($type) {
    $pdo = \Civi\Test::pdo();
    // only consider real tables and not views
    $query = sprintf(
      "SELECT table_name FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = %s AND TABLE_TYPE = %s",
      $pdo->quote(\Civi\Test::dsn('database')),
      $pdo->quote($type)
    );
    $tables = $pdo->query($query);
    $result = [];
    if (!empty($tables)) {
      foreach ($tables as $table) {
        $result[] = $table['TABLE_NAME'] ?? $table['table_name'];
      }
    }
    return $result;
  }

  public function setStrict($checks) {
    $dbName = \Civi\Test::dsn('database');
    if ($checks) {
      $queries = [
        "USE {$dbName};",
        "SET global innodb_flush_log_at_trx_commit = 1;",
        "SET SQL_MODE='STRICT_ALL_TABLES';",
        "SET foreign_key_checks = 1;",
      ];
    }
    else {
      $queries = [
        "USE {$dbName};",
        "SET foreign_key_checks = 0",
        "SET SQL_MODE='STRICT_ALL_TABLES';",
        "SET global innodb_flush_log_at_trx_commit = 2;",
      ];
    }
    foreach ($queries as $query) {
      if (\Civi\Test::execute($query) === FALSE) {
        throw new RuntimeException("Query failed: $query");
      }
    }
    return $this;
  }

  public function dropAll() {
    $queries = [];
    foreach ($this->getTables('VIEW') as $table) {
      if (preg_match('/^(civicrm_|log_)/', $table)) {
        $queries[] = "DROP VIEW $table";
      }
    }

    foreach ($this->getTables('BASE TABLE') as $table) {
      if (preg_match('/^(civicrm_|log_)/', $table)) {
        $queries[] = "DROP TABLE $table";
      }
    }

    $this->setStrict(FALSE);
    foreach ($queries as $query) {
      if (\Civi\Test::execute($query) === FALSE) {
        throw new RuntimeException("dropSchema: Query failed: $query");
      }
    }
    $this->setStrict(TRUE);

    return $this;
  }

  /**
   * @return Schema
   */
  public function truncateAll() {
    $tables = \Civi\Test::schema()->getTables('BASE TABLE');

    $truncates = [];
    $drops = [];
    foreach ($tables as $table) {
      // skip log tables
      if (substr($table, 0, 4) == 'log_') {
        continue;
      }

      // don't change list of installed extensions
      if ($table == 'civicrm_extension') {
        continue;
      }

      if (substr($table, 0, 14) == 'civicrm_value_') {
        $drops[] = 'DROP TABLE ' . $table . ';';
      }
      elseif (substr($table, 0, 9) == 'civitest_') {
        // ignore
      }
      else {
        $truncates[] = 'TRUNCATE ' . $table . ';';
      }
    }

    \Civi\Test::schema()->setStrict(FALSE);
    $queries = array_merge($truncates, $drops);
    foreach ($queries as $query) {
      if (\Civi\Test::execute($query) === FALSE) {
        throw new RuntimeException("Query failed: $query");
      }
    }
    \Civi\Test::schema()->setStrict(TRUE);

    return $this;
  }

  /**
   * Load a snapshot into CiviCRM's database.
   *
   * @param string $file
   *   Ex: '/path/to/civicrm-4.5.6-foobar.sql.bz2' or '/path/to/civicrm-4.5.6-foobar.mysql.gz'
   * @return Schema
   */
  public function loadSnapshot(string $file) {
    $dsn = \Civi\Test::dsn();
    $defaultsFile = $this->createMysqlDefaultsFile($dsn);

    $pipeline = [];

    if (preg_match(';sql.bz2$;', $file)) {
      $pipeline[] = sprintf('bzip2 -d -c %s', escapeshellarg($file));
    }
    elseif (preg_match(';sql.gz$;', $file)) {
      $pipeline[] = sprintf('gzip -d -c %s ', escapeshellarg($file));
    }
    else {
      $pipeline[] = sprintf('cat %s', escapeshellarg($file));
    }

    // Rewrite the "DEFINER". This regex is fine because we're handling
    // test snapshots and because the new definer comes from a trusted source.
    // Hypothetically, for untrusted snapshots, you might want something better.
    $definer = $this->getEscapedDefiner();
    $pipeline[] = sprintf('sed %s', escapeshellarg('s/DEFINER=`[^`]*`@`[^`]*`/DEFINER=' . $definer . '/g'));

    $pipeline[] = sprintf('mysql --defaults-file=%s %s', escapeshellarg($defaultsFile), escapeshellarg($dsn['database']));
    $cmd = implode(' | ', $pipeline);
    ProcessHelper::runOk($cmd);
    return $this;
  }

  /**
   * When calling "mysql" subprocess, it helps to put DB credentials into "my.cnf"-style file.
   *
   * @param array $dsn
   * @return string
   *   Path to the new "my.cnf" file.
   */
  protected function createMysqlDefaultsFile(array $dsn): string {
    $data = "[client]\n";
    $data .= "host={$dsn['hostspec']}\n";
    $data .= "user={$dsn['username']}\n";
    $data .= "password={$dsn['password']}\n";
    if (!empty($dsn['port'])) {
      $data .= "port={$dsn['port']}\n";
    }

    $file = sys_get_temp_dir() . '/my.cnf-' . hash('sha256', __FILE__ . stat(__FILE__)['mtime'] . $data);
    if (!file_exists($file)) {
      if (!file_put_contents($file, $data)) {
        throw new \RuntimeException("Failed to create temporary my.cnf connection file.");
      }
    }
    return $file;
  }

  protected function getEscapedDefiner(): string {
    $pdo = \Civi\Test::pdo();
    $definer = $pdo->query('SELECT user()')->fetchColumn();
    [$user, $host] = explode('@', $definer);
    $user = trim($user, '`');
    $host = trim($host, '`');
    return "`{$user}`@`{$host}`";
  }

  /**
   * Seeded tables whose AUTO_INCREMENT is offset before Data::populate() loads
   * the seed data, so that the seeded rows themselves do not start at id 1.
   *
   * Tables are added in batches as the related failures are fixed.
   * Do not add civicrm_domain: domain id 1 is assumed throughout.
   */
  public const SEED_TABLES_TO_OFFSET = [
    'civicrm_component',
    'civicrm_contact_type',
    'civicrm_dedupe_rule',
    'civicrm_dedupe_rule_group',
    'civicrm_entity_financial_account',
    'civicrm_financial_account',
    'civicrm_financial_type',
    'civicrm_job',
    'civicrm_location_type',
    'civicrm_mailing_bounce_pattern',
    'civicrm_mailing_bounce_type',
    'civicrm_mailing_component',
    'civicrm_membership_status',
    'civicrm_msg_template',
    'civicrm_option_group',
    'civicrm_option_value',
    'civicrm_participant_status_type',
    'civicrm_payment_processor_type',
    'civicrm_preferences_date',
    'civicrm_price_field',
    'civicrm_price_field_value',
    'civicrm_price_set',
    'civicrm_relationship_type',
    'civicrm_site_email_address',
    'civicrm_site_token',
    'civicrm_tag',
    'civicrm_uf_field',
    'civicrm_uf_group',
    'civicrm_uf_join',
  ];

  /**
   * This sets the AUTO_INCREMENT to flush out tests or code that muddle ids
   * of test entities but pass because the ids are all low starting at 1
   *
   * @param string[]|null $tables
   *   Only set these tables (e.g. the ones just truncated), or NULL for all
   *   tables. A table gets the same offset either way.
   *
   * @return Schema
   */
  public function setAutoIncrement(?array $tables = NULL) {
    // More than any seeded table's row count (civicrm_option_value has ~900),
    // so seeded tables' id ranges don't overlap.
    $separation = 1000;
    $autoIncrement = 1;

    $dbName = \Civi\Test::dsn('database');

    $pdo = \Civi\Test::pdo();
    // Only tables with an auto-increment column (AUTO_INCREMENT is NULL for the others).
    // Order by name so each table gets the same offset on every call.
    $query = sprintf(
      "SELECT table_name FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = %s AND TABLE_TYPE = 'BASE TABLE' AND AUTO_INCREMENT IS NOT NULL
        ORDER BY table_name",
      $pdo->quote($dbName)
    );
    $tablesToSet = $tables === NULL ? NULL : array_flip($tables);
    $queries = [];
    foreach ($pdo->query($query)->fetchAll(\PDO::FETCH_COLUMN) as $table_name) {
      // The offset comes from the table's position in the full list, so it is
      // the same whichever tables are being set.
      $autoIncrement += $separation;
      if ($tablesToSet === NULL || isset($tablesToSet[$table_name])) {
        $queries[] = "ALTER TABLE `$dbName`.`$table_name` AUTO_INCREMENT=$autoIncrement;";
      }
    }
    // execute() throws if a query fails.
    \Civi\Test::execute(implode("\n", $queries));
    return $this;
  }

}
