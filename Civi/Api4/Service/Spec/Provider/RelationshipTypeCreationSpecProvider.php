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
class RelationshipTypeCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('name_a_b')->setRequired(TRUE);
    $spec->getFieldByName('name_b_a')->setRequired(TRUE);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'RelationshipType' && $action === 'create';
  }

}
