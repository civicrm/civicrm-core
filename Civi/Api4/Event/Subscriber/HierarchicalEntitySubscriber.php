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
      $parentField = $this->getParentField($apiRequest->getEntityName());
      $requiredFields = [$parentField['fk_column'], $parentField['name']];
      $apiRequest->setSelect(array_unique(array_merge($apiRequest->getSelect(), $requiredFields)));
    }
  }

  /**
   * Postprocess
   */
  public function onApiRespond(RespondEvent $event): void {
    if ($this->applies($event)) {
      $apiRequest = $event->getApiRequest();
      $result = $event->getResponse();
      $records = $result->getArrayCopy();
      $parentField = $this->getParentField($apiRequest->getEntityName());
      $parentName = $parentField['name'];
      $idName = $parentField['fk_column'];

      // Filter out children, maintaining sorted order
      $children = [];
      $records = array_filter($records, function($record) use ($parentName, &$children) {
        if (!empty($record[$parentName])) {
          $children[] = $record;
        }
        return empty($record[$parentName]);
      });
      $records = array_column($records, NULL, $idName);

      $childCount = count($children) + 1;
      // Maintaining other sort criteria, move children under their parents
      while ($children && $childCount > count($children)) {
        // This guards against a loop getting "stuck" - if there's no progress after an iteration, abandon the orphaned children
        $childCount = count($children);
        foreach (array_reverse($children, TRUE) as $index => $child) {
          // If the child has more than one parent (Groups entity), just pick the 1st valid one
          foreach ((array) $child[$parentName] as $parentId) {
            if (isset($records[$parentId])) {
              $child['_depth'] = $records[$parentId]['_depth'] + 1;
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
    }
  }

  private function applies(Event $event): bool {
    $apiRequest = $event->getApiRequest();
    return $apiRequest['version'] == 4 &&
      is_a($apiRequest, 'Civi\Api4\Generic\AbstractGetAction') &&
      CoreUtil::isType($apiRequest->getEntityName(), 'HierarchicalEntity') &&
      in_array('_depth', $apiRequest->getSelect(), TRUE);
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
