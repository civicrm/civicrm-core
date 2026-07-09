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
    $entityName = $this->getEntityName();
    $idField = CoreUtil::getIdFieldName($entityName);
    $record = $this->values;

    // Attempt to look up record by name if id is not supplied.
    if (!isset($record[$idField]) && isset($record['name'])) {
      try {
        $record[$idField] = civicrm_api4($entityName, 'get', [
          'checkPermissions' => FALSE,
          'select' => [$idField],
          'where' => [['name', '=', $record['name']]],
        ])->single()[$idField];
      }
      catch (\CRM_Core_Exception $e) {
      }
    }

    // Prevent circular checks
    if ($this->action === 'checkAccess') {
      $granted = TRUE;
    }
    else {
      $granted = CoreUtil::checkAccessDelegated($entityName, $this->action, $record, \CRM_Core_Session::getLoggedInContactID() ?: 0);
    }
    $result->exchangeArray([
      [
        'access' => $granted,
        // Only return id if access was granted.
        $idField => isset($record[$idField]) && $granted ? $record[$idField] : NULL,
      ],
    ]);
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
