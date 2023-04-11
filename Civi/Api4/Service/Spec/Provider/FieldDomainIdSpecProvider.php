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
class FieldDomainIdSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * Generic create spec function to set sensible defaults for any entity with a "domain_id" field.
   */
  public function modifySpec(RequestSpec $spec) {
    $domainIdField = $spec->getFieldByName('domain_id');
    // TODO: The WordReplacement entity should have domain_id required so this OR condition can be removed
    if ($domainIdField && ($domainIdField->isRequired() || $domainIdField->getEntity() === 'WordReplacement')) {
      $domainIdField->setRequired(FALSE)->setDefaultValue('current_domain');
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'create';
  }

}
