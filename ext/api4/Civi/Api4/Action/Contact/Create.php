<?php

namespace Civi\Api4\Action\Contact;

use Civi\Api4\Generic\Result;
use Civi\Api4\Action\Create as DefaultCreate;

/**
 * @inheritDoc
 */
class Create extends DefaultCreate {

  protected function fillDefaults(&$params) {
    // Guess which type of contact is being created
    if (empty($params['contact_type']) && !empty($params['organization_name'])) {
      $params['contact_type'] = 'Organization';
    }
    if (empty($params['contact_type']) && !empty($params['household_name'])) {
      $params['contact_type'] = 'Household';
    }
    // Will default to Individual per fieldSpec
    parent::fillDefaults($params);
  }

}
