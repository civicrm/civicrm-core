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
      CRM_Core_Error::fatal(ts('You are not authorized to access this page.'));
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

    $userID = $session->get('userID');

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
    if (!empty($upcoming)) {
      $this->assign('upcomingCases', TRUE);
    }
    if (!empty($recent)) {
      $this->assign('recentCases', TRUE);
    }

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
