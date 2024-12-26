<?php

/**
 * Read the schema specification and parse into internal data structures
 */
class CRM_Core_CodeGen_Specification {
  public $tables;
  public $database;

  protected $classNames;

  public $buildVersion;

  /**
   * Read and parse.
   *
   * @param $schemaPath
   * @param string $buildVersion
   *   Which version of the schema to build.
   * @param bool $verbose
   */
  public function parse($schemaPath, $buildVersion, $verbose = TRUE) {
    $this->buildVersion = $buildVersion;

    // Disabling xml parsing as the schema is no longer xml-based
    return;

    if ($verbose) {
      echo 'Parsing schema description ' . $schemaPath . "\n";
    }
    $dbXML = CRM_Core_CodeGen_Util_Xml::parse($schemaPath);

    if ($verbose) {
      echo "Extracting database information\n";
    }
    $this->database = &$this->getDatabase($dbXML);

    $this->classNames = [];

    // TODO: peel DAO-specific stuff out of getTables, and spec reading into its own class
    if ($verbose) {
      echo "Extracting table information\n";
    }
    $this->tables = $this->getTables($dbXML, $this->database);

    $this->resolveForeignKeys($this->tables, $this->classNames);
    $this->tables = $this->orderTables($this->tables);

    // add archive tables here
    foreach ($this->tables as $name => $table) {
      if ($table['archive'] === 'true') {
        $name = 'archive_' . $table['name'];
        $table['name'] = $name;
        $table['archive'] = 'false';
        if (isset($table['foreignKey'])) {
          foreach ($table['foreignKey'] as $fkName => $fkValue) {
            if ($this->tables[$fkValue['table']]['archive'] === 'true') {
              $table['foreignKey'][$fkName]['table'] = 'archive_' . $table['foreignKey'][$fkName]['table'];
              $table['foreignKey'][$fkName]['uniqName']
                = str_replace('FK_', 'FK_archive_', $table['foreignKey'][$fkName]['uniqName']);
            }
          }
          $archiveTables[$name] = $table;
        }
      }
    }
  }

  /**
   * @param $dbXML
   *
   * @return array
   */
  public function &getDatabase(&$dbXML) {
    $database = ['name' => trim((string ) $dbXML->name)];

    $attributes = '';
    $this->checkAndAppend($attributes, $dbXML, 'character_set', 'DEFAULT CHARACTER SET ', '');
    $this->checkAndAppend($attributes, $dbXML, 'collate', 'COLLATE ', '');
    $attributes .= ' ROW_FORMAT=DYNAMIC';
    $database['attributes'] = $attributes;

    $tableAttributes_modern = $tableAttributes_simple = '';
    $this->checkAndAppend($tableAttributes_modern, $dbXML, 'table_type', 'ENGINE=', '');
    $this->checkAndAppend($tableAttributes_simple, $dbXML, 'table_type', 'TYPE=', '');
    $database['tableAttributes_modern'] = trim($tableAttributes_modern . ' ' . $attributes);
    $database['tableAttributes_simple'] = trim($tableAttributes_simple);

    $database['comment'] = $this->value('comment', $dbXML, '');

    return $database;
  }

  /**
   * @param $dbXML
   * @param $database
   *
   * @return array
   */
  public function getTables($dbXML, &$database) {
    $tables = [];
    foreach ($dbXML->tables as $tablesXML) {
      foreach ($tablesXML->table as $tableXML) {
        if ($this->value('drop', $tableXML, 0) > 0 && version_compare($this->value('drop', $tableXML, 0), $this->buildVersion, '<=')) {
          continue;
        }

        if (version_compare($this->value('add', $tableXML, 0), $this->buildVersion, '<=')) {
          $this->getTable($tableXML, $database, $tables);
        }
      }
    }

    return $tables;
  }

  /**
   * @param array $tables
   * @param string[] $classNames
   */
  public function resolveForeignKeys(&$tables, &$classNames) {
    foreach (array_keys($tables) as $name) {
      $this->resolveForeignKey($tables, $classNames, $name);
    }
  }

  /**
   * @param array $tables
   * @param string[] $classNames
   * @param string $name
   */
  public function resolveForeignKey(&$tables, &$classNames, $name) {
    if (!array_key_exists('foreignKey', $tables[$name])) {
      return;
    }

    foreach (array_keys($tables[$name]['foreignKey']) as $fkey) {
      $ftable = $tables[$name]['foreignKey'][$fkey]['table'];
      if (!array_key_exists($ftable, $classNames)) {
        echo "$ftable is not a valid foreign key table in $name\n";
        continue;
      }
      $tables[$name]['foreignKey'][$fkey]['className'] = $classNames[$ftable];
      $tables[$name]['foreignKey'][$fkey]['fileName'] = str_replace('_', '/', $classNames[$ftable]) . '.php';
      $tables[$name]['fields'][$fkey]['FKClassName'] = $classNames[$ftable];
      $tables[$name]['fields'][$fkey]['FKColumnName'] = $tables[$name]['foreignKey'][$fkey]['key'];
    }
  }

  /**
   * @param array $tables
   *
   * @return array
   */
  public function orderTables(&$tables) {
    $ordered = [];

    while (!empty($tables)) {
      foreach (array_keys($tables) as $name) {
        if ($this->validTable($tables, $ordered, $name)) {
          $ordered[$name] = $tables[$name];
          unset($tables[$name]);
        }
      }
    }
    return $ordered;
  }

  /**
   * @param array $tables
   * @param int $valid
   * @param string $name
   *
   * @return bool
   */
  public function validTable(&$tables, &$valid, $name) {
    if (!array_key_exists('foreignKey', $tables[$name])) {
      return TRUE;
    }

    foreach (array_keys($tables[$name]['foreignKey']) as $fkey) {
      $ftable = $tables[$name]['foreignKey'][$fkey]['table'];
      if (!array_key_exists($ftable, $valid) && $ftable !== $name) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param $tableXML
   * @param $database
   * @param $tables
   */
  public function getTable($tableXML, &$database, &$tables) {
    $name = trim((string ) $tableXML->name);
    $klass = trim((string ) $tableXML->class);
    $base = $this->value('base', $tableXML);
    $sourceFile = "xml/schema/{$base}/{$klass}.xml";
    $daoPath = "{$base}/DAO/";
    $baoPath = __DIR__ . '/../../../' . str_replace(' ', '', "{$base}/BAO/");
    $useBao = $this->value('useBao', $tableXML, file_exists($baoPath . $klass . '.php'));
    $pre = str_replace('/', '_', $daoPath);
    $this->classNames[$name] = $pre . $klass;

    $localizable = FALSE;
    foreach ($tableXML->field as $fieldXML) {
      if ($fieldXML->localizable) {
        $localizable = TRUE;
        break;
      }
    }

    $titleFromClass = preg_replace('/([a-z])([A-Z])/', '$1 $2', $klass);
    $table = [
      'name' => $name,
      'base' => $daoPath,
      'sourceFile' => $sourceFile,
      'fileName' => $klass . '.php',
      'objectName' => $klass,
      'title' => $tableXML->title ?? $titleFromClass,
      'titlePlural' => $tableXML->titlePlural ?? CRM_Utils_String::pluralize($tableXML->title ?? $titleFromClass),
      'icon' => $tableXML->icon ?? NULL,
      'labelField' => $tableXML->labelField ?? NULL,
      'add' => $tableXML->add ?? NULL,
      'component' => $tableXML->component ?? NULL,
      'paths' => (array) ($tableXML->paths ?? []),
      'labelName' => substr($name, 8),
      'className' => $this->classNames[$name],
      'bao' => ($useBao ? str_replace('DAO', 'BAO', $this->classNames[$name]) : $this->classNames[$name]),
      'entity' => $tableXML->entity ?? $klass,
      'attributes_simple' => trim($database['tableAttributes_simple']),
      'attributes_modern' => trim($database['tableAttributes_modern']),
      'comment' => $this->value('comment', $tableXML),
      'description' => $this->value('description', $tableXML),
      'localizable' => $localizable,
      'log' => $this->value('log', $tableXML, 'false'),
      'archive' => $this->value('archive', $tableXML, 'false'),
    ];

    $fields = [];
    foreach ($tableXML->field as $fieldXML) {
      if ($this->value('drop', $fieldXML, 0) > 0 && version_compare($this->value('drop', $fieldXML, 0), $this->buildVersion, '<=')) {
        continue;
      }

      if (version_compare($this->value('add', $fieldXML, 0), $this->buildVersion, '<=')) {
        $this->getField($fieldXML, $fields);
      }
    }

    $table['fields'] = &$fields;

    // Default label field
    if (!$table['labelField']) {
      $possibleLabels = ['label', 'title'];
      $table['labelField'] = CRM_Utils_Array::first(array_intersect($possibleLabels, array_keys($fields)));
    }

    if ($this->value('primaryKey', $tableXML)) {
      $this->getPrimaryKey($tableXML->primaryKey, $fields, $table);
    }

    if ($this->value('index', $tableXML)) {
      $index = [];
      foreach ($tableXML->index as $indexXML) {
        if ($this->value('drop', $indexXML, 0) > 0 && version_compare($this->value('drop', $indexXML, 0), $this->buildVersion, '<=')) {
          continue;
        }

        $this->getIndex($indexXML, $fields, $index);
      }
      CRM_Core_BAO_SchemaHandler::addIndexSignature($name, $index);
      $table['index'] = &$index;
    }

    if ($this->value('foreignKey', $tableXML)) {
      $foreign = [];
      foreach ($tableXML->foreignKey as $foreignXML) {

        if ($this->value('drop', $foreignXML, 0) > 0 && version_compare($this->value('drop', $foreignXML, 0), $this->buildVersion, '<=')) {
          continue;
        }
        if (version_compare($this->value('add', $foreignXML, 0), $this->buildVersion, '<=')) {
          $this->getForeignKey($foreignXML, $fields, $foreign, $name);
        }
      }
      if (!empty($foreign)) {
        $table['foreignKey'] = &$foreign;
      }
    }

    if ($this->value('dynamicForeignKey', $tableXML)) {
      $dynamicForeign = [];
      foreach ($tableXML->dynamicForeignKey as $foreignXML) {
        if ($this->value('drop', $foreignXML, 0) > 0 && version_compare($this->value('drop', $foreignXML, 0), $this->buildVersion, '<=')) {
          continue;
        }
        if (version_compare($this->value('add', $foreignXML, 0), $this->buildVersion, '<=')) {
          $this->getDynamicForeignKey($foreignXML, $dynamicForeign, $name);
        }
      }
      $table['dynamicForeignKey'] = $dynamicForeign;
      foreach ($dynamicForeign as $dfk) {
        $fields[$dfk['idColumn']]['FKColumnName'] = $dfk['key'];
        $fields[$dfk['idColumn']]['DFKEntityColumn'] = $dfk['typeColumn'];
      }
    }

    $tables[$name] = &$table;
  }

  /**
   * @param $fieldXML
   * @param $fields
   */
  public function getField(&$fieldXML, &$fields) {
    $name = trim((string ) $fieldXML->name);
    $field = ['name' => $name, 'localizable' => ((bool) $fieldXML->localizable) ? 1 : 0];
    $field = array_merge($field, \CRM_Utils_Schema::getTypeAttributes($fieldXML));

    $field['phpType'] = $this->getPhpType($fieldXML);
    $field['phpNullable'] = $this->getPhpNullable($fieldXML);

    $field['required'] = $this->value('required', $fieldXML);
    $field['collate'] = $this->value('collate', $fieldXML);
    $field['comment'] = $this->value('comment', $fieldXML);
    $field['deprecated'] = $this->value('deprecated', $fieldXML, FALSE);
    $field['default'] = $this->value('default', $fieldXML);

    $field['usage'] = CRM_Utils_Schema::getFieldUsage($fieldXML);
    $field['rule'] = $this->value('rule', $fieldXML);
    $field['title'] = $this->value('title', $fieldXML);
    if (!$field['title']) {
      $field['title'] = CRM_Utils_Schema::composeTitle($name);
    }
    $field['headerPattern'] = $this->value('headerPattern', $fieldXML);
    $field['dataPattern'] = $this->value('dataPattern', $fieldXML);
    $field['readonly'] = $this->value('readonly', $fieldXML);
    $field['uniqueName'] = $this->value('uniqueName', $fieldXML);
    $field['uniqueTitle'] = $this->value('uniqueTitle', $fieldXML);
    $field['serialize'] = $this->value('serialize', $fieldXML);
    $field['component'] = $this->value('component', $fieldXML);
    $field['html'] = CRM_Utils_Schema::getFieldHtml($fieldXML);
    $field['contactType'] = $this->value('contactType', $fieldXML);
    $field['permission'] = CRM_Utils_Schema::getFieldPermission($fieldXML);
    $field['widget'] = CRM_Utils_Schema::getFieldWidget($fieldXML);
    $field['localize_context'] = $this->value('localize_context', $fieldXML);
    $field['add'] = $this->value('add', $fieldXML);
    $field['pseudoconstant'] = CRM_Utils_Schema::getFieldPseudoconstant($fieldXML);
    $fields[$name] = &$field;
  }

  /**
   * Returns the PHPtype used within the DAO object
   *
   * @param object $fieldXML
   * @return string
   */
  private function getPhpType($fieldXML) {
    $type = $fieldXML->type;
    $phpType = $this->value('phpType', $fieldXML, 'string');

    if ($type == 'int' || $type == 'int unsigned' || $type == 'tinyint') {
      $phpType = 'int';
    }

    if ($type == 'float' || $type == 'decimal') {
      $phpType = 'float';
    }

    if ($type == 'boolean') {
      $phpType = 'bool';
    }

    if ($phpType !== 'string') {
      // Values are almost always fetched from the database as string
      $phpType .= '|string';
    }

    return $phpType;
  }

  /**
   * Returns whether the field is nullable in PHP.
   * Either because:
   *  - The SQL field is nullable
   *  - The field is a primary key, and so is null before new objects are saved
   *
   * @param object $fieldXML
   * @return bool
   */
  private function getPhpNullable($fieldXML) {
    $required = $this->value('required', $fieldXML);
    return !$required;
  }

  /**
   * @param object $primaryXML
   * @param array $fields
   * @param array $table
   */
  public function getPrimaryKey(&$primaryXML, &$fields, &$table) {
    $name = trim((string ) $primaryXML->name);

    // set the autoincrement property of the field
    $auto = $this->value('autoincrement', $primaryXML);
    if (isset($fields[$name])) {
      $fields[$name]['autoincrement'] = $auto;
      $fields[$name]['phpNullable'] = TRUE;
    }

    $primaryKey = [
      'name' => $name,
      'autoincrement' => $auto,
    ];

    // populate fields
    foreach ($primaryXML->fieldName as $v) {
      $fieldName = (string) ($v);
      $length = (string) ($v['length']);
      if (strlen($length) > 0) {
        $fieldName = "$fieldName($length)";
      }
      $primaryKey['field'][] = $fieldName;
    }

    // when field array is empty set it to the name of the primary key.
    if (empty($primaryKey['field'])) {
      $primaryKey['field'][] = $name;
    }

    // all fieldnames have to be defined and should exist in schema.
    foreach ($primaryKey['field'] as $fieldName) {
      if (!$fieldName) {
        echo "Invalid field definition for index '$name' in table {$table['name']}\n";
        return;
      }
      $parenOffset = strpos($fieldName, '(');
      if ($parenOffset > 0) {
        $fieldName = substr($fieldName, 0, $parenOffset);
      }
      if (!array_key_exists($fieldName, $fields)) {
        echo "Missing definition of field '$fieldName' for index '$name' in table {$table['name']}\n";
        print_r($fields);
        exit();
      }
    }

    $table['primaryKey'] = &$primaryKey;
  }

  /**
   * @param $indexXML
   * @param $fields
   * @param $indices
   */
  public function getIndex(&$indexXML, &$fields, &$indices) {
    //echo "\n\n*******************************************************\n";
    //echo "entering getIndex\n";

    $index = [];
    // empty index name is fine
    $indexName = trim((string) $indexXML->name);
    $index['name'] = $indexName;
    $index['field'] = [];

    // populate fields
    foreach ($indexXML->fieldName as $v) {
      $fieldName = (string) ($v);
      $length = (string) ($v['length']);
      if (strlen($length) > 0) {
        $fieldName = "$fieldName($length)";
      }
      $index['field'][] = $fieldName;
    }

    $index['localizable'] = FALSE;
    foreach ($index['field'] as $fieldName) {
      if (isset($fields[$fieldName]) and $fields[$fieldName]['localizable']) {
        $index['localizable'] = TRUE;
        break;
      }
    }

    // check for unique index
    if ($this->value('unique', $indexXML)) {
      $index['unique'] = TRUE;
    }

    // field array cannot be empty
    if (empty($index['field'])) {
      echo "No fields defined for index $indexName\n";
      return;
    }

    // all fieldnames have to be defined and should exist in schema.
    foreach ($index['field'] as $fieldName) {
      if (!$fieldName) {
        echo "Invalid field definition for index '$indexName'\n";
        return;
      }
      $parenOffset = strpos($fieldName, '(');
      if ($parenOffset > 0) {
        $fieldName = substr($fieldName, 0, $parenOffset);
      }
      if (!array_key_exists($fieldName, $fields)) {
        echo "Missing definition of field '$fieldName' for index '$indexName'. Fields defined:\n";
        print_r($fields);
        exit();
      }
    }
    $indices[$indexName] = &$index;
  }

  /**
   * @param $foreignXML
   * @param $fields
   * @param $foreignKeys
   * @param string $currentTableName
   */
  public function getForeignKey(&$foreignXML, &$fields, &$foreignKeys, &$currentTableName) {
    $name = trim((string ) $foreignXML->name);

    /** need to make sure there is a field of type name */
    if (!array_key_exists($name, $fields)) {
      echo "Foreign key '$name' in $currentTableName does not have a field definition, ignoring\n";
      return;
    }

    /** need to check for existence of table and key **/
    $table = trim($this->value('table', $foreignXML));
    $foreignKey = [
      'name' => $name,
      'table' => $table,
      'uniqName' => "FK_{$currentTableName}_{$name}",
      'key' => trim($this->value('key', $foreignXML)),
      'import' => $this->value('import', $foreignXML, FALSE),
      'export' => $this->value('import', $foreignXML, FALSE),
      // we do this matching in a separate phase (resolveForeignKeys)
      'className' => NULL,
      'onDelete' => $this->value('onDelete', $foreignXML, FALSE),
    ];
    $foreignKeys[$name] = &$foreignKey;
  }

  /**
   * @param $foreignXML
   * @param $dynamicForeignKeys
   */
  public function getDynamicForeignKey(&$foreignXML, &$dynamicForeignKeys) {
    $foreignKey = [
      'idColumn' => trim($foreignXML->idColumn),
      'typeColumn' => trim($foreignXML->typeColumn),
      'key' => trim($this->value('key', $foreignXML) ?? 'id'),
    ];
    $dynamicForeignKeys[] = $foreignKey;
  }

  /**
   * @param $key
   * @param $object
   * @param null $default
   *
   * @return null|string|\SimpleXMLElement
   */
  protected function value($key, &$object, $default = NULL) {
    if (isset($object->$key)) {
      return (string ) $object->$key;
    }
    return $default;
  }

  /**
   * @param $attributes
   * @param $object
   * @param string $name
   * @param null $pre
   * @param null $post
   */
  protected function checkAndAppend(&$attributes, &$object, $name, $pre = NULL, $post = NULL) {
    if (!isset($object->$name)) {
      return;
    }

    $value = $pre . trim($object->$name) . $post;
    $this->append($attributes, ' ', trim($value));
  }

  /**
   * @param $str
   * @param $delim
   * @param $name
   */
  protected function append(&$str, $delim, $name) {
    if (empty($name)) {
      return;
    }

    if (is_array($name)) {
      foreach ($name as $n) {
        if (empty($n)) {
          continue;
        }
        if (empty($str)) {
          $str = $n;
        }
        else {
          $str .= $delim . $n;
        }
      }
    }
    else {
      if (empty($str)) {
        $str = $name;
      }
      else {
        $str .= $delim . $name;
      }
    }
  }

}
