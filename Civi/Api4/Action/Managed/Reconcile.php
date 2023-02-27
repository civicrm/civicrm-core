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

namespace Civi\Api4\Action\Managed;

use Civi\Api4\Generic\Result;

/**
 * Refresh managed entities.
 *
 * @since 5.50
 * @method $this setModules(array $modules) Set modules
 * @method array getModules() Get modules param
 */
class Reconcile extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Limit scope of reconcile to specific module(s).
   *
   * @var array
   */
  protected $modules = NULL;

  /**
   * @param string $moduleName
   * @return $this
   */
  public function addModule(string $moduleName) {
    $this->modules[] = $moduleName;
    return $this;
  }

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    \CRM_Core_ManagedEntities::singleton()->reconcile($this->modules);
  }

}
