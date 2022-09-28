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

      $field = new FieldSpec('source_contact_id', 'Activity', 'Integer');
      $field->setTitle(ts('Source Contact'));
      $field->setLabel(ts('Added by'));
      $field->setDescription(ts('Contact who created this activity.'));
      $field->setRequired($action === 'create');
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $spec->addFieldSpec($field);

      $field = new FieldSpec('target_contact_id', 'Activity', 'Array');
      $field->setTitle(ts('Target Contacts'));
      $field->setLabel(ts('With Contact(s)'));
      $field->setDescription(ts('Contact(s) involved in this activity.'));
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $field->setInputAttrs(['multiple' => TRUE]);
      $spec->addFieldSpec($field);

      $field = new FieldSpec('assignee_contact_id', 'Activity', 'Array');
      $field->setTitle(ts('Assignee Contacts'));
      $field->setLabel(ts('Assigned to'));
      $field->setDescription(ts('Contact(s) assigned to this activity.'));
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $field->setInputAttrs(['multiple' => TRUE]);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Activity';
  }

}
