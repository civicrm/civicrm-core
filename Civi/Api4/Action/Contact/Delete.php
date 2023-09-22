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

/**
 * Deletes a contact, by default moving to trash. Set `useTrash = FALSE` for permanent deletion.
 * @inheritDoc
 */
class Delete extends \Civi\Api4\Generic\DAODeleteAction {
  use \Civi\Api4\Generic\Traits\SoftDeleteActionTrait;

  /**
   * @param $items
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function deleteObjects($items) {
    foreach ($items as $item) {
      if (!\CRM_Contact_BAO_Contact::deleteContact($item['id'], FALSE, !$this->useTrash, $this->checkPermissions)) {
        throw new \CRM_Core_Exception("Could not delete {$this->getEntityName()} id {$item['id']}");
      }
      $ids[] = ['id' => $item['id']];
    }
    return $ids;
  }

}
