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
class CRM_Pledge_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * called when action is browse.
   */
  public function listPledges() {
    $controller = new CRM_Core_Controller_Simple(
      'CRM_Pledge_Form_Search',
      ts('Pledges'),
      NULL,
      FALSE, FALSE, TRUE, FALSE
    );
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('limit', 12);
    $controller->set('cid', $this->_contactId);
    $controller->set('context', 'user');
    $controller->set('force', 1);
    $controller->process();
    $controller->run();

    // add honor block.
    $honorParams = [];
    $honorParams = CRM_Pledge_BAO_Pledge::getHonorContacts($this->_contactId);
    if (!empty($honorParams)) {
      // assign vars to templates
      $this->assign('pledgeHonorRows', $honorParams);
      $this->assign('pledgeHonor', TRUE);
    }
    $session = CRM_Core_Session::singleton();
    $loggedUserID = $session->get('userID');
    $this->assign('loggedUserID', $loggedUserID);
  }

  /**
   * the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   */
  public function run() {
    parent::preProcess();
    $this->listPledges();
  }

}
