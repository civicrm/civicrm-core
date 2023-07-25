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
class UserJobSpecProvider extends AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec): void {
    $spec->getFieldByName('job_type')
      ->setSuffixes(['name', 'label', 'url']);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action): bool {
    return $entity === 'UserJob';
  }

}
