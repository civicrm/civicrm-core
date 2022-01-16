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
 * Class CRM_Event_Controller_Registration
 */
class CRM_Event_Controller_Registration extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {
    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Event_StateMachine_Registration($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    $config = CRM_Core_Config::singleton();

    //changes for custom data type File
    $uploadNames = $this->get('uploadNames');
    if (is_array($uploadNames) && !empty($uploadNames)) {
      $this->addActions($config->customFileUploadDir, $uploadNames);
    }
    else {
      // add all the actions
      $this->addActions();
    }
  }

  public function invalidKey() {
    $this->invalidKeyRedirect();
  }

}
