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


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\SearchSegment;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class SearchSegmentExtraFieldProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    foreach (self::getSets($spec->getEntity()) as $fullName => $set) {
      $field = new FieldSpec($fullName, $spec->getEntity());
      $field->setLabel($set['label']);
      $field->setColumnName($set['field_name']);
      $field->setOptions(array_column($set['items'], 'label'));
      $field->setSuffixes(['label']);
      $field->setSqlRenderer([__CLASS__, 'renderSql']);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity !== 'SearchSegment' && $action === 'get';
  }

  /**
   * @param string $entity
   * @return array[]
   */
  private static function getSets($entity) {
    if (!isset(\Civi::$statics['all_search_segments'])) {
      \Civi::$statics['all_search_segments'] = [];
      try {
        $searchSegments = SearchSegment::get(FALSE)->addOrderBy('label')->execute();
      }
      // Suppress SearchSegment BAO/table not found error e.g. during upgrade mode
      catch (\Exception $e) {
        return [];
      }
      foreach ($searchSegments as $set) {
        \Civi::$statics['all_search_segments'][$set['entity_name']]['segment_' . $set['name']] = $set;
      }
    }
    return \Civi::$statics['all_search_segments'][$entity] ?? [];
  }

  /**
   * Generates the sql case statement with a clause for each item.
   *
   * @param array $field
   * @return string
   */
  public static function renderSql(array $field): string {
    $set = self::getSets($field['entity'])[$field['name']];
    $sqlName = $field['sql_name'];
    $cases = [];
    foreach ($set['items'] as $index => $item) {
      $conditions = [];
      if (isset($item['min'])) {
        $conditions[] = $sqlName . ' >= ' . (float) $item['min'];
      }
      if (isset($item['max'])) {
        $conditions[] = $sqlName . ' < ' . (float) $item['max'];
      }
      // If no conditions, this is the ELSE clause
      if (!$conditions) {
        $elseClause = 'ELSE ' . (int) $index;
      }
      else {
        $cases[] = 'WHEN ' . implode(' AND ', $conditions) . ' THEN ' . (int) $index;
      }
    }
    // Place ELSE clause at the end
    if (isset($elseClause)) {
      $cases[] = $elseClause;
    }
    return 'CASE ' . implode("\n  ", $cases) . "\nEND";
  }

}
