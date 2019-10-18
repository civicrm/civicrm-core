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
 * Build various graphs using the dc chart library.
 */
class CRM_Utils_Chart {

  /**
   * Colours.
   * @var array
   */
  private static $_colours = [
    "#C3CC38",
    "#C8B935",
    "#CEA632",
    "#D3932F",
    "#D9802C",
    "#FA6900",
    "#DC9B57",
    "#F78F01",
    "#5AB56E",
    "#6F8069",
    "#C92200",
    "#EB6C5C",
  ];

  /**
   * Build The Bar Gharph.
   *
   * @param array $params
   *   Assoc array of name/value pairs.
   *
   * @return object
   *   $chart   object data used for client-side chart rendering (currently with dc chart library).
   */
  public static function barChart($params) {
    $output = static::commonParamsManipulation($params);
    if (empty($output)) {
      return NULL;
    }
    $output['type'] = 'barchart';
    // Default title
    $output += ['title' => ts('Bar Chart')];

    // ? Not sure what reports use this, but it's not implemented.
    // call user define function to handle on click event.
    // if ($onClickFunName = CRM_Utils_Array::value('on_click_fun_name', $params)) {
    //  $bars[$barCount]->set_on_click($onClickFunName);
    // }

    //// get the currency to set in tooltip.
    //$tooltip = CRM_Utils_Array::value('tip', $params, "$symbol #val#");

    return $output;
  }

  /**
   * Build a pie chart.
   *
   * @param array $params
   *   Assoc array of name/value pairs.
   *
   * @return array
   */
  public static function pieChart($params) {
    $output = static::commonParamsManipulation($params);
    if (empty($output)) {
      return NULL;
    }
    $output['type'] = 'piechart';
    $output += ['title' => ts('Pie Chart')];

    // ? Not sure what reports use this, but it's not implemented.
    // call user define function to handle on click event.
    // if ($onClickFunName = CRM_Utils_Array::value('on_click_fun_name', $params)) {
    //  $bars[$barCount]->set_on_click($onClickFunName);
    // }

    //// get the currency to set in tooltip.
    //$tooltip = CRM_Utils_Array::value('tip', $params, "$symbol #val#");

    return $output;
  }

  /**
   * Common data manipulation for charts.
   *
   * @param array $params
   *   Assoc array of name/value pairs.
   *
   * @return array
   */
  public static function commonParamsManipulation($params) {
    if (empty($params)) {
      return NULL;
    }
    $output = [];
    if (empty($params['multiValues'])) {
      $params['multiValues'] = [$params['values']];
    }

    $output['values'] = [];
    foreach ($params['multiValues'] as $i => $dataSet) {
      $output['values'][$i] = [];
      foreach ($dataSet as $k => $v) {
        $output['values'][$i][] = ['label' => $k, 'value' => (double) $v];
      }
    }
    if (!$output['values']) {
      return NULL;
    }

    // Ensure there's a legend (title)
    if (!empty($params['legend'])) {
      $output['title'] = $params['legend'];
    }

    $output['symbol'] = CRM_Core_BAO_Country::defaultCurrencySymbol();

    // ? Not sure what reports use this, but it's not implemented.
    // call user define function to handle on click event.
    // if ($onClickFunName = CRM_Utils_Array::value('on_click_fun_name', $params)) {
    //  $bars[$barCount]->set_on_click($onClickFunName);
    // }

    //// get the currency to set in tooltip.
    //$tooltip = CRM_Utils_Array::value('tip', $params, "$symbol #val#");

    return $output;
  }

  /**
   * @param $rows
   * @param $chart
   * @param $interval
   *
   * @return array
   */
  public static function chart($rows, $chart, $interval) {
    $lcInterval = strtolower($interval);
    $label = ucfirst($lcInterval);
    $chartData = $dateKeys = [];
    $intervalLabels = [
      'year' => ts('Yearly'),
      'fiscalyear' => ts('Yearly (Fiscal)'),
      'month' => ts('Monthly'),
      'quarter' => ts('Quarterly'),
      'week' => ts('Weekly'),
      'yearweek' => ts('Weekly'),
    ];

    switch ($lcInterval) {
      case 'month':
      case 'quarter':
      case 'week':
      case 'yearweek':
        foreach ($rows['receive_date'] as $key => $val) {
          list($year, $month) = explode('-', $val);
          $dateKeys[] = substr($rows[$interval][$key], 0, 3) . ' of ' . $year;
        }
        $legend = $intervalLabels[$lcInterval];
        break;

      default:
        foreach ($rows['receive_date'] as $key => $val) {
          list($year, $month) = explode('-', $val);
          $dateKeys[] = $year;
        }
        $legend = ts("%1", [1 => $label]);
        if (!empty($intervalLabels[$lcInterval])) {
          $legend = $intervalLabels[$lcInterval];
        }
        break;
    }

    if (!empty($dateKeys)) {
      $graph = [];
      if (!array_key_exists('multiValue', $rows)) {
        $rows['multiValue'] = [$rows['value']];
      }
      foreach ($rows['multiValue'] as $key => $val) {
        $graph[$key] = array_combine($dateKeys, $rows['multiValue'][$key]);
      }
      $chartData = [
        'legend' => "$legend " . CRM_Utils_Array::value('legend', $rows, ts('Contribution')) . ' ' . ts('Summary'),
        'values' => $graph[0],
        'multiValues' => $graph,
        'barKeys' => CRM_Utils_Array::value('barKeys', $rows, []),
      ];
    }

    // rotate the x labels.
    $chartData['xLabelAngle'] = CRM_Utils_Array::value('xLabelAngle', $rows, 0);
    if (!empty($rows['tip'])) {
      $chartData['tip'] = $rows['tip'];
    }

    // legend
    $chartData['xname'] = CRM_Utils_Array::value('xname', $rows);
    $chartData['yname'] = CRM_Utils_Array::value('yname', $rows);

    // carry some chart params if pass.
    foreach ([
      'xSize',
      'ySize',
      'divName',
    ] as $f) {
      if (!empty($rows[$f])) {
        $chartData[$f] = $rows[$f];
      }
    }

    return self::buildChart($chartData, $chart);
  }

  /**
   * @param $rows
   * @param $chart
   * @param $interval
   * @param $chartInfo
   *
   * @return array
   */
  public static function reportChart($rows, $chart, $interval, &$chartInfo) {
    foreach ($interval as $key => $val) {
      $graph[$val] = $rows['value'][$key];
    }

    $chartData = [
      'values' => $graph,
      'legend' => $chartInfo['legend'],
      'xname' => $chartInfo['xname'],
      'yname' => $chartInfo['yname'],
    ];

    // rotate the x labels.
    $chartData['xLabelAngle'] = CRM_Utils_Array::value('xLabelAngle', $chartInfo, 20);
    if (!empty($chartInfo['tip'])) {
      $chartData['tip'] = $chartInfo['tip'];
    }

    // carry some chart params if pass.
    foreach ([
      'xSize',
      'ySize',
      'divName',
    ] as $f) {
      if (!empty($rows[$f])) {
        $chartData[$f] = $rows[$f];
      }
    }

    return self::buildChart($chartData, $chart);
  }

  /**
   * @param array $params
   * @param $chart
   *
   * @return array
   */
  public static function buildChart(&$params, $chart) {
    $theChart = [];
    if ($chart && is_array($params) && !empty($params)) {
      // build the chart objects.
      $chartObj = CRM_Utils_Chart::$chart($params);

      if ($chartObj) {
        // calculate chart size.
        $xSize = CRM_Utils_Array::value('xSize', $params, 400);
        $ySize = CRM_Utils_Array::value('ySize', $params, 300);
        if ($chart == 'barChart') {
          $ySize = CRM_Utils_Array::value('ySize', $params, 250);
          $xSize = 60 * count($params['values']);
          // hack to show tooltip.
          if ($xSize < 200) {
            $xSize = (count($params['values']) > 1) ? 100 * count($params['values']) : 170;
          }
          elseif ($xSize > 600 && count($params['values']) > 1) {
            $xSize = (count($params['values']) + 400 / count($params['values'])) * count($params['values']);
          }
        }

        // generate unique id for this chart instance
        $uniqueId = md5(uniqid(rand(), TRUE));

        $theChart["chart_{$uniqueId}"]['size'] = ['xSize' => $xSize, 'ySize' => $ySize];
        $theChart["chart_{$uniqueId}"]['object'] = $chartObj;

        // assign chart data to template
        $template = CRM_Core_Smarty::singleton();
        $template->assign('uniqueId', $uniqueId);
        $template->assign("chartData", json_encode($theChart ?? []));
      }
    }

    return $theChart;
  }

}
