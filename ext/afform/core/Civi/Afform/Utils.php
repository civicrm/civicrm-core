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

namespace Civi\Afform;

use Civi\Api4\Utils\FormattingUtil;
use CRM_Afform_ExtensionUtil as E;

/**
 *
 * @package Civi\Afform
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class Utils {

  /**
   * Sorts entities according to references to each other
   *
   * Returns a list of entity names in order of when they should be processed,
   * so that an entity being referenced is saved before the entity referencing it.
   *
   * @param $formEntities
   * @param $entityValues
   * @return string[]
   */
  public static function getEntityWeights($formEntities, $entityValues) {
    $sorter = new \MJS\TopSort\Implementations\FixedArraySort();

    foreach ($formEntities as $entityName => $entity) {
      $references = [];
      foreach ($entityValues[$entityName] as $record) {
        foreach ($record['fields'] as $fieldName => $fieldValue) {
          foreach ((array) $fieldValue as $value) {
            if (!is_bool($value) && array_key_exists($value, $formEntities) && $value !== $entityName) {
              $references[$value] = $value;
            }
          }
        }
      }
      $sorter->add($entityName, $references);
    }
    // Return the list of entities ordered by weight
    return $sorter->sort();
  }

  /**
   * Subset of APIv4 operators that are appropriate for use on Afforms
   *
   * This list may be further reduced by fields which declare a limited number of
   * operators in their metadata.
   *
   * @return array
   */
  public static function getSearchOperators() {
    return [
      '=' => '=',
      '!=' => '≠',
      '>' => '>',
      '<' => '<',
      '>=' => '≥',
      '<=' => '≤',
      'CONTAINS' => E::ts('Contains'),
      'NOT CONTAINS' => E::ts("Doesn't Contain"),
      'IN' => E::ts('Is One Of'),
      'NOT IN' => E::ts('Not One Of'),
      'LIKE' => E::ts('Is Like'),
      'NOT LIKE' => E::ts('Not Like'),
      'REGEXP' => E::ts('Matches Pattern'),
      'NOT REGEXP' => E::ts("Doesn't Match Pattern"),
      'REGEXP BINARY' => E::ts('Matches Pattern (case-sensitive)'),
      'NOT REGEXP BINARY' => E::ts("Doesn't Match Pattern (case-sensitive)"),
    ];
  }

  public static function shouldReconcileManaged(array $updatedAfform, array $originalAfform = []): bool {
    $isChanged = function($field) use ($updatedAfform, $originalAfform) {
      return ($updatedAfform[$field] ?? NULL) !== ($originalAfform[$field] ?? NULL);
    };

    return $isChanged('placement') ||
      $isChanged('navigation') ||
      (!empty($updatedAfform['placement']) && $isChanged('title')) ||
      (!empty($updatedAfform['navigation']) && ($isChanged('title') || $isChanged('permission') || $isChanged('icon') || $isChanged('server_route')));
  }

  public static function shouldClearMenuCache(array $updatedAfform, array $originalAfform = []): bool {
    $isChanged = function($field) use ($updatedAfform, $originalAfform) {
      return ($updatedAfform[$field] ?? NULL) !== ($originalAfform[$field] ?? NULL);
    };

    return $isChanged('server_route') ||
      (!empty($updatedAfform['server_route']) && $isChanged('title'));
  }

  public static function formatViewValue(string $fieldName, array $fieldInfo, array $values, ?string $entityName = NULL, ?string $formName = NULL): string {
    $value = $values[$fieldName] ?? NULL;
    if (isset($value) && $value !== '') {
      $dataType = $fieldInfo['data_type'] ?? NULL;
      if (!empty($fieldInfo['options'])) {
        $value = FormattingUtil::replacePseudoconstant(array_column($fieldInfo['options'], 'label', 'id'), $value);
      }
      elseif (!empty($fieldInfo['fk_entity']) && $formName) {
        $autocomplete = civicrm_api4($fieldInfo['fk_entity'], 'autocomplete', [
          'checkPermissions' => FALSE,
          'formName' => "afform:$formName",
          'fieldName' => "$entityName:$fieldName",
          'ids' => (array) $value,
        ]);
        $value = $autocomplete->column('label');
      }
      elseif ($dataType === 'Boolean') {
        $value = $value ? ts('Yes') : ts('No');
      }
      elseif ($dataType === 'Date' || $dataType === 'Timestamp') {
        $value = \CRM_Utils_Date::customFormat($value);
      }
      if (is_array($value)) {
        $value = implode(', ', $value);
      }
    }
    return $value ?? '';
  }

}
