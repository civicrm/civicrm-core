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
 * Typically the first form will display the search criteria and it's results
 *
 * The second form is used to process search results with the associated actions
 *
 */
class CRM_Activity_Controller_Search extends CRM_Core_Controller {

  /**
   * @var string
   */
  protected $entity = 'Activity';

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool $modal
   * @param int|mixed|null $action
   */
  public function __construct($title = NULL, $modal = TRUE, $action = CRM_Core_Action::NONE) {

    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Activity_StateMachine_Search($this, $action);

    // Create and instantiate the pages.
    $this->addPages($this->_stateMachine, $action);

    // Add all the actions.
    $this->addActions();
    $this->set('entity', $this->entity);
  }

  /**
   * Getter for selectorName.
   *
   * @return mixed
   */
  public function selectorName() {
    return $this->get('selectorName');
  }

}
