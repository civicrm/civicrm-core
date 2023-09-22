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

use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Generic\Result;

/**
 * Merge 2 contacts
 *
 * @method $this setMode(string $mode)
 * @method string getMode()
 * @method $this setContactId(int $contactId)
 * @method int getContactId()
 * @method $this setDuplicateId(int $duplicateId)
 * @method int getDuplicateId()
 */
class MergeDuplicates extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Main contact to keep with merged values
   *
   * @var int
   * @required
   */
  protected $contactId;

  /**
   * Duplicate contact to delete
   *
   * @var int
   * @required
   */
  protected $duplicateId;

  /**
   * Whether to run in "Safe Mode".
   *
   * Safe Mode skips the merge if there are conflicts. Does a force merge otherwise.
   *
   * @var string
   * @required
   * @options safe,aggressive
   */
  protected $mode = 'safe';

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $merge = \CRM_Dedupe_Merger::merge(
      [['srcID' => $this->duplicateId, 'dstID' => $this->contactId]],
      [],
      $this->mode,
      FALSE,
      $this->getCheckPermissions()
    );
    if ($merge) {
      $result[] = $merge;
    }
    else {
      throw new \CRM_Core_Exception('Merge failed');
    }
  }

  /**
   * @return array
   */
  public static function fields(BasicGetFieldsAction $action) {
    return [];
  }

}
