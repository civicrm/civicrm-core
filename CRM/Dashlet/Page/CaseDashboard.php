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
 * Main page for Case Dashboard dashlet
 *
 */
class CRM_Dashlet_Page_CaseDashboard extends CRM_Core_Page {

  /**
   * Case dashboard as dashlet.
   *
   * @return void
   */
  public function run() {
    //check for civicase access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }

    $summary = CRM_Case_BAO_Case::getCasesSummary(TRUE);

    if (!empty($summary)) {
      $this->assign('casesSummary', $summary);
    }

    return parent::run();
  }

}
