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

namespace Civi\Api4\Action\Note;

use Civi\Api4\Generic\Result;

/**
 * @inheritDoc
 */
class Get extends \Civi\Api4\Generic\DAOGetAction {

  /**
   * @inheritDoc
   */
  protected function getObjects(Result $result) {
    // Terrible hacky support for deprecated hook_civicrm_notePrivacy
    // This entire file can be deleted as soon as we kill that hook.
    $onlyCount = $this->getSelect() === ['row_count'];
    if (!$onlyCount && $this->getCheckPermissions() && !$this->getGroupBy() && $this->getSelect()) {
      // Hack in some extra selects for fields needed by the deprecated hook
      $this->addSelect('privacy', 'entity_table', 'entity_id', 'contact_id');
    }
    parent::getObjects($result);
    // Here comes the really bad part...
    // Silver lining, this will emit a noisy deprecation notice if the hook gets used
    if ($this->getCheckPermissions() && !$onlyCount) {
      $allowedRows = [];
      foreach ($result as $row) {
        if (!\CRM_Core_BAO_Note::getNotePrivacyHidden($row)) {
          $allowedRows[] = $row;
        }
      }
      $result->exchangeArray($allowedRows);
    }
  }

}
