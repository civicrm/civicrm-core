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
class EventCreationSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('event_type_id')->setRequiredIf('empty($values.template_id)');
    $spec->getFieldByName('title')->setRequiredIf('empty($values.is_template)');
    $spec->getFieldByName('start_date')->setRequiredIf('empty($values.is_template)');
    $spec->getFieldByName('template_title')->setRequiredIf('!empty($values.is_template)');
    // Arguably this is a bad default in the schema
    $spec->getFieldByName('is_active')->setRequired(FALSE)->setDefaultValue(TRUE);

    $template_id = (new FieldSpec('template_id', 'Event', 'Integer'))
      ->setTitle(ts('Event Template'))
      ->setDescription(ts('Template on which to base this new event'))
      ->setInputType('EntityRef')
      // Afform-only (so far) metadata tells the form this field is used to create a new entity rather than update
      ->setInputAttr('autofill', 'create')
      ->setFkEntity('Event');
    $spec->addFieldSpec($template_id);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Event' && $action === 'create';
  }

}
