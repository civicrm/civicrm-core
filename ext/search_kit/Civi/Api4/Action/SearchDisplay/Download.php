<?php

namespace Civi\Api4\Action\SearchDisplay;

use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Download the results of a SearchDisplay as a spreadsheet.
 *
 * Note: unlike other APIs this action will directly output a file
 * if 'format' is set to anything other than 'array'.
 *
 * @method $this setFormat(string $format)
 * @method string getFormat()
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
   * @options array,csv,xlsx,ods,pdf
   */
  protected $format = 'array';

  private $formats = [
    'xlsx' => [
      'writer' => 'Xlsx',
      'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    'ods' => [
      'writer' => 'Ods',
      'mime' => 'application/vnd.oasis.opendocument.spreadsheet',
    ],
    'pdf' => [
      'writer' => 'Dompdf',
      'mime' => 'application/pdf',
    ],
  ];

  /**
   * @param \Civi\Api4\Result\SearchDisplayRunResult $result
   * @throws \CRM_Core_Exception
   */
  protected function processResult(\Civi\Api4\Result\SearchDisplayRunResult $result) {
    $entityName = $this->savedSearch['api_entity'];
    $apiParams =& $this->_apiParams;
    $settings =& $this->display['settings'];

    // Checking permissions for menu, link or button columns is costly, so remove them early
    foreach ($settings['columns'] as $index => $col) {
      // Remove buttons/menus and other column types that cannot be rendered in a spreadsheet
      if (empty($col['key'])) {
        unset($settings['columns'][$index]);
      }
      // Avoid wasting time processing links, editable and other non-printable items from spreadsheet
      else {
        \CRM_Utils_Array::remove($settings['columns'][$index], 'link', 'editable', 'icons', 'cssClass');
      }
    }
    // Reset indexes as some items may have been removed
    $settings['columns'] = array_values($settings['columns']);

    // Displays are only exportable if they have actions enabled
    if (empty($settings['actions'])) {
      \CRM_Utils_System::permissionDenied();
    }

    // Force limit if the display has no pager
    if (!isset($settings['pager']) && !empty($settings['limit'])) {
      $apiParams['limit'] = $settings['limit'];
    }
    $apiParams['orderBy'] = $this->getOrderByFromSort();
    $this->augmentSelectClause($apiParams, $settings);

    $this->applyFilters();

    $apiResult = civicrm_api4($entityName, 'get', $apiParams);

    $rows = $this->formatResult($apiResult);

    $columns = [];
    foreach ($this->display['settings']['columns'] as $index => $col) {
      $col += ['type' => NULL, 'label' => '', 'rewrite' => FALSE];
      $columns[$index] = $col;
      // Convert html to plain text
      if ($col['type'] === 'html') {
        foreach ($rows as $i => $row) {
          $row['columns'][$index]['val'] = htmlspecialchars_decode(strip_tags($row['columns'][$index]['val']));
          $rows[$i] = $row;
        }
      }
    }

    // Unicode-safe filename for download
    $fileName = \CRM_Utils_File::makeFilenameWithUnicode($this->display['label']) . '.' . $this->format;

    switch ($this->format) {
      case 'array':
        $result[] = array_column($columns, 'label');
        foreach ($rows as $data) {
          $row = array_column(array_intersect_key($data['columns'], $columns), 'val');
          $result[] = $row;
        }
        return;

      case 'csv':
        $this->outputCSV($rows, $columns, $fileName);
        break;

      default:
        $this->sendHeaders($fileName);
        $this->outputSpreadsheet($rows, $columns);
    }

    \CRM_Utils_System::civiExit();
  }

  /**
   * Return raw value if it is a single date, otherwise return parent
   * {@inheritDoc}
   */
  protected function formatViewValue($key, $rawValue, $data, $dataType, $format = NULL) {
    if (is_array($rawValue)) {
      return parent::formatViewValue($key, $rawValue, $data, $dataType, $format);
    }

    if (($dataType === 'Date' || $dataType === 'Timestamp') && in_array($this->format, ['csv', 'xlsx', 'ods'])) {
      return $rawValue;
    }
    else {
      return parent::formatViewValue($key, $rawValue, $data, $dataType, $format);
    }
  }

  /**
   * Outputs headers and CSV directly to browser for download
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
      $row = array_column(array_intersect_key($data['columns'], $columns), 'val');
      foreach ($row as &$val) {
        if (is_array($val)) {
          $val = implode(', ', $val);
        }
      }
      $csv->insertOne($row);
    }
    // Echo headers and content directly to browser
    $csv->output($fileName);
  }

  /**
   * Create PhpSpreadsheet document and output directly to browser for download
   * @param array $rows
   * @param array $columns
   */
  private function outputSpreadsheet(array $rows, array $columns) {
    $document = new Spreadsheet();
    $document->getProperties()
      ->setTitle($this->display['label']);
    $sheet = $document->getActiveSheet();

    // Header row
    foreach (array_values($columns) as $index => $col) {
      $sheet->setCellValue([$index + 1, 1], $col['label']);
      $sheet->getColumnDimensionByColumn($index)->setAutoSize(TRUE);
    }

    foreach ($rows as $rowNum => $data) {
      $colNum = 1;
      foreach ($columns as $index => $col) {
        $sheet->setCellValue([$colNum++, $rowNum + 2], $this->formatColumnValue($col, $data['columns'][$index]));
      }
    }

    $writer = IOFactory::createWriter($document, $this->formats[$this->format]['writer']);

    $writer->save('php://output');
  }

  /**
   * Returns final formatted column value
   *
   * @param array $col
   * @param array $value
   * @return string
   */
  protected function formatColumnValue(array $col, array $value) {
    $val = $value['val'] ?? '';
    return is_array($val) ? implode(', ', $val) : $val;
  }

  /**
   * Sets headers based on content type and file name
   *
   * @param string $fileName
   */
  protected function sendHeaders(string $fileName) {
    header('Content-Type: ' . $this->formats[$this->format]['mime']);
    header('Content-Transfer-Encoding: binary');
    header('Content-Description: File Transfer');
    header('Content-Disposition: ' . $this->getContentDisposition($fileName));
  }

  /**
   * Copied from \League\Csv\AbstractCsv::sendHeaders()
   * @param string $fileName
   * @return string
   */
  protected function getContentDisposition(string $fileName) {
    $flag = FILTER_FLAG_STRIP_LOW;
    if (strlen($fileName) !== mb_strlen($fileName)) {
      $flag |= FILTER_FLAG_STRIP_HIGH;
    }

    /** @var string $filtered_name */
    $filtered_name = filter_var($fileName, FILTER_UNSAFE_RAW, $flag);
    $filenameFallback = str_replace('%', '', $filtered_name);

    $disposition = sprintf('attachment; filename="%s"', str_replace('"', '\\"', $filenameFallback));
    if ($fileName !== $filenameFallback) {
      $disposition .= sprintf("; filename*=utf-8''%s", rawurlencode($fileName));
    }
    return $disposition;
  }

}
