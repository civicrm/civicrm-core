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
class CRM_Event_Controller_Search extends CRM_Core_Controller {

  protected $entity = 'Participant';

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {

    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Event_StateMachine_Search($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    $session = CRM_Core_Session::singleton();
    $uploadNames = $session->get('uploadNames');
    if (!empty($uploadNames)) {
      $uploadNames = array_merge($uploadNames,
        CRM_Core_BAO_File::uploadNames()
      );
    }
    else {
      $uploadNames = CRM_Core_BAO_File::uploadNames();
    }

    $config = CRM_Core_Config::singleton();
    $uploadDir = $config->uploadDir;

    // add all the actions
    $this->addActions($uploadDir, $uploadNames);
    $this->set('entity', $this->entity);
  }

}
