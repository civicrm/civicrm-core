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
class CRM_Group_StateMachine extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param object $controller
   * @param \const|int $action
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = [
      'CRM_Group_Form_Edit' => NULL,
      'CRM_Contact_Form_Search_Basic' => NULL,
      'CRM_Contact_Form_Task_AddToGroup' => NULL,
      'CRM_Contact_Form_Task_Result' => NULL,
    ];

    $this->addSequentialPages($this->_pages);
  }

  /**
   * Return the form name of the task. This is
   *
   * @return string
   */
  public function getTaskFormName() {
    return CRM_Utils_String::getClassName('CRM_Contact_Form_Task_AddToGroup');
  }

}
