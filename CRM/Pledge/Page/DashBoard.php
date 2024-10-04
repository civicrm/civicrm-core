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

    $this->assign('startToDate', [
      'Completed' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Completed'),
      'Cancelled' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Cancelled'),
      'Pending' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Pending'),
      'InProgress' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('In Progress'),
      'Overdue' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Overdue'),
    ]);

    // current year - prefix = 'year'
    $yearDate = \Civi::settings()->get('fiscalYearStart');
    $year = ['Y' => date('Y')];
    $this->assign('curYear', $year['Y']);
    $yearDate = array_merge($year, $yearDate);
    $yearDate = CRM_Utils_Date::format($yearDate) . '000000';
    $yearDateEnd = $year['Y'] . '1231235959';
    $this->assign('yearToDate', [
      'Completed' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Completed', $yearDate, $yearDateEnd),
      'Cancelled' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Cancelled', $yearDate, $yearDateEnd),
      'Pending' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Pending', $yearDate, $yearDateEnd),
      'InProgress' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('In Progress', $yearDate, $yearDateEnd),
      'Overdue' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Overdue', $yearDate, $yearDateEnd),
    ]);

    // current month - prefix = 'month'
    $currentMonth = date("F Y", mktime(0, 0, 0, date("m"), 01, date("Y")));
    $this->assign('currentMonthYear', $currentMonth);
    $monthDate = date('Ym') . '01000000';
    $monthDateEnd = CRM_Utils_Date::customFormat(date("Y-m-t", mktime(0, 0, 0, date("m"), 01, date("Y"))), '%Y%m%d') . '235959';
    $this->assign('monthToDate', [
      'Completed' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Completed', $monthDate, $monthDateEnd),
      'Cancelled' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Cancelled', $monthDate, $monthDateEnd),
      'Pending' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Pending', $monthDate, $monthDateEnd),
      'InProgress' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('In Progress', $monthDate, $monthDateEnd),
      'Overdue' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Overdue', $monthDate, $monthDateEnd),
    ]);

    // previous month - prefix = 'previous'
    $previousDate = CRM_Utils_Date::customFormat(date("Y-m-d", mktime(0, 0, 0, date("m") - 1, 01, date("Y"))), '%Y%m%d') . '000000';
    $previousDateEnd = CRM_Utils_Date::customFormat(date("Y-m-t", mktime(0, 0, 0, date("m") - 1, 01, date("Y"))), '%Y%m%d') . '235959';
    $previousMonth = date("F Y", mktime(0, 0, 0, date("m") - 1, 01, date("Y")));
    $this->assign('previousMonthYear', $previousMonth);
    $this->assign('previousToDate', [
      'Completed' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Completed', $previousDate, $previousDateEnd),
      'Cancelled' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Cancelled', $previousDate, $previousDateEnd),
      'Pending' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Pending', $previousDate, $previousDateEnd),
      'InProgress' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('In Progress', $previousDate, $previousDateEnd),
      'Overdue' => CRM_Pledge_BAO_Pledge::getTotalAmountAndCount('Overdue', $previousDate, $previousDateEnd),
    ]);
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
