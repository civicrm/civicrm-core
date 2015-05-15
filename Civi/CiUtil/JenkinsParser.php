<?php
namespace Civi\CiUtil;

/**
 * Parse Jenkins result files
 */
class JenkinsParser {
  /**
   * @param string $content
   *   Xml data.
   * @return array
   *   (string $testName => string $status)
   */
  public static function parseXmlResults($content) {
    $xml = simplexml_load_string($content);
    $results = array();
    foreach ($xml->suites as $suites) {
      foreach ($suites->suite as $suite) {
        foreach ($suite->cases as $cases) {
          foreach ($cases->case as $case) {
            $name = "{$case->className}::{$case->testName}";
            if ($case->failedSince == 0) {
              $results[$name] = 'pass';
            }
            else {
              $results[$name] = 'fail';
            }
          }
        }
      }
    }
    return $results;
  }

}
