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

namespace Civi\Api4\Action\Extension;

use Civi\Api4\Generic\BasicGetAction;

/**
 * Get extension info
 */
class Get extends BasicGetAction {

  protected function getRecords() {
    $statuses = \CRM_Extension_System::singleton()->getManager()->getStatuses();
    $mapper = \CRM_Extension_System::singleton()->getMapper();
    $result = [];
    foreach ($statuses as $key => $status) {
      try {
        $obj = $mapper->keyToInfo($key);
        $info = \CRM_Extension_System::createExtendedInfo($obj);
        $result[] = $info;
      }
      catch (\CRM_Extension_Exception $ex) {
        \Civi::log()->error(sprintf('Failed to read extension (%s). Please refresh the extension list.', $key));
      }
    }
    return $result;
  }

}
