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
 * This page is for Case Dashboard.
 */
class CRM_Case_Page_DashBoard extends CRM_Core_Page {

  public $useLivePageJS = TRUE;

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    //check for civicase access.
    if (!CRM_Case_BAO_Case::accessCiviCase()) {
      CRM_Core_Error::statusBounce(ts('You are not authorized to access this page.'));
    }

    //validate case configuration.
    $configured = CRM_Case_BAO_Case::isCaseConfigured();
    $this->assign('notConfigured', !$configured['configured']);
    $this->assign('allowToAddNewCase', $configured['allowToAddNewCase']);
    if (!$configured['configured']) {
      return;
    }

    $session = CRM_Core_Session::singleton();
    $allCases = CRM_Utils_Request::retrieve('all', 'Positive', $this);

    CRM_Utils_System::setTitle(ts('CiviCase Dashboard'));

    //validate access for all cases.
    if ($allCases && !CRM_Core_Permission::check('access all cases and activities')) {
      $allCases = 0;
      CRM_Core_Session::setStatus(ts('You are not authorized to access all cases and activities.'), ts('Sorry'), 'error');
    }
    $this->assign('all', $allCases);
    if (!$allCases) {
      $this->assign('myCases', TRUE);
    }
    else {
      $this->assign('myCases', FALSE);
    }

    $this->assign('newClient', FALSE);
    if (CRM_Core_Permission::check('add contacts') &&
      CRM_Core_Permission::check('access all cases and activities')
    ) {
      $this->assign('newClient', TRUE);
    }
    $summary = CRM_Case_BAO_Case::getCasesSummary($allCases);
    $upcoming = CRM_Case_BAO_Case::getCases($allCases, [], 'dashboard', TRUE);
    $recent = CRM_Case_BAO_Case::getCases($allCases, ['type' => 'recent'], 'dashboard', TRUE);

    $this->assign('casesSummary', $summary);
    $this->assign('upcomingCases', !empty($upcoming));
    $this->assign('recentCases', !empty($recent));

    $controller = new CRM_Core_Controller_Simple('CRM_Case_Form_Search',
      ts('Case'), CRM_Core_Action::BROWSE,
      NULL,
      FALSE, FALSE, TRUE
    );
    $controller->set('context', 'dashboard');
    $controller->setEmbedded(TRUE);
    $controller->process();
    $controller->run();
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    return parent::run();
  }

}
