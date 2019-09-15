<?php
namespace Civi\Api4\Action\Contact;

use Civi\Api4\Generic\DAOGetFieldsAction;

class GetFields extends DAOGetFieldsAction {

  protected function getRecords() {
    $fields = parent::getRecords();

    $apiKeyPerms = ['edit api keys', 'administer CiviCRM'];
    if ($this->checkPermissions && !\CRM_Core_Permission::check([$apiKeyPerms])) {
      unset($fields['api_key']);
    }

    return $fields;
  }

}
