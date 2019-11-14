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
class CRM_Export_StateMachine_Standalone extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param object $controller
   * @param \const|int $action
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = [
      'CRM_Export_Form_Select' => NULL,
      'CRM_Export_Form_Map' => NULL,
    ];

    $this->addSequentialPages($this->_pages, $action);
  }

  /**
   * @todo So far does nothing.
   *
   * @return string
   */
  public function getTaskFormName() {
    return '';
  }

  /**
   * @todo not sure if this is needed
   */
  public function shouldReset() {
    return FALSE;
  }

}
