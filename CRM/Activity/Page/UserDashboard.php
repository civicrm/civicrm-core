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
 * This class is for building event(participation) block on user dashboard.
 */
class CRM_Activity_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * List participations for the UF user.
   *
   * @return bool
   */
  public function listActivities() {

    $controller
      = new CRM_Core_Controller_Simple(
        'CRM_Activity_Form_Search', ts('Activities'),
        NULL,
        FALSE, FALSE, TRUE, FALSE
      );
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('context', 'user');
    $controller->set('cid', $this->_contactId);
    // Limit to status "Scheduled" and "Available"
    $controller->set('status', ['IN' => [1, 7]]);
    $controller->set('activity_role', 2);
    $controller->set('force', 1);
    $controller->process();
    $controller->run();

    return FALSE;
  }

  /**
   * The main function that is called when the page loads.
   *
   * It decides the which action has to be taken for the page.
   */
  public function run() {
    parent::preProcess();
    $this->listActivities();
  }

}
