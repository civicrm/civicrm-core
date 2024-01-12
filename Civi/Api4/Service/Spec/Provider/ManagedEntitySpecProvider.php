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
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Provides calculated fields for APIs using the `ManagedEntity` trait
 * @service
 * @internal
 */
class ManagedEntitySpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $field = (new FieldSpec('has_base', $spec->getEntity(), 'Boolean'))
      ->setLabel(ts('Is Packaged'))
      ->setTitle(ts('Is Packaged'))
      ->setColumnName('id')
      ->setDescription(ts('Is provided by an extension'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'renderHasBase']);
    $spec->addFieldSpec($field);

    $field = (new FieldSpec('base_module', $spec->getEntity(), 'String'))
      ->setLabel(ts('Packaged Extension'))
      ->setTitle(ts('Packaged Extension'))
      ->setColumnName('id')
      ->setDescription(ts('Name of extension which provides this package'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->setOptionsCallback(['CRM_Core_BAO_Managed', 'getBaseModules'])
      ->setSqlRenderer([__CLASS__, 'renderBaseModule']);
    $spec->addFieldSpec($field);

    $field = (new FieldSpec('local_modified_date', $spec->getEntity(), 'Timestamp'))
      ->setLabel(ts('Locally Modified'))
      ->setTitle(ts('Locally modified'))
      ->setColumnName('id')
      ->setDescription(ts('When the managed entity was changed from its original settings'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'renderLocalModifiedDate']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    if ($action !== 'get') {
      return FALSE;
    }
    $className = CoreUtil::getApiClass($entity);
    return in_array('Civi\Api4\Generic\Traits\ManagedEntity', ReflectionUtils::getTraits($className), TRUE);
  }

  /**
   * Get sql snippet for has_base
   * @param array $field
   * return string
   */
  public static function renderHasBase(array $field): string {
    $id = $field['sql_name'];
    $entity = $field['entity'];
    return "IF($id IN (SELECT `entity_id` FROM `civicrm_managed` WHERE `entity_type` = '$entity'), '1', '0')";
  }

  /**
   * Get sql snippet for base_module
   * @param array $field
   * return string
   */
  public static function renderBaseModule(array $field): string {
    $id = $field['sql_name'];
    $entity = $field['entity'];
    return "(SELECT `civicrm_managed`.`module` FROM `civicrm_managed` WHERE `civicrm_managed`.`entity_id` = $id AND `civicrm_managed`.`entity_type` = '$entity' LIMIT 1)";
  }

  /**
   * Get sql snippet for local_modified_date
   * @param array $field
   * return string
   */
  public static function renderLocalModifiedDate(array $field): string {
    $id = $field['sql_name'];
    $entity = $field['entity'];
    return "(SELECT `civicrm_managed`.`entity_modified_date` FROM `civicrm_managed` WHERE `civicrm_managed`.`entity_id` = $id AND `civicrm_managed`.`entity_type` = '$entity' LIMIT 1)";
  }

}
