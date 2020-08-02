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
 * This class is used by the Search functionality.
 *
 *  - the search controller is used for building/processing multiform
 *    searches.
 *
 * Typically the first form will display the search criteria and its results
 *
 * The second form is used to process search results with the associated actions
 *
 */
class CRM_PCP_Controller_PCP extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {

    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_PCP_StateMachine_PCP($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $uploadNames = $this->get('uploadNames');
    if (!empty($uploadNames)) {
      $config = CRM_Core_Config::singleton();
      $this->addActions($config->customFileUploadDir, $uploadNames);
    }
    else {
      $this->addActions();
    }
  }

}
