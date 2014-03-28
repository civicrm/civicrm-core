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

require_once 'CRM/Core/Page.php';

/**
 * Page to decide the flow of adding an item.
 */
class CRM_Auction_Page_AddItem extends CRM_Core_Page {

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
    // get the requested action
    $action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');

    // set breadcrumb to append to 2nd layer pages
    $breadCrumb = array(array('title' => ts('Manage Items'),
        'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          'reset=1'
        ),
      ));
    // what action to take ?
    if ($action & CRM_Core_Action::ADD) {
      $session = CRM_Core_Session::singleton();
      if ($session->get('userID')) {
        // For logged in user directly go to add/update item page.
        $controller = new CRM_Core_Controller_Simple('CRM_Auction_Form_Item',
          'New Item',
          $action
        );
        $controller->set('donorID', $session->get('userID'));
      }
      else {
        // For anonymous user go via account creation wizard.
        require_once 'CRM/Auction/Controller/Item.php';
        $controller = new CRM_Auction_Controller_Item('New Item', $action);
      }
      return $controller->run();
    }
    elseif ($action & CRM_Core_Action::UPDATE) {
      $session = CRM_Core_Session::singleton();
      if ($session->get('userID')) {
        $controller = new CRM_Core_Controller_Simple('CRM_Auction_Form_Item',
          'Update Item',
          $action
        );
        $controller->set('donorID', $session->get('userID'));
        return $controller->run();
      }
    }

    // parent run
    parent::run();
  }
}

