<?php
namespace Civi\CiUtil;

class CsvPrinter {
  var $file;
  var $headers;
  var $hasHeader = FALSE;

  function __construct($file, $headers) {
    $this->file = fopen($file, "w");
    $this->headers = $headers;
  }

  function printHeader() {
    if ($this->hasHeader) {
      return;
    }

    $headers = array_values($this->headers);
    array_unshift($headers, 'TEST NAME');
    fputcsv($this->file, $headers);

    $this->hasHeader = TRUE;
  }

  function printRow($test, $values) {
    $this->printHeader();
    $row = $values;
    array_unshift($row, $test);
    fputcsv($this->file, $row);
  }
}