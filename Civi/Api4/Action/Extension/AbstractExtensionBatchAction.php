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

namespace Civi\Api4\Action\Extension;

use Civi\Api4\Generic\BasicBatchAction;
use Civi\Api4\Generic\Result;

/**
 * Base class for Extension batch actions (Enable, Disable, Uninstall).
 *
 * @method $this setReload(array|bool $reload) Optionally reload $ENTITIES after saving with an extra SELECT.
 * @method array|bool getReload()
 */
abstract class AbstractExtensionBatchAction extends BasicBatchAction {

  /**
   * Optionally reload $ENTITIES after processing with an extra SELECT.
   *
   * By default, this action returns partial records containing only `key` and `status`.
   * If more is needed, set `reload` to `TRUE` or an array of fields to SELECT.
   *
   * @var array|bool
   */
  protected $reload;

  /**
   * Perform the extension manager operation for the matched extension keys.
   *
   * @param \CRM_Extension_Manager $manager
   * @param string[] $keys
   */
  abstract protected function processExtensions(\CRM_Extension_Manager $manager, array $keys): void;

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @param array $items
   */
  protected function processBatch(Result $result, array $items): void {
    if (empty($items)) {
      return;
    }

    $keys = array_column($items, 'key');
    $manager = \CRM_Extension_System::singleton()->getManager();
    $this->processExtensions($manager, $keys);

    if (!empty($this->reload)) {
      $result->exchangeArray($this->reloadResults($items, $this->reload));
      return;
    }

    $statuses = $manager->getStatuses();
    foreach ($keys as $key) {
      $result[] = [
        'key' => $key,
        'status' => $statuses[$key] ?? \CRM_Extension_Manager::STATUS_UNKNOWN,
      ];
    }
  }

}
