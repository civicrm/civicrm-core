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
 * $Id$
 *
 */


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class EventCreationSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $spec->getFieldByName('event_type_id')->setRequiredIf('empty($values.template_id)');
    $spec->getFieldByName('title')->setRequiredIf('empty($values.is_template)');
    $spec->getFieldByName('start_date')->setRequiredIf('empty($values.is_template)');
    $spec->getFieldByName('template_title')->setRequiredIf('!empty($values.is_template)');

    $template_id = new FieldSpec('template_id', 'Event', 'Integer');
    $template_id
      ->setTitle('Template Id')
      ->setDescription('Template on which to base this new event');
    $spec->addFieldSpec($template_id);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Event' && $action === 'create';
  }

}
