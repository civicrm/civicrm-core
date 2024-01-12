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

use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Service\AutoService;

/**
 * Class SpecGatherer
 * @package Civi\Api4\Service\Spec
 * @service spec_gatherer
 */
class SpecGatherer extends AutoService {

  /**
   * @var \Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface[]
   */
  protected $specProviders = [];

  /**
   * Returns a RequestSpec with all the fields available. Uses spec providers
   * to add or modify field specifications.
   *
   * @param string $entity
   * @param string $action
   * @param bool $includeCustom
   * @param array $values
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Service\Spec\RequestSpec
   * @throws \CRM_Core_Exception
   * @see \Civi\Api4\Service\Spec\Provider\CustomFieldCreationSpecProvider
   */
  public function getSpec(string $entity, string $action, bool $includeCustom, array $values = [], bool $checkPermissions = FALSE): RequestSpec {
    $specification = new RequestSpec($entity, $action, $values);

    // Real entities
    if (!str_starts_with($entity, 'Custom_')) {
      $this->addDAOFields($entity, $action, $specification, $values);
      if ($includeCustom) {
        $this->addCustomFields($entity, $specification, $checkPermissions);
      }
    }
    // Custom pseudo-entities
    else {
      $this->getCustomGroupFields(substr($entity, 7), $specification);
    }

    // Default value only makes sense for create actions
    if ($action !== 'create') {
      foreach ($specification->getFields() as $field) {
        $field->setDefaultValue(NULL);
      }
    }

    foreach ($this->specProviders as $provider) {
      if ($provider->applies($entity, $action, $specification->getValues())) {
        $provider->modifySpec($specification);
      }
    }

    return $specification;
  }

  /**
   * @param \Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface $provider
   */
  public function addSpecProvider(SpecProviderInterface $provider): void {
    $this->specProviders[] = $provider;
  }

  /**
   * @param string $entityName
   * @param string $action
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   * @param array $values
   */
  private function addDAOFields(string $entityName, string $action, RequestSpec $spec, array $values) {
    $DAOFields = $this->getDAOFields($entityName);

    foreach ($DAOFields as $DAOField) {
      if (isset($DAOField['contactType']) && $spec->getValue('contact_type') && $DAOField['contactType'] !== $spec->getValue('contact_type')) {
        continue;
      }
      if (!empty($DAOField['component']) && !\CRM_Core_Component::isEnabled($DAOField['component'])) {
        continue;
      }
      $this->setDynamicFk($DAOField, $values);
      $field = SpecFormatter::arrayToField($DAOField, $entityName);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * Adds metadata about dynamic foreign key fields.
   *
   * E.g. some tables have a DFK with a pair of columns named `entity_table` and `entity_id`.
   * This will gather the list of 'dfk_entities' to add as metadata to the e.g. `entity_id` column.
   *
   * Additionally, if $values contains a value for e.g. `entity_table`,
   * then getFields will also output the corresponding `fk_entity` for the `entity_id` field.
   *
   * @param array $DAOField
   * @param array $values
   */
  private function setDynamicFk(array &$DAOField, array $values): void {
    if (empty($DAOField['FKClassName']) && !empty($DAOField['bao']) && $DAOField['type'] == \CRM_Utils_Type::T_INT) {
      // Check if this field is a key for a dynamic FK
      foreach ($DAOField['bao']::getReferenceColumns() ?? [] as $reference) {
        if ($reference instanceof \CRM_Core_Reference_Dynamic && $reference->getReferenceKey() === $DAOField['name']) {
          $entityTableColumn = $reference->getTypeColumn();
          $DAOField['DFKEntities'] = $reference->getTargetEntities();
          $DAOField['html']['controlField'] = $entityTableColumn;
          // If we have a value for entity_table then this field can pretend to be a single FK too.
          if (array_key_exists($entityTableColumn, $values) && $DAOField['DFKEntities']) {
            $DAOField['FKClassName'] = \CRM_Core_DAO_AllCoreTables::getFullName($DAOField['DFKEntities'][$values[$entityTableColumn]]);
          }
          break;
        }
      }
    }
  }

  /**
   * Get custom fields that extend this entity
   *
   * @param string $entity
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   * @param bool $checkPermissions
   * @throws \CRM_Core_Exception
   * @see \CRM_Core_SelectValues::customGroupExtends
   */
  private function addCustomFields(string $entity, RequestSpec $spec, bool $checkPermissions) {
    $values = $spec->getValues();

    // If contact type is given
    if ($entity === 'Contact' && !empty($values['contact_type'])) {
      $entity = $values['contact_type'];
    }

    $customInfo = \Civi\Api4\Utils\CoreUtil::getCustomGroupExtends($entity);
    if (!$customInfo) {
      return;
    }
    $grouping = $customInfo['grouping'];
    if (CoreUtil::isContact($entity)) {
      $grouping = 'contact_sub_type';
    }

    if ($checkPermissions) {
      $permissionType = in_array($spec->getAction(), ['create', 'update', 'save', 'delete', 'replace']) ?
        \CRM_Core_Permission::EDIT :
        \CRM_Core_Permission::VIEW;
      $customGroups = \CRM_Core_BAO_CustomGroup::getPermitted($permissionType);
    }
    else {
      $customGroups = \CRM_Core_BAO_CustomGroup::getActive();
    }

    foreach ($customGroups as $customGroup) {
      if ($this->customGroupBelongsTo($customGroup, $customInfo['extends'], $values, $grouping)) {
        foreach ($customGroup['fields'] as $fieldArray) {
          $field = SpecFormatter::arrayToField($fieldArray, $entity, $customGroup);
          $spec->addFieldSpec($field);
        }
      }
    }
  }

  /**
   * Check if custom group belongs to $extends entity and meets criteria from $values
   */
  private function customGroupBelongsTo(array $customGroup, array $extends, array $values, $grouping): bool {
    if ($customGroup['is_multiple'] || !in_array($customGroup['extends'], $extends)) {
      return FALSE;
    }
    // No values or grouping = no filtering needed
    if (empty($values) ||
      (empty($customGroup['extends_entity_column_value']) && empty($customGroup['extends_entity_column_id']))
    ) {
      return TRUE;
    }
    if (is_string($grouping) && array_key_exists($grouping, $values)) {
      foreach ((array) $values[$grouping] as $value) {
        if (in_array($value, $customGroup['extends_entity_column_value'])) {
          return TRUE;
        }
      }
      return empty($customGroup['extends_entity_column_value']);
    }
    // Handle multiple groupings
    // (in core, only Participant custom fields have multiple groupings)
    elseif (is_array($grouping)) {
      foreach ($grouping as $columnId => $group) {
        if (array_key_exists($group, $values)) {
          if (empty($values[$group])) {
            if (
              !$customGroup['extends_entity_column_value'] &&
              $customGroup['extends_entity_column_id'] == $columnId
            ) {
              return TRUE;
            }
          }
          elseif (
            array_intersect((array) $values[$group], (array) $customGroup['extends_entity_column_value']) &&
            $customGroup['extends_entity_column_id'] == $columnId
          ) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * @param string $customGroupName
   * @param \Civi\Api4\Service\Spec\RequestSpec $specification
   */
  private function getCustomGroupFields($customGroupName, RequestSpec $specification): void {
    foreach (\CRM_Core_BAO_CustomGroup::getAll() as $customGroup) {
      if ($customGroup['name'] === $customGroupName) {
        foreach ($customGroup['fields'] as $fieldArray) {
          if ($fieldArray['is_active']) {
            $field = SpecFormatter::arrayToField($fieldArray, 'Custom_' . $customGroupName, $customGroup);
            $specification->addFieldSpec($field);
          }
        }
        return;
      }
    }
  }

  /**
   * @param string $entityName
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function getDAOFields(string $entityName): array {
    $bao = CoreUtil::getBAOFromApiName($entityName);
    if (!$bao) {
      throw new \CRM_Core_Exception('Entity not loaded: ' . $entityName);
    }
    return $bao::getSupportedFields();
  }

}
