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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class LineItemCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * This runs for both create and get actions
   *
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('label')->setRequired(FALSE)->setDefaultValue('line item');
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'LineItem' && in_array($action, ['create']);
  }

}
