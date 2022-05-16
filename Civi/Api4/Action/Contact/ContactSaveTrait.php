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
 * Code shared by Contact create/update/save actions
 */
trait ContactSaveTrait {

  /**
   * @param array $items
   * @return array
   */
  protected function write(array $items) {
    foreach ($items as &$contact) {
      // For some reason the contact BAO requires this for updates
      if (!empty($contact['id'])) {
        $contact['contact_id'] = $contact['id'];
      }
      elseif (empty($contact['contact_type'])) {
        // Guess which type of contact is being created
        if (!empty($contact['organization_name'])) {
          $contact['contact_type'] = 'Organization';
        }
        elseif (!empty($contact['household_name'])) {
          $contact['contact_type'] = 'Household';
        }
        else {
          $contact['contact_type'] = 'Individual';
        }
      }
    }
    return parent::write($items);
  }

}
