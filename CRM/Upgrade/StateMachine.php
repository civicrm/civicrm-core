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

/**
 * State machine for managing different states of the upgrade process.
 */
class CRM_Upgrade_StateMachine extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param CRM_Upgrade_Controller $controller
   * @param array $pages
   * @param int $action
   *
   * @return CRM_Upgrade_StateMachine
   */
  public function __construct(&$controller, &$pages, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = &$pages;

    $this->addSequentialPages($this->_pages);
  }

}
