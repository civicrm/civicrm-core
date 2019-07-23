<?php

/**
 * Class CRM_Utils_RuleTest
 * @group headless
 */
class CRM_Utils_RuleTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * @dataProvider integerDataProvider
   * @param $inputData
   * @param $expectedResult
   */
  public function testInteger($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::integer($inputData));
  }

  /**
   * @return array
   */
  public function integerDataProvider() {
    return [
      [10, TRUE],
      ['145E+3', FALSE],
      ['10', TRUE],
      [-10, TRUE],
      ['-10', TRUE],
      ['-10foo', FALSE],
    ];
  }

  /**
   * @dataProvider positiveDataProvider
   * @param $inputData
   * @param $expectedResult
   */
  public function testPositive($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::positiveInteger($inputData));
  }

  /**
   * @return array
   */
  public function positiveDataProvider() {
    return [
      [10, TRUE],
      ['145.0E+3', FALSE],
      ['10', TRUE],
      [-10, FALSE],
      ['-10', FALSE],
      ['-10foo', FALSE],
    ];
  }

  /**
   * @dataProvider numericDataProvider
   * @param $inputData
   * @param $expectedResult
   */
  public function testNumeric($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::numeric($inputData));
  }

  /**
   * @return array
   */
  public function numericDataProvider() {
    return [
      [10, TRUE],
      ['145.0E+3', FALSE],
      ['10', TRUE],
      [-10, TRUE],
      ['-10', TRUE],
      ['-10foo', FALSE],
    ];
  }

  /**
   * @dataProvider moneyDataProvider
   * @param $inputData
   * @param $expectedResult
   */
  public function testMoney($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::money($inputData));
  }

  /**
   * @return array
   */
  public function moneyDataProvider() {
    return [
      [10, TRUE],
      ['145.0E+3', FALSE],
      ['10', TRUE],
      [-10, TRUE],
      ['-10', TRUE],
      ['-10foo', FALSE],
      ['-10.0345619', TRUE],
      ['-10.010,4345619', TRUE],
      ['10.0104345619', TRUE],
      ['-0', TRUE],
      ['-.1', TRUE],
      ['.1', TRUE],
      // Test currency symbols too, default locale uses $, so if we wanted to test others we'd need to reconfigure locale
      ['$500.3333', TRUE],
      ['-$500.3333', TRUE],
      ['$-500.3333', TRUE],
    ];
  }

  /**
   * @dataProvider colorDataProvider
   * @param $inputData
   * @param $expectedResult
   */
  public function testColor($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::color($inputData));
  }

  /**
   * @return array
   */
  public function colorDataProvider() {
    return [
      ['#000000', TRUE],
      ['#ffffff', TRUE],
      ['#123456', TRUE],
      ['#00aaff', TRUE],
      // Some of these are valid css colors but we reject anything that doesn't conform to the html5 spec for <input type="color">
      ['#ffffff00', FALSE],
      ['#fff', FALSE],
      ['##000000', FALSE],
      ['ffffff', FALSE],
      ['red', FALSE],
      ['#orange', FALSE],
      ['', FALSE],
      ['rgb(255, 255, 255)', FALSE],
    ];
  }

  /**
   * @return array
   */
  public function extenionKeyTests() {
    $keys = [];
    $keys[] = ['org.civicrm.multisite', TRUE];
    $keys[] = ['au.org.contribute2016', TRUE];
    $keys[] = ['%3Csvg%20onload=alert(0)%3E', FALSE];
    return $keys;
  }

  /**
   * @param $key
   * @param $expectedResult
   * @dataProvider extenionKeyTests
   */
  public function testExtenionKeyValid($key, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::checkExtensionKeyIsValid($key));
  }

  /**
   * @return array
   */
  public function alphanumericData() {
    $expectTrue = [
      0,
      999,
      -5,
      '',
      'foo',
      '0',
      '-',
      '_foo',
      'one-two',
      'f00',
    ];
    $expectFalse = [
      ' ',
      5.7,
      'one two',
      'one.two',
      'A<B',
      "<script>alert('XSS');</script>",
      '(foo)',
      'foo;',
      '[foo]',
    ];
    $data = [];
    foreach ($expectTrue as $value) {
      $data[] = [$value, TRUE];
    }
    foreach ($expectFalse as $value) {
      $data[] = [$value, FALSE];
    }
    return $data;
  }

  /**
   * @dataProvider alphanumericData
   * @param $value
   * @param $expected
   */
  public function testAlphanumeric($value, $expected) {
    $this->assertEquals($expected, CRM_Utils_Rule::alphanumeric($value));
  }

}
