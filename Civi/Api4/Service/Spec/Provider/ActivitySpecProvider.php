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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class ActivitySpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();

    $field = new FieldSpec('source_contact_id', 'Activity', 'Integer');
    $field->setTitle(ts('Source Contact'));
    $field->setDescription(ts('Contact who created this activity.'));
    $field->setRequired($action === 'create');
    $field->setFkEntity('Contact');
    $spec->addFieldSpec($field);

    $field = new FieldSpec('target_contact_id', 'Activity', 'Array');
    $field->setTitle(ts('Target Contacts'));
    $field->setDescription(ts('Contact(s) involved in this activity.'));
    $field->setFkEntity('Contact');
    $spec->addFieldSpec($field);

    $field = new FieldSpec('assignee_contact_id', 'Activity', 'Array');
    $field->setTitle(ts('Assignee Contacts'));
    $field->setDescription(ts('Contact(s) assigned to this activity.'));
    $field->setFkEntity('Contact');
    $spec->addFieldSpec($field);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Activity' && in_array($action, ['create', 'update'], TRUE);
  }

}
