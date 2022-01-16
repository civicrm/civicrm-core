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
class CRM_Group_Controller extends CRM_Core_Controller {

  protected $entity = 'Contact';

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool|int $action
   * @param bool $modal
   */
  public function __construct($title = NULL, $action = CRM_Core_Action::NONE, $modal = TRUE) {
    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Group_StateMachine($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // hack for now, set Search to Basic mode
    $this->_pages['Basic']->setAction(CRM_Core_Action::BASIC);

    // add all the actions
    $config = CRM_Core_Config::singleton();

    // to handle file type custom data
    $uploadDir = $config->uploadDir;

    $uploadNames = $this->get('uploadNames');
    if (!empty($uploadNames)) {
      $uploadNames = array_merge($uploadNames,
        CRM_Core_BAO_File::uploadNames()
      );
    }
    else {
      $uploadNames = CRM_Core_BAO_File::uploadNames();
    }

    // add all the actions
    $this->addActions($uploadDir, $uploadNames);
    $this->set('entity', $this->entity);
  }

  /**
   * @return mixed
   */
  public function run() {
    return parent::run();
  }

  /**
   * @return mixed
   */
  public function selectorName() {
    return $this->get('selectorName');
  }

}
