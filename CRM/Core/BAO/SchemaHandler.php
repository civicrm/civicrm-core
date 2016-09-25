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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 *  This file contains functions for creating and altering CiviCRM-tables structure.
 *
 * $table = array(
 *  'name'  => TABLE_NAME,
 *  'attributes' => ATTRIBUTES,
 *  'fields' => array(
 *    array(
 *      'name' => FIELD_NAME,
 *      // can be field, index, constraint
 *      'type' => FIELD_SQL_TYPE,
 *      'class'         => FIELD_CLASS_TYPE,
 *      'primary'       => BOOLEAN,
 *      'required'      => BOOLEAN,
 *      'searchable'    => TRUE,
 *      'fk_table_name' => FOREIGN_KEY_TABLE_NAME,
 *      'fk_field_name' => FOREIGN_KEY_FIELD_NAME,
 *      'comment'       => COMMENT,
 *      'default'       => DEFAULT, )
 *      ...
 *  ));
 */
class CRM_Core_BAO_SchemaHandler {

  /**
   * Create a CiviCRM-table
   *
   * @param array $params
   *
   * @return bool
   *   TRUE if successfully created, FALSE otherwise
   *
   */
  public static function createTable(&$params) {
    $sql = self::buildTableSQL($params);
    // do not i18n-rewrite
    $dao = CRM_Core_DAO::executeQuery($sql, array(), TRUE, NULL, FALSE, FALSE);
    $dao->free();

    $config = CRM_Core_Config::singleton();
    if ($config->logging) {
      // logging support
      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferencesFor($params['name'], NULL, FALSE);
    }

    // always do a trigger rebuild for this table
    CRM_Core_DAO::triggerRebuild($params['name']);

    return TRUE;
  }

  /**
   * @param array $params
   *
   * @return string
   */
  public static function buildTableSQL(&$params) {
    $sql = "CREATE TABLE {$params['name']} (";
    if (isset($params['fields']) &&
      is_array($params['fields'])
    ) {
      $separator = "\n";
      $prefix = NULL;
      foreach ($params['fields'] as $field) {
        $sql .= self::buildFieldSQL($field, $separator, $prefix);
        $separator = ",\n";
      }
      foreach ($params['fields'] as $field) {
        $sql .= self::buildPrimaryKeySQL($field, $separator, $prefix);
      }
      foreach ($params['fields'] as $field) {
        $sql .= self::buildSearchIndexSQL($field, $separator, $prefix);
      }
      if (isset($params['indexes'])) {
        foreach ($params['indexes'] as $index) {
          $sql .= self::buildIndexSQL($index, $separator, $prefix);
        }
      }
      foreach ($params['fields'] as $field) {
        $sql .= self::buildForeignKeySQL($field, $separator, $prefix, $params['name']);
      }
    }
    $sql .= "\n) {$params['attributes']};";
    return $sql;
  }

  /**
   * @param array $params
   * @param $separator
   * @param $prefix
   *
   * @return string
   */
  public static function buildFieldSQL(&$params, $separator, $prefix) {
    $sql = '';
    $sql .= $separator;
    $sql .= str_repeat(' ', 8);
    $sql .= $prefix;
    $sql .= "`{$params['name']}` {$params['type']}";

    if (!empty($params['required'])) {
      $sql .= " NOT NULL";
    }

    if (!empty($params['attributes'])) {
      $sql .= " {$params['attributes']}";
    }

    if (!empty($params['default']) &&
      $params['type'] != 'text'
    ) {
      $sql .= " DEFAULT {$params['default']}";
    }

    if (!empty($params['comment'])) {
      $sql .= " COMMENT '{$params['comment']}'";
    }

    return $sql;
  }

  /**
   * @param array $params
   * @param $separator
   * @param $prefix
   *
   * @return NULL|string
   */
  public static function buildPrimaryKeySQL(&$params, $separator, $prefix) {
    $sql = NULL;
    if (!empty($params['primary'])) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= $prefix;
      $sql .= "PRIMARY KEY ( {$params['name']} )";
    }
    return $sql;
  }

  /**
   * @param array $params
   * @param $separator
   * @param $prefix
   * @param bool $indexExist
   *
   * @return NULL|string
   */
  public static function buildSearchIndexSQL(&$params, $separator, $prefix, $indexExist = FALSE) {
    $sql = NULL;

    // dont index blob
    if ($params['type'] == 'text') {
      return $sql;
    }

    //create index only for searchable fields during ADD,
    //create index only if field is become searchable during MODIFY,
    //drop index only if field is no more searchable and index was exist.
    if (!empty($params['searchable']) && !$indexExist) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= $prefix;
      $sql .= "INDEX_{$params['name']} ( {$params['name']} )";
    }
    elseif (empty($params['searchable']) && $indexExist) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= "DROP INDEX INDEX_{$params['name']}";
    }
    return $sql;
  }

  /**
   * @param array $params
   * @param $separator
   * @param $prefix
   *
   * @return string
   */
  public static function buildIndexSQL(&$params, $separator, $prefix) {
    $sql = '';
    $sql .= $separator;
    $sql .= str_repeat(' ', 8);
    if ($params['unique']) {
      $sql .= 'UNIQUE INDEX';
      $indexName = 'unique';
    }
    else {
      $sql .= 'INDEX';
      $indexName = 'index';
    }
    $indexFields = NULL;

    foreach ($params as $name => $value) {
      if (substr($name, 0, 11) == 'field_name_') {
        $indexName .= "_{$value}";
        $indexFields .= " $value,";
      }
    }
    $indexFields = substr($indexFields, 0, -1);

    $sql .= " $indexName ( $indexFields )";
    return $sql;
  }

  /**
   * @param string $tableName
   * @param string $fkTableName
   *
   * @return bool
   */
  public static function changeFKConstraint($tableName, $fkTableName) {
    $fkName = "{$tableName}_entity_id";
    if (strlen($fkName) >= 48) {
      $fkName = substr($fkName, 0, 32) . "_" . substr(md5($fkName), 0, 16);
    }
    $dropFKSql = "
ALTER TABLE {$tableName}
      DROP FOREIGN KEY `FK_{$fkName}`;";

    $dao = CRM_Core_DAO::executeQuery($dropFKSql);
    $dao->free();

    $addFKSql = "
ALTER TABLE {$tableName}
      ADD CONSTRAINT `FK_{$fkName}` FOREIGN KEY (`entity_id`) REFERENCES {$fkTableName} (`id`) ON DELETE CASCADE;";
    // CRM-7007: do not i18n-rewrite this query
    $dao = CRM_Core_DAO::executeQuery($addFKSql, array(), TRUE, NULL, FALSE, FALSE);
    $dao->free();

    return TRUE;
  }

  /**
   * @param array $params
   * @param $separator
   * @param $prefix
   * @param string $tableName
   *
   * @return NULL|string
   */
  public static function buildForeignKeySQL(&$params, $separator, $prefix, $tableName) {
    $sql = NULL;
    if (!empty($params['fk_table_name']) && !empty($params['fk_field_name'])) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= $prefix;
      $fkName = "{$tableName}_{$params['name']}";
      if (strlen($fkName) >= 48) {
        $fkName = substr($fkName, 0, 32) . "_" . substr(md5($fkName), 0, 16);
      }

      $sql .= "CONSTRAINT FK_$fkName FOREIGN KEY ( `{$params['name']}` ) REFERENCES {$params['fk_table_name']} ( {$params['fk_field_name']} ) ";
      $sql .= CRM_Utils_Array::value('fk_attributes', $params);
    }
    return $sql;
  }

  /**
   * @param array $params
   * @param bool $indexExist
   * @param bool $triggerRebuild
   *
   * @return bool
   */
  public static function alterFieldSQL(&$params, $indexExist = FALSE, $triggerRebuild = TRUE) {
    $sql = str_repeat(' ', 8);
    $sql .= "ALTER TABLE {$params['table_name']}";

    // lets suppress the required flag, since that can cause sql issue
    $params['required'] = FALSE;

    switch ($params['operation']) {
      case 'add':
        $separator = "\n";
        $prefix = "ADD ";
        $sql .= self::buildFieldSQL($params, $separator, "ADD COLUMN ");
        $separator = ",\n";
        $sql .= self::buildPrimaryKeySQL($params, $separator, "ADD PRIMARY KEY ");
        $sql .= self::buildSearchIndexSQL($params, $separator, "ADD INDEX ");
        $sql .= self::buildForeignKeySQL($params, $separator, "ADD ", $params['table_name']);
        break;

      case 'modify':
        $separator = "\n";
        $prefix = "MODIFY ";
        $sql .= self::buildFieldSQL($params, $separator, $prefix);
        $separator = ",\n";
        $sql .= self::buildSearchIndexSQL($params, $separator, "ADD INDEX ", $indexExist);
        break;

      case 'delete':
        $sql .= " DROP COLUMN `{$params['name']}`";
        if (!empty($params['primary'])) {
          $sql .= ", DROP PRIMARY KEY";
        }
        if (!empty($params['fk_table_name'])) {
          $sql .= ", DROP FOREIGN KEY FK_{$params['fkName']}";
        }
        break;
    }

    // CRM-7007: do not i18n-rewrite this query
    $dao = CRM_Core_DAO::executeQuery($sql, array(), TRUE, NULL, FALSE, FALSE);
    $dao->free();

    $config = CRM_Core_Config::singleton();
    if ($config->logging) {
      // CRM-16717 not sure why this was originally limited to add.
      // For example custom tables can have field length changes - which need to flow through to logging.
      // Are there any modifies we DON'T was to call this function for (& shouldn't it be clever enough to cope?)
      if ($params['operation'] == 'add' || $params['operation'] == 'modify') {
        $logging = new CRM_Logging_Schema();
        $logging->fixSchemaDifferencesFor($params['table_name'], array(trim($prefix) => array($params['name'])), FALSE);
      }
    }

    if ($triggerRebuild) {
      CRM_Core_DAO::triggerRebuild($params['table_name']);
    }

    return TRUE;
  }

  /**
   * Delete a CiviCRM-table
   *
   * @param string $tableName
   *   Name of the table to be created.
   */
  public static function dropTable($tableName) {
    $sql = "DROP TABLE $tableName";
    $dao = CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * @param string $tableName
   * @param string $columnName
   * @param bool $l18n
   *
   */
  public static function dropColumn($tableName, $columnName, $l18n = FALSE) {
    if (self::checkIfFieldExists($tableName, $columnName)) {
      $sql = "ALTER TABLE $tableName DROP COLUMN $columnName";
      if ($l18n) {
        CRM_Core_DAO::executeQuery($sql);
      }
      else {
        CRM_Core_DAO::executeQuery($sql, array(), TRUE, NULL, FALSE, FALSE);
      }
      $domain = new CRM_Core_DAO_Domain();
      $domain->find(TRUE);
      if ($domain->locales) {
        $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
        CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL);
      }
    }
  }

  /**
   * @param string $tableName
   * @param bool $dropUnique
   */
  public static function changeUniqueToIndex($tableName, $dropUnique = TRUE) {
    if ($dropUnique) {
      $sql = "ALTER TABLE $tableName
DROP INDEX `unique_entity_id` ,
ADD INDEX `FK_{$tableName}_entity_id` ( `entity_id` )";
    }
    else {
      $sql = " ALTER TABLE $tableName
DROP INDEX `FK_{$tableName}_entity_id` ,
ADD UNIQUE INDEX `unique_entity_id` ( `entity_id` )";
    }
    $dao = CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Create indexes.
   *
   * @param $tables
   *   Tables to create index for in the format:
   *     array('civicrm_entity_table' => 'entity_id')
   *     OR
   *     array('civicrm_entity_table' => array('entity_id', 'entity_table'))
   *   The latter will create a combined index on the 2 keys (in order).
   *
   *  Side note - when creating combined indexes the one with the most variation
   *  goes first  - so entity_table always goes after entity_id.
   *
   *  It probably makes sense to consider more sophisticated options at some point
   *  but at the moment this is only being as enhanced as fast as the test is.
   *
   * @todo add support for length & multilingual on combined keys.
   *
   * @param string $createIndexPrefix
   * @param array $substrLenghts
   */
  public static function createIndexes($tables, $createIndexPrefix = 'index', $substrLenghts = array()) {
    $queries = array();
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);

    // if we're multilingual, cache the information on internationalised fields
    static $columns = NULL;
    if (!CRM_Utils_System::isNull($locales) and $columns === NULL) {
      $columns = CRM_Core_I18n_SchemaStructure::columns();
    }

    foreach ($tables as $table => $fields) {
      $query = "SHOW INDEX FROM $table";
      $dao = CRM_Core_DAO::executeQuery($query);

      $currentIndexes = array();
      while ($dao->fetch()) {
        $currentIndexes[] = $dao->Key_name;
      }

      // now check for all fields if the index exists
      foreach ($fields as $field) {
        $fieldName = implode('_', (array) $field);

        if (is_array($field)) {
          // No support for these for combined indexes as yet - add a test when you
          // want to add that.
          $lengthName = '';
          $lengthSize = '';
        }
        else {
          // handle indices over substrings, CRM-6245
          // $lengthName is appended to index name, $lengthSize is the field size modifier
          $lengthName = isset($substrLenghts[$table][$fieldName]) ? "_{$substrLenghts[$table][$fieldName]}" : '';
          $lengthSize = isset($substrLenghts[$table][$fieldName]) ? "({$substrLenghts[$table][$fieldName]})" : '';
        }

        $names = array(
          "index_{$fieldName}{$lengthName}",
          "FK_{$table}_{$fieldName}{$lengthName}",
          "UI_{$fieldName}{$lengthName}",
          "{$createIndexPrefix}_{$fieldName}{$lengthName}",
        );

        // skip to the next $field if one of the above $names exists; handle multilingual for CRM-4126
        foreach ($names as $name) {
          $regex = '/^' . preg_quote($name) . '(_[a-z][a-z]_[A-Z][A-Z])?$/';
          if (preg_grep($regex, $currentIndexes)) {
            continue 2;
          }
        }

        // the index doesn't exist, so create it
        // if we're multilingual and the field is internationalised, do it for every locale
        // @todo remove is_array check & add multilingual support for combined indexes and add a test.
        // Note combined indexes currently using this function are on fields like
        // entity_id + entity_table which are not multilingual.
        if (!is_array($field) && !CRM_Utils_System::isNull($locales) and isset($columns[$table][$fieldName])) {
          foreach ($locales as $locale) {
            $queries[] = "CREATE INDEX {$createIndexPrefix}_{$fieldName}{$lengthName}_{$locale} ON {$table} ({$fieldName}_{$locale}{$lengthSize})";
          }
        }
        else {
          $queries[] = "CREATE INDEX {$createIndexPrefix}_{$fieldName}{$lengthName} ON {$table} (" . implode(',', (array) $field) . "{$lengthSize})";
        }
      }
    }

    // run the queries without i18n-rewriting
    $dao = new CRM_Core_DAO();
    foreach ($queries as $query) {
      $dao->query($query, FALSE);
    }
  }

  /**
   * Drop an index if one by that name exists.
   *
   * @param string $tableName
   * @param string $indexName
   */
  public static function dropIndexIfExists($tableName, $indexName) {
    if (self::checkIfIndexExists($tableName, $indexName)) {
      CRM_Core_DAO::executeQuery("DROP INDEX $indexName ON $tableName");
    }
  }

  /**
   * @param int $customFieldID
   * @param string $tableName
   * @param string $columnName
   * @param $length
   *
   * @throws Exception
   */
  public static function alterFieldLength($customFieldID, $tableName, $columnName, $length) {
    // first update the custom field tables
    $sql = "
UPDATE civicrm_custom_field
SET    text_length = %1
WHERE  id = %2
";
    $params = array(
      1 => array($length, 'Integer'),
      2 => array($customFieldID, 'Integer'),
    );
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "
SELECT is_required, default_value
FROM   civicrm_custom_field
WHERE  id = %2
";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->fetch()) {
      $clause = '';

      if ($dao->is_required) {
        $clause = " NOT NULL";
      }

      if (!empty($dao->default_value)) {
        $clause .= " DEFAULT '{$dao->default_value}'";
      }
      // now modify the column
      $sql = "
ALTER TABLE {$tableName}
MODIFY      {$columnName} varchar( $length )
            $clause
";
      CRM_Core_DAO::executeQuery($sql);
    }
    else {
      CRM_Core_Error::fatal(ts('Could Not Find Custom Field Details for %1, %2, %3',
        array(
          1 => $tableName,
          2 => $columnName,
          3 => $customFieldID,
        )
      ));
    }
  }

  /**
   * Check if the table has an index matching the name.
   *
   * @param string $tableName
   * @param array $indexName
   *
   * @return \CRM_Core_DAO|object
   */
  public static function checkIfIndexExists($tableName, $indexName) {
    $result = CRM_Core_DAO::executeQuery(
      "SHOW INDEX FROM $tableName WHERE key_name = %1 AND seq_in_index = 1",
      array(1 => array($indexName, 'String'))
    );
    if ($result->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the table has a specified column
   *
   * @param string $tableName
   * @param string $columnName
   *
   * @return \CRM_Core_DAO|object
   */
  public static function checkIfFieldExists($tableName, $columnName) {
    $result = CRM_Core_DAO::executeQuery(
      "SHOW COLUMNS FROM $tableName LIKE %1",
      array(1 => array($columnName, 'String'))
    );
    if ($result->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Remove a foreign key from a table if it exists
   *
   * @param $table_name
   * @param $constraint_name
   */
  public static function safeRemoveFK($table_name, $constraint_name) {

    $config = CRM_Core_Config::singleton();
    $dbUf = DB::parseDSN($config->dsn);
    $query = "
      SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = %1
      AND TABLE_NAME = %2
      AND CONSTRAINT_NAME = %3
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ";
    $params = array(
      1 => array($dbUf['database'], 'String'),
      2 => array($table_name, 'String'),
      3 => array($constraint_name, 'String'),
    );
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->fetch()) {
      CRM_Core_DAO::executeQuery("ALTER TABLE {$table_name} DROP FOREIGN KEY {$constraint_name}", array());
      return TRUE;
    }
    return FALSE;
  }

}
