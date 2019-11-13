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
 * This class handle mailing and contact related functions
 */
class CRM_Mailing_Page_Tab extends CRM_Contact_Page_View {
  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;
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

    CRM_Utils_System::setTitle(ts('Mailings sent to %1', [1 => $displayName]));
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
