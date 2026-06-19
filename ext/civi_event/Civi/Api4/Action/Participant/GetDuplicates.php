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

namespace Civi\Api4\Action\Participant;

use Civi\API\EntityLookupTrait;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Generic\DAOCreateAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Participant;

/**
 * Get matching participants.
 *
 */
class GetDuplicates extends DAOCreateAction {
  use EntityLookupTrait;

  /**
   * @param \Civi\Api4\Generic\Result $result
   *
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result) {
    $this->define('Event', 'Event', ['id' => $this->values['event_id']]);
    if ($this->lookup('Event', 'allow_same_participant_emails')) {
      // Per https://github.com/civicrm/civicrm-core/pull/14884
      // it has been determined that this event configuration option equates to
      // permitting multiple registrations by the same contact.
      return;
    }
    // This assumes we want to permit limited information
    // to 'any' user (who can access this api) - ie whether a space is available feels like a
    // front end js task.
    $apiCall = Participant::get(FALSE)
      ->addSelect('id', 'status_id', 'status_id.name', 'status_id.label', 'status_id.class', 'status_id.is_counted')
      ->addWhere('event_id', '=', $this->values['event_id'])
      ->addWhere('contact_id', '=', $this->values['contact_id'])
      // The idea behind the class is that the statuses should be configurable.
      // It should be a better pick than just going for 'Cancelled'
      ->addWhere('status_id.class', '!=', 'Negative')
      // Allow multiple test attempts as it is reasonable to think an
      // admin might want to hammer the form a bit
      ->addWhere('is_test', '=', FALSE);

    // It is OK to have more than one registration, if they have different roles.
    $roleID = $this->values['role_id'] ?? NULL;
    $roleIDName = $this->values['role_id:name'] ?? $this->values['role_id.name'] ?? NULL;
    if ($roleID) {
      $apiCall->addWhere('role_id', 'IN', (array) $roleID);
    }
    elseif ($roleIDName) {
      $apiCall->addWhere('role_id.name', 'IN', (array) $roleID);
    }
    else {
      $roleID = $this->lookup('Event', 'default_role_id');
      $apiCall->addWhere('role_id', 'IN', (array) $roleID);
    }
    $participants = $apiCall->execute();

    foreach ($participants as $participant) {
      $result[] = $participant;
    }
  }

  /**
   * Combines getFields from Contact + related entities into a flat array
   *
   * @param \Civi\Api4\Generic\BasicGetFieldsAction $action
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function fields(BasicGetFieldsAction $action): array {
    $relevantFields = ['contact_id', 'role_id', 'role_id:name', 'event_id', 'status_id'];
    $fields = (array) civicrm_api4('Participant', 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'create',
      'loadOptions' => $action->getLoadOptions(),
      'where' => [['name', 'IN', $relevantFields], ['type', 'IN', ['Field']]],
    ]);
    foreach ($fields as &$field) {
      if (in_array($field['name'], ['event_id', 'contact_id'], TRUE)) {
        $field['required'] = TRUE;
      }
    }
    return $fields;
  }

}
