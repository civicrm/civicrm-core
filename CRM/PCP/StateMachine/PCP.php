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
 * State machine for managing different pages while setting up PCP flow.
 */
class CRM_PCP_StateMachine_PCP extends CRM_Core_StateMachine {

  /**
   * Class constructor.
   *
   * @param CRM_PCP_Controller_PCP $controller
   * @param int $action
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);
    CRM_Core_Session::singleton()->set('singleForm', FALSE);

    $this->_pages = [
      'CRM_PCP_Form_PCPAccount' => NULL,
      'CRM_PCP_Form_Campaign' => NULL,
    ];

    $this->addSequentialPages($this->_pages);
  }

}
