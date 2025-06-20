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
class CRM_Core_I18n_Schema {

  /**
   * Drop all views (for use by CRM_Core_DAO::dropAllTables() mostly).
   */
  public static function dropAllViews() {
    $locales = CRM_Core_I18n::getMultilingual();
    if (!$locales) {
      return;
    }

    $tables = CRM_Core_I18n_SchemaStructure::tables();

    foreach ($locales as $locale) {
      foreach ($tables as $table) {
        CRM_Core_DAO::executeQuery("DROP VIEW IF EXISTS {$table}_{$locale}");
      }
    }
  }

  /**
   * Switch database from single-lang to multi (by adding
   * the first language and dropping the original columns).
   *
   * @param string $locale
   *   the first locale to create (migrate to).
   */
  public static function makeMultilingual($locale) {
    $isUpdateDone = FALSE;
    $domain = new CRM_Core_BAO_Domain();
    $domain->find();
    $domains = [];
    while ($domain->fetch()) {
      // We need to build an array to iterate through here as something later down clears
      // the cache on the fetch results & causes only the first to be retrieved.
      $domains[] = clone $domain;
    }
    foreach ($domains as $domain) {
      // skip if the domain is already multi-lang.
      if ($domain->locales) {
        continue;
      }

      if (!$isUpdateDone) {
        $isUpdateDone = self::alterTablesToSupportMultilingual($locale);
      }

      // update civicrm_domain.locales
      $domain->locales = $locale;
      $domain->save();

      // CRM-21627 Updates the $dbLocale
      CRM_Core_BAO_ConfigSetting::applyLocale(Civi::settings($domain->id), $domain->locales);
    }
  }

  /**
   * Switch database from multi-lang back to single (by dropping
   * additional columns and views and retaining only the selected locale).
   *
   * @param string $retain
   *   the locale to retain.
   */
  public static function makeSinglelingual($retain) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = CRM_Core_Config::domainID();
    $domain->find(TRUE);
    $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);

    // break early if the db is already single-lang
    if (!$locales) {
      return;
    }

    // lets drop all triggers first
    $logging = new CRM_Logging_Schema();
    $logging->dropTriggers();

    // turn subsequent tables singlelingual
    $tables = CRM_Core_I18n_SchemaStructure::tables();
    foreach ($tables as $table) {
      self::makeSinglelingualTable($retain, $table);
    }

    // update civicrm_domain.locales
    $domain->locales = 'NULL';
    $domain->save();

    //CRM-6963 -fair assumption.
    global $dbLocale;
    $dbLocale = '';

    // now lets rebuild all triggers
    self::clearCaches();
  }

  /**
   * Switch a given table from multi-lang to single (by retaining only the selected locale).
   *
   * @param string $retain
   *   the locale to retain.
   * @param string $table
   *   the table containing the column.
   * @param string $class
   *   schema structure class to use to recreate indices.
   *
   * @param array $triggers
   */
  public static function makeSinglelingualTable(
    $retain,
    $table,
    $class = 'CRM_Core_I18n_SchemaStructure',
    $triggers = []
  ) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = CRM_Core_Config::domainID();
    $domain->find(TRUE);
    $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);

    // break early if the db is already single-lang
    if (!$locales) {
      return;
    }

    $columns =& $class::columns();
    $indices =& $class::indices();
    $queries = [];
    $dropQueries = [];
    // drop indices
    if (isset($indices[$table])) {
      foreach ($indices[$table] as $index) {
        foreach ($locales as $loc) {
          $queries[] = "DROP INDEX {$index['name']}_{$loc} ON {$table}";
        }
      }
    }

    $dao = new CRM_Core_DAO();
    // deal with columns
    foreach ($columns[$table] as $column => $type) {
      $queries[] = "ALTER TABLE {$table} CHANGE `{$column}_{$retain}` `{$column}` {$type}";
      foreach ($locales as $loc) {
        if (strcmp($loc, $retain) !== 0) {
          $dropQueries[] = "ALTER TABLE {$table} DROP {$column}_{$loc}";
        }
      }
    }

    // drop views
    foreach ($locales as $loc) {
      $queries[] = "DROP VIEW IF EXISTS {$table}_{$loc}";
    }

    // add original indices
    $queries = array_merge($queries, self::createIndexQueries(NULL, $table));

    // execute the queries without i18n rewriting
    $dao = new CRM_Core_DAO();
    foreach ($queries as $query) {
      $dao->query($query, FALSE);
    }

    foreach ($dropQueries as $query) {
      $dao->query($query, FALSE);
    }

    if (!empty($triggers)) {
      if (CRM_Core_Config::isUpgradeMode()) {
        foreach ($triggers as $triggerInfo) {
          $when = $triggerInfo['when'];
          $event = $triggerInfo['event'];
          $triggerName = "{$table}_{$when}_{$event}";
          CRM_Core_DAO::executeQuery("DROP TRIGGER IF EXISTS {$triggerName}");
        }
      }
    }
  }

  /**
   * Add a new locale to a multi-lang db, setting
   * its values to the current default locale.
   *
   * @param string $locale
   *   the new locale to add.
   * @param string $source
   *   the locale to copy from.
   */
  public static function addLocale($locale, $source) {
    // get the current supported locales
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = CRM_Core_Config::domainID();
    $domain->find(TRUE);
    $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);

    // break early if the locale is already supported
    if (in_array($locale, $locales)) {
      return;
    }

    $dao = new CRM_Core_DAO();

    // build the required SQL queries
    $columns = CRM_Core_I18n_SchemaStructure::columns();
    $indices = CRM_Core_I18n_SchemaStructure::indices();
    $queries = [];
    foreach ($columns as $table => $hash) {
      // add new columns
      foreach ($hash as $column => $type) {
        // CRM-7854: skip existing columns
        if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, "{$column}_{$locale}", FALSE)) {
          continue;
        }
        $queries[] = "ALTER TABLE {$table} ADD {$column}_{$locale} {$type}";
        $queries[] = "UPDATE {$table} SET {$column}_{$locale} = {$column}_{$source}";
      }

      // add view
      $queries[] = self::createViewQuery($locale, $table, $dao);

      // add new indices
      $queries = array_merge($queries, array_values(self::createIndexQueries($locale, $table)));
    }

    // execute the queries without i18n rewriting
    foreach ($queries as $query) {
      $dao->query($query, FALSE);
    }

    // update civicrm_domain.locales
    $locales[] = $locale;
    $domain->locales = implode(CRM_Core_DAO::VALUE_SEPARATOR, $locales);
    $domain->save();

    self::clearCaches();
  }

  /**
   * Rebuild multilingual indices, views and triggers (useful for upgrades)
   *
   * @param array $locales
   *   locales to be rebuilt.
   * @param string $version
   *   version of schema structure to use.
   * @param bool $isUpgradeMode
   *   Are we upgrading our database
   */
  public static function rebuildMultilingualSchema($locales, $version = NULL, $isUpgradeMode = FALSE) {
    if ($version) {
      $latest = self::getLatestSchema($version);
      require_once "CRM/Core/I18n/SchemaStructure_{$latest}.php";
      $class = "CRM_Core_I18n_SchemaStructure_{$latest}";
    }
    else {
      $class = 'CRM_Core_I18n_SchemaStructure';
    }
    $indices =& $class::indices();
    $tables =& $class::tables();
    $queries = [];
    $dao = new CRM_Core_DAO();

    // get all of the already existing indices
    $existing = [];
    foreach (array_keys($indices) as $table) {
      $existing[$table] = [];
      $dao->query("SHOW INDEX FROM $table", FALSE);
      while ($dao->fetch()) {
        if (preg_match('/_[a-z][a-z]_[A-Z][A-Z]$/', $dao->Key_name)) {
          $existing[$table][] = $dao->Key_name;
        }
      }
    }

    // from all of the CREATE INDEX queries fetch the ones creating missing indices
    foreach ($locales as $locale) {
      foreach (array_keys($indices) as $table) {
        $allQueries = self::createIndexQueries($locale, $table, $class);
        foreach ($allQueries as $name => $query) {
          if (!in_array("{$name}_{$locale}", $existing[$table])) {
            $queries[] = $query;
          }
        }
      }
    }

    // rebuild views
    $logging_enabled = \Civi::settings()->get('logging');

    foreach ($locales as $locale) {
      foreach ($tables as $table) {
        $queries[] = self::createViewQuery($locale, $table, $dao, $class, $isUpgradeMode);

        if ($logging_enabled) {
          $queries[] = self::createViewQuery($locale, 'log_' . $table, $dao, $class, $isUpgradeMode);
        }
      }
    }

    // rebuild triggers
    $last = array_pop($locales);

    foreach ($queries as $query) {
      $dao->query($query, FALSE);
    }

    // invoke the meta trigger creation call
    CRM_Core_DAO::triggerRebuild();
  }

  /**
   * Rewrite SQL query to use views to access tables with localized columns.
   *
   * @param string $query
   *   the query for rewrite.
   *
   * @return string
   *   the rewritten query
   */
  public static function rewriteQuery($query) {
    global $dbLocale;
    $tables = self::schemaStructureTables();
    foreach ($tables as $table) {
      // CRM-19093
      // should match the civicrm table name such as: civicrm_event
      // but must not match the table name if it's a substring of another table: civicrm_events_in_cart
      $query = preg_replace("/([^'\"])({$table})(\z|[^a-z_'\"])/", "\\1\\2{$dbLocale}\\3", $query);
    }
    // uncomment the below to rewrite the civicrm_value_* queries
    // $query = preg_replace("/(civicrm_value_[a-z0-9_]+_\d+)([^_])/", "\\1{$dbLocale}\\2", $query);
    return $query;
  }

  /**
   * @param null $version
   * @param bool $force
   *
   * @return array
   */
  public static function schemaStructureTables($version = NULL, $force = FALSE) {
    static $_tables = NULL;
    if ($_tables === NULL || $force) {
      if ($version) {
        $latest = self::getLatestSchema($version);
        // FIXME: Doing require_once is a must here because a call like CRM_Core_I18n_SchemaStructure_4_1_0 makes
        // class loader look for file like - CRM/Core/I18n/SchemaStructure/4/1/0.php which is not what we want to be loaded
        require_once "CRM/Core/I18n/SchemaStructure_{$latest}.php";
        $class = "CRM_Core_I18n_SchemaStructure_{$latest}";
        $tables =& $class::tables();
      }
      else {
        $tables = CRM_Core_I18n_SchemaStructure::tables();
      }
      $_tables = $tables;
    }
    return $_tables;
  }

  /**
   * @param $version
   *
   * @return mixed
   */
  public static function getLatestSchema($version) {
    // remove any .upgrade sub-str from version. Makes it easy to do version_compare & give right result
    $version = str_ireplace(".upgrade", "", $version);

    // fetch all the SchemaStructure versions we ship and sort by version
    $schemas = [];
    foreach (scandir(dirname(__FILE__)) as $file) {
      $matches = [];
      if (preg_match('/^SchemaStructure_([0-9a-z_]+)\.php$/', $file, $matches)) {
        $schemas[] = str_replace('_', '.', $matches[1]);
      }
    }
    usort($schemas, 'version_compare');

    // find the latest schema structure older than (or equal to) $version
    do {
      $latest = array_pop($schemas);
    } while (version_compare($latest, $version, '>'));

    return str_replace('.', '_', $latest);
  }

  /**
   * CREATE INDEX queries for a given locale and table.
   *
   * @param string $locale
   *   locale for which the queries should be created (null to create original indices).
   * @param string $table
   *   table for which the queries should be created.
   * @param string $class
   *   schema structure class to use.
   *
   * @return array
   *   array of CREATE INDEX queries
   */
  private static function createIndexQueries($locale, $table, $class = 'CRM_Core_I18n_SchemaStructure') {
    $indices =& $class::indices();
    $columns =& $class::columns();
    if (!isset($indices[$table])) {
      return [];
    }

    $queries = [];
    foreach ($indices[$table] as $index) {
      $unique = isset($index['unique']) && $index['unique'] ? 'UNIQUE' : '';
      foreach ($index['field'] as $i => $col) {
        // if a given column is localizable, extend its name with the locale
        if ($locale and isset($columns[$table][$col])) {
          $index['field'][$i] = "{$col}_{$locale}";
        }
      }
      $cols = implode(', ', $index['field']);
      $name = $index['name'];
      if ($locale) {
        $name .= '_' . $locale;
      }
      // CRM-7854: skip existing indices
      if (CRM_Core_DAO::checkConstraintExists($table, $name)) {
        continue;
      }
      $queries[$index['name']] = "CREATE {$unique} INDEX {$name} ON {$table} ({$cols})";
    }
    return $queries;
  }

  /**
   * CREATE VIEW query for a given locale and table.
   *
   * @param string $locale
   *   locale of the view.
   * @param string $table
   *   table of the view.
   * @param CRM_Core_DAO $dao
   *   A DAO object to run DESCRIBE queries.
   * @param string $class
   *   schema structure class to use.
   * @param bool $isUpgradeMode
   *   Are we in upgrade mode therefore only build based off table not class
   * @return string
   *   The generated CREATE VIEW query
   */
  private static function createViewQuery($locale, $table, &$dao, $class = 'CRM_Core_I18n_SchemaStructure', $isUpgradeMode = FALSE) {
    $columns =& $class::columns();
    $cols = [];
    $tableCols = [];
    $db = $dao->_database;
    $lookup_table = $table;

    if (substr($table, 0, 4) == 'log_') {
      $lookup_table = substr($table, 4);
      $dsn = defined('CIVICRM_LOGGING_DSN') ? CRM_Utils_SQL::autoSwitchDSN(CIVICRM_LOGGING_DSN) : CRM_Utils_SQL::autoSwitchDSN(CIVICRM_DSN);
      $dsn = DB::parseDSN($dsn);
      $db = $dsn['database'];
    }
    $dao->query("DESCRIBE `{$db}`.{$table}", FALSE);

    while ($dao->fetch()) {
      // view non-internationalized columns directly
      if (!in_array($dao->Field, array_keys($columns[$lookup_table])) &&
        !preg_match('/_[a-z][a-z]_[A-Z][A-Z]$/', $dao->Field)
      ) {
        $cols[] = '`' . $dao->Field . '`';
      }
      $tableCols[] = $dao->Field;
    }
    // view internationalized columns through an alias
    foreach ($columns[$lookup_table] as $column => $_) {
      if (!$isUpgradeMode) {
        $cols[] = "`{$column}_{$locale}` `{$column}`";
      }
      elseif (in_array("{$column}_{$locale}", $tableCols)) {
        $cols[] = "`{$column}_{$locale}` `{$column}`";
      }
    }
    return "CREATE OR REPLACE VIEW `{$db}`.{$table}_{$locale} AS SELECT " . implode(', ', $cols) . " FROM `{$db}`.{$table}";
  }

  /**
   * @param $info
   * @param null $tableName
   */
  public static function triggerInfo(&$info, $tableName = NULL) {
    // get the current supported locales
    $locales = CRM_Core_I18n::getMultilingual();
    if (!$locales) {
      return;
    }

    $locale = array_pop($locales);

    // CRM-10027
    if (count($locales) == 0) {
      return;
    }

    $currentVer = CRM_Core_BAO_Domain::version(TRUE);

    if ($currentVer && CRM_Core_Config::isUpgradeMode()) {
      // take exact version so that proper schema structure file in invoked
      $latest = self::getLatestSchema($currentVer);
      require_once "CRM/Core/I18n/SchemaStructure_{$latest}.php";
      $class = "CRM_Core_I18n_SchemaStructure_{$latest}";
    }
    else {
      $class = 'CRM_Core_I18n_SchemaStructure';
    }

    $columns =& $class::columns();

    foreach ($columns as $table => $hash) {
      if ($tableName &&
        $tableName != $table
      ) {
        continue;
      }

      $trigger = [];

      foreach ($hash as $column => $_) {
        $trigger[] = "IF NEW.{$column}_{$locale} IS NOT NULL AND NEW.{$column}_{$locale} != '' THEN";
        foreach ($locales as $old) {
          $trigger[] = "IF NEW.{$column}_{$old} IS NULL OR NEW.{$column}_{$old} = '' THEN SET NEW.{$column}_{$old} = NEW.{$column}_{$locale}; END IF;";
        }
        foreach ($locales as $old) {
          $trigger[] = "ELSEIF NEW.{$column}_{$old} IS NOT NULL AND NEW.{$column}_{$old} != '' THEN";
          foreach (array_merge($locales, [
            $locale,
          ]) as $loc) {
            if ($loc == $old) {
              continue;
            }
            $trigger[] = "IF NEW.{$column}_{$loc} IS NULL OR NEW.{$column}_{$loc} = '' THEN SET NEW.{$column}_{$loc} = NEW.{$column}_{$old}; END IF;";
          }
        }
        $trigger[] = 'END IF;';
      }

      $sql = implode(' ', $trigger);
      $info[] = [
        'table' => [$table],
        'when' => 'BEFORE',
        'event' => ['INSERT', 'UPDATE'],
        'sql' => $sql,
      ];
    }

  }

  /**
   * Alter tables to the structure to support multilingual.
   *
   * This alters the db structure to use language specific field names for
   * localised fields and adds the relevant views.
   *
   * @param string $locale
   *
   * @return bool
   */
  protected static function alterTablesToSupportMultilingual($locale): bool {
    $dao = new CRM_Core_DAO();

    // build the column-adding SQL queries
    $columns = CRM_Core_I18n_SchemaStructure::columns();
    $indices = CRM_Core_I18n_SchemaStructure::indices();
    $queries = [];
    foreach ($columns as $table => $hash) {
      // drop old indices
      if (isset($indices[$table])) {
        foreach ($indices[$table] as $index) {
          if (CRM_Core_BAO_SchemaHandler::checkIfIndexExists($table, $index['name'])) {
            $queries[] = "DROP INDEX {$index['name']} ON {$table}";
          }
        }
      }
      // deal with columns
      foreach ($hash as $column => $type) {
        $queries[] = "ALTER TABLE {$table} ADD {$column}_{$locale} {$type}";
        if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column)) {
          $queries[] = "UPDATE {$table} SET {$column}_{$locale} = {$column}";
          $queries[] = "ALTER TABLE {$table} DROP {$column}";
        }
      }

      // add view
      $queries[] = self::createViewQuery($locale, $table, $dao);

      // add new indices
      $queries = array_merge($queries, array_values(self::createIndexQueries($locale, $table)));
    }

    // execute the queries without i18n rewriting
    foreach ($queries as $query) {
      $dao->query($query, FALSE);
    }
    return TRUE;
  }

  /**
   * Clear relevant caches after changing available languages
   * @return void
   */
  private static function clearCaches() {
    Civi::rebuild([
      // Clear metadata in case it holds any language-specific info
      'metadata' => TRUE,
      // Flush translated string cache
      'strings' => TRUE,
      // Rebuild sql triggers because i18n schema is trigger-based
      'triggers' => TRUE,
      // Reconcile managed entities because some are language-specific
      'entities' => TRUE,
    ])->execute();
  }

}
