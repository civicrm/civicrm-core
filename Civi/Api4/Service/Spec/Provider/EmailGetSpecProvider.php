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
 * This applies to all entities with an email field.
 *
 * In the contact of "get", email validation doesn't make sense so change it to text.
 *
 * @service
 * @internal
 */
class EmailGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    foreach ($spec->getFields() as $field) {
      if ($field->getInputType() === 'Email') {
        $field->setInputType('Text');
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'get';
  }

}
