<?php

/**
 * Class CRM_Utils_FileTest
 * @group headless
 */
class CRM_Utils_FileTest extends CiviUnitTestCase {

  /**
   * Test is child path.
   */
  public function testIsChildPath() {
    $testCases = array();
    $testCases[] = array('/ab/cd/ef', '/ab/cd', FALSE);
    $testCases[] = array('/ab/cd', '/ab/cd/ef', TRUE);
    $testCases[] = array('/ab/cde', '/ab/cd/ef', FALSE);
    $testCases[] = array('/ab/cde', '/ab/cd', FALSE);
    $testCases[] = array('/ab/cd', 'ab/cd/ef', FALSE);
    foreach ($testCases as $testCase) {
      $actual = CRM_Utils_File::isChildPath($testCase[0], $testCase[1], FALSE);
      $this->assertEquals($testCase[2], $actual, sprintf("parent=[%s] child=[%s] expected=[%s] actual=[%s]",
        $testCase[0], $testCase[1], $testCase[2], $actual
      ));
    }
  }

}
