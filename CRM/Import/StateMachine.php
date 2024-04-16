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
 *
 * @internal
 */
class CRM_Import_StateMachine extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param CRM_Import_Controller $controller
   * @param int $action
   * @param ?string $entityPrefix
   *
   * @internal only supported for core use.
   *
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE, $entityPrefix = NULL) {
    parent::__construct($controller, $action);
    if (!$entityPrefix) {
      $classPrefix = str_replace('_Controller', '', get_class($controller));
    }
    else {
      $classPrefix = 'CRM_' . $entityPrefix . '_Import';
    }
    $this->_pages = [
      $classPrefix . '_Form_DataSource' => NULL,
      $classPrefix . '_Form_MapField' => NULL,
      $classPrefix . '_Form_Preview' => NULL,
    ];

    $this->addSequentialPages($this->_pages);
  }

}
