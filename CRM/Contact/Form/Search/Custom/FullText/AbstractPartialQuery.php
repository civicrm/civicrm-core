<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
abstract class CRM_Contact_Form_Search_Custom_FullText_AbstractPartialQuery {

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $label;

  /**
   * Class constructor.
   *
   * @param string $name
   * @param string $label
   */
  public function __construct($name, $label) {
    $this->name = $name;
    $this->label = $label;
  }

  /**
   * Get label.
   *
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Get name.
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Execute a query and write out a page worth of matches to $detailTable.
   *
   * TODO: Consider removing $entityIDTableName from the function-signature. Each implementation could be
   * responsible for its own temp tables.
   *
   * TODO: Understand why $queryLimit and $detailLimit are different
   *
   * @param string $queryText
   *   A string of text to search for.
   * @param string $entityIDTableName
   *   A temporary table into which we can write a list of all matching IDs.
   * @param string $detailTable
   *   A table into which we can write details about a page worth of matches.
   * @param array|NULL $queryLimit overall limit (applied when building $entityIDTableName)
   *                   NULL if no limit; or array(0 => $limit, 1 => $offset)
   * @param array|NULL $detailLimit final limit (applied when building $detailTable)
   *                   NULL if no limit; or array(0 => $limit, 1 => $offset)
   * @return array
   *   keys: match-descriptor
   *   - count: int
   */
  public abstract function fillTempTable($queryText, $entityIDTableName, $detailTable, $queryLimit, $detailLimit);

  /**
   * @return bool
   */
  public function isActive() {
    return TRUE;
  }

  /**
   * @param $tables
   * @param $extends
   */
  public function fillCustomInfo(&$tables, $extends) {
    $sql = "
SELECT     cg.table_name, cf.column_name
FROM       civicrm_custom_group cg
INNER JOIN civicrm_custom_field cf ON cf.custom_group_id = cg.id
WHERE      cg.extends IN $extends
AND        cg.is_active = 1
AND        cf.is_active = 1
AND        cf.is_searchable = 1
AND        cf.html_type IN ( 'Text', 'TextArea', 'RichTextEditor' )
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (!array_key_exists($dao->table_name, $tables)) {
        $tables[$dao->table_name] = [
          'id' => 'entity_id',
          'fields' => [],
        ];
      }
      $tables[$dao->table_name]['fields'][$dao->column_name] = NULL;
    }
  }

  /**
   * Run queries.
   *
   * @param string $queryText
   * @param array $tables
   *   A list of places to query. Keys may be:.
   *   - sql: an array of SQL queries to execute
   *   - final: an array of SQL queries to execute at the end
   *   - *: All other keys are treated as table names
   * @param string $entityIDTableName
   * @param int $limit
   *
   * @return array
   *   Keys: match-descriptor
   *   - count: int
   *   - files: NULL | array
   * @throws \CRM_Core_Exception
   */
  public function runQueries($queryText, &$tables, $entityIDTableName, $limit) {
    $sql = "TRUNCATE {$entityIDTableName}";
    CRM_Core_DAO::executeQuery($sql);

    $files = NULL;

    foreach ($tables as $tableName => $tableValues) {
      if ($tableName == 'final') {
        continue;
      }
      else {
        if ($tableName == 'sql') {
          foreach ($tableValues as $sqlStatement) {
            $sql = "
REPLACE INTO {$entityIDTableName} ( entity_id )
$sqlStatement
{$this->toLimit($limit)}
";
            CRM_Core_DAO::executeQuery($sql);
          }
        }
        elseif ($tableName == 'file') {
          $searcher = CRM_Core_BAO_File::getSearchService();
          if (!($searcher && CRM_Core_Permission::check('access uploaded files'))) {
            continue;
          }

          $query = $tableValues + [
            'text' => CRM_Utils_QueryFormatter::singleton()
            ->format($queryText, CRM_Utils_QueryFormatter::LANG_SOLR),
          ];
          list($intLimit, $intOffset) = $this->parseLimitOffset($limit);
          $files = $searcher->search($query, $intLimit, $intOffset);
          $matches = [];
          foreach ($files as $file) {
            $matches[] = ['entity_id' => $file['xparent_id']];
          }
          if ($matches) {
            $insertSql = CRM_Utils_SQL_Insert::into($entityIDTableName)->usingReplace()->rows($matches)->toSQL();
            CRM_Core_DAO::executeQuery($insertSql);
          }
        }
        else {
          $fullTextFields = []; // array (string $sqlColumnName)
          $clauses = []; // array (string $sqlExpression)

          foreach ($tableValues['fields'] as $fieldName => $fieldType) {
            if ($fieldType == 'Int') {
              if (is_numeric($queryText)) {
                $clauses[] = "$fieldName = {$queryText}";
              }
            }
            else {
              $fullTextFields[] = $fieldName;
            }
          }

          if (!empty($fullTextFields)) {
            $clauses[] = $this->matchText($tableName, $fullTextFields, $queryText);
          }

          if (empty($clauses)) {
            continue;
          }

          $whereClause = implode(' OR ', $clauses);

          //resolve conflict between entity tables.
          if ($tableName == 'civicrm_note' &&
            $entityTable = CRM_Utils_Array::value('entity_table', $tableValues)
          ) {
            $whereClause .= " AND entity_table = '{$entityTable}'";
          }

          $sql = "
REPLACE  INTO {$entityIDTableName} ( entity_id )
SELECT   {$tableValues['id']}
FROM     $tableName
WHERE    ( $whereClause )
AND      {$tableValues['id']} IS NOT NULL
GROUP BY {$tableValues['id']}
{$this->toLimit($limit)}
";
          CRM_Core_DAO::executeQuery($sql);
        }
      }
    }

    if (isset($tables['final'])) {
      foreach ($tables['final'] as $sqlStatement) {
        CRM_Core_DAO::executeQuery($sqlStatement);
      }
    }

    return [
      'count' => CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM {$entityIDTableName}"),
      'files' => $files,
    ];
  }

  /**
   * Create a SQL expression for matching against a list of.
   * text columns.
   *
   * @param string $table
   *   Eg "civicrm_note" or "civicrm_note mynote".
   * @param array|string $fullTextFields list of field names
   * @param string $queryText
   * @return string
   *   SQL, eg "MATCH (col1) AGAINST (queryText)" or "col1 LIKE '%queryText%'"
   */
  public function matchText($table, $fullTextFields, $queryText) {
    return CRM_Utils_QueryFormatter::singleton()->formatSql($table, $fullTextFields, $queryText);
  }

  /**
   * For any records in $toTable that originated with this query,
   * append file information.
   *
   * @param string $toTable
   * @param string $parentIdColumn
   * @param array $files
   *   See return format of CRM_Core_FileSearchInterface::search.
   */
  public function moveFileIDs($toTable, $parentIdColumn, $files) {
    if (empty($files)) {
      return;
    }

    $filesIndex = CRM_Utils_Array::index(['xparent_id', 'file_id'], $files);
    // ex: $filesIndex[$xparent_id][$file_id] = array(...the file record...);

    $dao = CRM_Core_DAO::executeQuery("
      SELECT distinct {$parentIdColumn}
      FROM {$toTable}
      WHERE table_name = %1
    ", [
      1 => [$this->getName(), 'String'],
    ]);
    while ($dao->fetch()) {
      if (empty($filesIndex[$dao->{$parentIdColumn}])) {
        continue;
      }

      CRM_Core_DAO::executeQuery("UPDATE {$toTable}
        SET file_ids = %1
        WHERE table_name = %2 AND {$parentIdColumn} = %3
      ", [
        1 => [implode(',', array_keys($filesIndex[$dao->{$parentIdColumn}])), 'String'],
        2 => [$this->getName(), 'String'],
        3 => [$dao->{$parentIdColumn}, 'Int'],
      ]);
    }
  }

  /**
   * @param int|array $limit
   * @return string
   *   SQL
   * @see CRM_Contact_Form_Search_Custom_FullText::toLimit
   */
  public function toLimit($limit) {
    if (is_array($limit)) {
      list ($limit, $offset) = $limit;
    }
    if (empty($limit)) {
      return '';
    }
    $result = "LIMIT {$limit}";
    if ($offset) {
      $result .= " OFFSET {$offset}";
    }
    return $result;
  }

  /**
   * @param array|int $limit
   * @return array
   *   (0 => $limit, 1 => $offset)
   */
  public function parseLimitOffset($limit) {
    if (is_scalar($limit)) {
      $intLimit = $limit;
    }
    else {
      list ($intLimit, $intOffset) = $limit;
    }
    if (!$intOffset) {
      $intOffset = 0;
    }
    return [$intLimit, $intOffset];
  }

}
