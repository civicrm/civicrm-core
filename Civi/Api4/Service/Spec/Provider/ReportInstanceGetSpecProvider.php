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

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Utils\CoreUtil;

/**
 * @service
 * @internal
 */
class ReportInstanceGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    // Groups field
    $field = new FieldSpec('grouping', $spec->getEntity(), 'Array');
    $field->setLabel(ts('Grouping'))
      ->setTitle(ts('Grouping'))
      ->setColumnName('id')
      ->setDescription(ts('Component grouping'))
      ->setType('Extra')
      ->setInputType('Select')
      ->setSqlRender([__CLASS__, 'getComponentSql'])
      ->setSuffixes(['name', 'label'])
      ->setOptionsCallback([__CLASS__, 'getComponentList']);
    $spec->addFieldSpec($field);

  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    // Applies to 'Contact' plus pseudo-entities 'Individual', 'Organization', 'Household'
    return CoreUtil::isContact($entity) && $action === 'get';
  }

  /**
   * @param array $field
   * @param string $fieldAlias
   * @param string $operator
   * @param mixed $value
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @param int $depth
   * return string
   */
  
   public static function getComponentSql(array $field, Api4SelectQuery $query): string {

    $optiongroupid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'report_template', 'id', 'name');

     $sql = "
     SELECT 
     CASE
         WHEN comp.name IS NOT NULL THEN SUBSTRING(comp.name, 5)
         WHEN v.grouping IS NOT NULL THEN v.grouping
         ELSE 'Contact'
     END as compName
     FROM civicrm_option_value v
     WHERE v.option_group_id = $optiongroupid
     AND v.is_active = 1
     AND civicrm_component.component_id = comp.id
     ORDER BY v.weight ASC, inst.title ASC";
 
    }
  }

  /**
   * Callback function to build option lists components pseudo-field.
   *
   * @param \Civi\Api4\Service\Spec\FieldSpec $spec
   * @param array $values
   * @param bool|array $returnFormat
   * @param bool $checkPermissions
   * @return array
   */
  public static function getComponentList($spec, $values, $returnFormat, $checkPermissions) {
    $components = $checkPermissions ? \CRM_Core_PseudoConstant::group() : \CRM_Core_PseudoConstant::allGroup(NULL, FALSE);
    $options = \CRM_Utils_Array::makeNonAssociative($components, 'id', 'label');
    if ($options && is_array($returnFormat) && in_array('name', $returnFormat)) {
      $groupIndex = array_flip(array_keys($components));
      $dao = \CRM_Core_DAO::executeQuery('SELECT id, name FROM civicrm_group WHERE id IN (%1)', [
        1 => [implode(',', array_keys($components)), 'CommaSeparatedIntegers'],
      ]);
      while ($dao->fetch()) {
        $options[$groupIndex[$dao->id]]['name'] = $dao->name;
      }
    }
    return $options;
  }

}
