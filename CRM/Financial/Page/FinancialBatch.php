<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying list of financial types
 */
class CRM_Financial_Page_FinancialBatch extends CRM_Core_Page_Basic {

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = null;

  /**
   * Get BAO Name
   *
   * @return string classname of BAO.
   */
  function getBAOName() {
    return 'CRM_Batch_BAO_Batch';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array();
    }
    return self::$_links;
  }

  /**
   * Run the page.
   *
   * This method is called after the page is created. It checks for the
   * type of action and executes that action.
   * Finally it calls the parent's run method.
   *
   * @return void
   * @access public
   *
   */
  function run() {
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->set("context", $context);
    // assign vars to templates
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, false, 0);
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, false, 'browse'); // default to 'browse'

    // what action to take ?
    if ($action &
      (CRM_Core_Action::UPDATE |
        CRM_Core_Action::ADD |
        CRM_Core_Action::CLOSE |
        CRM_Core_Action::REOPEN |
        CRM_Core_Action::EXPORT)) {
      $this->edit($action, $id) ;
    }
    // parent run
    return parent::run();
  }


  /**
   * Get name of edit form
   *
   * @return string classname of edit form.
   */
  function editForm() {
    return 'CRM_Financial_Form_FinancialBatch';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Accounting Batch';
  }

  /**
   * Get user context.
   *  
   * Redirect to civicrm home page when clicked on cancel button
   *
   * @return string user context.
   */
  function userContext($mode = null) {
    $context = $this->get("context");
    if ($mode == CRM_Core_Action::UPDATE || ($mode = CRM_Core_Action::ADD & isset($context))) {
      return "civicrm/financial/financialbatches";
    }
    return 'civicrm';
  }

  function userContextParams($mode = NULL) {
    $context = $this->get("context");
    if ($mode == CRM_Core_Action::UPDATE || ($mode = CRM_Core_Action::ADD & isset($context))) {
      return "reset=1&batchStatus={$context}";
    }
  }

}