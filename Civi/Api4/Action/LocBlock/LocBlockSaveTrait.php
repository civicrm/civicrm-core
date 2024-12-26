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

namespace Civi\Api4\Action\LocBlock;

use Civi\Api4\LocBlock;
use Civi\Api4\Utils\CoreUtil;

/**
 * Code shared by LocBlock create/update/save actions
 */
trait LocBlockSaveTrait {

  /**
   * @param array $items
   * @return array
   */
  protected function write(array $items) {
    foreach ($items as &$item) {
      self::saveLocations($item);
    }
    $saved = parent::write($items);
    return $saved;
  }

  /**
   * @param array $params
   */
  protected function saveLocations(array &$params) {
    if (!empty($params['id'])) {
      $locBlock = LocBlock::get(FALSE)
        ->addWhere('id', '=', $params['id'])
        ->execute()->first();
    }
    foreach (['Address', 'Email', 'Phone', 'IM'] as $joinEntity) {
      foreach (['', '_2'] as $suffix) {
        $joinField = strtolower($joinEntity) . $suffix . '_id';
        $item = \CRM_Utils_Array::filterByPrefix($params, "$joinField.");
        $entityId = $params[$joinField] ?? $locBlock[$joinField] ?? NULL;
        if ($item) {
          $labelField = CoreUtil::getInfoItem($joinEntity, 'label_field');
          // If NULL was given for the required field (e.g. `email`) then delete the record IF it's not in use
          if (!empty($params['id']) && $entityId && $labelField && array_key_exists($labelField, $item) && ($item[$labelField] === NULL || $item[$labelField] === '')) {
            $referenceCount = CoreUtil::getRefCountTotal($joinEntity, $entityId);
            if ($referenceCount <= 1) {
              civicrm_api4($joinEntity, 'delete', [
                'checkPermissions' => FALSE,
                'where' => [
                  ['id', '=', $entityId],
                ],
              ]);
            }
          }
          // Otherwise save if the required field (e.g. `email`) has a value (or no fields are required)
          elseif (!array_key_exists($labelField, $item) || (isset($item[$labelField]) &&  $item[$labelField] !== '')) {
            $item['contact_id'] = '';
            if ($entityId) {
              $item['id'] = $entityId;
            }
            $saved = civicrm_api4($joinEntity, 'save', [
              'checkPermissions' => FALSE,
              'records' => [$item],
            ])->first();
            $params[$joinField] = $saved['id'] ?? NULL;
          }
        }
      }
    }
  }

  protected function resolveFKValues(array &$record): void {
    // Override parent function with noop to prevent spurious matching
  }

}
