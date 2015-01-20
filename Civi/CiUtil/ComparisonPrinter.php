<?php
namespace Civi\CiUtil;

class ComparisonPrinter {
  var $headers;
  var $hasHeader = FALSE;

  public function __construct($headers) {
    $this->headers = $headers;
  }

  public function printHeader() {
    if ($this->hasHeader) {
      return;
    }

    ## LEGEND
    print "LEGEND\n";
    $i = 1;
    foreach ($this->headers as $header) {
      printf("% 2d: %s\n", $i, $header);
      $i++;
    }
    print "\n";

    ## HEADER
    printf("%-90s ", 'TEST NAME');
    $i = 1;
    foreach ($this->headers as $header) {
      printf("%-10d ", $i);
      $i++;
    }
    print "\n";

    $this->hasHeader = TRUE;
  }

  public function printRow($test, $values) {
    $this->printHeader();
    printf("%-90s ", $test);
    foreach ($values as $value) {
      printf("%-10s ", $value);
    }
    print "\n";
  }

}
