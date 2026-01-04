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
    if (preg_match(';sql.bz2$;', $file)) {
      $cmd = sprintf('bzip2 -d -c %s | mysql --defaults-file=%s %s', escapeshellarg($file), escapeshellarg($defaultsFile), escapeshellarg($dsn['database']));
    }
    elseif (preg_match(';sql.gz$;', $file)) {
      $cmd = sprintf('gzip -d -c %s | mysql --defaults-file=%s %s', escapeshellarg($file), escapeshellarg($defaultsFile), escapeshellarg($dsn['database']));
    }
    else {
      $cmd = sprintf('cat %s | mysql --defaults-file=%s %s', escapeshellarg($file), escapeshellarg($defaultsFile), escapeshellarg($dsn['database']));
    }
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

  /**
   * This sets the AUTO_INCREMENT to flush out tests or code that muddle ids
   * of test entities but pass because the ids are all low starting at 1
   *
   * @return Schema
   */
  public function setAutoIncrement() {
    // Might want to tweak this
    $separation = 100;
    $autoIncrement = 1;

    // This is a list of all standard tables (as of 2025/05/26)
    // The intention is to gradually comment out tables from this list and fix the related failures.
    // This continues until the entire list is commented out.  Or the earth is struck by a giant asteroid...

    $excluded_tables = [
      'civicrm_acl',
      // 'civicrm_acl_cache',
      // 'civicrm_acl_contact_cache',
      'civicrm_acl_entity_role',
      // 'civicrm_action_log',
      'civicrm_action_schedule',
      'civicrm_activity',
      'civicrm_activity_contact',
      'civicrm_address',
      // 'civicrm_address_format',
      // 'civicrm_afform_submission',
      'civicrm_batch',
      // 'civicrm_cache',
      'civicrm_campaign',
      'civicrm_campaign_group',
      'civicrm_case',
      'civicrm_case_activity',
      'civicrm_case_contact',
      'civicrm_case_type',
      // 'civicrm_component',
      'civicrm_contact',
      // 'civicrm_contact_type',
      'civicrm_contribution',
      'civicrm_contribution_page',
      'civicrm_contribution_product',
      'civicrm_contribution_recur',
      'civicrm_contribution_soft',
      'civicrm_contribution_widget',
      // 'civicrm_country',
      // 'civicrm_county',
      // 'civicrm_currency',
      'civicrm_custom_field',
      'civicrm_custom_group',
      // 'civicrm_dashboard',
      'civicrm_dashboard_contact',
      // 'civicrm_dedupe_exception',
      'civicrm_dedupe_rule',
      'civicrm_dedupe_rule_group',
      // 'civicrm_domain',
      'civicrm_email',
      'civicrm_entity_batch',
      'civicrm_entity_file',
      'civicrm_entity_financial_account',
      'civicrm_entity_financial_trxn',
      'civicrm_entity_tag',
      'civicrm_event',
      // 'civicrm_extension',
      'civicrm_file',
      'civicrm_financial_account',
      'civicrm_financial_item',
      'civicrm_financial_trxn',
      'civicrm_financial_type',
      'civicrm_group',
      'civicrm_group_contact',
      // 'civicrm_group_contact_cache',
      'civicrm_group_nesting',
      'civicrm_group_organization',
      'civicrm_im',
      // 'civicrm_install_canary',
      // 'civicrm_job',
      // 'civicrm_job_log',
      'civicrm_line_item',
      // 'civicrm_location_type',
      // 'civicrm_loc_block',
      // 'civicrm_log',
      'civicrm_mailing',
      'civicrm_mailing_abtest',
      'civicrm_mailing_bounce_pattern',
      'civicrm_mailing_bounce_type',
      'civicrm_mailing_component',
      'civicrm_mailing_event_bounce',
      'civicrm_mailing_event_confirm',
      'civicrm_mailing_event_delivered',
      'civicrm_mailing_event_opened',
      'civicrm_mailing_event_queue',
      'civicrm_mailing_event_reply',
      'civicrm_mailing_event_subscribe',
      'civicrm_mailing_event_trackable_url_open',
      'civicrm_mailing_event_unsubscribe',
      'civicrm_mailing_group',
      'civicrm_mailing_job',
      'civicrm_mailing_recipients',
      'civicrm_mailing_spool',
      'civicrm_mailing_trackable_url',
      'civicrm_mail_settings',
      // 'civicrm_managed',
      'civicrm_mapping',
      'civicrm_mapping_field',
      'civicrm_membership',
      'civicrm_membership_block',
      'civicrm_membership_log',
      'civicrm_membership_payment',
      'civicrm_membership_status',
      'civicrm_membership_type',
      // 'civicrm_menu',
      // 'civicrm_msg_template',
      // 'civicrm_navigation',
      'civicrm_note',
      'civicrm_openid',
      // 'civicrm_option_group',
      // 'civicrm_option_value',
      'civicrm_participant',
      'civicrm_participant_payment',
      'civicrm_participant_status_type',
      'civicrm_payment_processor',
      'civicrm_payment_processor_type',
      'civicrm_payment_token',
      'civicrm_pcp',
      'civicrm_pcp_block',
      'civicrm_phone',
      'civicrm_pledge',
      'civicrm_pledge_block',
      'civicrm_pledge_payment',
      // 'civicrm_preferences_date',
      'civicrm_premiums',
      'civicrm_premiums_product',
      'civicrm_prevnext_cache',
      'civicrm_price_field',
      'civicrm_price_field_value',
      'civicrm_price_set',
      'civicrm_price_set_entity',
      'civicrm_print_label',
      'civicrm_product',
      'civicrm_queue',
      'civicrm_queue_item',
      'civicrm_recurring_entity',
      'civicrm_relationship',
      // 'civicrm_relationship_cache',
      // 'civicrm_relationship_type',
      // 'civicrm_report_instance',
      // 'civicrm_role',
      'civicrm_saved_search',
      // 'civicrm_search_display',
      // 'civicrm_search_segment',
      // 'civicrm_session',
      // 'civicrm_setting',
      'civicrm_site_email_address',
      'civicrm_site_token',
      'civicrm_sms_provider',
      // 'civicrm_state_province',
      'civicrm_status_pref',
      'civicrm_subscription_history',
      'civicrm_survey',
      // 'civicrm_system_log',
      'civicrm_tag',
      'civicrm_tell_friend',
      // 'civicrm_timezone',
      // 'civicrm_totp',
      'civicrm_translation',
      'civicrm_uf_field',
      'civicrm_uf_group',
      'civicrm_uf_join',
      'civicrm_uf_match',
      'civicrm_user_job',
      'civicrm_user_role',
      'civicrm_website',
      'civicrm_word_replacement',
      // 'civicrm_worldregion',
    ];

    $dbName = \Civi\Test::dsn('database');

    $pdo = \Civi\Test::pdo();
    $query = sprintf(
      "SELECT table_name FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = %s AND TABLE_TYPE = 'BASE TABLE' AND AUTO_INCREMENT = 1",
      $pdo->quote($dbName)
    );
    $tables = $pdo->query($query);
    $queries = [
      "USE {$dbName};",
    ];

    if (!empty($tables)) {
      foreach ($tables as $table) {
        $table_name = $table['TABLE_NAME'] ?? $table['table_name'];
        if (!in_array($table_name, $excluded_tables, TRUE)) {
          $autoIncrement += $separation;
          $queries[] = "ALTER TABLE $table_name AUTO_INCREMENT=$autoIncrement;";
        }
      }
    }
    foreach ($queries as $query) {
      if (\Civi\Test::execute($query) === FALSE) {
        throw new RuntimeException("Query failed: $query");
      }
    }
    return $this;
  }

}
