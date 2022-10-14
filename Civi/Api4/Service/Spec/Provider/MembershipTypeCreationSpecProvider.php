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

/**
 * @service
 * @internal
 */
class MembershipTypeCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $spec->getFieldByName('duration_interval')->setDefaultValue(1);
  }

  /**
   * When does this apply.
   *
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    return $entity === 'MembershipType' && $action === 'create';
  }

}
