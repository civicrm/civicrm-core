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
 * Page for displaying list of Payment-Instrument.
 */
class CRM_Contribute_Page_DashBoard extends CRM_Core_Page {

  /**
   * Heart of the viewing process.
   *
   * The runner gets all the meta data for the contact and calls the appropriate type of page to view.
   */
  public function preProcess() {
    CRM_Utils_System::setTitle(ts('CiviContribute'));

    $status = ['Valid', 'Cancelled'];
    $prefixes = ['start', 'month', 'year'];
    $startDate = NULL;
    $startToDate = $monthToDate = $yearToDate = [];

    //get contribution dates.
    $dates = CRM_Contribute_BAO_Contribution::getContributionDates();
    foreach (['now', 'yearDate', 'monthDate'] as $date) {
      $$date = $dates[$date];
    }
    // fiscal years end date
    $yearNow = date('Ymd', strtotime('+1 year -1 day', strtotime($yearDate)));

    foreach ($prefixes as $prefix) {
      $aName = $prefix . 'ToDate';
      $dName = $prefix . 'Date';

      if ($prefix == 'year') {
        $now = $yearNow;
      }

      // appending end date i.e $now with time
      // to also calculate records of end date till mid-night
      $nowWithTime = $now . '235959';

      foreach ($status as $s) {
        ${$aName}[$s] = CRM_Contribute_BAO_Contribution::getTotalAmountAndCount($s, $$dName, $nowWithTime);
        ${$aName}[$s]['url'] = CRM_Utils_System::url('civicrm/contribute/search',
          "reset=1&force=1&status=1&receive_date_low={$$dName}&receive_date_high=$now&test=0"
        );
      }

      $this->assign($aName, $$aName);
    }

    //for contribution tabular View
    $buildTabularView = $_GET['showtable'] ?? FALSE;
    $this->assign('buildTabularView', $buildTabularView);
    if ($buildTabularView) {
      return;
    }

    // Check for admin permission to see if we should include the Manage Contribution Pages action link
    $this->assign('isAdmin', CRM_Core_Permission::check('administer CiviCRM'));
  }

  /**
   * the main function that is called when the page loads,
   * it decides the which action has to be taken for the page.
   *
   * @return null
   */
  public function run() {
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
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Contribute/Page/DashBoard.js', 1, 'html-header');

    return parent::run();
  }

}
