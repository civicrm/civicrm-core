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

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Event\Event;
use Civi\API\Event\PrepareEvent;
use Civi\API\Event\RespondEvent;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * This intercepts api.get actions for HierarchicalEntities and, if the `_depth` field is present in the select clause,
 * will populate the `_depth` field and sort results into hierarchical order.
 *
 * @service
 * @internal
 */
class HierarchicalEntitySubscriber extends AutoService implements EventSubscriberInterface {

  private static $_originalLimit;
  private static $_originalOffset;

  private static $_extraFields = ['_depth', '_descendents'];

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.prepare' => ['onApiPrepare', 100],
      'civi.api.respond' => ['onApiRespond', 100],
    ];
  }

  /**
   * Preprocess
   */
  public function onApiPrepare(PrepareEvent $event): void {
    if ($this->applies($event)) {
      /** @var \Civi\Api4\Generic\AbstractGetAction $apiRequest */
      $apiRequest = $event->getApiRequest();
      // Stash limit & offset for later
      self::$_originalLimit = $apiRequest->getLimit();
      self::$_originalOffset = $apiRequest->getOffset();
      // Remove limit & offset because we need to process every entity in the tree
      $apiRequest->setLimit(0);
      $apiRequest->setOffset(0);
      // Ensure id & parent are selected for use in postprocessing
      $entityName = $apiRequest->getEntityName();
      $parentField = $this->getParentField($entityName);
      $requiredFields = [$parentField['fk_column'] ?? CoreUtil::getIdFieldName($entityName), $parentField['name']];
      if (!empty($parentField['dfk_entities']) && !empty($parentField['input_attrs']['control_field'])) {
        $requiredFields[] = $parentField['input_attrs']['control_field'];
      }
      $apiRequest->setSelect(array_unique(array_merge($apiRequest->getSelect(), $requiredFields)));
    }
  }

  /**
   * Postprocess
   */
  public function onApiRespond(RespondEvent $event): void {
    if ($this->applies($event) && $event->getResponse()->count()) {
      $apiRequest = $event->getApiRequest();
      $entityName = $apiRequest->getEntityName();
      $result = $event->getResponse();
      $records = $result->getArrayCopy();
      $parentField = $this->getParentField($entityName);
      $parentName = $parentField['name'];
      $idName = $parentField['fk_column'] ?? CoreUtil::getIdFieldName($entityName);

      // If the parentField uses a DFK, check if the original entity is excluded via the where clause
      // If so, an extra query will be needed to fetch the children
      $usesDfk = !empty($parentField['dfk_entities']);
      $dfkControlName = $usesDfk ? $parentField['input_attrs']['control_field'] ?? NULL : NULL;
      $dfkValue = NULL;
      $needsExtraDfkQuery = FALSE;
      $whereClause = $apiRequest->getWhere();
      if ($dfkControlName) {
        $dfkOptions = array_column(\Civi::entity($entityName)->getOptions($dfkControlName), NULL, 'name');
        $dfkValue = $dfkOptions[$entityName]['id'];
        // Check to see if the where clause is already set to include self as the target entity
        foreach ($whereClause as $index => $clause) {
          if (is_array($clause) && !empty($clause[2]) && empty($clause[3]) && in_array($clause[1], ['=', 'IN'], TRUE) && $clause[0] === $dfkControlName || str_starts_with($clause[0], "$dfkControlName:")) {
            // Lookup pseudoconstant for dfk options
            [, $suffix] = array_pad(explode(':', $clause[0]), 2, 'id');
            $needsExtraDfkQuery = !in_array($dfkOptions[$entityName][$suffix], (array) $clause[2], TRUE);
            $whereClause[$index] = [$dfkControlName, '=', $dfkValue];
          }
          if (is_array($clause) && ($clause[0] === $parentName || str_starts_with($clause[0], "$parentName:"))) {
            unset($whereClause[$index]);
          }
        }
      }

      // Filter out children, maintaining sorted order
      $children = [];
      if (!$needsExtraDfkQuery) {
        $records = array_filter($records, function($record) use ($parentName, $dfkControlName, $dfkValue, &$children) {
          $isChild = !empty($record[$parentName]);
          if ($dfkValue) {
            $isChild = $record[$dfkControlName] == $dfkValue;
          }
          if ($isChild) {
            $children[] = $record;
          }
          return !$isChild;
        });
      }
      $records = array_column($records, NULL, $idName);

      if ($needsExtraDfkQuery) {
        $parentIds = array_keys($records);
        $select = array_diff($apiRequest->getSelect(), self::$_extraFields);
        $apiRequest->setSelect($select);
        while ($parentIds) {
          $apiRequest->setWhere(array_merge($whereClause, [[$parentName, 'IN', $parentIds]]));
          $newChildren = $apiRequest->execute();
          $parentIds = $newChildren->column($idName);
          $children = array_merge($children, (array) $newChildren);
        }
      }

      $childCount = count($children) + 1;
      // Maintaining other sort criteria, move children under their parents
      while ($children && $childCount > count($children)) {
        // Guard loop against getting "stuck" - if there's no progress after an iteration, abandon the orphaned children
        $childCount = count($children);
        foreach (array_reverse($children, TRUE) as $index => $child) {
          // If the child has more than one parent (Groups entity), just pick the 1st valid one
          foreach ((array) $child[$parentName] as $parentId) {
            if (isset($records[$parentId])) {
              $child['_descendents'] = 0;
              self::propagateDescendents($records, $parentId, $parentName);
              $child['_depth'] = ($records[$parentId]['_depth'] ?? 0) + 1;
              $records = self::array_insert_after($records, $parentId, [$child[$idName] => $child]);
              unset($children[$index]);
              break;
            }
          }
        }
      }

      // Apply original limit/offset
      if (self::$_originalOffset || self::$_originalLimit) {
        $records = array_slice($records, self::$_originalOffset ?: 0, self::$_originalLimit ?: NULL);
      }

      $result->exchangeArray(array_values($records));
      $result->rowCount = count($records);
    }
  }

  /**
   * Recursively propagates the count of descendents to the parents and parents-of-parents.
   *
   * @param array $records
   *   Reference to the collection of records where propagation is done
   * @param mixed $parentId
   *   Identifier of the parent record
   * @param string $parentName
   *   Name of the 'parent_id' field
   *
   * @return void
   */
  private static function propagateDescendents(array &$records, $parentId, $parentName) {
    $records[$parentId]['_descendents'] ??= 0;
    $records[$parentId]['_descendents'] += 1;
    // If the child has more than one parent (Groups entity), just pick the 1st valid one
    foreach ((array) $records[$parentId][$parentName] as $parentId) {
      if (isset($records[$parentId])) {
        self::propagateDescendents($records, $parentId, $parentName);
        return;
      }
    }
  }

  private function applies(Event $event): bool {
    $apiRequest = $event->getApiRequest();
    return $apiRequest['version'] == 4 &&
      is_a($apiRequest, 'Civi\Api4\Generic\AbstractGetAction') &&
      CoreUtil::isType($apiRequest->getEntityName(), 'HierarchicalEntity') &&
      array_intersect(self::$_extraFields, $apiRequest->getSelect());
  }

  private function getParentField(string $entityName): array {
    $parentField = CoreUtil::getInfoItem($entityName, 'parent_field');
    return civicrm_api4($entityName, 'getFields', [
      'checkPermissions' => FALSE,
      'where' => [['name', '=', $parentField]],
    ])->first();
  }

  private static function array_insert_after(array $records, string $key, array $newRecord) {
    $pos = array_search($key, array_keys($records)) + 1;

    return array_slice($records, 0, $pos, TRUE) +
      $newRecord +
      array_slice($records, $pos, NULL, TRUE);
  }

}
