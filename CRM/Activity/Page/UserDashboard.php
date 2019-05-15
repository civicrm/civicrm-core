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
