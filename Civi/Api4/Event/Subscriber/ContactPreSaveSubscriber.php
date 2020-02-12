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


namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\AbstractAction;

class ContactPreSaveSubscriber extends Generic\PreSaveSubscriber {

  public $supportedOperation = 'create';

  public function modify(&$contact, AbstractAction $request) {
    // Guess which type of contact is being created
    if (empty($contact['contact_type']) && !empty($contact['organization_name'])) {
      $contact['contact_type'] = 'Organization';
    }
    if (empty($contact['contact_type']) && !empty($contact['household_name'])) {
      $contact['contact_type'] = 'Household';
    }
  }

  public function applies(AbstractAction $request) {
    return $request->getEntityName() === 'Contact';
  }

}
