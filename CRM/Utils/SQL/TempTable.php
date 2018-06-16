<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 *
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
 * $tmpTbl = CRM_Utils_SQL_TempTable::build()->setDurable()->setUtf8()->createWithColumns('id int(10, name varchar(64)');
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

  const UTF8 = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci';
  const CATEGORY_LENGTH = 12;
  const CATEGORY_REGEXP = ';^[a-zA-Z0-9]+$;';
  const ID_LENGTH = 37; // MAX{64} - CATEGORY_LENGTH{12} - CONST_LENGHTH{15} = 37
  const ID_REGEXP = ';^[a-zA-Z0-9_]+$;';

  /**
   * @var bool
   */
  protected $durable, $utf8;

  protected $category;

  protected $id;

  protected $autodrop;

  /**
   * @return CRM_Utils_SQL_TempTable
   */
  public static function build() {
    $t = new CRM_Utils_SQL_TempTable();
    $t->category = NULL;
    $t->id = md5(uniqid('', TRUE));
    // The constant CIVICRM_TEMP_FORCE_DURABLE is for local debugging.
    $t->durable = CRM_Utils_Constant::value('CIVICRM_TEMP_FORCE_DURABLE', FALSE);
    // I suspect it would be better to just say utf8=true, but a lot of existing queries don't do the utf8 bit.
    $t->utf8 = CRM_Utils_Constant::value('CIVICRM_TEMP_FORCE_UTF8', FALSE);
    $t->autodrop = FALSE;
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
    $parts[] = $this->category ? $this->category : 'dflt';
    $parts[] = $this->id ? $this->id : 'dflt';
    return implode('_', $parts);
  }

  /**
   * Create the table using results from a SELECT query.
   *
   * @param string|CRM_Utils_SQL_Select $selectQuery
   * @return CRM_Utils_SQL_TempTable
   */
  public function createWithQuery($selectQuery) {
    $sql = sprintf('%s %s AS %s',
      $this->toSQL('CREATE'),
      $this->utf8 ? self::UTF8 : '',
      ($selectQuery instanceof CRM_Utils_SQL_Select ? $selectQuery->toSQL() : $selectQuery)
    );
    CRM_Core_DAO::executeQuery($sql, array(), TRUE, NULL, TRUE, FALSE);
    return $this;
  }

  /**
   * Create the empty table.
   *
   * @parma string $columns
   *   SQL column listing.
   *   Ex: 'id int(10), name varchar(64)'.
   * @return CRM_Utils_SQL_TempTable
   */
  public function createWithColumns($columns) {
    $sql = sprintf('%s (%s) %s',
      $this->toSQL('CREATE'),
      $columns,
      $this->utf8 ? self::UTF8 : ''
    );
    CRM_Core_DAO::executeQuery($sql, array(), TRUE, NULL, TRUE, FALSE);
    return $this;
  }

  /**
   * Drop the table.
   *
   * @return CRM_Utils_SQL_TempTable
   */
  public function drop() {
    $sql = $this->toSQL('DROP', 'IF EXISTS');
    CRM_Core_DAO::executeQuery($sql, array(), TRUE, NULL, TRUE, FALSE);
    return $this;
  }

  /**
   * @param string $action
   *   Ex: 'CREATE', 'DROP'
   * @param string|NULL $ifne
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
  public function isUtf8() {
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
   * @param string|NULL $category
   * @return CRM_Utils_SQL_TempTable
   */
  public function setCategory($category) {
    if ($category && !preg_match(self::CATEGORY_REGEXP, $category) || strlen($category) > self::CATEGORY_LENGTH) {
      throw new \RuntimeException("Malformed temp table category");
    }
    $this->category = $category;
    return $this;
  }

  /**
   * @parma bool $value
   * @return CRM_Utils_SQL_TempTable
   */
  public function setDurable($durable = TRUE) {
    $this->durable = $durable;
    return $this;
  }

  /**
   * @param mixed $id
   * @return CRM_Utils_SQL_TempTable
   */
  public function setId($id) {
    if ($id && !preg_match(self::ID_REGEXP, $id) || strlen($id) > self::ID_LENGTH) {
      throw new \RuntimeException("Malformed temp table id");
    }
    $this->id = $id;
    return $this;
  }

  public function setUtf8($value = TRUE) {
    $this->utf8 = $value;
    return $this;
  }

}
