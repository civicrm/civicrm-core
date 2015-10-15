<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * Main page for activity dashlet
 *
 */
class CRM_Dashlet_Page_Activity extends CRM_Core_Page {

  /**
   * List activities as dashlet.
   *
   * @return void
   */
  public function run() {
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    $this->assign('contactID', $contactID);
    $this->assign('contactId', $contactID);

    $context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'dashlet');
    $this->assign('context', $context);

    // a user can always view their own activity
    // if they have access CiviCRM permission
    $permission = CRM_Core_Permission::VIEW;

    // make the permission edit if the user has edit permission on the contact
    if (CRM_Contact_BAO_Contact_Permission::allow($contactID, CRM_Core_Permission::EDIT)) {
      $permission = CRM_Core_Permission::EDIT;
    }

    $admin = CRM_Core_Permission::check('view all activities') || CRM_Core_Permission::check('administer CiviCRM');

    $this->assign('admin', $admin);

    // also create the form element for the activity filter box
    $controller = new CRM_Core_Controller_Simple('CRM_Activity_Form_ActivityFilter',
      ts('Activity Filter'), NULL
    );
    $controller->setEmbedded(TRUE);
    $controller->run();

    return parent::run();
  }

}
