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

namespace Civi\Api4\Action\Contact;

use Civi\Api4\Generic\Result;

/**
 * Get the prior contact a contact was merged from.
 *
 * @method $this setContactId(int $cid) Set contact ID (required)
 * @method int getContactId() Get contact ID param
 * @method $this setIsTest(bool $isTest) Set isTest param
 * @method bool getIsTest() Get isTest param
 */
class GetMergedFrom extends \Civi\Api4\Generic\AbstractAction {

  /**
   * ID of contact to find prior contact for
   *
   * @var int
   * @required
   */
  protected $contactId;

  /**
   * Get test deletions rather than live?
   * @var bool
   */
  protected $isTest = FALSE;

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result): void {
    $activities = [];
    $deleteActivities = \Civi\Api4\ActivityContact::get($this->checkPermissions)
      ->addSelect('activity_id')
      ->addWhere('contact_id', '=', $this->contactId)
      ->addWhere('activity_id.activity_type_id:name', '=', 'Contact Merged')
      ->addWhere('activity_id.is_deleted', '=', FALSE)
      ->addWhere('activity_id.is_test', '=', $this->isTest)
      ->addWhere('record_type_id:name', '=', 'Activity Targets')
      ->execute();
    foreach ($deleteActivities as $deleteActivity) {
      $activities[] = $deleteActivity['activity_id'];
    }
    if (empty($activities)) {
      $result[] = $activities;
      return;
    }

    $activityContacts = \Civi\Api4\ActivityContact::get($this->checkPermissions)
      ->addSelect('contact_id')
      ->addWhere('activity_id.parent_id', 'IN', $activities)
      ->addWhere('record_type_id:name', '=', 'Activity Targets')
      ->execute();
    $contacts = [];
    foreach ($activityContacts as $activityContact) {
      $result[] = ['id' => $activityContact['contact_id']];
    }
  }

}
