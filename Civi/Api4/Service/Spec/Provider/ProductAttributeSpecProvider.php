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
class ProductAttributeSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  public function modifySpec(RequestSpec $spec): void {
    $field = $spec->getFieldByName('options');
    $field->addOutputFormatter([__CLASS__, 'formatOptions']);
  }

  public static function formatOptions(&$value) {
    if (is_string($value)) {
      $value = \CRM_Contribute_BAO_Premium::parseProductOptions($value);
    }
  }

  public function applies($entity, $action): bool {
    return $entity === 'Product' && $action === 'get';
  }

}
