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
 * State machine for managing different states of the Import process.
 */
class CRM_Import_StateMachine extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param object $controller
   * @param \const|int $action
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $classType = str_replace('_Controller', '', get_class($controller));
    $this->_pages = [
      $classType . '_Form_DataSource' => NULL,
      $classType . '_Form_MapField' => NULL,
      $classType . '_Form_Preview' => NULL,
    ];

    $this->addSequentialPages($this->_pages);
  }

}
