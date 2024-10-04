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

namespace Civi\Schema;

/**
 * Supports older extensions that created dao-based entities with
 * `civix generate:entity-boilerplate`.
 */
class LegacySqlEntityMetadata extends EntityMetadataBase {

  /**
   * @return string|\CRM_Core_DAO
   */
  public function getClassName(): string {
    return $this->getEntity()['class'];
  }

  public function getProperty(string $propertyName) {
    switch ($propertyName) {
      case 'title':
        return $this->getClassName()::getEntityTitle();

      case 'title_plural':
        return $this->getClassName()::getEntityTitle(TRUE);

      case 'icon':
        return $this->getClassName()::$_icon ?? NULL;

      case 'paths':
        return $this->getClassName()::getEntityPaths();

      case 'label_field':
        return $this->getClassName()::getLabelField();

      case 'primary_keys':
        return $this->getClassName()::$_primaryKey ?? ['id'];

      case 'primary_key':
        return $this->getClassName()::$_primaryKey[0] ?? 'id';

      case 'description':
        return $this->getClassName()::getEntityDescription();

      default:
        return $this->getEntity()[$propertyName] ?? NULL;
    }
  }

  public function getFields(): array {
    $fields = [];
    $primaryKeys = $this->getProperty('primary_keys');
    foreach ($this->getClassName()::fields() as $uniqueName => $legacyField) {
      $fieldName = $legacyField['name'];
      $field = [
        'title' => $legacyField['title'] ?? $fieldName,
        'sql_type' => $this->getSqlType($legacyField),
        'input_type' => $legacyField['html']['type'] ?? NULL,
        'description' => $legacyField['description'] ?? NULL,
        'required' => $legacyField['required'] ?? FALSE,
        'add' => $legacyField['add'] ?? NULL,
        'usage' => $this->getUsage($legacyField),
        'deprecated' => $legacyField['deprecated'] ?? FALSE,
        'readonly' => $legacyField['readonly'] ?? FALSE,
        'localizable' => !empty($legacyField['localizable']),
        'localize_context' => $legacyField['localize_context'] ?? NULL,
        'unique_title' => $legacyField['uniqueTitle'] ?? NULL,
        'contact_type' => $legacyField['contactType'] ?? NULL,
        'component' => $legacyField['component'] ?? NULL,
        'serialize' => $legacyField['serialize'] ?? NULL,
        'permission' => $legacyField['permission'] ?? NULL,
      ];
      if ($uniqueName !== $fieldName) {
        $field['unique_name'] = $uniqueName;
      }
      if (array_key_exists('default', $legacyField)) {
        $field['default'] = $legacyField['default'];
        if ($field['default'] === 'NULL') {
          $field['default'] = NULL;
        }
        elseif (is_string($field['default'])) {
          $field['default'] = trim($field['default'], '"\'');
          if (str_contains($field['sql_type'], 'int')) {
            $field['default'] = (int) $field['default'];
          }
          if ($field['sql_type'] === 'boolean') {
            $field['default'] = (bool) $field['default'];
          }
        }
      }
      unset($legacyField['html']['type']);
      if (!empty($legacyField['html'])) {
        $field['input_attrs'] = \CRM_Utils_Array::rekey($legacyField['html'], ['CRM_Utils_String', 'convertStringToSnakeCase']);
      }
      foreach (['rows', 'cols', 'maxlength'] as $attr) {
        if (!empty($legacyField[$attr])) {
          $field['input_attrs'][$attr] = $legacyField[$attr];
        }
      }
      if (!empty($legacyField['pseudoconstant'])) {
        $field['pseudoconstant'] = \CRM_Utils_Array::rekey($legacyField['pseudoconstant'], ['CRM_Utils_String', 'convertStringToSnakeCase']);
        unset($field['pseudoconstant']['option_edit_path']);
      }
      if (!empty($legacyField['FKClassName'])) {
        $field['entity_reference'] = [
          'entity' => \CRM_Core_DAO_AllCoreTables::getEntityNameForClass($legacyField['FKClassName']),
          // Making assumptions about key but this is a legacy filler & it doesn't really matter
          'key' => 'id',
        ];
      }
      // Making assumptions about the nature of an `entity_id` field but this is a legacy filler & it doesn't really matter
      if ($fieldName === 'entity_id') {
        $field['entity_reference'] = [
          'dynamic_entity' => 'entity_table',
          'key' => 'id',
        ];
      }
      if (in_array($fieldName, $primaryKeys)) {
        $field['primary_key'] = TRUE;
        $field['auto_increment'] = TRUE;
      }
      $fields[$fieldName] = $field;
    }
    return $fields;
  }

  private function getSqlType(array $legacyField): string {
    switch ($legacyField['type']) {
      case \CRM_Utils_Type::T_INT:
        return 'int';

      case \CRM_Utils_Type::T_STRING:
        return 'varchar' . (empty($legacyField['maxlength']) ? '' : "({$legacyField['maxlength']})");

      case \CRM_Utils_Type::T_TEXT:
        return 'text';

      case \CRM_Utils_Type::T_LONGTEXT:
        return 'longtext';

      case \CRM_Utils_Type::T_BOOLEAN:
        return 'boolean';

      case \CRM_Utils_Type::T_FLOAT:
        return 'double';

      case \CRM_Utils_Type::T_MEDIUMBLOB:
        return 'mediumblob';

      case \CRM_Utils_Type::T_BLOB:
        return 'blob';

      case \CRM_Utils_Type::T_TIMESTAMP:
        return 'timestamp';

      case \CRM_Utils_Type::T_MONEY:
        return 'decimal' . (empty($legacyField['precision']) ? '' : '(' . implode(',', $legacyField['precision']) . ')');

      case \CRM_Utils_Type::T_DATE:
        return 'date';

      case \CRM_Utils_Type::T_DATE + \CRM_Utils_Type::T_TIME:
        return 'datetime';

      default:
        throw new \CRM_Core_Exception('Unknown field type for ' . $legacyField['name']);
    }
  }

  private function getUsage(array $legacyField): array {
    if (isset($legacyField['usage'])) {
      return array_keys(array_filter($legacyField['usage']));
    }
    $usage = [];
    if (!empty($legacyField['import'])) {
      $usage = ['import', 'duplicate_matching'];
    }
    if (!empty($legacyField['export'])) {
      $usage[] = 'export';
    }
    return $usage;
  }

}
