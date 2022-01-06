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
 * @inheritDoc
 */
class Save extends \Civi\Api4\Generic\DAOSaveAction {

  /**
   * @param array $items
   * @return array
   */
  protected function write(array $items) {
    $saved = [];
    foreach ($items as $item) {
      // For some reason the contact BAO requires this for updates
      if (!empty($item['id']) && !\CRM_Utils_System::isNull($item['id'])) {
        $item['contact_id'] = $item['id'];
      }
      $saved[] = \CRM_Contact_BAO_Contact::create($item);
    }
    return $saved;
  }

}
