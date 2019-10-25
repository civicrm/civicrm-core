<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * This class handle grant related functions
 *
 */
class CRM_Grant_Page_Tab extends CRM_Contact_Page_View {

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;
  public $_permission = NULL;
  public $_contactId = NULL;

  /**
   * called when action is browse.
   *
   */
  public function browse() {
    $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_Search', ts('Grants'), $this->_action);
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('cid', $this->_contactId);
    $controller->set('context', 'grant');
    $controller->process();
    $controller->run();

    if ($this->_contactId) {
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('displayName', $displayName);
      $this->ajaxResponse['tabCount'] = CRM_Contact_BAO_Contact::getCountComponent('grant', $this->_contactId);
    }
  }

  /**
   * called when action is view.
   *
   * @return null
   */
  public function view() {
    $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_GrantView', 'View Grant', $this->_action);
    $controller->setEmbedded(TRUE);
    $controller->set('id', $this->_id);
    $controller->set('cid', $this->_contactId);

    return $controller->run();
  }

  /**
   * called when action is update or new.
   *
   * @return null
   */
  public function edit() {
    $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_Grant', 'Create grant', $this->_action);
    $controller->setEmbedded(TRUE);
    $controller->set('id', $this->_id);
    $controller->set('cid', $this->_contactId);

    return $controller->run();
  }

  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);

    if ($context == 'standalone') {
      $this->_action = CRM_Core_Action::ADD;
    }
    else {
      $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
      $displayName = CRM_Contact_BAO_Contact::displayName($this->_contactId);
      $this->assign('contactId', $this->_contactId);
      $this->assign('displayName', $displayName);

      // check logged in url permission
      CRM_Contact_Page_View::checkUserPermission($this);
    }
    $this->assign('action', $this->_action);

    if ($this->_permission == CRM_Core_Permission::EDIT && !CRM_Core_Permission::check('edit grants')) {
      // demote to view since user does not have edit grant rights
      $this->_permission = CRM_Core_Permission::VIEW;
      $this->assign('permission', 'view');
    }
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    $this->setContext();

    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::ADD | CRM_Core_Action::DELETE)) {
      $this->edit();
    }
    else {
      $this->browse();
    }
    return parent::run();
  }

  public function setContext() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Integer', $this);
    $session = CRM_Core_Session::singleton();

    $qfKey = CRM_Utils_Request::retrieve('key', 'String', $this);
    //validate the qfKey
    if (!CRM_Utils_Rule::qfKey($qfKey)) {
      $qfKey = NULL;
    }

    switch ($context) {
      case 'search':
        $urlParams = 'force=1';
        if ($qfKey) {
          $urlParams .= "&qfKey=$qfKey";
        }
        $this->assign('searchKey', $qfKey);

        $url = CRM_Utils_System::url('civicrm/grant/search', $urlParams);
        break;

      case 'dashboard':
        $url = CRM_Utils_System::url('civicrm/grant', 'reset=1');
        break;

      case 'edit':
        $url = CRM_Utils_System::url('civicrm/contact/view/grant', 'reset=1&id=' . $this->_id . '&cid=' . $this->_contactId . '&action=view&context=grant&selectedChild=grant');
        break;

      case 'grant':
        $url = CRM_Utils_System::url('civicrm/contact/view', 'action=browse&selectedChild=grant&cid=' . $this->_contactId);
        break;

      case 'standalone':
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
        break;

      default:
        $cid = NULL;
        if ($this->_contactId) {
          $cid = '&cid=' . $this->_contactId;
        }
        $url = CRM_Utils_System::url('civicrm/grant/search', 'reset=1&force=1' . $cid);
        break;
    }
    $session->pushUserContext($url);

    if (CRM_Utils_Request::retrieve('confirmed', 'Boolean')) {
      CRM_Grant_BAO_Grant::del($this->_id);
      CRM_Utils_System::redirect($url);
    }
  }

}
