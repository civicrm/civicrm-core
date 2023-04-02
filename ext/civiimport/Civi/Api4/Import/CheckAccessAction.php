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

namespace Civi\Api4\Import;

use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\CoreUtil;

/**
 * Check if current user is authorized to perform specified action on the given import.
 *
 * This is overridden to implement a permission on editing imported rows, mostly
 * to make it less confusing as there is no meaning to importing edited rows.
 */
class CheckAccessAction extends \Civi\Api4\Generic\CheckAccessAction {

  /**
   * @param \Civi\Api4\Generic\Result $result
   *
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result): void {
    // Prevent circular checks
    $action = $this->action;
    $entity = $this->getEntityName();
    $userID = \CRM_Core_Session::getLoggedInContactID() ?: 0;
    if ($action === 'checkAccess') {
      $granted = TRUE;
    }
    elseif (isset(\Civi::$statics[__CLASS__ . $entity][$action][$userID])) {
      $granted = \Civi::$statics[__CLASS__ . $entity][$action][$userID];
    }
    // If _status is not passed we could do a look up - but this permission is more of a
    // UI thing than a true permission - ie the point is not to confuse the user
    // with a meaningless option to edit-in-place in the search so it's kinda optional.
    elseif (in_array($this->getValue('_status'), ['soft_credit_imported', 'pledge_payment_imported', 'IMPORTED'])) {
      $granted = \Civi::$statics[__CLASS__ . $entity][$action][$userID] = FALSE;
    }
    else {
      $granted = \Civi::$statics[__CLASS__ . $entity][$action][$userID] = CoreUtil::checkAccessDelegated($entity, $action, $this->values, $userID);
    }
    $result->exchangeArray([['access' => $granted]]);
  }

}
