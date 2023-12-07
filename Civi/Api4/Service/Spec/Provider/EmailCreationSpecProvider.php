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
class EmailCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('email')->setRequired(TRUE);
    $spec->getFieldByName('on_hold')->setRequired(FALSE);
    $spec->getFieldByName('is_bulkmail')->setRequired(FALSE);
    $spec->getFieldByName('is_primary')->setRequired(FALSE);

    $defaultLocationType = \CRM_Core_BAO_LocationType::getDefault()->id ?? NULL;
    $spec->getFieldByName('location_type_id')->setDefaultValue($defaultLocationType ? (int) $defaultLocationType : NULL);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Email' && $action === 'create';
  }

}
