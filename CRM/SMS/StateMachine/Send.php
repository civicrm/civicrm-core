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
class CRM_SMS_StateMachine_Send extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param object $controller
   * @param \const|int $action
   *
   * @return \CRM_SMS_StateMachine_Send CRM_SMS_StateMachine
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = [
      'CRM_SMS_Form_Group' => NULL,
      'CRM_SMS_Form_Upload' => NULL,
      'CRM_SMS_Form_Schedule' => NULL,
    ];

    $this->addSequentialPages($this->_pages);
  }

}
