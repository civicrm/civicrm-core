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

namespace Civi\Api4\Service\Spec;

use Civi\Api4\CustomField;
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Utils\CoreUtil;

/**
 * Class SpecGatherer
 * @package Civi\Api4\Service\Spec
 */
class SpecGatherer {

  /**
   * @var \Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface[]
   */
  protected $specProviders = [];

  /**
   * Returns a RequestSpec with all the fields available. Uses spec providers
   * to add or modify field specifications.
   * @see \Civi\Api4\Service\Spec\Provider\CustomFieldCreationSpecProvider
   *
   * @param string $entity
   * @param string $action
   * @param bool $includeCustom
   * @param array $values
   *
   * @return \Civi\Api4\Service\Spec\RequestSpec
   */
  public function getSpec($entity, $action, $includeCustom, $values = []) {
    $specification = new RequestSpec($entity, $action, $values);

    // Real entities
    if (strpos($entity, 'Custom_') !== 0) {
      $this->addDAOFields($entity, $action, $specification, $values);
      if ($includeCustom) {
        $this->addCustomFields($entity, $specification, $values);
      }
    }
    // Custom pseudo-entities
    else {
      $this->getCustomGroupFields(substr($entity, 7), $specification);
    }

    // Default value only makes sense for create actions
    if ($action != 'create') {
      foreach ($specification->getFields() as $field) {
        $field->setDefaultValue(NULL);
      }
    }

    foreach ($this->specProviders as $provider) {
      if ($provider->applies($entity, $action)) {
        $provider->modifySpec($specification);
      }
    }

    return $specification;
  }

  /**
   * @param \Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface $provider
   */
  public function addSpecProvider(SpecProviderInterface $provider) {
    $this->specProviders[] = $provider;
  }

  /**
   * @param string $entity
   * @param string $action
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  private function addDAOFields($entity, $action, RequestSpec $spec) {
    $DAOFields = $this->getDAOFields($entity);

    foreach ($DAOFields as $DAOField) {
      if ($DAOField['name'] == 'id' && $action == 'create') {
        continue;
      }
      if (array_key_exists('contactType', $DAOField) && $spec->getValue('contact_type') && $DAOField['contactType'] != $spec->getValue('contact_type')) {
        continue;
      }
      if (!empty($DAOField['component']) &&
        !in_array($DAOField['component'], \Civi::settings()->get('enable_components'), TRUE)
      ) {
        continue;
      }
      if ($action !== 'create' || isset($DAOField['default'])) {
        $DAOField['required'] = FALSE;
      }
      if ($DAOField['name'] == 'is_active' && empty($DAOField['default'])) {
        $DAOField['default'] = '1';
      }
      $field = SpecFormatter::arrayToField($DAOField, $entity);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * Get custom fields that extend this entity
   *
   * @param string $entity
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   * @throws \API_Exception
   * @see \CRM_Core_SelectValues::customGroupExtends
   */
  private function addCustomFields($entity, RequestSpec $spec) {
    $customInfo = \Civi\Api4\Utils\CoreUtil::getCustomGroupExtends($entity);
    if (!$customInfo) {
      return;
    }
    // If a contact_type was passed in, exclude custom groups for other contact types
    if ($entity === 'Contact' && $spec->getValue('contact_type')) {
      $extends = ['Contact', $spec->getValue('contact_type')];
    }
    else {
      $extends = $customInfo['extends'];
    }
    $customFields = CustomField::get(FALSE)
      ->addWhere('custom_group_id.extends', 'IN', $extends)
      ->addWhere('custom_group_id.is_multiple', '=', '0')
      ->setSelect(['custom_group_id.name', 'custom_group_id.title', '*'])
      ->execute();

    foreach ($customFields as $fieldArray) {
      $field = SpecFormatter::arrayToField($fieldArray, $entity);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * @param string $customGroup
   * @param \Civi\Api4\Service\Spec\RequestSpec $specification
   */
  private function getCustomGroupFields($customGroup, RequestSpec $specification) {
    $customFields = CustomField::get(FALSE)
      ->addWhere('custom_group_id.name', '=', $customGroup)
      ->setSelect(['custom_group_id.name', 'custom_group_id.table_name', 'custom_group_id.title', '*'])
      ->execute();

    foreach ($customFields as $fieldArray) {
      $field = SpecFormatter::arrayToField($fieldArray, 'Custom_' . $customGroup);
      $specification->addFieldSpec($field);
    }
  }

  /**
   * @param string $entityName
   *
   * @return array
   * @throws \API_Exception
   */
  private function getDAOFields(string $entityName): array {
    $bao = CoreUtil::getBAOFromApiName($entityName);
    if (!$bao) {
      throw new \API_Exception('Entity not loaded' . $entityName);
    }
    return $bao::getSupportedFields();
  }

}
