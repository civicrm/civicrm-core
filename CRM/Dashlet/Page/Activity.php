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

    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'dashlet');
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
