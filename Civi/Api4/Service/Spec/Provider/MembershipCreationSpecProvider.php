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

/**
 * @service
 * @internal
 */
class MembershipCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $spec->getFieldByName('status_id')->setRequired(FALSE);
    // This is a bit of dark-magic - the membership BAO code is particularly
    // nasty. It has a lot of logic in it that does not belong there in
    // terms of our current expectations. Removing it is difficult
    // so the plan is that new api v4 membership.create users will either
    // use the Order api flow when financial code should kick in. Otherwise
    // the crud flow will bypass all the financial processing in membership.create.
    // The use of the 'version' parameter to drive this is to be sure that
    // we know the bypass will not affect the v3 api to the extent
    // it cannot be reached by the v3 api at all (in time we can move some
    // of the code we are deprecating into the v3 api, to die of natural deprecation).
    $spec->addFieldSpec(new FieldSpec('version', 'Membership', 'Integer'));
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
  public function applies($entity, $action): bool {
    return $entity === 'Membership' && $action === 'create';
  }

}
