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
 */

/**
 * This page is for the Pledge Dashboard.
 */
class CRM_Pledge_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process: The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviPledge'));

    $startToDate = array();
    $yearToDate = array();
    $monthToDate = array();
    $previousToDate = array();

    $prefixes = array('start', 'month', 'year', 'previous');
    $status = array('Completed', 'Cancelled', 'Pending', 'In Progress', 'Overdue');

    // cumulative (since inception) - prefix = 'start'
    $startDate = NULL;
    $startDateEnd = NULL;

    // current year - prefix = 'year'
    $config = CRM_Core_Config::singleton();
    $yearDate = $config->fiscalYearStart;
    $year = array('Y' => date('Y'));
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
   * the main function that is called when the page loads,
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
