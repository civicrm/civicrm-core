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
 * Table naming rules:
 *   - MySQL imposes a 64 char limit.
 *   - All temp tables start with "civicrm_tmp".
 *   - Durable temp tables: "civicrm_tmp_d_{12}_{32}"
 *   - Ephemeral temp tables: "civicrm_tmp_e_{12}_{32}"
 *
 * To use `TempTable`:
 *   - Begin by calling `CRM_Utils_SQL_TempTable::build()`.
 *   - Optionally, describe the table with `setDurable()`, `setCategory()`, `setId()`.
 *   - Finally, call `getName()` or `createWithQuery()` or `createWithColumns()`.
 *
 * Example 1: Just create a table name. You'll be responsible for CREATE/DROP actions.
 *
 * $name = CRM_Utils_SQL_TempTable::build()->getName();
 * $name = CRM_Utils_SQL_TempTable::build()->setDurable()->getName();
 * $name = CRM_Utils_SQL_TempTable::build()->setCategory('contactstats')->setId($contact['id'])->getName();
 *
 * Example 2: Create a temp table using the results of a SELECT query.
 *
 * $tmpTbl = CRM_Utils_SQL_TempTable::build()->createWithQuery('SELECT id, display_name FROM civicrm_contact');
 * $tmpTbl = CRM_Utils_SQL_TempTable::build()->createWithQuery(CRM_Utils_SQL_Select::from('civicrm_contact')->select('display_name'));
 *
 * Example 3: Create an empty temp table with list of columns.
 *
 * $tmpTbl = CRM_Utils_SQL_TempTable::build()->setDurable()->createWithColumns('id int(10, name varchar(64)');
 *
 * Example 4: Drop a table that you previously created.
 *
 * $tmpTbl->drop();
 *
 * Example 5: Auto-drop a temp table when $tmpTbl falls out of scope
 *
 * $tmpTbl->setAutodrop();
 *
 */
class CRM_Utils_SQL_TempTable {

  /**
   * @deprecated
   * The system will attempt to use the same as your other tables, and
   * if you really need something else then use createWithColumns and
   * specify it per-column there.
   */
  const UTF8 = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';

  const CATEGORY_LENGTH = 12;
  const CATEGORY_REGEXP = ';^[a-zA-Z0-9]+$;';
  // MAX{64} - CATEGORY_LENGTH{12} - CONST_LENGHTH{15} = 37
  const ID_LENGTH = 37;
  const ID_REGEXP = ';^[a-zA-Z0-9_]+$;';
  const INNODB = 'ENGINE=InnoDB';
  const MEMORY = 'ENGINE=MEMORY';

  /**
   * @var bool
   */
  protected $durable;

  /**
   * @var bool
   */
  protected $utf8;

  protected $category;

  protected $id;

  protected $autodrop;

  protected $memory;

  protected $createSql;

  /**
   * @return CRM_Utils_SQL_TempTable
   */
  public static function build() {
    $t = new CRM_Utils_SQL_TempTable();
    $t->category = NULL;
    $t->id = bin2hex(random_bytes(16));
    // The constant CIVICRM_TEMP_FORCE_DURABLE is for local debugging.
    $t->durable = CRM_Utils_Constant::value('CIVICRM_TEMP_FORCE_DURABLE', FALSE);
    $t->utf8 = TRUE;
    $t->autodrop = FALSE;
    $t->memory = FALSE;
    return $t;
  }

  public function __destruct() {
    if ($this->autodrop) {
      $this->drop();
    }
  }

  /**
   * Determine the full table name.
   *
   * @return string
   *   Ex: 'civicrm_tmp_d_foo_abcd1234abcd1234'
   */
  public function getName() {
    $parts = ['civicrm', 'tmp'];
    $parts[] = ($this->durable ? 'd' : 'e');
    $parts[] = $this->category ?: 'dflt';
    $parts[] = $this->id ?: 'dflt';
    return implode('_', $parts);
  }

  /**
   * Create the table using results from a SELECT query.
   *
   * @param string|CRM_Utils_SQL_Select $selectQuery
   * @return CRM_Utils_SQL_TempTable
   */
  public function createWithQuery($selectQuery) {
    $sql = sprintf('%s %s %s AS %s',
      $this->toSQL('CREATE'),
      $this->memory ? self::MEMORY : self::INNODB,
      $this->getUtf8String(),
      ($selectQuery instanceof CRM_Utils_SQL_Select ? $selectQuery->toSQL() : $selectQuery)
    );
    CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, TRUE, FALSE);
    $this->createSql = $sql;
    return $this;
  }

  /**
   * Get the utf8 string for the table.
   *
   * Our tables are either utf8_unicode_ci OR utf8mb4_unicode_ci - check the contact table
   * to see which & use the matching one. Or early adopters may have switched
   * switched to other collations e.g. utf8mb4_0900_ai_ci (the default in mysql
   * 8).
   *
   * @return string
   */
  public function getUtf8String() {
    return $this->utf8 ? ('COLLATE ' . CRM_Core_BAO_SchemaHandler::getInUseCollation()) : '';
  }

  /**
   * Create the empty table.
   *
   * @param string $columns
   *   SQL column listing.
   *   Ex: 'id int(10), name varchar(64)'.
   * @return CRM_Utils_SQL_TempTable
   */
  public function createWithColumns($columns) {
    $sql = sprintf('%s (%s) %s %s',
      $this->toSQL('CREATE'),
      $columns,
      $this->memory ? self::MEMORY : self::INNODB,
      $this->getUtf8String()
    );
    CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, TRUE, FALSE);
    $this->createSql = $sql;
    return $this;
  }

  /**
   * Drop the table.
   *
   * @return CRM_Utils_SQL_TempTable
   */
  public function drop() {
    $sql = $this->toSQL('DROP', 'IF EXISTS');
    CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, TRUE, FALSE);
    return $this;
  }

  /**
   * @param string $action
   *   Ex: 'CREATE', 'DROP'
   * @param string|null $ifne
   *   Ex: 'IF EXISTS', 'IF NOT EXISTS'.
   * @return string
   *   Ex: 'CREATE TEMPORARY TABLE `civicrm_tmp_e_foo_abcd1234`'
   *   Ex: 'CREATE TABLE IF NOT EXISTS `civicrm_tmp_d_foo_abcd1234`'
   */
  private function toSQL($action, $ifne = NULL) {
    $parts = [];
    $parts[] = $action;
    if (!$this->durable) {
      $parts[] = 'TEMPORARY';
    }
    $parts[] = 'TABLE';
    if ($ifne) {
      $parts[] = $ifne;
    }
    $parts[] = '`' . $this->getName() . '`';
    return implode(' ', $parts);
  }

  /**
   * @return string|NULL
   */
  public function getCategory() {
    return $this->category;
  }

  /**
   * @return string|NULL
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @return string|NULL
   */
  public function getCreateSql() {
    return $this->createSql;
  }

  /**
   * @return bool
   */
  public function isAutodrop() {
    return $this->autodrop;
  }

  /**
   * @return bool
   */
  public function isDurable() {
    return $this->durable;
  }

  /**
   * @return bool
   */
  public function isMemory() {
    return $this->memory;
  }

  /**
   * @deprecated
   * @return bool
   */
  public function isUtf8() {
    CRM_Core_Error::deprecatedFunctionWarning('your own charset/collation per column with createWithColumns if you really need latin1');
    return $this->utf8;
  }

  /**
   * @param bool $autodrop
   * @return CRM_Utils_SQL_TempTable
   */
  public function setAutodrop($autodrop = TRUE) {
    $this->autodrop = $autodrop;
    return $this;
  }

  /**
   * @param string|null $category
   *
   * @return CRM_Utils_SQL_TempTable
   */
  public function setCategory($category) {
    if ($category && !preg_match(self::CATEGORY_REGEXP, $category) || strlen($category) > self::CATEGORY_LENGTH) {
      throw new \RuntimeException("Malformed temp table category $category");
    }
    $this->category = $category;
    return $this;
  }

  /**
   * Set whether the table should be durable.
   *
   * Durable tables are not TEMPORARY in the mysql sense.
   *
   * @param bool $durable
   *
   * @return CRM_Utils_SQL_TempTable
   */
  public function setDurable($durable = TRUE) {
    $this->durable = $durable;
    return $this;
  }

  /**
   * Setter for id
   *
   * @param mixed $id
   *
   * @return CRM_Utils_SQL_TempTable
   */
  public function setId($id) {
    if ($id && !preg_match(self::ID_REGEXP, $id) || strlen($id) > self::ID_LENGTH) {
      throw new \RuntimeException("Malformed temp table id");
    }
    $this->id = $id;
    return $this;
  }

  /**
   * Set table engine to MEMORY.
   *
   * @param bool $value
   *
   * @return $this
   */
  public function setMemory($value = TRUE) {
    if (\Civi::settings()->get('disable_sql_memory_engine')) {
      $value = FALSE;
    }
    $this->memory = $value;
    return $this;
  }

  /**
   * Set table collation to UTF8.
   *
   * @deprecated This method is deprecated as tables should be assumed to have
   * UTF-8 as the default character set and collation; some other character set
   * or collation may be specified in the column definition.
   *
   * @param bool $value
   *
   * @return $this
   */
  public function setUtf8($value = TRUE) {
    CRM_Core_Error::deprecatedFunctionWarning('your own charset/collation per column with createWithColumns if you really need latin1');
    $this->utf8 = $value;
    return $this;
  }

}
