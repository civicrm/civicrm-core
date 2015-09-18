<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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
   * Class constructor.
   *
   * @param null $title
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
