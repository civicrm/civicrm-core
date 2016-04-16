<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class handle mailing and contact related functions
 */
class CRM_Mailing_Page_Tab extends CRM_Contact_Page_View {
  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  static $_links = NULL;
  public $_permission = NULL;
  public $_contactId = NULL;

  /**
   * Called when action is browse.
   */
  public function browse() {
  }

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);

    $this->assign('contactId', $this->_contactId);
    $this->assign('displayName', $displayName);

    // Check logged in url permission.
    CRM_Contact_Page_View::checkUserPermission($this);

    CRM_Utils_System::setTitle(ts('Mailings sent to %1', array(1 => $displayName)));
  }

  /**
   * The main function that is called when the page loads.
   *
   * It decides the which action has to be taken for the page.
   */
  public function run() {
    $this->preProcess();
    $this->browse();
    parent::run();
  }

}
