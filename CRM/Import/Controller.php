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
class CRM_Import_Controller extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param array $arguments
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct(string $title, array $arguments) {
    parent::__construct($title, TRUE);
    set_time_limit(0);
    $this->_stateMachine = new CRM_Import_StateMachine($this, TRUE, $arguments['entity_prefix']);
    // 1 (or TRUE)  has been the action passed historically - but it is probably meaningless.
    $this->addPages($this->_stateMachine, CRM_Core_Action::ADD);
    $config = CRM_Core_Config::singleton();
    $this->addActions($config->uploadDir, ['uploadFile']);
  }

}
