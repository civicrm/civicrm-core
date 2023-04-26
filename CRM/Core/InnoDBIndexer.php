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
 * The InnoDB indexer is responsible for creating and destroying
 * full-text indices on InnoDB classes.
 */
class CRM_Core_InnoDBIndexer {
  const IDX_PREFIX = 'civicrm_fts_';

  /**
   * @var CRM_Core_InnoDBIndexer
   */
  private static $singleton = NULL;

  /**
   * @param bool $fresh
   * @return CRM_Core_InnoDBIndexer
   */
  public static function singleton($fresh = FALSE) {
    if ($fresh || self::$singleton === NULL) {
      $indices = [
        'civicrm_address' => [
          ['street_address', 'city', 'postal_code'],
        ],
        'civicrm_activity' => [
          ['subject', 'details'],
        ],
        'civicrm_contact' => [
          ['sort_name', 'nick_name', 'display_name'],
        ],
        'civicrm_contribution' => [
          ['source', 'amount_level', 'trxn_Id', 'invoice_id'],
        ],
        'civicrm_email' => [
          ['email'],
        ],
        'civicrm_membership' => [
          ['source'],
        ],
        'civicrm_note' => [
          ['subject', 'note'],
        ],
        'civicrm_participant' => [
          ['source', 'fee_level'],
        ],
        'civicrm_phone' => [
          ['phone'],
        ],
        'civicrm_tag' => [
          ['name'],
        ],
      ];
      $active = Civi::settings()->get('enable_innodb_fts');
      self::$singleton = new self($active, $indices);
    }
    return self::$singleton;
  }

  /**
   * (Setting Callback)
   * Respond to changes in the "enable_innodb_fts" setting
   *
   * @param bool $oldValue
   * @param bool $newValue
   */
  public static function onToggleFts($oldValue, $newValue): void {
    if (empty($oldValue) && empty($newValue)) {
      return;
    }

    $indexer = CRM_Core_InnoDBIndexer::singleton();
    $indexer->setActive($newValue);
    $indexer->fixSchemaDifferences();
  }

  /**
   * Indices.
   *
   * (string $table => array $indices)
   *
   * ex: $indices['civicrm_contact'][0] = array('first_name', 'last_name');
   *
   * @var array
   */
  protected $indices;

  /**
   * @var bool
   */
  protected $isActive;

  /**
   * Class constructor.
   *
   * @param bool $isActive
   * @param array $indices
   */
  public function __construct($isActive, $indices) {
    $this->isActive = $isActive;
    $this->indices = $this->normalizeIndices($indices);
  }

  /**
   * Fix schema differences.
   *
   * Limitation: This won't pick up stale indices on tables which are not
   * declared in $this->indices. That's not much of an issue for now b/c
   * we have a static list of tables.
   */
  public function fixSchemaDifferences() {
    foreach ($this->indices as $tableName => $ign) {
      $todoSqls = $this->reconcileIndexSqls($tableName);
      foreach ($todoSqls as $todoSql) {
        CRM_Core_DAO::executeQuery($todoSql);
      }
    }
  }

  /**
   * Determine if an index is expected to exist.
   *
   * @param string $table
   * @param array $fields
   *   List of field names that must be in the index.
   * @return bool
   */
  public function hasDeclaredIndex($table, $fields) {
    if (!$this->isActive) {
      return FALSE;
    }

    if (isset($this->indices[$table])) {
      foreach ($this->indices[$table] as $idxFields) {
        // TODO determine if $idxFields must be exact match or merely a subset
        // if (sort($fields) == sort($idxFields)) {
        if (array_diff($fields, $idxFields) == []) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Get a list of FTS index names that are currently defined in the database.
   *
   * @param string $table
   *
   * @return array
   *   (string $indexName => string $indexName)
   */
  public function findActualFtsIndexNames(string $table): array {
    $dao = CRM_Core_DAO::executeQuery("
  SELECT index_name as index_name
  FROM information_Schema.STATISTICS
  WHERE table_schema = DATABASE()
    AND table_name = '$table'
    AND index_type = 'FULLTEXT'
  GROUP BY index_name
    ");

    $indexNames = [];
    while ($dao->fetch()) {
      $indexNames[$dao->index_name] = $dao->index_name;
    }
    return $indexNames;
  }

  /**
   * Generate a "CREATE INDEX" statement for each desired
   * FTS index.
   *
   * @param $table
   *
   * @return array
   *   (string $indexName => string $sql)
   */
  public function buildIndexSql($table): array {
    // array (string $idxName => string $sql)
    $sqls = [];
    if ($this->isActive && isset($this->indices[$table])) {
      foreach ($this->indices[$table] as $fields) {
        $name = self::IDX_PREFIX . md5($table . '::' . implode(',', $fields));
        $sqls[$name] = sprintf('CREATE FULLTEXT INDEX %s ON %s (%s)', $name, $table, implode(',', $fields));
      }
    }
    return $sqls;
  }

  /**
   * Generate a "DROP INDEX" statement for each existing FTS index.
   *
   * @param string $table
   *
   * @return array
   *   (string $idxName => string $sql)
   */
  public function dropIndexSql($table) {
    $sqls = [];
    $names = $this->findActualFtsIndexNames($table);
    foreach ($names as $name) {
      $sqls[$name] = sprintf("DROP INDEX %s ON %s", $name, $table);
    }
    return $sqls;
  }

  /**
   * Construct a set of SQL statements which will create (or preserve)
   * required indices and destroy unneeded indices.
   *
   * @param string $table
   *
   * @return array
   */
  public function reconcileIndexSqls($table) {
    $buildIndexSqls = $this->buildIndexSql($table);
    $dropIndexSqls = $this->dropIndexSql($table);

    $allIndexNames = array_unique(array_merge(
      array_keys($dropIndexSqls),
      array_keys($buildIndexSqls)
    ));

    $todoSqls = [];
    foreach ($allIndexNames as $indexName) {
      if (isset($buildIndexSqls[$indexName]) && isset($dropIndexSqls[$indexName])) {
        // already exists
      }
      elseif (isset($buildIndexSqls[$indexName])) {
        $todoSqls[] = $buildIndexSqls[$indexName];
      }
      else {
        $todoSqls[] = $dropIndexSqls[$indexName];
      }
    }
    return $todoSqls;
  }

  /**
   * Put the indices into a normalized format.
   *
   * @param $indices
   * @return array
   */
  public function normalizeIndices($indices) {
    $result = [];
    foreach ($indices as $table => $indicesByTable) {
      foreach ($indicesByTable as $k => $fields) {
        sort($fields);
        $result[$table][] = $fields;
      }
    }
    return $result;
  }

  /**
   * Setter for isActive.
   *
   * @param bool $isActive
   */
  public function setActive($isActive) {
    $this->isActive = $isActive;
  }

  /**
   * Getter for isActive.
   *
   * @return bool
   */
  public function getActive() {
    return $this->isActive;
  }

}
