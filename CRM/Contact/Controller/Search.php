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
class CRM_Contact_Controller_Search extends CRM_Core_Controller {

  /**
   * Class constructor.
   *
   * @param string $title
   * @param bool $modal
   * @param int|mixed|null $action
   */
  public function __construct($title = NULL, $modal = TRUE, $action = CRM_Core_Action::NONE) {
    parent::__construct($title, $modal);

    $this->_stateMachine = new CRM_Contact_StateMachine_Search($this, $action);

    // create and instantiate the pages
    $this->addPages($this->_stateMachine, $action);

    // add all the actions
    $this->addActions();
  }

  /**
   * @return mixed
   */
  public function selectorName() {
    return $this->get('selectorName');
  }

  public function invalidKey() {
    $message = ts('Because your session timed out, we have reset the search page.');
    CRM_Core_Session::setStatus($message);

    // see if we can figure out the url and redirect to the right search form
    // note that this happens really early on, so we can't use any of the form or controller
    // variables
    $config = CRM_Core_Config::singleton();
    $qString = $_GET[$config->userFrameworkURLVar];
    $args = "reset=1";
    $path = 'civicrm/contact/search/advanced';
    if (strpos($qString, 'basic') !== FALSE) {
      $path = 'civicrm/contact/search/basic';
    }
    elseif (strpos($qString, 'builder') !== FALSE) {
      $path = 'civicrm/contact/search/builder';
    }
    elseif (
      strpos($qString, 'custom') !== FALSE &&
      isset($_REQUEST['csid'])
    ) {
      $path = 'civicrm/contact/search/custom';
      $args = "reset=1&csid={$_REQUEST['csid']}";
    }

    $url = CRM_Utils_System::url($path, $args);
    CRM_Utils_System::redirect($url);
  }

}
