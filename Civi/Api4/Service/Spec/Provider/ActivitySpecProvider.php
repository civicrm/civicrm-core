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

/**
 * @service
 * @internal
 */
class ActivitySpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();

    if (\CRM_Core_Component::isEnabled('CiviCase')) {
      $field = new FieldSpec('case_id', 'Activity', 'Integer');
      $field->setTitle(ts('Case ID'));
      $field->setLabel($action === 'get' ? ts('Filed on Case') : ts('File on Case'));
      $field->setDescription(ts('CiviCase this activity belongs to.'));
      $field->setFkEntity('Case');
      $field->setInputType('EntityRef');
      $field->setColumnName('id');
      $field->setSqlRenderer(['\Civi\Api4\Service\Schema\Joiner', 'getExtraJoinSql']);
      $spec->addFieldSpec($field);
    }

    if (in_array($action, ['create', 'update'], TRUE)) {
      // The database default '1' is problematic as the option list is user-configurable,
      // so activity type '1' doesn't necessarily exist. Best make the field required.
      $spec->getFieldByName('activity_type_id')
        ->setDefaultValue(NULL)
        ->setRequired($action === 'create');
    }

    if (in_array($action, ['get', 'create', 'update'], TRUE)) {
      $field = new FieldSpec('source_contact_id', 'Activity', 'Integer');
      $field->setTitle(ts('Source Contact'));
      $field->setLabel(ts('Added by'));
      $field->setColumnName('id');
      $field->setDescription(ts('Contact who created this activity.'));
      $field->setRequired($action === 'create');
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $field->setSqlRenderer([__CLASS__, 'renderSqlForActivityContactIds']);
      $spec->addFieldSpec($field);

      $field = new FieldSpec('target_contact_id', 'Activity', 'Array');
      $field->setTitle(ts('Target Contacts'));
      $field->setLabel(ts('With Contacts'));
      $field->setColumnName('id');
      $field->setDescription(ts('Contacts involved in this activity.'));
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $field->setInputAttrs(['multiple' => TRUE]);
      $field->setSerialize(\CRM_Core_DAO::SERIALIZE_COMMA);
      $field->setSqlRenderer([__CLASS__, 'renderSqlForActivityContactIds']);
      $spec->addFieldSpec($field);

      $field = new FieldSpec('assignee_contact_id', 'Activity', 'Array');
      $field->setTitle(ts('Assignee Contacts'));
      $field->setLabel(ts('Assigned to'));
      $field->setColumnName('id');
      $field->setDescription(ts('Contacts assigned to this activity.'));
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $field->setInputAttrs(['multiple' => TRUE]);
      $field->setSerialize(\CRM_Core_DAO::SERIALIZE_COMMA);
      $field->setSqlRenderer([__CLASS__, 'renderSqlForActivityContactIds']);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Activity';
  }

  public static function renderSqlForActivityContactIds(array $field, Api4SelectQuery $query): string {
    $contactLinkTypes = [
      'source_contact_id' => 'Activity Source',
      'target_contact_id' => 'Activity Targets',
      'assignee_contact_id' => 'Activity Assignees',
    ];
    $recordTypeId = \CRM_Core_PseudoConstant::getKey(
        'CRM_Activity_BAO_ActivityContact',
        'record_type_id',
        $contactLinkTypes[$field['name']]);
    return "(SELECT GROUP_CONCAT(`civicrm_activity_contact`.`contact_id`)
              FROM `civicrm_activity_contact`
              WHERE `civicrm_activity_contact`.`activity_id` = {$field['sql_name']}
              AND record_type_id = $recordTypeId)";
  }

}
