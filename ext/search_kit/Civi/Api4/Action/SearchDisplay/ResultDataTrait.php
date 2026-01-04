<?php

namespace Civi\Api4\Action\SearchDisplay;

use Civi\Util\PhpSpreadsheetUtil;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Currency;

trait ResultDataTrait {

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
    'array' => [
      'writer' => 'JSON',
      'mime' => 'application/json',
    ],
    'csv' => [
      'writer' => 'CSV',
      'mime' => 'text/csv',
    ],
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

  private function processData($label, $columns, $rows, &$fileName) {
    $data_columns = [];
    $result = [];
    foreach ($columns as $index => $col) {
      if (!$this->isColumnEnabled($index)) {
        continue;
      }
      $col += ['type' => NULL, 'label' => '', 'rewrite' => FALSE];
      $data_columns[$index] = $col;
      // Convert html to plain text
      if ($col['type'] === 'html') {
        foreach ($rows as $i => $row) {
          $row['columns'][$index]['val'] = htmlspecialchars_decode(strip_tags($row['columns'][$index]['val']));
          $rows[$i] = $row;
        }
      }
    }

    if (empty($fileName)) {
      // Unicode-safe filename for download
      $fileName = \CRM_Utils_File::makeFilenameWithUnicode($label) . '.' . $this->format;
    }

    switch ($this->format) {
      case 'array':
        $result[] = array_column($data_columns, 'label');
        foreach ($rows as $data) {
          $row = array_column(array_intersect_key($data['columns'], $data_columns), 'val');
          $result[] = $row;
        }
        return $result;

      case 'csv':
        $this->outputCSV($rows, $data_columns, $fileName);
        break;

      default:
        $this->outputSpreadsheet($rows, $data_columns);
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
      $sheet->getColumnDimensionByColumn($index + 1)->setAutoSize(TRUE);
    }

    global $civicrmLocale;
    $moneyLocale = $civicrmLocale->moneyFormat ?? (\Civi::settings()->get('format_locale') ?? \CRM_Core_I18n::getLocale());

    foreach ($rows as $rowNum => $data) {
      foreach ($columns as $colNum => $col) {
        $value = $data['columns'][$colNum];
        $cell = $sheet->getCell([$colNum + 1, $rowNum + 2]);
        $cell->setValue($this->formatColumnValue($col, $value));

        if ($value['dataType'] === 'Money') {
          $numberFormatter = new \NumberFormatter($moneyLocale . '@currency=' . $value['val']['currency'], \NumberFormatter::CURRENCY);
          $currencySymbol = $numberFormatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
          $cell->getStyle()->getNumberFormat()->setFormatCode(new Currency($currencySymbol, locale: $numberFormatter->getLocale()));
        }
        elseif ($value['dataType'] === 'Date') {
          $format = \Civi::settings()->get(($col['format'] ?? NULL) ?: 'dateformatFull');
          $cell->getStyle()->getNumberFormat()->setFormatCode(PhpSpreadsheetUtil::crmDateFormatToFormatCode($format));
        }
        elseif ($value['dataType'] === 'Timestamp') {
          $format = \Civi::settings()->get(($col['format'] ?? NULL) ?: 'dateformatDatetime');
          $cell->getStyle()->getNumberFormat()->setFormatCode(PhpSpreadsheetUtil::crmDateFormatToFormatCode($format));
        }
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
   * @return scalar|null
   */
  protected function formatColumnValue(array $col, array $value) {
    $val = $value['val'];

    if (!in_array($this->format, ['array', 'csv'], TRUE)) {
      if ($value['dataType'] === 'Money') {
        return $val['value'];
      }

      if (($value['dataType'] === 'Date' || $value['dataType'] === 'Timestamp') && ($val !== NULL)) {
        return Date::stringToExcel($val);
      }
    }

    return is_array($val) ? implode(', ', $val) : $val;
  }

}
