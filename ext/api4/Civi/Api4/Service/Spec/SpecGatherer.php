<?php

namespace Civi\Api4\Service\Spec;

use Civi\Api4\CustomField;
use Civi\Api4\Service\Spec\Provider\SpecProviderInterface;
use Civi\Api4\Utils\CoreUtil;

class SpecGatherer {

  /**
   * @var SpecProviderInterface[]
   */
  protected $specProviders = [];

  /**
   * A cache of DAOs based on entity
   *
   * @var \CRM_Core_DAO[]
   */
  protected $DAONames;

  /**
   * Returns a RequestSpec with all the fields available. Uses spec providers
   * to add or modify field specifications.
   * For an example @see CustomFieldSpecProvider.
   *
   * @param string $entity
   * @param string $action
   * @param $includeCustom
   *
   * @return \Civi\Api4\Service\Spec\RequestSpec
   */
  public function getSpec($entity, $action, $includeCustom) {
    $specification = new RequestSpec($entity, $action);

    // Real entities
    if (strpos($entity, 'Custom_') !== 0) {
      $this->addDAOFields($entity, $action, $specification);
      if ($includeCustom && array_key_exists($entity, \CRM_Core_SelectValues::customGroupExtends())) {
        $this->addCustomFields($entity, $specification);
      }
    }
    // Custom pseudo-entities
    else {
      $this->getCustomGroupFields(substr($entity, 7), $specification);
    }

    foreach ($this->specProviders as $provider) {
      if ($provider->applies($entity, $action)) {
        $provider->modifySpec($specification);
      }
    }

    return $specification;
  }

  /**
   * @param SpecProviderInterface $provider
   */
  public function addSpecProvider(SpecProviderInterface $provider) {
    $this->specProviders[] = $provider;
  }

  /**
   * @param string $entity
   * @param RequestSpec $specification
   */
  private function addDAOFields($entity, $action, RequestSpec $specification) {
    $DAOFields = $this->getDAOFields($entity);

    foreach ($DAOFields as $DAOField) {
      if ($DAOField['name'] == 'id' && $action == 'create') {
        continue;
      }
      $field = SpecFormatter::arrayToField($DAOField, $entity);
      $specification->addFieldSpec($field);
    }
  }

  /**
   * @param string $entity
   * @param RequestSpec $specification
   */
  private function addCustomFields($entity, RequestSpec $specification) {
    $extends = ($entity == 'Contact') ? ['Contact', 'Individual', 'Organization', 'Household'] : [$entity];
    $customFields = CustomField::get()
      ->addWhere('custom_group.extends', 'IN', $extends)
      ->setSelect(['custom_group.name', 'custom_group_id', 'name', 'label', 'data_type', 'html_type', 'is_required', 'is_searchable', 'is_search_range', 'weight', 'is_active', 'is_view', 'option_group_id', 'default_value'])
      ->execute();

    foreach ($customFields as $fieldArray) {
      $field = SpecFormatter::arrayToField($fieldArray, $entity);
      $specification->addFieldSpec($field);
    }
  }

  /**
   * @param string $customGroup
   * @param RequestSpec $specification
   */
  private function getCustomGroupFields($customGroup, RequestSpec $specification) {
    $customFields = CustomField::get()
      ->addWhere('custom_group.name', '=', $customGroup)
      ->setSelect(['custom_group.name', 'custom_group_id', 'name', 'label', 'data_type', 'html_type', 'is_required', 'is_searchable', 'is_search_range', 'weight', 'is_active', 'is_view', 'option_group_id', 'default_value', 'custom_group.table_name', 'column_name'])
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
    $dao = CoreUtil::getDAOFromApiName($entityName);

    return $dao::fields();
  }

}
