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

/**
 * @service
 * @internal
 */
class ActionScheduleSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    if ($spec->getAction() === 'create') {
      $spec->getFieldByName('title')->setRequiredIf('empty($values.name)');
      $spec->getFieldByName('name')->setRequired(FALSE);
      // Repeat events do not require mapping_id or start_action_unit (or seemingly start_action_condition)- although
      // we don't have that level of nuance available so we make them optional for all events.
      $spec->getFieldByName('mapping_id')->setRequiredIf('empty($values.used_for)');
      $spec->getFieldByName('start_action_condition')->setRequiredIf('empty($values.absolute_date) && empty($values.start_action_date)');
      $spec->getFieldByName('entity_value')->setRequired(TRUE);
      $spec->getFieldByName('start_action_offset')->setRequiredIf('empty($values.absolute_date)');
      $spec->getFieldByName('start_action_date')->setRequiredIf('empty($values.absolute_date)');
      $spec->getFieldByName('absolute_date')->setRequiredIf('empty($values.start_action_date)');
      $spec->getFieldByName('group_id')->setRequiredIf('!empty($values.limit_to) && !empty($values.recipient) && $values.recipient === "group"');
      $spec->getFieldByName('recipient_manual')->setRequiredIf('!empty($values.limit_to) && !empty($values.recipient) && $values.recipient === "manual"');
      $spec->getFieldByName('subject')->setRequiredIf('!empty($values.is_active) && (empty($values.mode) || $values.mode !== "SMS")');
      $spec->getFieldByName('body_html')->setRequiredIf('!empty($values.is_active) && (empty($values.mode) || $values.mode !== "SMS")');
      $spec->getFieldByName('sms_body_text')->setRequiredIf('!empty($values.is_active) && !empty($values.mode) && $values.mode !== "Email"');
      $spec->getFieldByName('sms_provider_id')->setRequiredIf('!empty($values.is_active) && !empty($values.mode) && $values.mode !== "Email"');
      $spec->getFieldByName('repetition_frequency_interval')->setRequiredIf('!empty($values.is_repeat)');
      $spec->getFieldByName('end_frequency_interval')->setRequiredIf('!empty($values.is_repeat)');
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'ActionSchedule';
  }

}
