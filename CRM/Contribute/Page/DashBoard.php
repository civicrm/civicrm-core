<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Page for displaying list of Payment-Instrument
 */
class CRM_Contribute_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process. The runner gets all the meta data for
   * the contact and calls the appropriate type of page to view.
   *
   * @return void
   * @access public
   *
   */
  function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviContribute'));

    $status      = array('Valid', 'Cancelled');
    $prefixes    = array('start', 'month', 'year');
    $startDate   = NULL;
    $startToDate = $monthToDate = $yearToDate = array();

    //get contribution dates.
    $dates = CRM_Contribute_BAO_Contribution::getContributionDates();
    foreach (array(
      'now', 'yearDate', 'monthDate') as $date) {
      $$date = $dates[$date];
    }
    $yearNow = $yearDate + 10000;
    foreach ($prefixes as $prefix) {
      $aName = $prefix . 'ToDate';
      $dName = $prefix . 'Date';

      if ($prefix == 'year') {
        $now = $yearNow;
      }

      foreach ($status as $s) {
        ${$aName}[$s] = CRM_Contribute_BAO_Contribution::getTotalAmountAndCount($s, $$dName, $now);
        ${$aName}[$s]['url'] = CRM_Utils_System::url('civicrm/contribute/search',
          "reset=1&force=1&status=1&start={$$dName}&end=$now&test=0"
        );
      }

      $this->assign($aName, $$aName);
    }

    //for contribution tabular View
    $buildTabularView = CRM_Utils_Array::value('showtable', $_GET, FALSE);
    $this->assign('buildTabularView', $buildTabularView);
    if ($buildTabularView) {
      return;
    }

    // Check for admin permission to see if we should include the Manage Contribution Pages action link
    $isAdmin = 0;
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $isAdmin = 1;
    }
    $this->assign('isAdmin', $isAdmin);
  }

  /**
   * This function is the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->preProcess();

    $controller = new CRM_Core_Controller_Simple('CRM_Contribute_Form_Search',
      ts('Contributions'), NULL
    );
    $controller->setEmbedded(TRUE);

    $controller->set('limit', 10);
    $controller->set('force', 1);
    $controller->set('context', 'dashboard');
    $controller->process();
    $controller->run();
    $chartForm = new CRM_Core_Controller_Simple('CRM_Contribute_Form_ContributionCharts',
      ts('Contributions Charts'), NULL
    );

    $chartForm->setEmbedded(TRUE);
    $chartForm->process();
    $chartForm->run();

    return parent::run();
  }
}

