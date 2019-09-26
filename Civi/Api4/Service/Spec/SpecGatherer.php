<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
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
   */
  private function addDAOFields($entity, $action, RequestSpec $specification) {
    $DAOFields = $this->getDAOFields($entity);

    foreach ($DAOFields as $DAOField) {
      if ($DAOField['name'] == 'id' && $action == 'create') {
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
   * @param string $entity
   * @param \Civi\Api4\Service\Spec\RequestSpec $specification
   */
  private function addCustomFields($entity, RequestSpec $specification) {
    $extends = ($entity == 'Contact') ? ['Contact', 'Individual', 'Organization', 'Household'] : [$entity];
    $customFields = CustomField::get()
      ->setCheckPermissions(FALSE)
      ->addWhere('custom_group.extends', 'IN', $extends)
      ->setSelect(['custom_group.name', 'custom_group_id', 'name', 'label', 'data_type', 'html_type', 'is_searchable', 'is_search_range', 'weight', 'is_active', 'is_view', 'option_group_id', 'default_value', 'date_format', 'time_format', 'start_date_years', 'end_date_years'])
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
    $customFields = CustomField::get()
      ->addWhere('custom_group.name', '=', $customGroup)
      ->setSelect(['custom_group.name', 'custom_group_id', 'name', 'label', 'data_type', 'html_type', 'is_searchable', 'is_search_range', 'weight', 'is_active', 'is_view', 'option_group_id', 'default_value', 'custom_group.table_name', 'column_name', 'date_format', 'time_format', 'start_date_years', 'end_date_years'])
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

    return $bao::fields();
  }

}
