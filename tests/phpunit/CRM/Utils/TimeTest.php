<?php

/**
 * Class CRM_Utils_TimeTest
 * @group headless
 */
class CRM_Utils_TimeTest extends CiviUnitTestCase {

  /**
   * Equal cases.
   *
   * @return array
   */
  public function equalCases() {
    // array(0 => $timeA, 1 => $timeB, 2 => $threshold, 3 => $expectedResult)
    $cases = array();
    $cases[] = array('2012-04-01 12:00:00', '2012-04-01 12:00:00', 0, 1);
    $cases[] = array('2012-04-01 12:00:00', '2012-04-01 12:00:01', 0, 0);
    $cases[] = array('2012-04-01 12:00:00', '2012-04-01 12:00:50', 60, 1);
    $cases[] = array('2012-04-01 12:00:00', '2012-04-01 12:01:02', 60, 0);
    $cases[] = array('2012-04-01 12:00', '2012-04-01 12:01', 0, 0);
    $cases[] = array('2012-04-01 12:00', '2012-04-01 12:01', 60, 1);
    $cases[] = array('2012-04-01 12:00', '2012-04-01 12:01', 120, 1);
    return $cases;
  }

  /**
   * Test equals.
   *
   * @param string $timeA
   * @param string $timeB
   * @param int $threshold
   * @param bool $expectedResult
   *
   * @dataProvider equalCases
   */
  public function testEquals($timeA, $timeB, $threshold, $expectedResult) {
    $actual = CRM_Utils_Time::isEqual($timeA, $timeB, $threshold);
    $this->assertEquals($expectedResult, $actual);

    $actual = CRM_Utils_Time::isEqual($timeB, $timeA, $threshold);
    $this->assertEquals($expectedResult, $actual);
  }

}
