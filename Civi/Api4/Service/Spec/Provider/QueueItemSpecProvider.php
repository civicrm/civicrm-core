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

use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Core\Service\AutoService;

/**
 * @service
 * @internal
 */
class QueueItemSpecProvider extends AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec): void {
    $spec->getFieldByName('data')->setPermission([\CRM_Core_Permission::ALWAYS_DENY_PERMISSION]);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action): bool {
    return $entity === 'QueueItem' && in_array($action, ['create', 'update']);
  }

}
