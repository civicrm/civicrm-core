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
 */


namespace Civi\Api4\Generic;

use Civi\API\Request;

/**
 * Check if current user is authorized to perform specified action on a given $ENTITY.
 *
 * @method $this setAction(string $action)
 * @method string getAction()
 * @method $this setValues(array $values)
 * @method array getValues()
 */
class CheckAccessAction extends AbstractAction {

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
    $action = Request::create($this->getEntityName(), $this->action, ['version' => 4]);
    // This checks gatekeeper permissions
    $authorized = $action->isAuthorized();
    // For get actions, just run a get and ACLs will be applied to the query.
    //It's a cheap trick and not as efficient as not running the query at all, but for lack of a centralized ACL checker, it will do.
    if (is_a($action, '\Civi\Api4\Generic\DAOGetAction')) {
      $authorized = $authorized && $action->addSelect('id')->addWhere('id', '=', $this->values['id'])->execute()->count();
    }
    else {
      // TODO: Check ACLs for other actions
      // Currently there is no metadata or standard way of doing this for any given entity.
      // Although some entities do have methods to do it, others do not. It's a hodgepodge.
      // We need to add this to core, as this is not the right place for such a thing.
      // This api ought to invoke the thing, not implement it.
    }
    $result->exchangeArray([['access' => $authorized]]);
  }

  /**
   * This action is always allowed
   *
   * @return bool
   */
  public function isAuthorized() {
    return TRUE;
  }

  /**
   * Add an item to the values array
   * @param string $fieldName
   * @param mixed $value
   * @return $this
   */
  public function addValue(string $fieldName, $value) {
    $this->values[$fieldName] = $value;
    return $this;
  }

}
