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

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class DomainGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('is_active', $spec->getEntity(), 'Boolean');
    $field->setLabel(ts('Active Domain'))
      ->setTitle(ts('Active'))
      ->setColumnName('id')
      ->setDescription(ts('Is this the current active domain'))
      ->setType('Extra')
      ->setSqlRenderer([__CLASS__, 'renderIsActiveDomain']);
    $spec->addFieldSpec($field);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Domain' && $action === 'get';
  }

  /**
   * @param array $field
   * return string
   */
  public static function renderIsActiveDomain(array $field): string {
    $currentDomain = \CRM_Core_Config::domainID();
    return "IF({$field['sql_name']} = '$currentDomain', '1', '0')";
  }

}
