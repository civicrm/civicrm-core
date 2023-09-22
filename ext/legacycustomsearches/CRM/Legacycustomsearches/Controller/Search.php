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
 * The second form is used to process search results with the associated actions.
 */
class CRM_Legacycustomsearches_Controller_Search extends CRM_Core_Controller {

  protected $entity = 'Contact';

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool $modal
   * @param int|mixed|null $action
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($title = NULL, $modal = TRUE, $action = CRM_Core_Action::NONE) {
    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Legacycustomsearches_StateMachine_Search($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $this->addActions();
    $this->set('entity', $this->entity);
  }

  /**
   * @return string
   */
  public function selectorName(): string {
    return $this->get('selectorName');
  }

  /**
   * Handle invalid session key.
   */
  public function invalidKey(): void {
    $message = ts('Because your session timed out, we have reset the search page.');
    CRM_Core_Session::setStatus($message);
    $url = CRM_Utils_System::url('civicrm/contact/search/custom', "reset=1&csid={$_REQUEST['csid']}");
    CRM_Utils_System::redirect($url);
  }

}
