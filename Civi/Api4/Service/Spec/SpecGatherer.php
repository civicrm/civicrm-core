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


namespace Civi\Api4\Service\Spec;

use Civi\Api4\CustomField;
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Utils\CoreUtil;

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
    $specification = new RequestSpec($entity, $action);

    // Real entities
    if (strpos($entity, 'Custom_') !== 0) {
      $this->addDAOFields($entity, $action, $specification, $values);
      if ($includeCustom && array_key_exists($entity, \CRM_Core_SelectValues::customGroupExtends())) {
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
   * @param \Civi\Api4\Service\Spec\RequestSpec $specification
   * @param array $values
   */
  private function addDAOFields($entity, $action, RequestSpec $specification, $values = []) {
    $DAOFields = $this->getDAOFields($entity);

    foreach ($DAOFields as $DAOField) {
      if ($DAOField['name'] == 'id' && $action == 'create') {
        continue;
      }
      if (array_key_exists('contactType', $DAOField) && !empty($values['contact_type']) && $DAOField['contactType'] != $values['contact_type']) {
        continue;
      }
      if ($action !== 'create' || isset($DAOField['default'])) {
        $DAOField['required'] = FALSE;
      }
      if ($DAOField['name'] == 'is_active' && empty($DAOField['default'])) {
        $DAOField['default'] = '1';
      }
      $field = SpecFormatter::arrayToField($DAOField, $entity);
      $specification->addFieldSpec($field);
    }
  }

  /**
   * Get custom fields that extend this entity
   *
   * @see \CRM_Core_SelectValues::customGroupExtends
   *
   * @param string $entity
   * @param \Civi\Api4\Service\Spec\RequestSpec $specification
   * @param array $values
   * @throws \API_Exception
   */
  private function addCustomFields($entity, RequestSpec $specification, $values = []) {
    // Custom_group.extends pretty much maps 1-1 with entity names, except for a couple oddballs (Contact, Participant).
    $extends = [$entity];
    if ($entity === 'Contact') {
      $contactType = !empty($values['contact_type']) ? [$values['contact_type']] : \CRM_Contact_BAO_ContactType::basicTypes();
      $extends = array_merge(['Contact'], $contactType);
    }
    if ($entity === 'Participant') {
      $extends = ['Participant', 'ParticipantRole', 'ParticipantEventName', 'ParticipantEventType'];
    }
    $customFields = CustomField::get(FALSE)
      ->addWhere('custom_group.extends', 'IN', $extends)
      ->addWhere('custom_group.is_multiple', '=', '0')
      ->setSelect(['custom_group.name', '*'])
      ->execute();

    foreach ($customFields as $fieldArray) {
      $field = SpecFormatter::arrayToField($fieldArray, $entity);
      $specification->addFieldSpec($field);
    }
  }

  /**
   * @param string $customGroup
   * @param \Civi\Api4\Service\Spec\RequestSpec $specification
   */
  private function getCustomGroupFields($customGroup, RequestSpec $specification) {
    $customFields = CustomField::get(FALSE)
      ->addWhere('custom_group.name', '=', $customGroup)
      ->setSelect(['custom_group.name', 'custom_group.table_name', '*'])
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
   */
  private function getDAOFields($entityName) {
    $bao = CoreUtil::getBAOFromApiName($entityName);

    return $bao::getSupportedFields();
  }

}
