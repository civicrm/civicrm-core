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
 * Base for concrete DAO/BAO classes which are defined with a schema/entityType.php file.
 */
abstract class CRM_Core_DAO_Base extends CRM_Core_DAO {

  private static $_entityProviders = [];

  public function __construct() {
    parent::__construct();
    // Historically a generated DAO would have one class variable per field.
    // To prevent undefined property warnings, this dynamic DAO mimics that by
    // initializing the object with a property for each field.
    foreach (static::getEntityProvider()->getFields() as $name => $field) {
      $this->$name = NULL;
    }
  }

  /**
   * @inheritDoc
   */
  public function keys(): array {
    $keys = [];
    foreach (static::getEntityProvider()->getFields() as $name => $field) {
      if (!empty($field['primary_key'])) {
        $keys[] = $name;
      }
    }
    return $keys;
  }

  public static function getEntityTitle($plural = FALSE) {
    $entityProvider = static::getEntityProvider();
    $title = $entityProvider->getMeta('title');
    if ($plural) {
      return $entityProvider->getMeta('title_plural') ?? $title;
    }
    return $title;
  }

  /**
   * @inheritDoc
   */
  public static function getEntityPaths(): array {
    return static::getEntityProvider()->getMeta('paths');
  }

  public static function getLabelField(): ?string {
    return static::getEntityProvider()->getMeta('label_field');
  }

  /**
   * @inheritDoc
   */
  public static function getEntityDescription(): ?string {
    return static::getEntityProvider()->getMeta('description');
  }

  /**
   * @inheritDoc
   */
  public static function getTableName() {
    return static::getEntityProvider()->getMeta('table');
  }

  /**
   * @inheritDoc
   */
  public function getLog(): bool {
    return static::getEntityProvider()->getMeta('log');
  }

  /**
   * @inheritDoc
   */
  public static function getEntityIcon(string $entityName, ?int $entityId = NULL): ?string {
    return static::getEntityProvider()->getMeta('icon');
  }

  /**
   * @inheritDoc
   */
  protected static function getTableAddVersion(): string {
    return static::getEntityProvider()->getMeta('add') ?? '1.0';
  }

  /**
   * @inheritDoc
   */
  public static function getExtensionName(): ?string {
    return static::getEntityProvider()->getMeta('module');
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
    if (!isset(Civi::$statics[static::class]['fields'])) {
      Civi::$statics[static::class]['fields'] = static::loadSchemaFields();
      // Note: `fields_callback` is now deprecated in favor of the `civi.entity.fields` event.
      CRM_Core_DAO_AllCoreTables::invoke(static::class, 'fields_callback', Civi::$statics[static::class]['fields']);
    }
    return Civi::$statics[static::class]['fields'];
  }

  private static function loadSchemaFields(): array {
    $fields = [];
    $entityProvider = static::getEntityProvider();
    $baoName = CRM_Core_DAO_AllCoreTables::getBAOClassName(static::class);

    foreach ($entityProvider->getFields() as $fieldName => $fieldSpec) {
      $field = [
        'name' => $fieldName,
        'type' => !empty($fieldSpec['data_type']) ? \CRM_Utils_Type::getValidTypes()[$fieldSpec['data_type']] : CRM_Utils_Schema::getCrmTypeFromSqlType($fieldSpec['sql_type']),
        'title' => $fieldSpec['title'],
        'description' => $fieldSpec['description'] ?? NULL,
      ];
      if (!empty($fieldSpec['required'])) {
        $field['required'] = TRUE;
      }
      if (str_starts_with($fieldSpec['sql_type'], 'decimal(')) {
        $precision = CRM_Core_BAO_SchemaHandler::getFieldLength($fieldSpec['sql_type']);
        $field['precision'] = array_map('intval', explode(',', $precision));
      }
      foreach (['maxlength', 'size', 'rows', 'cols'] as $attr) {
        if (isset($fieldSpec['input_attrs'][$attr])) {
          $field[$attr] = $fieldSpec['input_attrs'][$attr];
          unset($fieldSpec['input_attrs'][$attr]);
        }
      }
      if (str_contains($fieldSpec['sql_type'], 'char(')) {
        $length = CRM_Core_BAO_SchemaHandler::getFieldLength($fieldSpec['sql_type']);
        if (!isset($field['size'])) {
          $field['size'] = constant(CRM_Utils_Schema::getDefaultSize($length));
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
      $field['where'] = $entityProvider->getMeta('table') . '.' . $field['name'];
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
      $field['table_name'] = $entityProvider->getMeta('table');
      $field['entity'] = $entityProvider->getMeta('name');
      $field['bao'] = $baoName;
      $field['localizable'] = intval($fieldSpec['localizable'] ?? 0);
      if (!empty($fieldSpec['localize_context'])) {
        $field['localize_context'] = (string) $fieldSpec['localize_context'];
      }
      if (!empty($fieldSpec['entity_reference'])) {
        if (!empty($fieldSpec['entity_reference']['entity'])) {
          $field['FKClassName'] = CRM_Core_DAO_AllCoreTables::getDAONameForEntity($fieldSpec['entity_reference']['entity']);
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
        $field['html'] = CRM_Utils_Array::rekey($fieldSpec['input_attrs'], fn($str) => CRM_Utils_String::convertStringToCamel($str, FALSE));
      }
      if (!empty($fieldSpec['input_type'])) {
        $field['html']['type'] = $fieldSpec['input_type'];
      }
      if (!empty($fieldSpec['pseudoconstant'])) {
        $field['pseudoconstant'] = CRM_Utils_Array::rekey($fieldSpec['pseudoconstant'], fn($str) => CRM_Utils_String::convertStringToCamel($str, FALSE));
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
    return $fields;
  }

  /**
   * @inheritDoc
   */
  public static function indices(bool $localize = TRUE): array {
    $entityProvider = static::getEntityProvider();
    $entityIndices = $entityProvider->getMeta('indices');
    $indices = [];
    if ($entityIndices) {
      $fields = $entityProvider->getFields();
      foreach ($entityIndices as $name => $info) {
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
        $index['sig'] = $entityProvider->getMeta('table') . '::' . intval($info['unique'] ?? 0) . '::' . implode('::', $index['field']);
        $indices[$name] = $index;
      }
    }
    return ($localize && $indices) ? CRM_Core_DAO_AllCoreTables::multilingualize(static::class, $indices) : $indices;
  }

  private static function getEntityProvider(): \Civi\Schema\EntityProvider {
    $entityName = CRM_Core_DAO_AllCoreTables::getEntityNameForClass(static::class);
    self::$_entityProviders[$entityName] ??= Civi::entity($entityName);
    return self::$_entityProviders[$entityName];
  }

}
