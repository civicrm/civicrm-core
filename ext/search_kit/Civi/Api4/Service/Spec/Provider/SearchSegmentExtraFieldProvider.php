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

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Core\Service\AutoService;

/**
 * @service
 * @internal
 */
class SearchSegmentExtraFieldProvider extends AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    foreach (self::getSets($spec->getEntity()) as $fullName => $set) {
      $field = new FieldSpec($fullName, $spec->getEntity());
      $field->setLabel($set['label']);
      $field->setColumnName('id');
      $field->setOptions(array_column($set['items'], 'label'));
      $field->setSuffixes(['label']);
      $field->setSqlRenderer([__CLASS__, 'renderSql']);
      $field->setInputType('Select');
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
        // Use api wrapper instead of calling SearchSegment class directly to avoid "class not found"
        // errors that may occur during installs and upgrades
        $searchSegments = civicrm_api4('SearchSegment', 'get', [
          'checkPermissions' => FALSE,
          'orderBy' => ['label' => 'ASC'],
        ]);
      }
      // Suppress SearchSegment class/table not found error e.g. during upgrade mode
      catch (\Exception $e) {
        return [];
      }
      foreach ($searchSegments as $set) {
        \Civi::$statics['all_search_segments'][$set['entity_name']]['segment_' . $set['name']] = $set;
        if ($set['entity_name'] === 'Contact') {
          foreach (\CRM_Contact_BAO_ContactType::basicTypes() as $contactType) {
            \Civi::$statics['all_search_segments'][$contactType]['segment_' . $set['name']] = $set;
          }
        }
      }
    }
    return \Civi::$statics['all_search_segments'][$entity] ?? [];
  }

  /**
   * Generates the sql case statement with a clause for each item.
   *
   * @param array $field
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @return string
   */
  public static function renderSql(array $field, Api4SelectQuery $query): string {
    $set = self::getSets($field['entity'])[$field['name']];

    // Field prefix to use if entity comes from a join
    $prefix = ($field['explicit_join'] ? $field['explicit_join'] . '.' : '') . ($field['implicit_join'] ? $field['implicit_join'] . '.' : '');
    $cases = [];
    foreach ($set['items'] as $index => $item) {
      $conditions = [];
      foreach ($item['when'] ?? [] as $clause) {
        // Add field prefix
        $clause[0] = $prefix . $clause[0];
        $conditions[] = $query->composeClause($clause, 'WHERE', 0);
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
