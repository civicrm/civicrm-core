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
        $this->addCustomFields($entity, $specification);
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
   * @param array $values
   */
  private function addDAOFields($entity, $action, RequestSpec $spec, array $values) {
    $DAOFields = $this->getDAOFields($entity);

    foreach ($DAOFields as $DAOField) {
      if ($DAOField['name'] == 'id' && $action == 'create') {
        $DAOField['required'] = FALSE;
      }
      if (array_key_exists('contactType', $DAOField) && $spec->getValue('contact_type') && $DAOField['contactType'] != $spec->getValue('contact_type')) {
        continue;
      }
      if (!empty($DAOField['component']) && !\CRM_Core_Component::isEnabled($DAOField['component'])) {
        continue;
      }
      if ($DAOField['name'] == 'is_active' && empty($DAOField['default'])) {
        $DAOField['default'] = '1';
      }
      $this->setDynamicFk($DAOField, $entity, $values);
      $field = SpecFormatter::arrayToField($DAOField, $entity);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * Cleverly enables getFields to report dynamic FKs if a value is supplied for the entity type.
   *
   * E.g. many tables have a DFK with a pair of `entity_table` and `entity_id` columns.
   * If you supply a value for `entity_table`, then getFields will output the correct `fk_entity` for the `entity_id` field.
   *
   * @param array $DAOField
   * @param string $entityName
   * @param array $values
   */
  private function setDynamicFk(array &$DAOField, string $entityName, array $values): void {
    if (empty($field['FKClassName']) && $values) {
      $bao = CoreUtil::getBAOFromApiName($entityName);
      // Check all dynamic FKs for entity for a match with this field and a supplied value
      foreach ($bao::getReferenceColumns() ?? [] as $reference) {
        if ($reference instanceof \CRM_Core_Reference_Dynamic
          && $reference->getReferenceKey() === $DAOField['name']
          && array_key_exists($reference->getTypeColumn(), $values)
        ) {
          $DAOField['FKClassName'] = \CRM_Core_DAO_AllCoreTables::getClassForTable($values[$reference->getTypeColumn()]);
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
   * @throws \CRM_Core_Exception
   * @see \CRM_Core_SelectValues::customGroupExtends
   */
  private function addCustomFields($entity, RequestSpec $spec) {
    $customInfo = \Civi\Api4\Utils\CoreUtil::getCustomGroupExtends($entity);
    if (!$customInfo) {
      return;
    }
    $values = $spec->getValues();
    $extends = $customInfo['extends'];
    $grouping = $customInfo['grouping'];

    $query = CustomField::get(FALSE)
      ->setSelect(['custom_group_id.name', 'custom_group_id.title', '*'])
      ->addWhere('is_active', '=', TRUE)
      ->addWhere('custom_group_id.is_active', '=', TRUE)
      ->addWhere('custom_group_id.is_multiple', '=', FALSE);

    // Contact custom groups are extra complicated because contact_type can be a value for extends
    if ($entity === 'Contact') {
      if (array_key_exists('contact_type', $values)) {
        $extends = ['Contact'];
        if ($values['contact_type']) {
          $extends[] = $values['contact_type'];
        }
      }
      // Now grouping can be treated normally
      $grouping = 'contact_sub_type';
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
