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

use Civi\Core\Attribute\CiviEventListener;
use Civi\API\Event\Event;
use Civi\API\Event\ExceptionEvent;
use Civi\API\Event\PrepareEvent;
use Civi\API\Event\RespondEvent;
use Civi\Api4\Generic\AbstractGetAction;
use Civi\Api4\Generic\CachedDAOGetAction;
use Civi\Api4\Generic\DAOGetAction;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\FormattingUtil;

/**
 * Intercept api.get actions for HierarchicalEntities
 *
 * If the `_depth` or `_descendents` field is present in the select clause,
 * this populates them and sorts results into hierarchical order.
 */
class HierarchicalEntitySubscriber {

  private static array $requestState = [];

  private static array $parentFieldCache = [];

  private static array $_extraFields = ['_depth', '_descendents'];

  /**
   * Preprocess
   */
  #[CiviEventListener(event: 'civi.api.prepare', priority: 100)]
  public function onApiPrepare(PrepareEvent $event): void {
    if (!$this->applies($event)) {
      return;
    }

    /** @var \Civi\Api4\Generic\AbstractGetAction $apiRequest */
    $apiRequest = $event->getApiRequest();
    $originalLimit = $apiRequest->getLimit();
    $originalOffset = $apiRequest->getOffset();
    $originalSelect = $apiRequest->getSelect();

    $entityName = $apiRequest->getEntityName();
    $parentField = $this->getParentField($entityName);
    $idName = $parentField['fk_column'] ?? CoreUtil::getIdFieldName($entityName);

    $requiredFields = [$idName, $parentField['name']];
    if (!empty($parentField['dfk_entities']) && !empty($parentField['input_attrs']['control_field'])) {
      $requiredFields[] = $parentField['input_attrs']['control_field'];
    }

    $needsHydration = FALSE;
    $baseRequest = NULL;

    // Two-phase query optimization:
    // If the query is paginated ($originalLimit > 0) and requests expensive fields beyond
    // what is needed to construct and sort the hierarchy, defer fetching those fields
    // until after the page has been sliced in onApiRespond.
    if ($this->shouldUseTwoPhaseQuery($apiRequest)) {
      $minimalSelect = array_unique(array_merge(
        $requiredFields,
        array_intersect($originalSelect, self::$_extraFields)
      ));
      $orderByFields = array_keys($apiRequest->getOrderBy());
      if ($orderByFields) {
        foreach ($originalSelect as $selectExpr) {
          if (preg_match('/\s+AS\s+(`?\w+`?)$/i', $selectExpr, $matches) && in_array(trim($matches[1], '`'), $orderByFields, TRUE)) {
            $minimalSelect[] = $selectExpr;
          }
        }
      }
      if (array_diff($originalSelect, $minimalSelect)) {
        $needsHydration = TRUE;
        $baseRequest = clone $apiRequest;
        $apiRequest->setSelect($minimalSelect);
      }
    }

    if (!$needsHydration) {
      // Ensure id & parent are selected for use in postprocessing
      $apiRequest->setSelect(array_unique(array_merge($originalSelect, $requiredFields)));
    }

    // Remove limit & offset because we need to process every entity in the tree
    $apiRequest->setLimit(0);
    $apiRequest->setOffset(0);

    self::$requestState[$event->getRequestId()] = [
      'originalLimit' => $originalLimit,
      'originalOffset' => $originalOffset,
      'originalSelect' => $originalSelect,
      'needsHydration' => $needsHydration,
      'baseRequest' => $baseRequest,
      'idName' => $idName,
    ];
  }

  /**
   * Postprocess
   */
  #[CiviEventListener(event: 'civi.api.respond', priority: 100)]
  public function onApiRespond(RespondEvent $event): void {
    $requestId = $event->getRequestId();
    $state = self::$requestState[$requestId] ?? NULL;
    unset(self::$requestState[$requestId]);

    $result = $event->getResponse();
    if (!$state || !$result->count()) {
      return;
    }

    /** @var \Civi\Api4\Generic\AbstractGetAction $apiRequest */
    $apiRequest = $event->getApiRequest();
    $originalLimit = $state['originalLimit'];
    $originalOffset = $state['originalOffset'];
    $originalSelect = $state['originalSelect'];
    $needsHydration = $state['needsHydration'];
    $baseRequest = $state['baseRequest'];
    $idName = $state['idName'];

    $entityName = $apiRequest->getEntityName();
    $records = $result->getArrayCopy();
    $parentField = $this->getParentField($entityName);
    $parentName = $parentField['name'];

    // If the parentField uses a DFK, check if the original entity is excluded via the where clause
    // If so, an extra query will be needed to fetch the children
    $dfkControlName = !empty($parentField['dfk_entities']) ? ($parentField['input_attrs']['control_field'] ?? NULL) : NULL;
    $dfkValue = NULL;
    $needsExtraDfkQuery = FALSE;
    $whereClause = $apiRequest->getWhere();
    if ($dfkControlName) {
      $dfkOptions = array_column(\Civi::entity($entityName)->getOptions($dfkControlName), NULL, 'name');
      $dfkValue = $dfkOptions[$entityName]['id'];
      foreach ($whereClause as $index => $clause) {
        if (is_array($clause) && !empty($clause[2]) && empty($clause[3]) && in_array($clause[1], ['=', 'IN'], TRUE) && ($clause[0] === $dfkControlName || str_starts_with($clause[0], "$dfkControlName:"))) {
          $suffix = FormattingUtil::getSuffix($clause[0]) ?? 'id';
          $needsExtraDfkQuery = !in_array($dfkOptions[$entityName][$suffix], (array) $clause[2], TRUE);
          $whereClause[$index] = [$dfkControlName, '=', $dfkValue];
        }
        if (is_array($clause) && ($clause[0] === $parentName || str_starts_with($clause[0], "$parentName:"))) {
          unset($whereClause[$index]);
        }
      }
    }

    $maxRows = $originalLimit > 0 ? ($originalOffset ?: 0) + $originalLimit : PHP_INT_MAX;
    $topLevel = [];
    $childrenByParent = [];

    if (!$needsExtraDfkQuery) {
      $allRecordIds = array_flip(array_column($records, $idName));
      foreach ($records as $record) {
        $chosenParentId = NULL;
        if (!$dfkValue || $record[$dfkControlName] == $dfkValue) {
          // If the child has more than one parent (Groups entity), pick the 1st valid one
          foreach ((array) ($record[$parentName] ?? NULL) as $parentId) {
            if (isset($allRecordIds[$parentId])) {
              $chosenParentId = $parentId;
              break;
            }
          }
        }
        if ($chosenParentId !== NULL) {
          $childrenByParent[$chosenParentId][] = $record;
        }
        else {
          $topLevel[$record[$idName]] = $record;
        }
      }
      if ($originalLimit > 0) {
        $topLevel = array_slice($topLevel, 0, $maxRows, TRUE);
      }
    }
    else {
      $topLevel = array_column($records, NULL, $idName);
      if ($originalLimit > 0) {
        $topLevel = array_slice($topLevel, 0, $maxRows, TRUE);
      }
      $parentIds = array_keys($topLevel);
      $childQuery = clone $apiRequest;
      $childQuery->setSelect(array_diff($apiRequest->getSelect(), self::$_extraFields));
      while ($parentIds) {
        $childQuery->setWhere(array_merge($whereClause, [[$parentName, 'IN', $parentIds]]));
        $newChildren = $childQuery->execute();
        $parentIds = [];
        foreach ($newChildren as $child) {
          $childrenByParent[$child[$parentName]][] = $child;
          $parentIds[] = $child[$idName];
        }
      }
    }

    $needsDescendents = in_array('_descendents', $originalSelect, TRUE);
    $flattened = [];
    $visited = [];
    foreach ($topLevel as $node) {
      if (count($flattened) >= $maxRows) {
        break;
      }
      self::flattenTreeNode($node, 0, $idName, $maxRows, $needsDescendents, $childrenByParent, $flattened, $visited);
    }

    if ($originalOffset > 0) {
      $flattened = array_slice($flattened, $originalOffset, $originalLimit ?: NULL, TRUE);
    }

    // Hydrate deferred fields for the sliced records on this page
    if ($needsHydration && !empty($flattened) && $baseRequest) {
      $hydrationSelect = array_diff($originalSelect, self::$_extraFields);
      if (!in_array($idName, $hydrationSelect, TRUE)) {
        $hydrationSelect[] = $idName;
      }

      $hydrationQuery = clone $baseRequest;
      $hydrationQuery->setSelect($hydrationSelect);
      $hydrationQuery->setWhere([[$idName, 'IN', array_keys($flattened)]]);
      $hydrationQuery->setLimit(0);
      $hydrationQuery->setOffset(0);
      $hydrationQuery->setOrderBy([]);

      $hydrated = $hydrationQuery->execute()->indexBy($idName);
      $extraKeys = array_flip(self::$_extraFields);
      foreach ($flattened as $id => &$record) {
        if (isset($hydrated[$id])) {
          $record = array_merge((array) $hydrated[$id], array_intersect_key($record, $extraKeys));
        }
      }
    }

    $result->exchangeArray(array_values($flattened));
    $result->rowCount = count($flattened);
  }

  #[CiviEventListener(event: 'civi.api.exception', priority: 100)]
  public function onApiException(ExceptionEvent $event): void {
    if ($this->applies($event)) {
      unset(self::$requestState[$event->getRequestId()]);
    }
  }

  /**
   * Appends a node and its descendants in pre-order DFS, computing `_depth` and `_descendents`.
   */
  private static function flattenTreeNode(
    array $node,
    int $depth,
    string $idName,
    int $maxRows,
    bool $needsDescendents,
    array &$childrenByParent,
    array &$flattened,
    array &$visited
  ): int {
    $id = $node[$idName];
    $visited[$id] = TRUE;

    $emit = count($flattened) < $maxRows;
    $descendents = 0;
    if ($emit) {
      $node['_depth'] = $depth;
      $node['_descendents'] = 0;
      $flattened[$id] = $node;
    }
    elseif (!$needsDescendents) {
      return 0;
    }

    if (!empty($childrenByParent[$id])) {
      foreach ($childrenByParent[$id] as $child) {
        if (isset($visited[$child[$idName]])) {
          continue;
        }
        $descendents += 1 + self::flattenTreeNode($child, $depth + 1, $idName, $maxRows, $needsDescendents, $childrenByParent, $flattened, $visited);
        if (!$needsDescendents && count($flattened) >= $maxRows) {
          break;
        }
      }
      if ($emit) {
        $flattened[$id]['_descendents'] = $descendents;
      }
    }

    return $descendents;
  }

  /**
   * Determine whether a paginated get action should defer fetching expensive SELECT fields
   * until after the hierarchy has been constructed and sliced.
   */
  private function shouldUseTwoPhaseQuery(AbstractGetAction $apiRequest): bool {
    if (!$apiRequest instanceof DAOGetAction || $apiRequest->getLimit() <= 0 || $apiRequest->getHaving() || $apiRequest->getGroupBy()) {
      return FALSE;
    }
    if ($apiRequest instanceof CachedDAOGetAction && $apiRequest->willUseCache()) {
      return FALSE;
    }
    $basicFields = array_keys(\Civi::entity($apiRequest->getEntityName())->getFields());
    $cheapFields = array_merge($basicFields, self::$_extraFields, ['row_count']);
    return (bool) array_diff($apiRequest->getSelect(), $cheapFields);
  }

  private function applies(Event $event): bool {
    $apiRequest = $event->getApiRequest();
    return $apiRequest['version'] == 4 &&
      is_a($apiRequest, 'Civi\Api4\Generic\AbstractGetAction') &&
      CoreUtil::isType($apiRequest->getEntityName(), 'HierarchicalEntity') &&
      array_intersect(self::$_extraFields, $apiRequest->getSelect());
  }

  private function getParentField(string $entityName): array {
    return self::$parentFieldCache[$entityName] ??= civicrm_api4($entityName, 'getFields', [
      'checkPermissions' => FALSE,
      'where' => [['name', '=', CoreUtil::getInfoItem($entityName, 'parent_field')]],
    ])->first();
  }

}
