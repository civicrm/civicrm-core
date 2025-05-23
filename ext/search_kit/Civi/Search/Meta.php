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

namespace Civi\Search;

use CRM_Search_ExtensionUtil as E;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\Utils\CoreUtil;

/**
 * Search Metadata utilities
 * @package Civi\Search
 */
class Meta {

  /**
   * Get calculated fields used by a saved search
   *
   * @param string $apiEntity
   * @param array $apiParams
   * @return array
   */
  public static function getCalcFields($apiEntity, $apiParams): array {
    $api = \Civi\API\Request::create($apiEntity, 'get', $apiParams + ['checkPermissions' => FALSE]);
    if (!is_a($api, '\Civi\Api4\Generic\DAOGetAction')) {
      return [];
    }
    $calcFields = [];
    $selectQuery = new \Civi\Api4\Query\Api4SelectQuery($api);
    $joinMap = $joinCount = [];
    foreach ($apiParams['join'] ?? [] as $join) {
      [$entityName, $alias] = explode(' AS ', $join[0]);
      $num = '';
      if (!empty($joinCount[$entityName])) {
        $num = ' ' . (++$joinCount[$entityName]);
      }
      else {
        $joinCount[$entityName] = 1;
      }
      $label = CoreUtil::getInfoItem($entityName, 'title');
      $joinMap[$alias] = $label . $num;
    }

    $dataTypeToInputType = [
      'Integer' => 'Number',
      'Date' => 'Date',
      'Timestamp' => 'Date',
      'Boolean' => 'CheckBox',
    ];

    foreach ($apiParams['select'] ?? [] as $select) {
      if (str_contains($select, ' AS ')) {
        $expr = SqlExpression::convert($select, TRUE);
        $label = $expr::getTitle();
        foreach ($expr->getFields() as $num => $fieldName) {
          $field = $selectQuery->getField($fieldName);
          $joinName = explode('.', $fieldName)[0];
          $label .= ($num ? ', ' : ': ') . (isset($joinMap[$joinName]) ? $joinMap[$joinName] . ' ' : '') . $field['title'];
        }
        if ($expr::getDataType()) {
          $dataType = $expr::getDataType();
          $inputType = $dataTypeToInputType[$dataType] ?? 'Text';
        }
        else {
          $dataType = $field['data_type'] ?? 'String';
          $inputType = $field['input_type'] ?? $dataTypeToInputType[$dataType] ?? 'Text';
        }
        $options = FALSE;
        if ($expr->getType() === 'SqlFunction' && $expr::getOptions()) {
          $inputType = 'Select';
          $options = CoreUtil::formatOptionList($expr::getOptions(), ['id', 'label']);
        }

        $calcFields[] = [
          'name' => $expr->getAlias(),
          'label' => $label,
          'input_type' => $inputType,
          'data_type' => $dataType,
          'options' => $options,
        ];
      }
    }
    return $calcFields;
  }

  /**
   * Compute the SQL name of a column (for a "DB Entity").
   *
   * @param string $key
   *   Logical name of a field in a search-display. Identifies the ORIGIN of the data.
   *   Ex: 'email_primary.email'
   * @param string|null $sqlName
   *   If available, the custom-name requested by the site-builder.
   *   Ex: 'the_preferred_email'
   * @return array
   *   Tuple: [0 => string $name, 1 => string $suffix]
   * @throws \CRM_Core_Exception
   */
  public static function createSqlName(string $key, ?string $sqlName = NULL): array {
    // WARNING: This formula lives in both Civi\Search\Meta and crmSearchAdmin.module.js. Keep synchronized!

    // Strip the pseuoconstant suffix
    [$name, $suffix] = array_pad(explode(':', $key), 2, NULL);
    if (!empty($sqlName)) {
      if (!preg_match(';^[A-Za-z0-9_]+$;', $sqlName) || strlen($sqlName) > 58) {
        throw new \CRM_Core_Exception("Malformed column name");
      }
      $name = $sqlName;
    }
    // Sanitize the name and limit to 58 characters.
    // 64 characters is the max for some versions of SQL, minus the length of "index_" = 58.
    if (strlen($name) <= 58) {
      $name = \CRM_Utils_String::munge($name, '_', NULL);
    }
    // Append a hash of the full name to trimmed names to keep them unique but predictable
    else {
      $name = \CRM_Utils_String::munge($name, '_', 42) . substr(md5($name), 0, 16);
    }
    return [$name, $suffix];
  }

  /**
   * @param array $column
   * @param array{fields: array, expr: SqlExpression, dataType: string} $expr
   * @return array
   */
  public static function formatFieldSpec(array $column, array $expr): array {
    [$name, $suffix] = self::createSqlName($column['key'], $column['name'] ?? NULL);

    $spec = [
      'name' => $name,
      'data_type' => $expr['dataType'],
      'suffixes' => $suffix ? ['id', $suffix] : NULL,
      'options' => FALSE,
    ];
    $field = \CRM_Utils_Array::first($expr['fields']);
    $spec['original_field_name'] = $field['name'] ?? NULL;
    $spec['original_field_entity'] = $field['entity'] ?? NULL;
    if ($expr['expr']->getType() === 'SqlField') {
      // An entity id counts as a FK
      if (!$field['fk_entity'] && $field['name'] === CoreUtil::getIdFieldName($field['entity'])) {
        $spec['entity_reference'] = [
          'entity' => $field['entity'],
        ];
        $spec['input_type'] = 'EntityRef';
      }
      else {
        $originalEntity = CoreUtil::isContact($field['entity']) ? 'Contact' : $field['entity'];
        $originalField = \Civi::entity($originalEntity)->getField($field['name']);
        $spec['input_type'] = $originalField['input_type'] ?? NULL;
        $spec['serialize'] = $originalField['serialize'] ?? NULL;
        $spec['entity_reference'] = $originalField['entity_reference'] ?? NULL;
      }
      if ($suffix) {
        // Options will be looked up by SKEntitySpecProvider::getOptionsForSKEntityField
        $spec['options'] = TRUE;
      }
    }
    elseif ($expr['expr']->getType() === 'SqlFunction') {
      // For functions that have options, e.g. SqlFunctionDAYOFWEEK
      if ($suffix) {
        $spec['options'] = CoreUtil::formatOptionList($expr['expr']::getOptions(), $spec['suffixes']);
        $spec['input_type'] = 'Select';
      }
      // For field options that pass through the function, e.g. SqlFunctionGROUP_CONCAT
      elseif (!empty($field['suffixes']) && $spec['data_type'] === $field['data_type']) {
        $spec['input_type'] = 'Select';
        $spec['options'] = TRUE;
        $spec['suffixes'] = $field['suffixes'];
      }
      else {
        $spec['input_type'] = self::getInputTypeFromDataType($spec['data_type']);
      }
      if ($expr['expr']->getSerialize()) {
        $spec['serialize'] = $expr['expr']->getSerialize();
      }
      elseif ($spec['data_type'] === $field['data_type']) {
        $spec['serialize'] = $field['serialize'] ?? NULL;
      }
    }
    return $spec;
  }

  private static function getInputTypeFromDataType(string $dataType): ?string {
    $dataTypeToInputType = [
      'Array' => 'Text',
      'Boolean' => 'Radio',
      'Date' => 'Date',
      'Float' => 'Number',
      'Integer' => 'Number',
      'Money' => 'Number',
      'String' => 'Text',
      'Text' => 'TextArea',
      'Timestamp' => 'Date',
    ];
    return $dataTypeToInputType[$dataType] ?? NULL;
  }

}
