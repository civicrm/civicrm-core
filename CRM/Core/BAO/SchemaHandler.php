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
  public static function createTable($params) {
    $sql = self::buildTableSQL($params);
    // do not i18n-rewrite
    CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);

    if (CRM_Core_Config::singleton()->logging) {
      // logging support
      $logging = new CRM_Logging_Schema();
      $logging->fixSchemaDifferencesFor($params['name']);
    }

    // always do a trigger rebuild for this table
    Civi::service('sql_triggers')->rebuild($params['name'], TRUE);

    return TRUE;
  }

  /**
   * @param array $params
   *
   * @return string
   */
  public static function buildTableSQL($params) {
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
        $sql .= self::buildSearchIndexSQL($field, $separator);
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
  public static function buildFieldSQL($params, $separator, $prefix) {
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
  public static function buildPrimaryKeySQL($params, $separator, $prefix) {
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
   * @param string $separator
   * @param string $prefix
   * @param string|null $existingIndex
   *
   * @return NULL|string
   */
  public static function buildSearchIndexSQL($params, $separator, $prefix = '', $existingIndex = NULL) {
    $sql = NULL;

    // dont index blob
    if ($params['type'] == 'text') {
      return $sql;
    }

    // Add index if field is searchable if it does not reference a foreign key
    // (skip indexing FK fields because it would be redundant to have 2 indexes)
    if (!empty($params['searchable']) && empty($params['fk_table_name']) && substr($existingIndex ?? '', 0, 5) !== 'INDEX') {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= $prefix;
      $sql .= "INDEX_{$params['name']} ( {$params['name']} )";
    }
    // Drop search index if field is no longer searchable
    elseif (empty($params['searchable']) && substr($existingIndex ?? '', 0, 5) === 'INDEX') {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= "DROP INDEX $existingIndex";
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

    CRM_Core_DAO::executeQuery($dropFKSql);

    $addFKSql = "
ALTER TABLE {$tableName}
      ADD CONSTRAINT `FK_{$fkName}` FOREIGN KEY (`entity_id`) REFERENCES {$fkTableName} (`id`) ON DELETE CASCADE;";
    // CRM-7007: do not i18n-rewrite this query
    CRM_Core_DAO::executeQuery($addFKSql, [], TRUE, NULL, FALSE, FALSE);

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
  public static function buildForeignKeySQL($params, $separator, $prefix, $tableName) {
    $sql = NULL;
    if (!empty($params['fk_table_name']) && !empty($params['fk_field_name'])) {
      $sql .= $separator;
      $sql .= str_repeat(' ', 8);
      $sql .= $prefix;
      $fkName = $params['fkName'] ?? self::getIndexName($tableName, $params['name']);

      $sql .= "CONSTRAINT FK_$fkName FOREIGN KEY ( `{$params['name']}` ) REFERENCES {$params['fk_table_name']} ( {$params['fk_field_name']} ) ";
      $sql .= $params['fk_attributes'] ?? '';
    }
    return $sql;
  }

  /**
   * Delete a CiviCRM-table.
   *
   * @param string $tableName
   *   Name of the table to be created.
   */
  public static function dropTable($tableName) {
    $sql = "DROP TABLE $tableName";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * @param string $tableName
   * @param string $columnName
   * @param bool $l18n
   * @param bool $isUpgradeMode
   *
   */
  public static function dropColumn($tableName, $columnName, $l18n = FALSE, $isUpgradeMode = FALSE) {
    if (self::checkIfFieldExists($tableName, $columnName)) {
      $sql = "ALTER TABLE $tableName DROP COLUMN $columnName";
      if ($l18n) {
        CRM_Core_DAO::executeQuery($sql);
      }
      else {
        CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
      }
      $locales = CRM_Core_I18n::getMultilingual();
      if ($locales) {
        CRM_Core_I18n_Schema::rebuildMultilingualSchema($locales, NULL, $isUpgradeMode);
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
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Create indexes.
   *
   * @param $tables
   *   Tables to create index for in the format:
   *     ['civicrm_entity_table' => ['entity_id']]
   *     OR
   *     array['civicrm_entity_table' => array['entity_id', 'entity_table']]
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
   * @param array $substrLengths
   */
  public static function createIndexes($tables, $createIndexPrefix = 'index', $substrLengths = []) {
    $queries = [];
    $locales = CRM_Core_I18n::getMultilingual();

    // if we're multilingual, cache the information on internationalised fields
    static $columns = NULL;
    if (!CRM_Utils_System::isNull($locales) and $columns === NULL) {
      $columns = CRM_Core_I18n_SchemaStructure::columns();
    }

    foreach ($tables as $table => $fields) {
      $query = "SHOW INDEX FROM $table";
      $dao = CRM_Core_DAO::executeQuery($query);

      $currentIndexes = [];
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
          $lengthName = isset($substrLengths[$table][$fieldName]) ? "_{$substrLengths[$table][$fieldName]}" : '';
          $lengthSize = isset($substrLengths[$table][$fieldName]) ? "({$substrLengths[$table][$fieldName]})" : '';
        }

        $names = [
          "index_{$fieldName}{$lengthName}",
          "FK_{$table}_{$fieldName}{$lengthName}",
          "UI_{$fieldName}{$lengthName}",
          "{$createIndexPrefix}_{$fieldName}{$lengthName}",
        ];

        // skip to the next $field if one of the above $names exists; handle multilingual for CRM-4126
        foreach ($names as $name) {
          $regex = '/^' . preg_quote($name) . '(_[a-z][a-z]_[A-Z][A-Z])?$/';
          if (preg_grep($regex, $currentIndexes)) {
            continue 2;
          }
        }

        $indexType = $createIndexPrefix === 'UI' ? 'UNIQUE' : '';

        // the index doesn't exist, so create it
        // if we're multilingual and the field is internationalised, do it for every locale
        // @todo remove is_array check & add multilingual support for combined indexes and add a test.
        // Note combined indexes currently using this function are on fields like
        // entity_id + entity_table which are not multilingual.
        if (!is_array($field) && !CRM_Utils_System::isNull($locales) and isset($columns[$table][$fieldName])) {
          foreach ($locales as $locale) {
            $queries[] = "CREATE $indexType INDEX {$createIndexPrefix}_{$fieldName}{$lengthName}_{$locale} ON {$table} ({$fieldName}_{$locale}{$lengthSize})";
          }
        }
        else {
          $queries[] = "CREATE $indexType INDEX {$createIndexPrefix}_{$fieldName}{$lengthName} ON {$table} (" . implode(',', (array) $field) . "{$lengthSize})";
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
   * Get indexes for tables
   * @param array $tables
   *   array of table names to find indexes for
   *
   * @return array('tableName' => array('index1', 'index2'))
   */
  public static function getIndexes($tables) {
    $indexes = [];
    foreach ($tables as $table) {
      $query = "SHOW INDEX FROM $table";
      $dao = CRM_Core_DAO::executeQuery($query);

      $tableIndexes = [];
      while ($dao->fetch()) {
        $tableIndexes[$dao->Key_name]['name'] = $dao->Key_name;
        $tableIndexes[$dao->Key_name]['field'][] = $dao->Column_name .
         ($dao->Sub_part ? '(' . $dao->Sub_part . ')' : '');
        $tableIndexes[$dao->Key_name]['unique'] = ($dao->Non_unique == 0 ? 1 : 0);
      }
      $indexes[$table] = $tableIndexes;
    }
    return $indexes;
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
   * @throws CRM_Core_Exception
   */
  public static function alterFieldLength($customFieldID, $tableName, $columnName, $length) {
    // first update the custom field tables
    $sql = "
UPDATE civicrm_custom_field
SET    text_length = %1
WHERE  id = %2
";
    $params = [
      1 => [$length, 'Integer'],
      2 => [$customFieldID, 'Integer'],
    ];
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
      throw new CRM_Core_Exception(ts('Could Not Find Custom Field Details for %1, %2, %3',
        [
          1 => $tableName,
          2 => $columnName,
          3 => $customFieldID,
        ]
      ));
    }
  }

  /**
   * Check if the table has an index matching the name.
   *
   * @param string $tableName
   * @param string $indexName
   *
   * @return bool
   */
  public static function checkIfIndexExists($tableName, $indexName) {
    $result = CRM_Core_DAO::executeQuery(
      "SHOW INDEX FROM $tableName WHERE key_name = %1 AND seq_in_index = 1",
      [1 => [$indexName, 'String']]
    );
    if ($result->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the table has a specified column.
   *
   * @param string $tableName
   * @param string $columnName
   * @param bool $i18nRewrite
   *   Whether to rewrite the query on multilingual setups.
   *
   * @return bool
   */
  public static function checkIfFieldExists($tableName, $columnName, $i18nRewrite = TRUE) {
    $query = "SHOW COLUMNS FROM $tableName LIKE '%1'";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$columnName, 'Alphanumeric']], TRUE, NULL, FALSE, $i18nRewrite);
    return (bool) $dao->fetch();
  }

  /**
   * Check if a foreign key Exists
   * @param string $table_name
   * @param string $constraint_name
   * @return bool TRUE if FK is found
   */
  public static function checkFKExists($table_name, $constraint_name) {
    $dao = new CRM_Core_DAO();
    $query = "
      SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = %1
      AND TABLE_NAME = %2
      AND CONSTRAINT_NAME = %3
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ";
    $params = [
      1 => [$dao->_database, 'String'],
      2 => [$table_name, 'String'],
      3 => [$constraint_name, 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, FALSE);

    if ($dao->fetch()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Remove a foreign key from a table if it exists.
   *
   * @param $table_name
   * @param $constraint_name
   *
   * @return bool
   */
  public static function safeRemoveFK($table_name, $constraint_name) {
    if (self::checkFKExists($table_name, $constraint_name)) {
      CRM_Core_DAO::executeQuery("ALTER TABLE {$table_name} DROP FOREIGN KEY {$constraint_name}", [], TRUE, NULL, FALSE, FALSE);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Add index signature hash to DAO file calculation.
   *
   * @param string $table table name
   * @param array $indices index array spec
   */
  public static function addIndexSignature($table, &$indices) {
    foreach ($indices as $indexName => $index) {
      $indices[$indexName]['sig'] = $table . "::" .
        (array_key_exists('unique', $index) ? $index['unique'] : 0) . "::" .
        implode("::", $index['field']);
    }
  }

  /**
   * Compare the indices specified in the XML files with those in the DB.
   *
   * @param bool $dropFalseIndices
   *  If set - this function deletes false indices present in the DB which mismatches the expected
   *  values of xml file so that civi re-creates them with correct values using createMissingIndices() function.
   * @param array|false $tables
   *   An optional array of tables - if provided the results will be restricted to these tables.
   *
   * @return array
   *   index specifications
   */
  public static function getMissingIndices($dropFalseIndices = FALSE, $tables = FALSE) {
    $requiredSigs = $existingSigs = [];
    // Get the indices defined (originally) in the xml files
    $requiredIndices = CRM_Core_DAO_AllCoreTables::indices();
    $reqSigs = [];
    if ($tables !== FALSE) {
      $requiredIndices = array_intersect_key($requiredIndices, array_fill_keys($tables, TRUE));
    }
    foreach ($requiredIndices as $table => $indices) {
      $reqSigs[] = CRM_Utils_Array::collect('sig', $indices);
    }
    CRM_Utils_Array::flatten($reqSigs, $requiredSigs);

    // Get the indices in the database
    $existingIndices = CRM_Core_BAO_SchemaHandler::getIndexes(array_keys($requiredIndices));
    $extSigs = [];
    foreach ($existingIndices as $table => $indices) {
      CRM_Core_BAO_SchemaHandler::addIndexSignature($table, $indices);
      $extSigs[] = CRM_Utils_Array::collect('sig', $indices);
    }
    CRM_Utils_Array::flatten($extSigs, $existingSigs);

    // Compare
    $missingSigs = array_diff($requiredSigs, $existingSigs);

    //CRM-20774 - Drop index key which exist in db but the value varies.
    $existingKeySigs = array_intersect_key($missingSigs, $existingSigs);
    if ($dropFalseIndices && !empty($existingKeySigs)) {
      foreach ($existingKeySigs as $sig) {
        $sigParts = explode('::', $sig);
        foreach ($requiredIndices[$sigParts[0]] as $index) {
          if ($index['sig'] == $sig && !empty($index['name'])) {
            self::dropIndexIfExists($sigParts[0], $index['name']);
            continue;
          }
        }
      }
    }

    // Get missing indices
    $missingIndices = [];
    foreach ($missingSigs as $sig) {
      $sigParts = explode('::', $sig);
      if (array_key_exists($sigParts[0], $requiredIndices)) {
        foreach ($requiredIndices[$sigParts[0]] as $index) {
          if ($index['sig'] == $sig) {
            $missingIndices[$sigParts[0]][] = $index;
            continue;
          }
        }
      }
    }
    return $missingIndices;
  }

  /**
   * Create missing indices.
   *
   * @param array $missingIndices as returned by getMissingIndices()
   */
  public static function createMissingIndices($missingIndices) {
    $queries = [];
    foreach ($missingIndices as $table => $indexList) {
      foreach ($indexList as $index) {
        $queries[] = "CREATE " .
        (array_key_exists('unique', $index) && $index['unique'] ? 'UNIQUE ' : '') .
        "INDEX {$index['name']} ON {$table} (" .
          implode(", ", $index['field']) .
        ")";
      }
    }

    /* FIXME potential problem if index name already exists, so check before creating */
    $dao = new CRM_Core_DAO();
    foreach ($queries as $query) {
      $dao->query($query, FALSE);
    }
  }

  /**
   * Build the sql to alter the field.
   *
   * @param array $params
   *
   * @return string
   */
  public static function buildFieldChangeSql($params) {
    $sql = str_repeat(' ', 8);
    $sql .= "ALTER TABLE {$params['table_name']}";
    return $sql . self::getFieldAlterSQL($params);
  }

  /**
   * Get the sql to alter an individual field.
   *
   * This will need to have an ALTER TABLE statement appended but by getting
   * by individual field we can do one or many.
   *
   * @param array $params
   *
   * @return string
   */
  public static function getFieldAlterSQL($params) {
    $sql = '';
    switch ($params['operation']) {
      case 'add':
        $separator = "\n";
        $sql .= self::buildFieldSQL($params, $separator, "ADD COLUMN ");
        $separator = ",\n";
        $sql .= self::buildPrimaryKeySQL($params, $separator, "ADD PRIMARY KEY ");
        $sql .= self::buildSearchIndexSQL($params, $separator, "ADD INDEX ");
        $sql .= self::buildForeignKeySQL($params, $separator, "ADD ", $params['table_name']);
        break;

      case 'modify':
        $separator = "\n";
        $existingIndex = NULL;
        $dao = CRM_Core_DAO::executeQuery("SHOW INDEX FROM `{$params['table_name']}` WHERE Column_name = '{$params['name']}'");
        if ($dao->fetch()) {
          $existingIndex = $dao->Key_name;
        }
        $fkSql = self::buildForeignKeySQL($params, ",\n", "ADD ", $params['table_name']);
        if (substr(($existingIndex ?? ''), 0, 2) === 'FK' && !$fkSql) {
          $sql .= "$separator DROP FOREIGN KEY {$existingIndex},\nDROP INDEX {$existingIndex}";
          $separator = ",\n";
        }
        $sql .= self::buildFieldSQL($params, $separator, "MODIFY ");
        $separator = ",\n";
        $sql .= self::buildSearchIndexSQL($params, $separator, "ADD INDEX ", $existingIndex);
        if (!$existingIndex && $fkSql) {
          $sql .= $fkSql;
        }
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
    return $sql;
  }

  /**
   * Turns tableName + columnName into a safe & predictable index name
   *
   * @param $tableName
   * @param $columnName
   * @return string
   */
  public static function getIndexName($tableName, $columnName) {
    $indexName = "{$tableName}_{$columnName}";
    if (strlen($indexName) >= 48) {
      $indexName = substr($indexName, 0, 32) . "_" . substr(md5($indexName), 0, 16);
    }
    return $indexName;
  }

  /**
   * Performs the utf8mb4 migration.
   *
   * @param bool $revert
   *   Being able to revert if primarily for unit testing.
   * @param array $patterns
   *   Defaults to ['civicrm\_%'] but can be overridden to specify any pattern. eg ['civicrm\_%', 'civi%\_%', 'veda%\_%'].
   * @param array $databaseList
   *   Allows you to specify an alternative database to the configured CiviCRM database.
   *
   * @return bool
   */
  public static function migrateUtf8mb4($revert = FALSE, $patterns = ['civicrm\_%'], $databaseList = NULL) {
    $newCharSet = $revert ? 'utf8' : 'utf8mb4';
    $newCollation = $revert ? 'utf8_unicode_ci' : 'utf8mb4_unicode_ci';
    $newBinaryCollation = $revert ? 'utf8_bin' : 'utf8mb4_bin';
    $tables = [];
    $dao = new CRM_Core_DAO();
    $databases = $databaseList ?? [$dao->_database];

    $tableNameLikePatterns = [];
    $logTableNameLikePatterns = [];

    foreach ($patterns as $pattern) {
      $pattern = CRM_Utils_Type::escape($pattern, 'String');
      $tableNameLikePatterns[] = "Name LIKE '{$pattern}'";
      $logTableNameLikePatterns[] = "Name LIKE 'log\_{$pattern}'";
    }

    foreach ($databases as $database) {
      CRM_Core_DAO::executeQuery("ALTER DATABASE `{$database}` CHARACTER SET = $newCharSet COLLATE = $newCollation");
      $dao = CRM_Core_DAO::executeQuery("SHOW TABLE STATUS FROM `{$database}` WHERE Engine = 'InnoDB' AND (" . implode(' OR ', $tableNameLikePatterns) . ")");
      while ($dao->fetch()) {
        $tables["`{$database}`.`{$dao->Name}`"] = [
          'Engine' => $dao->Engine,
        ];
      }
    }
    // If we specified a list of databases assume the user knows what they are doing.
    // If they specify the database they should also specify the pattern.
    if (!$databaseList) {
      $dsn = defined('CIVICRM_LOGGING_DSN') ? CRM_Utils_SQL::autoSwitchDSN(CIVICRM_LOGGING_DSN) : CRM_Utils_SQL::autoSwitchDSN(CIVICRM_DSN);
      $dsn = DB::parseDSN($dsn);
      $logging_database = $dsn['database'];
      $dao = CRM_Core_DAO::executeQuery("SHOW TABLE STATUS FROM `{$logging_database}` WHERE Engine <> 'MyISAM' AND (" . implode(' OR ', $logTableNameLikePatterns) . ")");
      while ($dao->fetch()) {
        $tables["`{$logging_database}`.`{$dao->Name}`"] = [
          'Engine' => $dao->Engine,
        ];
      }
    }
    foreach ($tables as $table => $param) {
      $query = "ALTER TABLE $table";
      $dao = CRM_Core_DAO::executeQuery("SHOW FULL COLUMNS FROM $table", [], TRUE, NULL, FALSE, FALSE);
      $index = 0;
      $params = [];
      $tableCollation = $newCollation;
      while ($dao->fetch()) {
        if (!$dao->Collation || $dao->Collation === $newCollation || $dao->Collation === $newBinaryCollation) {
          continue;
        }
        if (strpos($dao->Collation, 'utf8') !== 0) {
          continue;
        }

        if (strpos($dao->Collation, '_bin') !== FALSE) {
          $tableCollation = $newBinaryCollation;
        }
        else {
          $tableCollation = $newCollation;
        }
        if ($dao->Null === 'YES') {
          $null = 'NULL';
        }
        else {
          $null = 'NOT NULL';
        }
        $default = '';
        if ($dao->Default !== NULL) {
          $index++;
          $default = "DEFAULT %$index";
          $params[$index] = [$dao->Default, 'String'];
        }
        elseif ($dao->Null === 'YES') {
          $default = 'DEFAULT NULL';
        }
        $index++;
        $params[$index] = [$dao->Comment, 'String'];
        $query .= " MODIFY `{$dao->Field}` {$dao->Type} CHARACTER SET $newCharSet COLLATE $tableCollation $null $default {$dao->Extra} COMMENT %$index,";
      }
      $query .= " CHARACTER SET = $newCharSet COLLATE = $tableCollation";
      if ($param['Engine'] === 'InnoDB') {
        $query .= ' ROW_FORMAT = Dynamic KEY_BLOCK_SIZE = 0';
      }
      // Disable i18n rewrite.
      CRM_Core_DAO::executeQuery($query, $params, TRUE, NULL, FALSE, FALSE);
    }
    // Rebuild triggers and other schema reconciliation if needed.
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    return TRUE;
  }

  /**
   * Get the database collation.
   *
   * @return string
   */
  public static function getDBCollation() {
    return CRM_Core_DAO::singleValueQuery('SELECT @@collation_database');
  }

  /**
   * Get the collation actually being used by the tables in the database.
   *
   * The db collation may not match the collation used by the tables, get what is
   * set on the tables (represented by civicrm_contact).
   *
   * @return string
   */
  public static function getInUseCollation(): string {
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__])) {
      $dao = CRM_Core_DAO::executeQuery('SHOW TABLE STATUS LIKE \'civicrm_contact\'');
      $dao->fetch();
      \Civi::$statics[__CLASS__][__FUNCTION__] = $dao->Collation;
    }
    return \Civi::$statics[__CLASS__][__FUNCTION__];
  }

  /**
   * Does the database support utf8mb4.
   *
   * Utf8mb4 is required to support emojis but older databases may not have it enabled.
   *
   * This is aggressively cached despite just being a string function
   * as it is expected it might be called many times.
   *
   * @return bool
   */
  public static function databaseSupportsUTF8MB4(): bool {
    if (!isset(\Civi::$statics[__CLASS__][__FUNCTION__])) {
      \Civi::$statics[__CLASS__][__FUNCTION__] = stripos(self::getInUseCollation(), 'utf8mb4') === 0;
    }
    return \Civi::$statics[__CLASS__][__FUNCTION__];
  }

  /**
   * Get the database collation.
   *
   * @return string
   */
  public static function getDBCharset() {
    return CRM_Core_DAO::singleValueQuery('SELECT @@character_set_database');
  }

}
