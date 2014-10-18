<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class is used by the Search functionality.
 *
 *  - the search controller is used for building/processing multiform
 *    searches.
 *
 * Typically the first form will display the search criteria and it's results
 *
 * The second form is used to process search results with the asscociated actions
 *
 */
class CRM_Contact_Controller_Search extends CRM_Core_Controller {

  /**
   * class constructor
   */
  function __construct($title = NULL, $modal = TRUE, $action = CRM_Core_Action::NONE) {
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
    // note that this happens really early on, so we cant use any of the form or controller
    // variables
    $config  = CRM_Core_Config::singleton();
    $qString = $_GET[$config->userFrameworkURLVar];
    $args = "reset=1";
    $path = 'civicrm/contact/search/advanced';
    if (strpos($qString, 'basic') !== FALSE) {
      $path = 'civicrm/contact/search/basic';
    }
    else if (strpos($qString, 'builder') !== FALSE) {
      $path = 'civicrm/contact/search/builder';
    }
    else if (
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

