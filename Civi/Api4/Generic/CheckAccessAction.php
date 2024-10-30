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

namespace Civi\Api4\Generic;

use Civi\Api4\Utils\CoreUtil;

/**
 * Check if current user is authorized to perform specified action on a given $ENTITY.
 *
 * @method $this setAction(string $action)
 * @method string getAction()
 * @method $this setValues(array $values)
 * @method array getValues()
 */
class CheckAccessAction extends AbstractAction {

  use Traits\GetSetValueTrait;

  /**
   * @var string
   * @required
   */
  protected $action;

  /**
   * @var array
   * @required
   */
  protected $values = [];

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    // Prevent circular checks
    if ($this->action === 'checkAccess') {
      $granted = TRUE;
    }
    else {
      $granted = CoreUtil::checkAccessDelegated($this->getEntityName(), $this->action, $this->values, \CRM_Core_Session::getLoggedInContactID() ?: 0);
    }
    $result->exchangeArray([['access' => $granted]]);
  }

  /**
   * This action is always allowed
   *
   * @return bool
   */
  public function isAuthorized(): bool {
    return TRUE;
  }

}
