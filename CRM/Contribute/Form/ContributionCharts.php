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

  /**
   * @var array|array[]
   */
  private array $contributionsByYear;

  public function preProcess() {
    \Civi::resources()->addBundle('visual');

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
    ]);
    $defaultValues['chart_type'] = $this->_chartType;
    $this->setDefaults($defaultValues);

    //take available years from database to show in drop down
    $currentYear = date('Y');
    $years = $this->getContributionTotalsByYear() + [date('Y') => TRUE];
    ksort($years);
    foreach (array_keys($years) as $year) {
      $years[$year] = $year;
    }
    $this->addElement('select', 'select_year', ts('Select Year (for monthly breakdown)'), $years);
    $this->setDefaults([
      'select_year' => $this->_year ?: $currentYear,
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
    $chartInfoMonthly = $this->contributionChartMonthly($selectedYear);

    $chartData = $abbrMonthNames = [];
    if (is_array($chartInfoMonthly)) {
      $abbrMonthNames = CRM_Utils_Date::getAbbrMonthNames();

      foreach ($abbrMonthNames as $monthKey => $monthName) {
        $val = $chartInfoMonthly['By Month'][$monthKey] ?? 0;

        // don't include zero value month.
        if (!$val && ($chartType != 'bvg')) {
          continue;
        }

        //build the params for chart.
        $chartData['by_month']['values'][$monthName] = $val;
      }
      $chartData['by_month']['legend'] = ts('By Month - %1', [1 => $selectedYear]);

      // handle onclick event.
      $chartData['by_month']['on_click_fun_name'] = 'byMonthOnClick';
      $chartData['by_month']['yname'] = ts('Contribution');
    }

    //take contribution information by yearly
    $chartInfoYearly = $this->getContributionTotalsByYear();

    if (!empty($chartInfoYearly)) {
      $chartData['by_year']['legend'] = ts('By Year');
      $chartData['by_year']['values'] = $chartInfoYearly;

      // handle onclick event.
      $chartData['by_year']['on_click_fun_name'] = 'byYearOnClick';
      $chartData['by_year']['yname'] = ts('Total Amount');
    }
    $this->assign('hasContributions', !empty($chartInfoYearly));

    // process the data.
    $chartCnt = 1;

    $monthlyChart = $yearlyChart = FALSE;

    foreach ($chartData as $chartKey => & $values) {
      $chartValues = $values['values'] ?? NULL;

      if (!is_array($chartValues) || empty($chartValues)) {
        continue;
      }
      if ($chartKey === 'by_year') {
        $yearlyChart = TRUE;
        if (!empty($config->fiscalYearStart) && ($config->fiscalYearStart['M'] !== 1 || $config->fiscalYearStart['d'] !== 1)) {
          $values['xLabelAngle'] = 45;
        }
        else {
          $values['xLabelAngle'] = 0;
        }
      }
      if ($chartKey === 'by_month') {
        $monthlyChart = TRUE;
      }

      $values['divName'] = "chart_{$chartKey}";
      $funName = ($chartType == 'bvg') ? 'barChart' : 'pieChart';

      // build the chart objects.
      $values['object'] = CRM_Utils_Chart::$funName($values);

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
          $year = substr($index, 0, 4);
          $year = is_numeric($year) ? (int) $year : date('Y');
          if (!empty($config->fiscalYearStart) && ($config->fiscalYearStart['M'] != 1 || $config->fiscalYearStart['d'] != 1)) {
            $startDate = date('Ymd', mktime(0, 0, 0, $config->fiscalYearStart['M'], $config->fiscalYearStart['d'], $year));
            $endDate = date('Ymd', mktime(0, 0, 0, $config->fiscalYearStart['M'], $config->fiscalYearStart['d'], $year + 1));
          }
          else {
            $startDate = CRM_Utils_Date::format(['Y' => $year]);
            $endDate = date('Ymd', mktime(0, 0, 0, 13, 0, $year));
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
    $this->assign('hasChart', !empty($chartData));
    $this->assign('chartData', json_encode($chartData ?? []));
  }

  /**
   * Get the contribution details by month of the year.
   *
   * @param int $param
   *   Year.
   *
   * @return array|null
   *   associated array
   */
  private function contributionChartMonthly($param) {
    if ($param) {
      $param = [1 => [$param, 'Integer']];
    }
    else {
      $param = date("Y");
      $param = [1 => [$param, 'Integer']];
    }

    $query = "
    SELECT   sum(contrib.total_amount) AS ctAmt,
             MONTH( contrib.receive_date) AS contribMonth
      FROM   civicrm_contribution AS contrib
INNER JOIN   civicrm_contact AS contact ON ( contact.id = contrib.contact_id )
     WHERE   contrib.contact_id = contact.id
       AND   contrib.is_test = 0
       AND   contrib.contribution_status_id = 1
       AND   date_format(contrib.receive_date,'%Y') = %1
       AND   contact.is_deleted = 0
  GROUP BY   contribMonth
  ORDER BY   month(contrib.receive_date)";

    $dao = CRM_Core_DAO::executeQuery($query, $param);

    $params = NULL;
    while ($dao->fetch()) {
      if ($dao->contribMonth) {
        $params['By Month'][$dao->contribMonth] = $dao->ctAmt;
      }
    }
    return $params;
  }

  /**
   * Get the contribution details by year.
   *
   * @return array|null
   *   associated array
   */
  private function getContributionTotalsByYear() {
    if (!isset($this->contributionsByYear)) {
      $config = CRM_Core_Config::singleton();
      $yearClause = "year(contrib.receive_date) as contribYear";
      if (!empty($config->fiscalYearStart) && ($config->fiscalYearStart['M'] != 1 || $config->fiscalYearStart['d'] != 1)) {
        $yearClause = "CASE
        WHEN (MONTH(contrib.receive_date)>= " . $config->fiscalYearStart['M'] . "
          && DAYOFMONTH(contrib.receive_date)>= " . $config->fiscalYearStart['d'] . " )
          THEN
            concat(YEAR(contrib.receive_date), '-',YEAR(contrib.receive_date)+1)
          ELSE
            concat(YEAR(contrib.receive_date)-1,'-', YEAR(contrib.receive_date))
        END AS contribYear";
      }

      $query = "
    SELECT   sum(contrib.total_amount) AS ctAmt,
             {$yearClause}
      FROM   civicrm_contribution AS contrib
INNER JOIN   civicrm_contact contact ON ( contact.id = contrib.contact_id )
     WHERE   contrib.is_test = 0
       AND   contrib.contribution_status_id = 1
       AND   contact.is_deleted = 0
  GROUP BY   contribYear
  ORDER BY   contribYear";
      $dao = CRM_Core_DAO::executeQuery($query);

      $this->contributionsByYear = [];
      while ($dao->fetch()) {
        if (!empty($dao->contribYear)) {
          $this->contributionsByYear[$dao->contribYear] = $dao->ctAmt;
        }
      }
    }
    return $this->contributionsByYear;
  }

}
