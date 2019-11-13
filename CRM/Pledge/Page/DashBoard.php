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
 * This page is for the Pledge Dashboard.
 */
class CRM_Pledge_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviPledge'));

    $startToDate = [];
    $yearToDate = [];
    $monthToDate = [];
    $previousToDate = [];

    $prefixes = ['start', 'month', 'year', 'previous'];
    $status = ['Completed', 'Cancelled', 'Pending', 'In Progress', 'Overdue'];

    // cumulative (since inception) - prefix = 'start'
    $startDate = NULL;
    $startDateEnd = NULL;

    // current year - prefix = 'year'
    $yearDate = \Civi::settings()->get('fiscalYearStart');
    $year = ['Y' => date('Y')];
    $this->assign('curYear', $year['Y']);
    $yearDate = array_merge($year, $yearDate);
    $yearDate = CRM_Utils_Date::format($yearDate);
    $yearDate = $yearDate . '000000';
    $yearDateEnd = $year['Y'] . '1231235959';

    // current month - prefix = 'month'
    $currentMonth = date("F Y", mktime(0, 0, 0, date("m"), 01, date("Y")));
    $this->assign('currentMonthYear', $currentMonth);
    $monthDate = date('Ym') . '01000000';
    $monthDateEnd = CRM_Utils_Date::customFormat(date("Y-m-t", mktime(0, 0, 0, date("m"), 01, date("Y"))), '%Y%m%d') . '235959';

    // previous month - prefix = 'previous'
    $previousDate = CRM_Utils_Date::customFormat(date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 01, date("Y"))), '%Y%m%d') . '000000';
    $previousDateEnd = CRM_Utils_Date::customFormat(date("Y-m-t", mktime(0, 0, 0, date("m") - 1, 01, date("Y"))), '%Y%m%d') . '235959';
    $previousMonth = date("F Y", mktime(0, 0, 0, date("m") - 1, 01, date("Y")));
    $this->assign('previousMonthYear', $previousMonth);

    foreach ($prefixes as $prefix) {
      $aName = $prefix . 'ToDate';
      $startName = $prefix . 'Date';
      $endName = $prefix . 'DateEnd';
      foreach ($status as $s) {
        ${$aName}[str_replace(" ", "", $s)] = CRM_Pledge_BAO_Pledge::getTotalAmountAndCount($s, $$startName, $$endName);
      }
      $this->assign($aName, $$aName);
    }
  }

  /**
   * The main function that is called when the page loads.
   *
   * it decides which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
    $this->preProcess();

    $controller = new CRM_Core_Controller_Simple('CRM_Pledge_Form_Search',
      ts('Pledge'),
      NULL
    );
    $controller->setEmbedded(TRUE);
    $controller->reset();
    $controller->set('limit', 10);
    $controller->set('force', 1);
    $controller->set('context', 'dashboard');
    $controller->process();
    $controller->run();

    return parent::run();
  }

}
