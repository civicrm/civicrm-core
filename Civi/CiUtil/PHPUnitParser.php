<?php
namespace Civi\CiUtil;

/**
 * Parse phpunit result files
 */
class PHPUnitParser {
  /**
   * @param string $content
   *   Phpunit streaming JSON.
   * @return array
   *   ["$class::$func" => $status]
   */
  protected static function parseJsonStream($content) {
    $content = '['
      . strtr($content, array("}{" => "},{"))
      . ']';
    return json_decode($content, TRUE);
  }

  /**
   * @param string $content
   *   Json stream.
   * @return array
   *   (string $testName => string $status)
   */
  public static function parseJsonResults($content) {
    $records = self::parseJsonStream($content);
    $results = array();
    foreach ($records as $r) {
      if ($r['event'] == 'test') {
        $results[$r['test']] = $r['status'];
      }
    }
    return $results;
  }

}
