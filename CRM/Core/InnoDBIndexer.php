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
 * The InnoDB indexer is responsible for creating and destroying
 * full-text indices on InnoDB classes.
 */
class CRM_Core_InnoDBIndexer {
  const IDX_PREFIX = "civicrm_fts_";

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
      $indices = array(
        'civicrm_address' => array(
          array('street_address', 'city', 'postal_code'),
        ),
        'civicrm_activity' => array(
          array('subject', 'details'),
        ),
        'civicrm_contact' => array(
          array('sort_name', 'nick_name', 'display_name'),
        ),
        'civicrm_contribution' => array(
          array('source', 'amount_level', 'trxn_Id', 'invoice_id'),
        ),
        'civicrm_email' => array(
          array('email'),
        ),
        'civicrm_membership' => array(
          array('source'),
        ),
        'civicrm_note' => array(
          array('subject', 'note'),
        ),
        'civicrm_participant' => array(
          array('source', 'fee_level'),
        ),
        'civicrm_phone' => array(
          array('phone'),
        ),
        'civicrm_tag' => array(
          array('name'),
        ),
      );
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
   * @param array $metadata
   *   Specification of the setting (per *.settings.php).
   */
  public static function onToggleFts($oldValue, $newValue, $metadata) {
    $indexer = CRM_Core_InnoDBIndexer::singleton();
    $indexer->setActive($newValue);
    $indexer->fixSchemaDifferences();
  }

  /**
   * @var array (string $table => array $indices)
   *
   * ex: $indices['civicrm_contact'][0] = array('first_name', 'last_name');
   */
  protected $indices;

  /**
   * @var bool
   */
  protected $isActive;

  /**
   * Class constructor.
   *
   * @param $isActive
   * @param $indices
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
        if (array_diff($fields, $idxFields) == array()) {
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
   * @return array
   *   (string $indexName => string $indexName)
   */
  public function findActualFtsIndexNames($table) {
    $mysqlVersion = CRM_Core_DAO::singleValueQuery('SELECT VERSION()');
    if (version_compare($mysqlVersion, '5.6', '<')) {
      // If we're not on 5.6+, then there cannot be any InnoDB FTS indices!
      // Also: information_schema.innodb_sys_indexes is only available on 5.6+.
      return array();
    }

    // Note: this only works in MySQL 5.6,  but this whole system is intended to only work in MySQL 5.6
    $sql = "
      SELECT i.name as index_name
      FROM information_schema.innodb_sys_tables t
      JOIN information_schema.innodb_sys_indexes i USING (table_id)
      WHERE t.name = concat(database(),'/$table')
      AND i.name like '" . self::IDX_PREFIX . "%'
      ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $indexNames = array();
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
  public function buildIndexSql($table) {
    $sqls = array(); // array (string $idxName => string $sql)
    if ($this->isActive && isset($this->indices[$table])) {
      foreach ($this->indices[$table] as $fields) {
        $name = self::IDX_PREFIX . md5($table . '::' . implode(',', $fields));
        $sqls[$name] = sprintf("CREATE FULLTEXT INDEX %s ON %s (%s)", $name, $table, implode(',', $fields));
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
    $sqls = array();
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

    $todoSqls = array();
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
    $result = array();
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
