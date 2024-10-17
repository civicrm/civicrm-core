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
use Civi\Api4\Utils\CoreUtil;

/**
 * @service
 * @internal
 */
class SortableEntitySpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * Generic create spec function applies to all SortableEntity types.
   * Disables required 'weight' field because that's auto-managed.
   *
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec): void {
    $weightFieldName = CoreUtil::getInfoItem($spec->getEntity(), 'order_by');
    $weightField = $spec->getFieldByName($weightFieldName);
    $weightField->setRequired(FALSE);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'create' && CoreUtil::isType($entity, 'SortableEntity');
  }

}
