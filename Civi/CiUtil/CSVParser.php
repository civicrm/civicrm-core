<?php
namespace Civi\CiUtil;

/**
 * Parse phpunit result files
 */
class CSVParser {

  /**
   * @param string $csvContent content; each row in the row csv should start with two cells:
   *   - cell 0: the test name
   *   - cell 1: the test status
   * @return array (string $testName => string $status)
   */
  public static function parseResults($csvContent) {
    $fh = fopen('php://memory', 'r+');
    fwrite($fh, $csvContent);
    rewind($fh);

    $results = array();
    while (($r = fgetcsv($fh)) !== FALSE) {
      $name = str_replace('.', '::', trim($r[0]));
      $status = trim($r[1]);
      $results[$name] = $status;
    }

    return $results;
  }

}