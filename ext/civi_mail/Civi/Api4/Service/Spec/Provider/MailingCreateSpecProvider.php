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

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Core\Service\AutoService;

/**
 * @service
 * @internal
 */
class MailingCreateSpecProvider extends AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    // This is a bit of dark-magic - the mailing BAO code is particularly
    // nasty. It has historically scheduled mailings on create but does not for
    // api v4 as that is more consistent with the create expectation.
    $spec->addFieldSpec(new FieldSpec('version', 'Mailing', 'Integer'));
    $spec->getFieldByName('version')->setDefaultValue(4)->setRequired(TRUE);
  }

  /**
   * When does this apply.
   *
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies(string $entity, string $action): bool {
    return $entity === 'Mailing' && $action === 'create';
  }

}
