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
class DashboardCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    // Arguably this is a bad default in the schema
    $spec->getFieldByName('is_active')->setRequired(FALSE)->setDefaultValue(TRUE);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return in_array($entity, ['Dashboard', 'DashboardContact'], TRUE) && $action === 'create';
  }

}
