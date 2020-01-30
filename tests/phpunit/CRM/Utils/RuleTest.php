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
   * @param $decimalPoint
   * @param $thousandSeparator
   * @param $currency
   * @param $expectedResult
   */
  public function testMoney($inputData, $decimalPoint, $thousandSeparator, $currency, $expectedResult) {
    $this->setDefaultCurrency($currency);
    $this->setMonetaryDecimalPoint($decimalPoint);
    $this->setMonetaryThousandSeparator($thousandSeparator);
    $this->assertEquals($expectedResult, CRM_Utils_Rule::money($inputData));
  }

  /**
   * @return array
   */
  public function moneyDataProvider() {
    return [
      [10, '.', ',', 'USD', TRUE],
      ['145.0E+3', '.', ',', 'USD', FALSE],
      ['10', '.', ',', 'USD', TRUE],
      [-10, '.', ',', 'USD', TRUE],
      ['-10', '.', ',', 'USD', TRUE],
      ['-10foo', '.', ',', 'USD', FALSE],
      ['-10.0345619', '.', ',', 'USD', TRUE],
      ['-10.010,4345619', '.', ',', 'USD', TRUE],
      ['10.0104345619', '.', ',', 'USD', TRUE],
      ['-0', '.', ',', 'USD', TRUE],
      ['-.1', '.', ',', 'USD', TRUE],
      ['.1', '.', ',', 'USD', TRUE],
      // Test currency symbols too, default locale uses $, so if we wanted to test others we'd need to reconfigure locale
      ['$1,234,567.89', '.', ',', 'USD', TRUE],
      ['-$1,234,567.89', '.', ',', 'USD', TRUE],
      ['$-1,234,567.89', '.', ',', 'USD', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', '.', ',', 'USD', TRUE],
      // This is the float format.
      [1234567.89, '.', ',', 'USD', TRUE],
      // Test EURO currency
      ['€1,234,567.89', '.', ',', 'EUR', TRUE],
      ['-€1,234,567.89', '.', ',', 'EUR', TRUE],
      ['€-1,234,567.89', '.', ',', 'EUR', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', '.', ',', 'EUR', TRUE],
      // This is the float format.
      [1234567.89, '.', ',', 'EUR', TRUE],
      // Test Norwegian KR currency
      ['kr1,234,567.89', '.', ',', 'NOK', TRUE],
      ['kr 1,234,567.89', '.', ',', 'NOK', TRUE],
      ['-kr1,234,567.89', '.', ',', 'NOK', TRUE],
      ['-kr 1,234,567.89', '.', ',', 'NOK', TRUE],
      ['kr-1,234,567.89', '.', ',', 'NOK', TRUE],
      ['kr -1,234,567.89', '.', ',', 'NOK', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', '.', ',', 'NOK', TRUE],
      // This is the float format.
      [1234567.89, '.', ',', 'NOK', TRUE],
      // Test different localization options: , as decimal separator and dot as thousand separator
      ['$1.234.567,89', ',', '.', 'USD', TRUE],
      ['-$1.234.567,89', ',', '.', 'USD', TRUE],
      ['$-1.234.567,89', ',', '.', 'USD', TRUE],
      ['1.234.567,89', ',', '.', 'USD', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', ',', '.', 'USD', TRUE],
      // This is the float format.
      [1234567.89, ',', '.', 'USD', TRUE],
      ['$1,234,567.89', ',', '.', 'USD', FALSE],
      ['-$1,234,567.89', ',', '.', 'USD', FALSE],
      ['$-1,234,567.89', ',', '.', 'USD', FALSE],
      // Now with a space as thousand separator
      ['$1 234 567,89', ',', ' ', 'USD', TRUE],
      ['-$1 234 567,89', ',', ' ', 'USD', TRUE],
      ['$-1 234 567,89', ',', ' ', 'USD', TRUE],
      ['1 234 567,89', ',', ' ', 'USD', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', ',', ' ', 'USD', TRUE],
      // This is the float format.
      [1234567.89, ',', ' ', 'USD', TRUE],
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
