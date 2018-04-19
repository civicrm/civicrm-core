<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

require_once 'packages/OpenFlashChart/php-ofc-library/open-flash-chart.php';

/**
 * Build various graphs using Open Flash Chart library.
 */
class CRM_Utils_OpenFlashChart {

  /**
   * Colours.
   * @var array
   */
  private static $_colours = array(
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
  );

  /**
   * Build The Bar Gharph.
   *
   * @param array $params
   *   Assoc array of name/value pairs.
   *
   * @return object
   *   $chart   object of open flash chart.
   */
  public static function &barChart(&$params) {
    $chart = NULL;
    if (empty($params)) {
      return $chart;
    }
    if (empty($params['multiValues'])) {
      $params['multiValues'] = array($params['values']);
    }

    $values = CRM_Utils_Array::value('multiValues', $params);
    if (!is_array($values) || empty($values)) {
      return $chart;
    }

    // get the required data.
    $chartTitle = !empty($params['legend']) ? $params['legend'] : ts('Bar Chart');

    $xValues = $yValues = array();
    $xValues = array_keys($values[0]);
    $yValues = array_values($values[0]);

    // set y axis parameters.
    $yMin = 0;

    // calculate max scale for graph.
    $yMax = ceil(max($yValues));
    if ($mod = $yMax % (str_pad(5, strlen($yMax) - 1, 0))) {
      $yMax += str_pad(5, strlen($yMax) - 1, 0) - $mod;
    }
    $ySteps = $yMax / 5;

    $bars = array();
    $config = CRM_Core_Config::singleton();
    $symbol = $config->defaultCurrencySymbol;
    foreach ($values as $barCount => $barVal) {
      $bars[$barCount] = new bar_glass();

      $yValues = array_values($barVal);
      foreach ($yValues as &$yVal) {
        // type casting is required for chart to render values correctly
        $yVal = (double) $yVal;
      }
      $bars[$barCount]->set_values($yValues);
      if ($barCount > 0) {
        // FIXME: for bars > 2, we'll need to come out with other colors
        $bars[$barCount]->colour('#BF3B69');
      }

      if ($barKey = CRM_Utils_Array::value($barCount, CRM_Utils_Array::value('barKeys', $params))) {
        $bars[$barCount]->key($barKey, 12);
      }

      // call user define function to handle on click event.
      if ($onClickFunName = CRM_Utils_Array::value('on_click_fun_name', $params)) {
        $bars[$barCount]->set_on_click($onClickFunName);
      }

      // get the currency to set in tooltip.
      $tooltip = CRM_Utils_Array::value('tip', $params, "$symbol #val#");
      $bars[$barCount]->set_tooltip($tooltip);
    }

    // create x axis label obj.
    $xLabels = new x_axis_labels();
    // set_labels function requires xValues array of string or x_axis_label
    // so type casting array values to string values
    array_walk($xValues, function (&$value, $index) {
      $value = (string) $value;
    });
    $xLabels->set_labels($xValues);

    // set angle for labels.
    if ($xLabelAngle = CRM_Utils_Array::value('xLabelAngle', $params)) {
      $xLabels->rotate($xLabelAngle);
    }

    // create x axis obj.
    $xAxis = new x_axis();
    $xAxis->set_labels($xLabels);

    // create y axis and set range.
    $yAxis = new y_axis();
    $yAxis->set_range($yMin, $yMax, $ySteps);

    // create chart title obj.
    $title = new title($chartTitle);

    // create chart.
    $chart = new open_flash_chart();

    // add x axis w/ labels to chart.
    $chart->set_x_axis($xAxis);

    // add y axis values to chart.
    $chart->add_y_axis($yAxis);

    // set title to chart.
    $chart->set_title($title);

    // add bar element to chart.
    foreach ($bars as $bar) {
      $chart->add_element($bar);
    }

    // add x axis legend.
    if ($xName = CRM_Utils_Array::value('xname', $params)) {
      $xLegend = new x_legend($xName);
      $xLegend->set_style("{font-size: 13px; color:#000000; font-family: Verdana; text-align: center;}");
      $chart->set_x_legend($xLegend);
    }

    // add y axis legend.
    if ($yName = CRM_Utils_Array::value('yname', $params)) {
      $yLegend = new y_legend($yName);
      $yLegend->set_style("{font-size: 13px; color:#000000; font-family: Verdana; text-align: center;}");
      $chart->set_y_legend($yLegend);
    }

    return $chart;
  }

  /**
   * Build The Pie Gharph.
   *
   * @param array $params
   *   Assoc array of name/value pairs.
   *
   * @return object
   *   $chart   object of open flash chart.
   */
  public static function &pieChart(&$params) {
    $chart = NULL;
    if (empty($params)) {
      return $chart;
    }
    $allValues = CRM_Utils_Array::value('values', $params);
    if (!is_array($allValues) || empty($allValues)) {
      return $chart;
    }

    // get the required data.
    $values = array();
    foreach ($allValues as $label => $value) {
      $values[] = new pie_value((double) $value, $label);
    }
    $graphTitle = !empty($params['legend']) ? $params['legend'] : ts('Pie Chart');

    // get the currency.
    $config = CRM_Core_Config::singleton();
    $symbol = $config->defaultCurrencySymbol;

    $pie = new pie();
    $pie->radius(100);

    // call user define function to handle on click event.
    if ($onClickFunName = CRM_Utils_Array::value('on_click_fun_name', $params)) {
      $pie->on_click($onClickFunName);
    }

    $pie->set_start_angle(35);
    $pie->add_animation(new pie_fade());
    $pie->add_animation(new pie_bounce(2));

    // set the tooltip.
    $tooltip = CRM_Utils_Array::value('tip', $params, "Amount is $symbol #val# of $symbol #total# <br>#percent#");
    $pie->set_tooltip($tooltip);

    // set colours.
    $pie->set_colours(self::$_colours);

    $pie->set_values($values);

    // create chart.
    $chart = new open_flash_chart();

    // create chart title obj.
    $title = new title($graphTitle);
    $chart->set_title($title);

    $chart->add_element($pie);
    $chart->x_axis = NULL;

    return $chart;
  }

  /**
   * Build The 3-D Bar Gharph.
   *
   * @param array $params
   *   Assoc array of name/value pairs.
   *
   * @return object
   *   $chart   object of open flash chart.
   */
  public static function &bar_3dChart(&$params) {
    $chart = NULL;
    if (empty($params)) {
      return $chart;
    }

    // $params['values'] should contains the values for each
    // criteria defined in $params['criteria']
    $values = CRM_Utils_Array::value('values', $params);
    $criteria = CRM_Utils_Array::value('criteria', $params);
    if (!is_array($values) || empty($values) || !is_array($criteria) || empty($criteria)) {
      return $chart;
    }

    // get the required data.
    $xReferences = $xValueLabels = $xValues = $yValues = array();

    foreach ($values as $xVal => $yVal) {
      if (!is_array($yVal) || empty($yVal)) {
        continue;
      }

      $xValueLabels[] = (string) $xVal;
      foreach ($criteria as $criteria) {
        $xReferences[$criteria][$xVal] = (double) CRM_Utils_Array::value($criteria, $yVal, 0);
        $yValues[] = (double) CRM_Utils_Array::value($criteria, $yVal, 0);
      }
    }

    if (empty($xReferences)) {

      return $chart;

    }

    // get the currency.
    $config = CRM_Core_Config::singleton();
    $symbol = $config->defaultCurrencySymbol;

    // set the tooltip.
    $tooltip = CRM_Utils_Array::value('tip', $params, "$symbol #val#");

    $count = 0;
    foreach ($xReferences as $criteria => $values) {
      $toolTipVal = $tooltip;
      // for separate tooltip for each criteria
      if (is_array($tooltip)) {
        $toolTipVal = CRM_Utils_Array::value($criteria, $tooltip, "$symbol #val#");
      }

      // create bar_3d object
      $xValues[$count] = new bar_3d();
      // set colour pattel
      $xValues[$count]->set_colour(self::$_colours[$count]);
      // define colur pattel with bar criteria
      $xValues[$count]->key((string) $criteria, 12);
      // define bar chart values
      $xValues[$count]->set_values(array_values($values));

      // set tooltip
      $xValues[$count]->set_tooltip($toolTipVal);
      $count++;
    }

    $chartTitle = !empty($params['legend']) ? $params['legend'] : ts('Bar Chart');

    // set y axis parameters.
    $yMin = 0;

    // calculate max scale for graph.
    $yMax = ceil(max($yValues));
    if ($mod = $yMax % (str_pad(5, strlen($yMax) - 1, 0))) {
      $yMax += str_pad(5, strlen($yMax) - 1, 0) - $mod;
    }

    // if max value of y-axis <= 0, then set default values
    if ($yMax <= 0) {
      $ySteps = 1;
      $yMax = 5;
    }
    else {
      $ySteps = $yMax / 5;
    }

    // create x axis label obj.
    $xLabels = new x_axis_labels();
    $xLabels->set_labels($xValueLabels);

    // set angle for labels.
    if ($xLabelAngle = CRM_Utils_Array::value('xLabelAngle', $params)) {
      $xLabels->rotate($xLabelAngle);
    }

    // create x axis obj.
    $xAxis = new x_axis();
    $xAxis->set_labels($xLabels);

    // create y axis and set range.
    $yAxis = new y_axis();
    $yAxis->set_range($yMin, $yMax, $ySteps);

    // create chart title obj.
    $title = new title($chartTitle);

    // create chart.
    $chart = new open_flash_chart();

    // add x axis w/ labels to chart.
    $chart->set_x_axis($xAxis);

    // add y axis values to chart.
    $chart->add_y_axis($yAxis);

    // set title to chart.
    $chart->set_title($title);

    foreach ($xValues as $bar) {
      // add bar element to chart.
      $chart->add_element($bar);
    }

    // add x axis legend.
    if ($xName = CRM_Utils_Array::value('xname', $params)) {
      $xLegend = new x_legend($xName);
      $xLegend->set_style("{font-size: 13px; color:#000000; font-family: Verdana; text-align: center;}");
      $chart->set_x_legend($xLegend);
    }

    // add y axis legend.
    if ($yName = CRM_Utils_Array::value('yname', $params)) {
      $yLegend = new y_legend($yName);
      $yLegend->set_style("{font-size: 13px; color:#000000; font-family: Verdana; text-align: center;}");
      $chart->set_y_legend($yLegend);
    }

    return $chart;
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
    $chartData = $dateKeys = array();
    $intervalLabels = array(
      'year' => ts('Yearly'),
      'fiscalyear' => ts('Yearly (Fiscal)'),
      'month' => ts('Monthly'),
      'quarter' => ts('Quarterly'),
      'week' => ts('Weekly'),
      'yearweek' => ts('Weekly'),
    );

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
        $legend = ts("%1", array(1 => $label));
        if (!empty($intervalLabels[$lcInterval])) {
          $legend = $intervalLabels[$lcInterval];
        }
        break;
    }

    if (!empty($dateKeys)) {
      $graph = array();
      if (!array_key_exists('multiValue', $rows)) {
        $rows['multiValue'] = array($rows['value']);
      }
      foreach ($rows['multiValue'] as $key => $val) {
        $graph[$key] = array_combine($dateKeys, $rows['multiValue'][$key]);
      }
      $chartData = array(
        'legend' => "$legend " . CRM_Utils_Array::value('legend', $rows, ts('Contribution')) . ' ' . ts('Summary'),
        'values' => $graph[0],
        'multiValues' => $graph,
        'barKeys' => CRM_Utils_Array::value('barKeys', $rows, array()),
      );
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
    foreach (array(
               'xSize',
               'ySize',
               'divName',
             ) as $f) {
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

    $chartData = array(
      'values' => $graph,
      'legend' => $chartInfo['legend'],
      'xname' => $chartInfo['xname'],
      'yname' => $chartInfo['yname'],
    );

    // rotate the x labels.
    $chartData['xLabelAngle'] = CRM_Utils_Array::value('xLabelAngle', $chartInfo, 20);
    if (!empty($chartInfo['tip'])) {
      $chartData['tip'] = $chartInfo['tip'];
    }

    // carry some chart params if pass.
    foreach (array(
               'xSize',
               'ySize',
               'divName',
             ) as $f) {
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
    $openFlashChart = array();
    if ($chart && is_array($params) && !empty($params)) {
      // build the chart objects.
      $chartObj = CRM_Utils_OpenFlashChart::$chart($params);

      $openFlashChart = array();
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

        $openFlashChart["chart_{$uniqueId}"]['size'] = array('xSize' => $xSize, 'ySize' => $ySize);
        $openFlashChart["chart_{$uniqueId}"]['object'] = $chartObj;

        // assign chart data to template
        $template = CRM_Core_Smarty::singleton();
        $template->assign('uniqueId', $uniqueId);
        $template->assign("openFlashChartData", json_encode($openFlashChart));
      }
    }

    return $openFlashChart;
  }

}
