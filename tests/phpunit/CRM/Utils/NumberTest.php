<?php

/**
 * Class CRM_Utils_NumberTest
 * @group headless
 */
class CRM_Utils_NumberTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function randomDecimalCases() {
    $cases = [];
    // array(array $precision, int $expectedMinInclusive, int $expectedMaxExclusive)
    $cases[] = [[1, 0], 0, 10];
    $cases[] = [[5, 2], 0, 1000];
    $cases[] = [[10, 8], 0, 100];
    return $cases;
  }

  /**
   * @param array $precision
   * @param int $expectedMinInclusive
   * @param int $expectedMaxExclusive
   * @dataProvider randomDecimalCases
   */
  public function testCreateRandomDecimal($precision, $expectedMinInclusive, $expectedMaxExclusive) {
    list ($sigFigs, $decFigs) = $precision;
    for ($i = 0; $i < 10; $i++) {
      $decimal = CRM_Utils_Number::createRandomDecimal($precision);
      // print "Assert $decimal between $expectedMinInclusive and $expectedMaxExclusive\n";
      $this->assertTrue(($expectedMinInclusive <= $decimal) && ($decimal < $expectedMaxExclusive), "Assert $decimal between $expectedMinInclusive and $expectedMaxExclusive");
      if (strpos($decimal, '.') === FALSE) {
        $decimal .= '.';
      }
      list ($before, $after) = explode('.', $decimal);
      $this->assertTrue(strlen($before) + strlen($after) <= $sigFigs, "Assert $decimal [$before;$after] has <= $sigFigs sigFigs");
      $this->assertTrue(strlen($after) <= $decFigs, "Assert $decimal [$before;$after] has <= $decFigs decFigs");
    }
  }

  /**
   * @return array
   */
  public function truncDecimalCases() {
    $cases = [];
    // array($value, $precision, $expectedValue)
    $cases[] = [523, [1, 0], 5];
    $cases[] = [523, [5, 2], 523];
    $cases[] = [523, [10, 8], 52.3];
    $cases[] = [12345, [3, 3], 0.123];
    $cases[] = [0.12345, [10, 0], 12345];
    $cases[] = [-123.45, [4, 2], -12.34];
    return $cases;
  }

  /**
   * @param $value
   * @param $precision
   * @param $expectedValue
   * @dataProvider truncDecimalCases
   */
  public function testCreateTruncatedDecimal($value, $precision, $expectedValue) {
    list ($sigFigs, $decFigs) = $precision;
    $this->assertEquals($expectedValue, CRM_Utils_Number::createTruncatedDecimal($value, $precision),
      "assert createTruncatedValue($value, ($sigFigs,$decFigs)) == $expectedValue"
    );
  }

  public function sizeCases() {
    $cases = [];
    $cases[] = ['20M', '20971520'];
    $cases[] = ['40G', '42949672960'];
    return $cases;
  }

  /**
   * @param $size
   * @param $expectedValue
   * @dataProvider sizeCases
   */
  public function testFormatUnitSize($size, $expectedValue) {
    $this->assertEquals($expectedValue, CRM_Utils_Number::formatUnitSize($size));
  }

}
