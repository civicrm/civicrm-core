<?php

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
