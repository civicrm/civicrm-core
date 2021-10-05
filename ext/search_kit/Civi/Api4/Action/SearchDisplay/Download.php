<?php

namespace Civi\Api4\Action\SearchDisplay;

use League\Csv\Writer;

/**
 * Download the results of a SearchDisplay as a spreadsheet.
 *
 * Note: unlike other APIs this action will directly output a file
 * if 'format' is set to anything other than 'array'.
 *
 * @package Civi\Api4\Action\SearchDisplay
 */
class Download extends AbstractRunAction {

  /**
   * Requested file format.
   *
   * 'array' will return a normal api result, with table headers as the first row.
   * 'csv', etc. will directly output a file to the browser.
   *
   * @var string
   * @required
   * @options array,csv
   */
  protected $format = 'array';

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \API_Exception
   */
  protected function processResult(\Civi\Api4\Generic\Result $result) {
    $entityName = $this->savedSearch['api_entity'];
    $apiParams =& $this->savedSearch['api_params'];
    $settings = $this->display['settings'];

    // Displays are only exportable if they have actions enabled
    if (empty($settings['actions'])) {
      \CRM_Utils_System::permissionDenied();
    }

    // Force limit if the display has no pager
    if (!isset($settings['pager']) && !empty($settings['limit'])) {
      $apiParams['limit'] = $settings['limit'];
    }
    $apiParams['orderBy'] = $this->getOrderByFromSort();
    $this->augmentSelectClause($apiParams);

    $this->applyFilters();

    $apiResult = civicrm_api4($entityName, 'get', $apiParams);

    $rows = $this->formatResult($apiResult);

    $columns = [];
    foreach ($this->display['settings']['columns'] as $col) {
      $col += ['type' => NULL, 'label' => '', 'rewrite' => FALSE];
      if ($col['type'] === 'field' && !empty($col['key'])) {
        $columns[] = $col;
      }
    }

    // This weird little API spits out a file and exits instead of returning a result
    $fileName = \CRM_Utils_File::makeFilenameWithUnicode($this->display['label']) . '.' . $this->format;

    switch ($this->format) {
      case 'array':
        $result[] = $columns;
        foreach ($rows as $data) {
          $row = [];
          foreach ($columns as $col) {
            $row[] = $this->formatColumnValue($col, $data);
          }
          $result[] = $row;
        }
        return;

      case 'csv':
        $this->outputCSV($rows, $columns, $fileName);
        break;
    }

    \CRM_Utils_System::civiExit();
  }

  /**
   * Outputs csv format directly to browser for download
   * @param array $rows
   * @param array $columns
   * @param string $fileName
   */
  private function outputCSV(array $rows, array $columns, string $fileName) {
    $csv = Writer::createFromFileObject(new \SplTempFileObject());
    $csv->setOutputBOM(Writer::BOM_UTF8);

    // Header row
    $csv->insertOne(array_column($columns, 'label'));

    foreach ($rows as $data) {
      $row = [];
      foreach ($columns as $col) {
        $row[] = $this->formatColumnValue($col, $data);
      }
      $csv->insertOne($row);
    }
    // Echo headers and content directly to browser
    $csv->output($fileName);
  }

  /**
   * Returns final formatted column value
   *
   * @param array $col
   * @param array $data
   * @return string
   */
  protected function formatColumnValue(array $col, array $data) {
    $val = $col['rewrite'] ?: $data[$col['key']]['view'] ?? '';
    if ($col['rewrite']) {
      foreach ($data as $k => $v) {
        $val = str_replace("[$k]", $v['view'], $val);
      }
    }
    return is_array($val) ? implode(', ', $val) : $val;
  }

}
