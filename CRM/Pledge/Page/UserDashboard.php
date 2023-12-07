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
  public function listPledges(): void {
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

    // Add honor block.
    $honorParams = CRM_Pledge_BAO_Pledge::getHonorContacts($this->_contactId);
    $this->assign('pledgeHonorRows', $honorParams);
    $this->assign('pledgeHonor', !empty($honorParams));
    $this->assign('loggedUserID', CRM_Core_Session::getLoggedInContactID());
  }

  /**
   * The main function that is called when the page loads.
   *
   * @throws \CRM_Core_Exception
   */
  public function run(): void {
    $this->preProcess();
    $this->listPledges();
  }

}
