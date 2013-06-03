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

require_once 'packages/OpenFlashChart/php-ofc-library/open-flash-chart.php';

/**
 * Build various graphs using Open Flash Chart library.
 */
class CRM_Utils_OpenFlashChart {

  /**
   * colours.
   * @var array
   * @static
   */
  private static $_colours = array(
    "#C3CC38", "#C8B935", "#CEA632", "#D3932F",
    "#D9802C", "#FA6900", "#DC9B57", "#F78F01",
    "#5AB56E", "#6F8069", "#C92200", "#EB6C5C",
  );

  /**
   * Build The Bar Gharph.
   *
   * @param  array  $params  assoc array of name/value pairs
   *
   * @return object $chart   object of open flash chart.
   * @static
   */
  static function &barChart(&$params) {
    $chart = NULL;
    if (empty($params)) {
      return $chart;
    }

    $values = CRM_Utils_Array::value('values', $params);
    if (!is_array($values) || empty($values)) {
      return $chart;
    }

    // get the required data.
    $xValues = $yValues = array();
    foreach ($values as $xVal => $yVal) {
      $yValues[] = (double)$yVal;

      // we has to have x values as string.
      $xValues[] = (string)$xVal;
    }
    $chartTitle = CRM_Utils_Array::value('legend', $params) ? $params['legend'] : ts('Bar Chart');

    //set y axis parameters.
    $yMin = 0;

    // calculate max scale for graph.
    $yMax = ceil(max($yValues));
    if ($mod = $yMax % (str_pad(5, strlen($yMax) - 1, 0))) {
      $yMax += str_pad(5, strlen($yMax) - 1, 0) - $mod;
    }
    $ySteps = $yMax / 5;

    // $bar = new bar( );
    // glass seem to be more cool
    $bar = new bar_glass();

    //set values.
    $bar->set_values($yValues);

    // call user define function to handle on click event.
    if ($onClickFunName = CRM_Utils_Array::value('on_click_fun_name', $params)) {
      $bar->set_on_click($onClickFunName);
    }

    // get the currency.
    $config = CRM_Core_Config::singleton();
    $symbol = $config->defaultCurrencySymbol;

    // set the tooltip.
    $tooltip = CRM_Utils_Array::value('tip', $params, "$symbol #val#");
    $bar->set_tooltip($tooltip);

    // create x axis label obj.
    $xLabels = new x_axis_labels();
    $xLabels->set_labels($xValues);

    // set angle for labels.
    if ($xLabelAngle = CRM_Utils_Array::value('xLabelAngle', $params)) {
      $xLabels->rotate($xLabelAngle);
    }

    // create x axis obj.
    $xAxis = new x_axis();
    $xAxis->set_labels($xLabels);

    //create y axis and set range.
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
    $chart->add_element($bar);

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
   * @param  array  $params  assoc array of name/value pairs
   *
   * @return object $chart   object of open flash chart.
   * @static
   */
  static function &pieChart(&$params) {
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
      $values[] = new pie_value((double)$value, $label);
    }
    $graphTitle = CRM_Utils_Array::value('legend', $params) ? $params['legend'] : ts('Pie Chart');

    //get the currency.
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

    //create chart.
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
   * @param  array  $params  assoc array of name/value pairs
   *
   * @return object $chart   object of open flash chart.
   * @static
   */
  static function &bar_3dChart(&$params) {
    $chart = NULL;
    if (empty($params)) {
      return $chart;
    }

    // $params['values'] should contains the values for each
    // criteria defined in $params['criteria']
    $values = CRM_Utils_Array::value('values', $params);
    $criterias = CRM_Utils_Array::value('criteria', $params);
    if (!is_array($values) || empty($values) || !is_array($criterias) || empty($criterias)) {
      return $chart;
    }

    // get the required data.
    $xReferences = $xValueLabels = $xValues = $yValues = array();

    foreach ($values as $xVal => $yVal) {
      if (!is_array($yVal) || empty($yVal)) {
        continue;
      }

      $xValueLabels[] = (string)$xVal;
      foreach ($criterias as $criteria) {
        $xReferences[$criteria][$xVal] = (double)CRM_Utils_Array::value($criteria, $yVal, 0);
        $yValues[] = (double)CRM_Utils_Array::value($criteria, $yVal, 0);
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
      // for seperate tooltip for each criteria
      if (is_array($tooltip)) {
        $toolTipVal = CRM_Utils_Array::value($criteria, $tooltip, "$symbol #val#");
      }

      // create bar_3d object
      $xValues[$count] = new bar_3d();
      // set colour pattel
      $xValues[$count]->set_colour(self::$_colours[$count]);
      // define colur pattel with bar criterias
      $xValues[$count]->key((string)$criteria, 12);
      // define bar chart values
      $xValues[$count]->set_values(array_values($values));

      // set tooltip
      $xValues[$count]->set_tooltip($toolTipVal);
      $count++;
    }

    $chartTitle = CRM_Utils_Array::value('legend', $params) ? $params['legend'] : ts('Bar Chart');

    //set y axis parameters.
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

    //create y axis and set range.
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

  static function chart($rows, $chart, $interval) {
    $chartData = array();

    switch ($interval) {
      case 'Month':
        foreach ($rows['receive_date'] as $key => $val) {
          list($year, $month) = explode('-', $val);
          $graph[substr($rows['Month'][$key], 0, 3) . ' ' . $year] = $rows['value'][$key];
        }

        $chartData = array(
          'values' => $graph,
          'legend' => ts('Monthly Contribution Summary'),
        );
        break;

      case 'Quarter':
        foreach ($rows['receive_date'] as $key => $val) {
          list($year, $month) = explode('-', $val);
          $graph['Quarter ' . $rows['Quarter'][$key] . ' of ' . $year] = $rows['value'][$key];
        }

        $chartData = array(
          'values' => $graph,
          'legend' => ts('Quarterly Contribution Summary'),
        );
        break;

      case 'Week':
        foreach ($rows['receive_date'] as $key => $val) {
          list($year, $month) = explode('-', $val);
          $graph['Week ' . $rows['Week'][$key] . ' of ' . $year] = $rows['value'][$key];
        }

        $chartData = array(
          'values' => $graph,
          'legend' => ts('Weekly Contribution Summary'),
        );
        break;

      case 'Year':
        foreach ($rows['receive_date'] as $key => $val) {
          list($year, $month) = explode('-', $val);
          $graph[$year] = $rows['value'][$key];
        }
        $chartData = array(
          'values' => $graph,
          'legend' => ts('Yearly Contribution Summary'),
        );
        break;
    }

    // rotate the x labels.
    $chartData['xLabelAngle'] = CRM_Utils_Array::value('xLabelAngle', $rows, 20);
    if (CRM_Utils_Array::value('tip', $rows)) {
      $chartData['tip'] = $rows['tip'];
    }

    //legend
    $chartData['xname'] = CRM_Utils_Array::value('xname', $rows);
    $chartData['yname'] = CRM_Utils_Array::value('yname', $rows);

    // carry some chart params if pass.
    foreach (array(
      'xSize', 'ySize', 'divName') as $f) {
      if (CRM_Utils_Array::value($f, $rows)) {
        $chartData[$f] = $rows[$f];
      }
    }

    return self::buildChart($chartData, $chart);
  }

  static function reportChart($rows, $chart, $interval, &$chartInfo) {
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
    if (CRM_Utils_Array::value('tip', $chartInfo)) {
      $chartData['tip'] = $chartInfo['tip'];
    }

    // carry some chart params if pass.
    foreach (array(
      'xSize', 'ySize', 'divName') as $f) {
      if (CRM_Utils_Array::value($f, $rows)) {
        $chartData[$f] = $rows[$f];
      }
    }

    return self::buildChart($chartData, $chart);
  }

  static function buildChart(&$params, $chart) {
    $openFlashChart = array();
    if ($chart && is_array($params) && !empty($params)) {
      // build the chart objects.
      eval("\$chartObj = CRM_Utils_OpenFlashChart::" . $chart . '( $params );');

      $openFlashChart = array();
      if ($chartObj) {
        // calculate chart size.
        $xSize = CRM_Utils_Array::value('xSize', $params, 400);
        $ySize = CRM_Utils_Array::value('ySize', $params, 300);
        if ($chart == 'barChart') {
          $ySize = CRM_Utils_Array::value('ySize', $params, 250);
          $xSize = 60 * count($params['values']);
          //hack to show tooltip.
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

