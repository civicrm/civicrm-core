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

use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Api4\Service\Spec\FieldSpec;

/**
 * @service
 * @internal
 */
class OptionGroupGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('edit_link', 'OptionGroup', 'String');
    $field->setLabel(ts('Edit link'))
      ->setTitle(ts('Edit link'))
      ->setDescription(ts('Link for editing this OptionGroup'))
      ->setColumnName('name')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'getEditLink']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('add_link', 'OptionGroup', 'String');
    $field->setLabel(ts('Add link'))
      ->setTitle(ts('Add link'))
      ->setDescription(ts('Link for editing this OptionGroup'))
      ->setColumnName('name')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'getAddLink']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'OptionGroup' && $action === 'get';
  }

  /**
   * Generate SQL for age field
   * @param array $field
   * @return string
   *
   *   If there is explicit handling defined in the menus, use that
   *   otherwise, use the new SK edit
   */
  public static function getEditLink(array $field): string {
    return "COALESCE(
      (SELECT path FROM `civicrm_menu` WHERE path = CONCAT('civicrm/admin/options/', {$field['sql_name']})), 
      CONCAT('civicrm/admin/options/edit#/?gid=', `a`.`id`)
    )";
  }

  /**
   * Generate SQL for age field
   * @param array $field
   * @return string
   *
   *   If there is explicit handling defined in the menus, use that
   *   Otherwise go to the old form using the 'add' alias to avoid being directed to SK
   */
  public static function getAddLink(array $field): string {
    return "CONCAT(
      COALESCE(
        (SELECT path FROM `civicrm_menu` WHERE path = CONCAT('civicrm/admin/options/', {$field['sql_name']})), 
        'civicrm/admin/options/add'
      ),
      '?action=add&gid=', `a`.`id`
    )";
  }

}
