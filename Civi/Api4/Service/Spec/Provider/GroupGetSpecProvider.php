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

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class GroupGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   *
   * @throws \CRM_Core_Exception
   */
  public function modifySpec(RequestSpec $spec): void {
    // Calculated field counts contacts in group
    $field = new FieldSpec('contact_count', 'Group', 'Integer');
    $field->setLabel(ts('Contact Count'))
      ->setDescription(ts('Number of contacts in group'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countContacts']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    return $entity === 'Group' && $action === 'get';
  }

  /**
   * Generate SQL for counting contacts
   * in static and smart groups
   *
   * @return string
   */
  public static function countContacts(array $field): string {
    return "COALESCE(
      NULLIF((SELECT COUNT(contact_id) FROM `civicrm_group_contact_cache` WHERE `group_id` = {$field['sql_name']}), 0),
      (SELECT COUNT(contact_id) FROM `civicrm_group_contact` WHERE `group_id` = {$field['sql_name']} AND `status` = 'Added')
    )";
  }

}
