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
class LineItemCreateSpecProvider extends AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    // Not required if qty + unit_price are set
    $spec->getFieldByName('line_total')->setRequired(FALSE);
    // If a contribution is deleted the lineItem will still exist. But we should always have a contribution
    //   when we create a lineItem
    // @fixme: But apparently CiviCRM core disagrees :-(
    // $spec->getFieldByName('contribution_id')->setRequired(TRUE);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'LineItem' && $action === 'create';
  }

}
