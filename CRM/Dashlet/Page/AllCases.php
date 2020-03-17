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
 * $Id$
 *
 */

/**
 * Main page for Cases dashlet
 *
 */
class CRM_Dashlet_Page_AllCases extends CRM_Core_Page {

  /**
   * List activities as dashlet.
   *
   * @return void
   */
  public function run() {
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric', $this, FALSE, 'dashlet');
    $this->assign('context', $context);

    //check for civicase access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }

    $controller = new CRM_Core_Controller_Simple('CRM_Case_Form_Search',
      ts('Case'), CRM_Core_Action::BROWSE,
      NULL,
      FALSE, FALSE, TRUE
    );
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();

    if (CRM_Case_BAO_Case::getCases(TRUE, ['type' => 'any'], 'dashboard', TRUE)) {
      $this->assign('casePresent', TRUE);
    }
    return parent::run();
  }

}
