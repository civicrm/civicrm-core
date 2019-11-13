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
 */

/**
 * Dummy page for details of Email.
 */
class CRM_Contact_Page_View_Useradd extends CRM_Core_Page {

  /**
   * Run the page.
   *
   * This method is called after the page is created.
   */
  public function run() {
    $controller = new CRM_Core_Controller_Simple('CRM_Contact_Form_Task_Useradd',
      ts('Add User'),
      CRM_Core_Action::ADD
    );
    $controller->setEmbedded(TRUE);

    $controller->process();
    $controller->run();

    return parent::run();
  }

}
