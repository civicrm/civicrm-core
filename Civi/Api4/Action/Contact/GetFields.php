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

namespace Civi\Api4\Action\Contact;

use Civi\Api4\Generic\DAOGetFieldsAction;

/**
 * @inheritDoc
 */
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
