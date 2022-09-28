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
    $calcFields = [];
    $api = \Civi\API\Request::create($apiEntity, 'get', $apiParams);
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
      if (strstr($select, ' AS ')) {
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

        $calcFields[] = [
          'name' => $expr->getAlias(),
          'label' => $label,
          'input_type' => $inputType,
          'data_type' => $dataType,
        ];
      }
    }
    return $calcFields;
  }

}
