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
class CRM_Contact_Page_View_Tag extends CRM_Core_Page {

  /**
   * @var int
   * @internal
   */
  public $_contactId;

  /**
   * Called when action is browse.
   */
  public function browse() {
    $controller = new CRM_Core_Controller_Simple('CRM_Tag_Form_Tag', ts('Contact Tags'), $this->_action);
    $controller->setEmbedded(TRUE);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();

    $session->pushUserContext(CRM_Utils_System::url('civicrm/contact/view', 'action=browse&selectedChild=tag'), FALSE);
    $controller->reset();
    $controller->set('contactId', $this->_contactId);
    $controller->process();
    $controller->run();
  }

  public function preProcess() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('contactId', $this->_contactId);

    // check logged in url permission
    CRM_Contact_Page_View::checkUserPermission($this);

    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    $this->assign('action', $this->_action);
  }

  /**
   * the main function that is called when the page loads
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    $this->browse();

    return parent::run();
  }

}
