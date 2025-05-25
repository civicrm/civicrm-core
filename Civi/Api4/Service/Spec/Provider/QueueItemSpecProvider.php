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
 * Set permissions on Queue Items to allow some fields (release_date etc) but NOT the data to be
 * edited for queue items - this allows search kits to be used to manage queue items but not to inject
 * executable code.
 *
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
    // Currently we do for all actions - it might be OK to select - but it fails anyway as
    // it is an serialized object & there are no non-crud actions. create & update are the minimum
    // required here.
    return $entity === 'QueueItem';
  }

}
