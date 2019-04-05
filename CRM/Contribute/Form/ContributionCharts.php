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
class CRM_Contribute_Form_ContributionCharts extends CRM_Core_Form {

  /**
   *  Year of chart.
   *
   * @var int
   */
  protected $_year = NULL;

  /**
   *  The type of chart.
   *
   * @var string
   */
  protected $_chartType = NULL;

  public function preProcess() {
    $this->_year = CRM_Utils_Request::retrieve('year', 'Int', $this);
    $this->_chartType = CRM_Utils_Request::retrieve('type', 'String', $this);

    $buildChart = FALSE;

    if ($this->_year || $this->_chartType) {
      $buildChart = TRUE;
    }
    $this->assign('buildChart', $buildChart);
    $this->postProcess();
  }

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    //p3 = Three dimensional pie chart.
    //bvg = Vertical bar chart
    $this->addElement('select', 'chart_type', ts('Chart Style'), [
        'bvg' => ts('Bar'),
        'p3' => ts('Pie'),
      ]
    );
    $defaultValues['chart_type'] = $this->_chartType;
    $this->setDefaults($defaultValues);

    //take available years from database to show in drop down
    $currentYear = date('Y');
    $years = [];
    if (!empty($this->_years)) {
      if (!array_key_exists($currentYear, $this->_years)) {
        $this->_years[$currentYear] = $currentYear;
        krsort($this->_years);
      }
      foreach ($this->_years as $k => $v) {
        $years[substr($k, 0, 4)] = substr($k, 0, 4);
      }
    }

    $this->addElement('select', 'select_year', ts('Select Year (for monthly breakdown)'), $years);
    $this->setDefaults([
      'select_year' => ($this->_year) ? $this->_year : $currentYear,
    ]);
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $config = CRM_Core_Config::singleton();
    $chartType = 'bvg';
    if ($this->_chartType) {
      $chartType = $this->_chartType;
    }
    $selectedYear = date('Y');
    if ($this->_year) {
      $selectedYear = $this->_year;
    }

    //take contribution information monthly
    $chartInfoMonthly = CRM_Contribute_BAO_Contribution_Utils::contributionChartMonthly($selectedYear);

    $chartData = $abbrMonthNames = [];
    if (is_array($chartInfoMonthly)) {
      for ($i = 1; $i <= 12; $i++) {
        $abbrMonthNames[$i] = strftime('%b', mktime(0, 0, 0, $i, 10, 1970));
      }

      foreach ($abbrMonthNames as $monthKey => $monthName) {
        $val = CRM_Utils_Array::value($monthKey, $chartInfoMonthly['By Month'], 0);

        // don't include zero value month.
        if (!$val && ($chartType != 'bvg')) {
          continue;
        }

        //build the params for chart.
        $chartData['by_month']['values'][$monthName] = $val;
      }
      $chartData['by_month']['legend'] = 'By Month' . ' - ' . $selectedYear;

      // handle onclick event.
      $chartData['by_month']['on_click_fun_name'] = 'byMonthOnClick';
      $chartData['by_month']['yname'] = ts('Contribution');
    }

    //take contribution information by yearly
    $chartInfoYearly = CRM_Contribute_BAO_Contribution_Utils::contributionChartYearly();

    //get the years.
    $this->_years = $chartInfoYearly['By Year'];
    $hasContributions = FALSE;
    if (is_array($chartInfoYearly)) {
      $hasContributions = TRUE;
      $chartData['by_year']['legend'] = 'By Year';
      $chartData['by_year']['values'] = $chartInfoYearly['By Year'];

      // handle onclick event.
      $chartData['by_year']['on_click_fun_name'] = 'byYearOnClick';
      $chartData['by_year']['yname'] = ts('Total Amount');
    }
    $this->assign('hasContributions', $hasContributions);

    // process the data.
    $chartCnt = 1;

    $monthlyChart = $yearlyChart = FALSE;

    foreach ($chartData as $chartKey => & $values) {
      $chartValues = CRM_Utils_Array::value('values', $values);

      if (!is_array($chartValues) || empty($chartValues)) {
        continue;
      }
      if ($chartKey == 'by_year') {
        $yearlyChart = TRUE;
        if (!empty($config->fiscalYearStart) && ($config->fiscalYearStart['M'] !== 1 || $config->fiscalYearStart['d'] !== 1)) {
          $values['xLabelAngle'] = 45;
        }
        else {
          $values['xLabelAngle'] = 0;
        }
      }
      if ($chartKey == 'by_month') {
        $monthlyChart = TRUE;
      }

      $values['divName'] = "open_flash_chart_{$chartKey}";
      $funName = ($chartType == 'bvg') ? 'barChart' : 'pieChart';

      // build the chart objects.
      $values['object'] = CRM_Utils_OpenFlashChart::$funName($values);

      //build the urls.
      $urlCnt = 0;
      foreach ($chartValues as $index => $val) {
        $urlParams = NULL;
        if ($chartKey == 'by_month') {
          $monthPosition = array_search($index, $abbrMonthNames);
          $startDate = CRM_Utils_Date::format(['Y' => $selectedYear, 'M' => $monthPosition]);
          $endDate = date('Ymd', mktime(0, 0, 0, $monthPosition + 1, 0, $selectedYear));
          $urlParams = "reset=1&force=1&status=1&start={$startDate}&end={$endDate}&test=0";
        }
        elseif ($chartKey == 'by_year') {
          if (!empty($config->fiscalYearStart) && ($config->fiscalYearStart['M'] != 1 || $config->fiscalYearStart['d'] != 1)) {
            $startDate = date('Ymd', mktime(0, 0, 0, $config->fiscalYearStart['M'], $config->fiscalYearStart['d'], substr($index, 0, 4)));
            $endDate = date('Ymd', mktime(0, 0, 0, $config->fiscalYearStart['M'], $config->fiscalYearStart['d'], (substr($index, 0, 4)) + 1));
          }
          else {
            $startDate = CRM_Utils_Date::format(['Y' => substr($index, 0, 4)]);
            $endDate = date('Ymd', mktime(0, 0, 0, 13, 0, substr($index, 0, 4)));
          }
          $urlParams = "reset=1&force=1&status=1&start={$startDate}&end={$endDate}&test=0";
        }
        if ($urlParams) {
          $values['on_click_urls']["url_" . $urlCnt++] = CRM_Utils_System::url('civicrm/contribute/search',
            $urlParams, TRUE, FALSE, FALSE
          );
        }
      }

      // calculate chart size.
      $xSize = 400;
      $ySize = 300;
      if ($chartType == 'bvg') {
        $ySize = 250;
        $xSize = 60 * count($chartValues);

        // reduce x size by 100 for by_month
        if ($chartKey == 'by_month') {
          $xSize -= 100;
        }

        //hack to show tooltip.
        if ($xSize < 150) {
          $xSize = 150;
        }
      }
      $values['size'] = ['xSize' => $xSize, 'ySize' => $ySize];
    }

    // finally assign this chart data to template.
    $this->assign('hasYearlyChart', $yearlyChart);
    $this->assign('hasByMonthChart', $monthlyChart);
    $this->assign('hasOpenFlashChart', empty($chartData) ? FALSE : TRUE);
    $this->assign('openFlashChartData', json_encode($chartData));
  }

}
