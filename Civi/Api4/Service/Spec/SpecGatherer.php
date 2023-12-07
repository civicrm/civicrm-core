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
          if (array_key_exists($entityTableColumn, $values)) {
            $DAOField['FKClassName'] = \CRM_Core_DAO_AllCoreTables::getClassForTable($values[$entityTableColumn]);
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

    // Handle contact type pseudo-entities
    $contactTypes = \CRM_Contact_BAO_ContactType::basicTypes();
    // If contact type is given
    if ($entity === 'Contact' && !empty($values['contact_type'])) {
      $entity = $values['contact_type'];
    }

    $customInfo = \Civi\Api4\Utils\CoreUtil::getCustomGroupExtends($entity);
    if (!$customInfo) {
      return;
    }
    $extends = $customInfo['extends'];
    $grouping = $customInfo['grouping'];
    if ($entity === 'Contact' || in_array($entity, $contactTypes, TRUE)) {
      $grouping = 'contact_sub_type';
    }

    $query = CustomField::get(FALSE)
      ->setSelect(['custom_group_id.name', 'custom_group_id.title', '*'])
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('custom_group_id.is_active', '=', TRUE)
      ->addWhere('custom_group_id.is_multiple', '=', FALSE);

    // Enforce permissions
    if ($checkPermissions && !\CRM_Core_Permission::customGroupAdmin()) {
      $allowedGroups = \CRM_Core_Permission::customGroup();
      if (!$allowedGroups) {
        return;
      }
      $query->addWhere('custom_group_id', 'IN', $allowedGroups);
    }

    if (is_string($grouping) && array_key_exists($grouping, $values)) {
      if (empty($values[$grouping])) {
        $query->addWhere('custom_group_id.extends_entity_column_value', 'IS EMPTY');
      }
      else {
        $clause = [
          ['custom_group_id.extends_entity_column_value', 'IS EMPTY'],
        ];
        foreach ((array) $values[$grouping] as $value) {
          $clause[] = ['custom_group_id.extends_entity_column_value', 'CONTAINS', $value];
        }
        $query->addClause('OR', $clause);
      }
    }
    // Handle multiple groupings
    // (In core, only Participant custom fields have multiple groupings)
    elseif (is_array($grouping)) {
      $clauses = [];
      foreach ($grouping as $columnId => $group) {
        if (array_key_exists($group, $values)) {
          if (empty($values[$group])) {
            $clauses[] = [
              'AND',
              [
                ['custom_group_id.extends_entity_column_id', '=', $columnId],
                ['custom_group_id.extends_entity_column_value', 'IS EMPTY'],
              ],
            ];
          }
          else {
            $clause = [];
            foreach ((array) $values[$group] as $value) {
              $clause[] = ['custom_group_id.extends_entity_column_value', 'CONTAINS', $value];
            }
            $clauses[] = [
              'AND',
              [
                ['custom_group_id.extends_entity_column_id', '=', $columnId],
                ['OR', $clause],
              ],
            ];
          }
        }
      }
      if ($clauses) {
        $clauses[] = [
          'AND',
          [
            ['custom_group_id.extends_entity_column_id', 'IS EMPTY'],
            ['custom_group_id.extends_entity_column_value', 'IS EMPTY'],
          ],
        ];
        $query->addClause('OR', $clauses);
      }
    }
    $query->addWhere('custom_group_id.extends', 'IN', $extends);

    foreach ($query->execute() as $fieldArray) {
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
      ->addWhere('is_active', '=', TRUE)
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
