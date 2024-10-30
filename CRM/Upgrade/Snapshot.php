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

use Civi\Test\Invasive;

/**
 * Provide helpers for recording data snapshots during an upgrade.
 */
class CRM_Upgrade_Snapshot {

  public static $pageSize = 50 * 1000;

  /**
   * How long should we retain old snapshots?
   *
   * Time is measured in terms of MINOR versions - eg "4" means "retain for 4 MINOR versions".
   * Thus, on v5.60, you could delete any snapshots predating 5.56.
   *
   * @var int
   */
  public static $cleanupAfter = 4;

  /**
   * List of reasons why the snapshots are not running.
   *
   * @var array|null
   */
  private static $activationIssues;

  /**
   * Get a list of reasons why the snapshots should not run.
   *
   * @return array
   *   List of printable messages.
   */
  public static function getActivationIssues(): array {
    if (static::$activationIssues === NULL) {
      $policy = CRM_Utils_Constant::value('CIVICRM_UPGRADE_SNAPSHOT', 'auto');
      static::$activationIssues = [];

      $version = CRM_Utils_SQL::getDatabaseVersion();
      if ((stripos($version, 'mariadb') !== FALSE) && version_compare($version, '10.6.0', '>=')) {
        // MariaDB briefly (10.6.0-10.6.5) flirted with the idea of phasing-out `COMPRESSED`. By default, snapshots won't work on those versions.
        // https://mariadb.com/kb/en/innodb-compressed-row-format/#read-only
        $roCompressed = CRM_Core_DAO::singleValueQuery('SELECT @@innodb_read_only_compressed');
        if (in_array($roCompressed, ['on', 'ON', 1, '1'])) {
          static::$activationIssues['row_compressed'] = ts('This MariaDB instance does not allow creating compressed snapshots.');
        }
      }

      if ($policy === TRUE) {
        return static::$activationIssues;
      }

      $limits = [
        'civicrm_contact' => 200 * 1000,
        'civicrm_contribution' => 200 * 1000,
        'civicrm_activity' => 200 * 1000,
        'civicrm_case' => 200 * 1000,
        'civicrm_mailing' => 200 * 1000,
        'civicrm_event' => 200 * 1000,
      ];

      foreach ($limits as $table => $limit) {
        try {
          // Use select MAX(id) rather than COUNT as COUNT is slow on large databases.
          $max = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM `{$table}`");
        }
        catch (\Exception $e) {
          $max = 0;
        }
        if ($max > $limit) {
          static::$activationIssues["count_{$table}"] = ts('Table "%1" has a large number of records (%2 > %3).', [
            1 => $table,
            2 => $max,
            3 => $limit,
          ]);
        }
      }

      if (CRM_Core_I18n::isMultilingual()) {
        static::$activationIssues['multilingual'] = ts('Multilingual snapshots have not been implemented.');
      }

      if ($policy === FALSE) {
        static::$activationIssues['override'] = ts('Snapshots disabled by override (CIVICRM_UPGRADE_SNAPSHOT).');
      }
    }

    return static::$activationIssues;
  }

  /**
   * Create the name of a MySQL snapshot table.
   *
   * @param string $owner
   *   Name of the component/module/extension that owns the snapshot.
   *   Ex: 'civicrm', 'sequentialcreditnotes', 'oauth_client'
   * @param string $version
   *   Ex: '5.50'
   * @param string $name
   *   Ex: 'dates'
   * @return string
   *   Ex: 'snap_civicrm_v5_50_dates'
   * @throws \CRM_Core_Exception
   *   If the resulting table name would be invalid, then this throws an exception.
   */
  public static function createTableName(string $owner, string $version, string $name): string {
    $versionParts = explode('.', $version);
    if (count($versionParts) !== 2) {
      throw new \CRM_Core_Exception("Snapshot support is currently only defined for two-part version (MAJOR.MINOR). Found ($version).");
      // If you change this, be sure to consider `cleanupTask()` as well.
      // One reason you might change it -- if you were going to track with the internal schema-numbers from an extension.
      // Of course, you could get similar effect with "0.{$schemaNumber}" eg "5002" ==> "0.5002"
    }
    $versionExpr = ($versionParts[0] . '_' . $versionParts[1]);

    $table = sprintf('snap_%s_v%s_%s', $owner, $versionExpr, $name);
    if (!preg_match(';^[a-z0-9_]+$;', $table)) {
      throw new CRM_Core_Exception("Malformed snapshot name ($table)");
    }
    if (strlen($table) > 64) {
      throw new CRM_Core_Exception("Snapshot name is too long ($table)");
    }

    return $table;
  }

  /**
   * Build a set of queueable tasks which will store a snapshot.
   *
   * @param string $owner
   *   Name of the component/module/extension that owns the snapshot.
   *   Ex: 'civicrm', 'sequentialcreditnotes', 'oauth_client'
   * @param string $version
   *   Ex: '5.50'
   * @param string $name
   * @param \CRM_Utils_SQL_Select $select
   * @throws \CRM_Core_Exception
   */
  public static function createTasks(string $owner, string $version, string $name, CRM_Utils_SQL_Select $select): iterable {
    $destTable = static::createTableName($owner, $version, $name);
    $srcTable = Invasive::get([$select, 'from']);

    // Sometimes, backups fail and people rollback and try again. Reset prior snapshots.
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `{$destTable}`");

    $maxId = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM `{$srcTable}`");
    $pageSize = CRM_Upgrade_Snapshot::$pageSize;
    for ($offset = 0; $offset <= $maxId; $offset += $pageSize) {
      $title = ts('Create snapshot from "%1" (%2: %3 => %4)', [
        1 => $srcTable,
        2 => $name,
        3 => $offset,
        4 => $offset + $pageSize,
      ]);
      $pageSelect = $select->copy()->where('id >= #MIN AND id < #MAX', [
        'MIN' => $offset,
        'MAX' => $offset + $pageSize,
      ]);
      $sqlAction = ($offset === 0) ? "CREATE TABLE {$destTable} ROW_FORMAT=COMPRESSED AS " : "INSERT INTO {$destTable} ";
      // Note: 'CREATE TABLE AS' implicitly preserves the character-set of the source-material, so we don't set that explicitly.
      yield new CRM_Queue_Task(
        [static::class, 'insertSnapshotTask'],
        [$sqlAction . $pageSelect->toSQL()],
        $title
      );
    }
  }

  /**
   * Generate the query/queries for storing a snapshot (if the local policy supports snapshotting).
   *
   * This method does all updates in one go. It is suitable for small/moderate data-sets. If you need to support
   * larger data-sets, use createTasks() instead.
   *
   * @param string $owner
   *   Name of the component/module/extension that owns the snapshot.
   *   Ex: 'civicrm'
   * @param string $version
   *   Ex: '5.50'
   * @param string $name
   * @param string $select
   *   Raw SQL SELECT for finding data.
   * @throws \CRM_Core_Exception
   * @return string[]
   *   SQL statements to execute.
   *   May be array if there no statements to execute.
   */
  public static function createSingleTask(string $owner, string $version, string $name, string $select): array {
    $destTable = static::createTableName($owner, $version, $name);
    if (!empty(CRM_Upgrade_Snapshot::getActivationIssues())) {
      return [];
    }

    $result = [];
    $result[] = "DROP TABLE IF EXISTS `{$destTable}`";
    $result[] = "CREATE TABLE `{$destTable}` ROW_FORMAT=COMPRESSED AS {$select}";
    return $result;
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $sql
   *
   * @return bool
   * @noinspection PhpUnusedParameterInspection
   */
  public static function insertSnapshotTask(CRM_Queue_TaskContext $ctx, string $sql): bool {
    CRM_Core_DAO::executeQuery($sql);
    // If anyone works on multilingual support, you might need to set $i18nRewrite. But doesn't matter since skip ML completely.
    return TRUE;
  }

  /**
   * Cleanup any old snapshot tables.
   *
   * @param CRM_Queue_TaskContext|null $ctx
   * @param string $owner
   *   Ex: 'civicrm', 'sequentialcreditnotes', 'oauth_client'
   * @param string|null $version
   *   The current version of CiviCRM.
   * @param int|null $cleanupAfter
   *   How long should we retain old snapshots?
   *   Time is measured in terms of MINOR versions - eg "4" means "retain for 4
   *   MINOR versions". Thus, on v5.60, you could delete any snapshots
   *   predating 5.56.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   * @noinspection PhpUnusedParameterInspection
   */
  public static function cleanupTask(?CRM_Queue_TaskContext $ctx = NULL, string $owner = 'civicrm', ?string $version = NULL, ?int $cleanupAfter = NULL): bool {
    $version = $version ?: CRM_Core_BAO_Domain::version();
    $cleanupAfter = $cleanupAfter ?: static::$cleanupAfter;

    [$major, $minor] = explode('.', $version);
    $cutoff = $major . '.' . max(0, $minor - $cleanupAfter);

    $dao = new CRM_Core_DAO();
    $query = '
      SELECT TABLE_NAME as tableName
      FROM   INFORMATION_SCHEMA.TABLES
      WHERE  TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME LIKE %1
    ';
    $tables = CRM_Core_DAO::executeQuery($query, [
      1 => ["snap_{$owner}_v%", 'String'],
    ])->fetchMap('tableName', 'tableName');

    $oldTables = array_filter($tables, function($table) use ($owner, $cutoff) {
      if (preg_match(";^snap_{$owner}_v(\d+)_(\d+)_;", $table, $m)) {
        $generatedVer = $m[1] . '.' . $m[2];
        return (bool) version_compare($generatedVer, $cutoff, '<');
      }
      return FALSE;
    });

    array_map(['CRM_Core_BAO_SchemaHandler', 'dropTable'], $oldTables);
    return TRUE;
  }

}
