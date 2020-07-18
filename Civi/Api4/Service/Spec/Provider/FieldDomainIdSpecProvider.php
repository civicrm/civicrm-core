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

use Civi\Api4\Service\Spec\RequestSpec;

class FieldDomainIdSpecProvider implements Generic\SpecProviderInterface {

  /**
   * Generic create spec function to set sensible defaults for any entity with a "domain_id" field.
   */
  public function modifySpec(RequestSpec $spec) {
    $domainIdField = $spec->getFieldByName('domain_id');
    if ($domainIdField && $domainIdField->isRequired()) {
      $domainIdField->setRequired(FALSE)->setDefaultValue('current_domain');;
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $action === 'create';
  }

}
