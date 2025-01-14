<?php

namespace CiviMix\Schema;

/**
 * To simplify backport considerations, `DAO` does not have formal name.
 * It is accessed via aliases like "CiviMix\Schema\*\DAO".
 *
 * Target: TBD (5.38+? 5.51+)
 */
return new class() extends \CRM_Core_DAO {

  public function __construct() {
    if (strpos(static::class, '@') !== FALSE) {
      // Template instance. Fake news!
      return;
    }
    parent::__construct();
    // Historically a generated DAO would have one class variable per field.
    // To prevent undefined property warnings, this dynamic DAO mimics that by
    // initializing the object with a property for each field.
    foreach (static::getEntityDefinition()['getFields']() as $name => $field) {
      $this->$name = NULL;
    }
  }

  /**
   * @inheritDoc
   */
  public function keys(): array {
    $keys = [];
    foreach (static::getEntityDefinition()['getFields']() as $name => $field) {
      if (!empty($field['primary_key'])) {
        $keys[] = $name;
      }
    }
    return $keys;
  }

  public static function getEntityTitle($plural = FALSE) {
    $info = static::getEntityInfo();
    return ($plural && isset($info['title_plural'])) ? $info['title_plural'] : $info['title'];
  }

  /**
   * @inheritDoc
   */
  public static function getEntityPaths(): array {
    $definition = static::getEntityDefinition();
    if (isset($definition['getPaths'])) {
      return $definition['getPaths']();
    }
    return [];
  }

  public static function getLabelField(): ?string {
    return static::getEntityInfo()['label_field'] ?? NULL;
  }

  /**
   * @inheritDoc
   */
  public static function getEntityDescription(): ?string {
    return static::getEntityInfo()['description'] ?? NULL;
  }

  /**
   * @inheritDoc
   */
  public static function getTableName() {
    return static::getEntityDefinition()['table'];
  }

  /**
   * @inheritDoc
   */
  public function getLog(): bool {
    return static::getEntityInfo()['log'] ?? FALSE;
  }

  /**
   * @inheritDoc
   */
  public static function getEntityIcon(string $entityName, ?int $entityId = NULL): ?string {
    return static::getEntityInfo()['icon'] ?? NULL;
  }

  /**
   * @inheritDoc
   */
  protected static function getTableAddVersion(): string {
    return static::getEntityInfo()['add'] ?? '1.0';
  }

  /**
   * @inheritDoc
   */
  public static function getExtensionName(): ?string {
    return static::getEntityDefinition()['module'];
  }

  /**
   * @inheritDoc
   */
  public static function &fields() {
    $fields = [];
    foreach (static::getSchemaFields() as $field) {
      $key = $field['uniqueName'] ?? $field['name'];
      unset($field['uniqueName']);
      $fields[$key] = $field;
    }
    return $fields;
  }

  private static function getSchemaFields(): array {
    if (!isset(\Civi::$statics[static::class]['fields'])) {
      \Civi::$statics[static::class]['fields'] = static::loadSchemaFields();
    }
    return \Civi::$statics[static::class]['fields'];
  }

  private static function loadSchemaFields(): array {
    $fields = [];
    $entityDef = static::getEntityDefinition();
    $baoName = \CRM_Core_DAO_AllCoreTables::getBAOClassName(static::class);

    foreach ($entityDef['getFields']() as $fieldName => $fieldSpec) {
      $field = [
        'name' => $fieldName,
        'type' => !empty($fieldSpec['data_type']) ? \CRM_Utils_Type::getValidTypes()[$fieldSpec['data_type']] : static::getCrmTypeFromSqlType($fieldSpec['sql_type']),
        'title' => $fieldSpec['title'],
        'description' => $fieldSpec['description'] ?? NULL,
      ];
      if (!empty($fieldSpec['required'])) {
        $field['required'] = TRUE;
      }
      if (strpos($fieldSpec['sql_type'], 'decimal(') === 0) {
        $precision = self::getFieldLength($fieldSpec['sql_type']);
        $field['precision'] = array_map('intval', explode(',', $precision));
      }
      foreach (['maxlength', 'size', 'rows', 'cols'] as $attr) {
        if (isset($fieldSpec['input_attrs'][$attr])) {
          $field[$attr] = $fieldSpec['input_attrs'][$attr];
          unset($fieldSpec['input_attrs'][$attr]);
        }
      }
      if (strpos($fieldSpec['sql_type'], 'char(') !== FALSE) {
        $length = self::getFieldLength($fieldSpec['sql_type']);
        if (!isset($field['size'])) {
          $field['size'] = constant(static::getDefaultSize($length));
        }
        if (!isset($field['maxlength'])) {
          $field['maxlength'] = $length;
        }
      }
      $usage = $fieldSpec['usage'] ?? [];
      $field['usage'] = [
        'import' => in_array('import', $usage),
        'export' => in_array('export', $usage),
        'duplicate_matching' => in_array('duplicate_matching', $usage),
        'token' => in_array('token', $usage),
      ];
      if ($field['usage']['import']) {
        $field['import'] = TRUE;
      }
      $field['where'] = $entityDef['table'] . '.' . $field['name'];
      if ($field['usage']['export'] || (!$field['usage']['export'] && $field['usage']['import'])) {
        $field['export'] = $field['usage']['export'];
      }
      if (!empty($fieldSpec['contact_type'])) {
        $field['contactType'] = $fieldSpec['contact_type'];
      }
      if (!empty($fieldSpec['permission'])) {
        $field['permission'] = $fieldSpec['permission'];
      }
      if (array_key_exists('default', $fieldSpec)) {
        $field['default'] = isset($fieldSpec['default']) ? (string) $fieldSpec['default'] : NULL;
        if (is_bool($fieldSpec['default'])) {
          $field['default'] = $fieldSpec['default'] ? '1' : '0';
        }
      }
      $field['table_name'] = $entityDef['table'];
      $field['entity'] = $entityDef['name'];
      $field['bao'] = $baoName;
      $field['localizable'] = intval($fieldSpec['localizable'] ?? 0);
      if (!empty($fieldSpec['localize_context'])) {
        $field['localize_context'] = (string) $fieldSpec['localize_context'];
      }
      if (!empty($fieldSpec['entity_reference'])) {
        if (!empty($fieldSpec['entity_reference']['entity'])) {
          $field['FKClassName'] = static::getDAONameForEntity($fieldSpec['entity_reference']['entity']);
        }
        if (!empty($fieldSpec['entity_reference']['dynamic_entity'])) {
          $field['DFKEntityColumn'] = $fieldSpec['entity_reference']['dynamic_entity'];
        }
        $field['FKColumnName'] = $fieldSpec['entity_reference']['key'] ?? 'id';
      }
      if (!empty($fieldSpec['component'])) {
        $field['component'] = $fieldSpec['component'];
      }
      if (!empty($fieldSpec['serialize'])) {
        $field['serialize'] = $fieldSpec['serialize'];
      }
      if (!empty($fieldSpec['unique_name'])) {
        $field['uniqueName'] = $fieldSpec['unique_name'];
      }
      if (!empty($fieldSpec['unique_title'])) {
        $field['unique_title'] = $fieldSpec['unique_title'];
      }
      if (!empty($fieldSpec['deprecated'])) {
        $field['deprecated'] = TRUE;
      }
      if (!empty($fieldSpec['input_attrs'])) {
        $field['html'] = \CRM_Utils_Array::rekey($fieldSpec['input_attrs'], function($str) {
           return \CRM_Utils_String::convertStringToCamel($str, FALSE);
        });
      }
      if (!empty($fieldSpec['input_type'])) {
        $field['html']['type'] = $fieldSpec['input_type'];
      }
      if (!empty($fieldSpec['pseudoconstant'])) {
        $field['pseudoconstant'] = \CRM_Utils_Array::rekey($fieldSpec['pseudoconstant'], function($str) {
           return \CRM_Utils_String::convertStringToCamel($str, FALSE);
        });
        if (!isset($field['pseudoconstant']['optionEditPath']) && !empty($field['pseudoconstant']['optionGroupName'])) {
          $field['pseudoconstant']['optionEditPath'] = 'civicrm/admin/options/' . $field['pseudoconstant']['optionGroupName'];
        }
      }
      if (!empty($fieldSpec['primary_key']) || !empty($fieldSpec['readonly'])) {
        $field['readonly'] = TRUE;
      }
      $field['add'] = $fieldSpec['add'] ?? NULL;
      $fields[$fieldName] = $field;
    }
    \CRM_Core_DAO_AllCoreTables::invoke(static::class, 'fields_callback', $fields);
    return $fields;
  }

  private static function getFieldLength($sqlType): ?string {
    $open = strpos($sqlType, '(');
    if ($open) {
      return substr($sqlType, $open + 1, -1);
    }
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public static function indices(bool $localize = TRUE): array {
    $definition = static::getEntityDefinition();
    $indices = [];
    if (isset($definition['getIndices'])) {
      $fields = $definition['getFields']();
      foreach ($definition['getIndices']() as $name => $info) {
        $index = [
          'name' => $name,
          'field' => [],
          'localizable' => FALSE,
        ];
        foreach ($info['fields'] as $fieldName => $length) {
          if (!empty($fields[$fieldName]['localizable'])) {
            $index['localizable'] = TRUE;
          }
          if (is_int($length)) {
            $fieldName .= "($length)";
          }
          $index['field'][] = $fieldName;
        }
        if (!empty($info['unique'])) {
          $index['unique'] = TRUE;
        }
        $index['sig'] = ($definition['table']) . '::' . intval($info['unique'] ?? 0) . '::' . implode('::', $index['field']);
        $indices[$name] = $index;
      }
    }
    return ($localize && $indices) ? \CRM_Core_DAO_AllCoreTables::multilingualize(static::class, $indices) : $indices;
  }

  public static function getEntityDefinition(): array {
    if (!isset(\Civi::$statics[static::class]['definition'])) {
      $class = new \ReflectionClass(static::class);
      $file = substr(basename($class->getFileName()), 0, -4) . '.entityType.php';
      $folder = dirname($class->getFileName(), 4) . '/schema/';
      $path = $folder . $file;
      \Civi::$statics[static::class]['definition'] = include $path;
    }
    return \Civi::$statics[static::class]['definition'];
  }

  private static function getEntityInfo(): array {
    return static::getEntityDefinition()['getInfo']();
  }

  private static function getDefaultSize($length) {
    // Infer from <length> tag if <size> was not explicitly set or was invalid
    // This map is slightly different from CRM_Core_Form_Renderer::$_sizeMapper
    // Because we usually want fields to render as smaller than their maxlength
    $sizes = [
      2 => 'TWO',
      4 => 'FOUR',
      6 => 'SIX',
      8 => 'EIGHT',
      16 => 'TWELVE',
      32 => 'MEDIUM',
      64 => 'BIG',
    ];
    foreach ($sizes as $size => $name) {
      if ($length <= $size) {
        return "CRM_Utils_Type::$name";
      }
    }
    return 'CRM_Utils_Type::HUGE';
  }

  private static function getCrmTypeFromSqlType(string $sqlType): int {
    [$type] = explode('(', $sqlType);
    switch ($type) {
      case 'varchar':
      case 'char':
        return \CRM_Utils_Type::T_STRING;

      case 'datetime':
        return \CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME;

      case 'decimal':
        return \CRM_Utils_Type::T_MONEY;

      case 'double':
        return \CRM_Utils_Type::T_FLOAT;

      case 'int unsigned':
      case 'tinyint':
        return \CRM_Utils_Type::T_INT;

      default:
        return constant('CRM_Utils_Type::T_' . strtoupper($type));
    }
  }

  private static function getDAONameForEntity($entity) {
    if (is_callable(['CRM_Core_DAO_AllCoreTables', 'getDAONameForEntity'])) {
      return \CRM_Core_DAO_AllCoreTables::getDAONameForEntity($entity);
    }
    else {
      return \CRM_Core_DAO_AllCoreTables::getFullName($entity);
    }
  }

};
