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
class CRM_Upgrade_Controller extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   */
  public function __construct(
    $title = NULL,
    $action = CRM_Core_Action::NONE,
    $modal = TRUE
  ) {
    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Upgrade_StateMachine($this,
      $this->getPages(),
      $action
    );

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $config = CRM_Core_Config::singleton();
    $this->addActions();
  }

}
