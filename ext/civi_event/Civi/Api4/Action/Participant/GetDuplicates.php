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
use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Generic\DAOCreateAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Participant;
use Civi\Api4\Utils\FormattingUtil;

/**
 * Get matching participants.
 *
 */
class GetDuplicates extends DAOCreateAction {
  use EntityLookupTrait;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $this->define('Event', 'Event', ['id' => $this->values['event_id']]);
    if ($this->lookup('Event', 'allow_same_participant_emails')) {
      // Per https://github.com/civicrm/civicrm-core/pull/14884
      // it has been determined that this event configuration option equates to
      // permitting multiple registrations by the same contact.
      return;
    }
    $roleID = $this->values['role_id'] ?? NULL;
    if (!$roleID) {
      $roleID = $this->lookup('Event','default_role_id');
    }
    // @todo - maybe this permission check should be FALSE? Do we need to call it from js?
    $participants = Participant::get($this->getCheckPermissions())
      // Only retrieve id because that matches Contact.getDuplicates and also
      // because we risk getting into a stale data or data scope creep situation for little gain
      // (ie calling code has the ids - it can get what it wants)
      ->addSelect('id')
      ->addWhere('event_id', '=', $this->values['event_id'])
      ->addWhere('contact_id', '=', $this->values['contact_id'])
      // The idea behind the class is that the statuses should be configurable.
      // It should be a better pick than just going for 'Cancelled'
      ->addWhere('role_id:class', '!=',  'Negative')
      // Allow multiple test attempts as it is reasonable to think an
      // admin might want to hammer the form a bit
      ->addWhere('is_test', '=', FALSE)
      // It is OK to have more than one registration, if they have different role.s
      ->addWhere('role_id:name', 'NOT IN', (array) $roleID)
      ->execute();

    foreach ($participants as $participant) {
      $result[] = $participant;
    }
  }

  /**
   * Combines getFields from Contact + related entities into a flat array
   *
   * @return array
   */
  public static function fields(BasicGetFieldsAction $action) {
    $relevantFields = ['contact_id', 'role_id', 'role_id:name', 'event_id'];
    $fields = (array) civicrm_api4('Participant', 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'create',
      'loadOptions' => $action->getLoadOptions(),
      'where' => [['name', 'IN', $relevantFields], ['type', 'IN', ['Field']]],
    ]);
    // Make event_id required.
    return $fields;
  }

}
