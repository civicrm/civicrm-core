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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_I18n_SchemaStructure {

  /**
   * Get translatable columns.
   *
   * @return array
   *   A table-indexed array of translatable columns.
   */
  public static function &columns() {
    static $result = NULL;
    if (!$result) {
      $result = [];
      global $civicrm_root;
      $sqlGenerator = require "$civicrm_root/mixin/lib/civimix-schema@5/src/SqlGenerator.php";
      foreach (\Civi\Schema\EntityRepository::getEntities() as $entity) {
        if (($entity['module'] ?? NULL) !== 'civicrm') {
          continue;
        }
        if (empty($entity['getFields'])) {
          continue;
        }
        foreach ($entity['getFields']() as $fieldName => $field) {
          if (!empty($field['localizable'])) {
            // dev/core#2581 set blank default for required fields; avoids error in MariaDB with STRICT_TRANS_TABLES
            if (!empty($field['required'])) {
              $field += ['default' => ''];
            }
            $result[$entity['table']][$fieldName] = $sqlGenerator::generateFieldSql($field);
          }
        }
      }
    }
    return $result;
  }

  /**
   * Get a table indexed array of the indices for translatable fields.
   *
   * @return array
   *   Indices for translatable fields.
   */
  public static function &indices() {
    static $result = NULL;
    if (!$result) {
      $result = [];
      foreach (self::columns() as $tableName => $localizableFields) {
        $entityName = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($tableName);
        $entity = \Civi\Schema\EntityRepository::getEntity($entityName);
        if (empty($entity['getIndices'])) {
          continue;
        }
        foreach ($entity['getIndices']() as $indexName => $indexInfo) {
          if (array_intersect_key($localizableFields, $indexInfo['fields'])) {
            $index = [
              'name' => $indexName,
              'field' => array_keys($indexInfo['fields'])
            ];
            if (!empty($indexInfo['unique'])) {
              $index['unique'] = 1;
            }
            $result[$tableName][$indexName] = $index;
          }
        }
      }
    }
    return $result;
  }

  /**
   * Get tables with translatable fields.
   *
   * @return array
   *   Array of names of tables with fields that can be translated.
   */
  public static function &tables() {
    $result = array_keys(self::columns());
    return $result;
  }

  /**
   * Get a list of widgets for editing translatable fields.
   *
   * @return array
   *   Array of the widgets for editing translatable fields.
   */
  public static function &widgets() {
    static $result = NULL;
    if (!$result) {
      $result = [];
      foreach (self::columns() as $tableName => $localizableFields) {
        $entityName = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($tableName);
        $entity = \Civi\Schema\EntityRepository::getEntity($entityName);
        if (empty($entity['getFields'])) {
          continue;
        }
        foreach ($entity['getFields']() as $fieldName => $field) {
          if (!empty($field['localizable'])) {
            $widget = [
              'type' => $field['input_type'],
            ];
            if (!empty($field['required'])) {
              $widget['required'] = 'true';
            }
            if (!empty($field['input_attrs']['label'])) {
              $widget['label'] = $field['input_attrs']['label'];
            }
            if (!empty($field['input_attrs']['rows'])) {
              $widget['rows'] = (string) $field['input_attrs']['rows'];
            }
            if (!empty($field['input_attrs']['cols'])) {
              $widget['cols'] = (string) $field['input_attrs']['cols'];
            }
            $result[$entity['table']][$fieldName] = $widget;
          }
        }
      }
    }

    return $result;
  }

}
