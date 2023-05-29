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
   * @param string $entity
   */
  public function __construct($controller, $action = CRM_Core_Action::NONE, $entity = 'Contact') {
    parent::__construct($controller, $action);

    $entityMap = ['Contribution' => 'Contribute', 'Membership' => 'Member', 'Participant' => 'Event'];
    $entity = $entityMap[$entity] ?? $entity;
    $this->_pages = [
      'CRM_' . $entity . '_Export_Form_Select' => NULL,
      'CRM_' . $entity . '_Export_Form_Map' => NULL,
    ];

    $this->addSequentialPages($this->_pages);
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
