<?php

/**
 * Class CRM_Utils_RuleTest
 * @group headless
 */
class CRM_Utils_RuleTest extends CiviUnitTestCase {

  /**
   * @dataProvider integerDataProvider
   * @param $inputData
   * @param $expectedResult
   */
  public function testInteger($inputData, $expectedResult): void {
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
  public function testPositive($inputData, $expectedResult): void {
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
  public function testNumeric($inputData, $expectedResult): void {
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
   * @dataProvider booleanDataProvider
   * @param $inputData
   * @param $expectedResult
   */
  public function testBoolean($inputData, $expectedResult): void {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::boolean($inputData));
  }

  /**
   * @return array
   */
  public function booleanDataProvider() {
    return [
      [TRUE, TRUE],
      ['TRUE', TRUE],
      [FALSE, TRUE],
      ['false', TRUE],
      ['banana', FALSE],
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
  public function testMoney($inputData, $decimalPoint, $thousandSeparator, $currency, $expectedResult): void {
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
  public function testColor($inputData, $expectedResult): void {
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
  public function testExtenionKeyValid($key, $expectedResult): void {
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
  public function testAlphanumeric($value, $expected): void {
    $this->assertEquals($expected, CRM_Utils_Rule::alphanumeric($value));
  }

  /**
   * Test Credit Cards
   * @return array
   */
  public static function creditCards(): array {
    $cases = [];
    $cases[] = ['4111 1111 1111 1111', 'VISA'];
    $cases[] = ['4111-1111-1111-1111', 'VISA'];
    $cases[] = ['4111111111111111', 'VISA'];
    $cases[] = ['5500 0000 0000 0004', 'MasterCard'];
    $cases[] = ['2223000048400011', 'MasterCard'];
    $cases[] = ['3400 0000 0000 009', 'AMEX'];
    $cases[] = ['3088 0000 0000 0009', 'JCB'];
    $cases[] = ['2014 0000 0000 009', 'ENROUTE'];
    $cases[] = ['6011 0000 0000 0004', 'DISCOVER'];
    $cases[] = ['3000 0000 0000 04', 'DINERSCLUB'];
    return $cases;
  }

  /**
   * Test Credit Card Validation
   * @param string $number CreditCard number for testing
   * @param string $type CreditCard type to match against.
   * @dataProvider creditCards
   */
  public function testCreditCardValidation($number, $type): void {
    $this->assertTrue(CRM_Utils_Rule::creditCardNumber($number, $type));
  }

  /**
   * Test cvvs.
   *
   * @return array
   */
  public static function cvvs(): array {
    $cases = [];
    $cases[] = ['1', 'visa', FALSE];
    $cases[] = ['23', 'visa', FALSE];
    $cases[] = ['111', 'visa', TRUE];
    $cases[] = ['123', 'visa', TRUE];
    $cases[] = ['13', 'visa', FALSE];
    $cases[] = ['1234', 'visa', FALSE];
    $cases[] = ['897', 'mastercard', TRUE];
    $cases[] = ['123', 'jcb', FALSE];
    $cases[] = ['8765', 'jcb', FALSE];
    $cases[] = ['8765', '', FALSE];
    $cases[] = ['1234', 'amex', TRUE];
    $cases[] = ['465', 'amex', FALSE];
    $cases[] = ['10O7', 'amex', FALSE];
    $cases[] = ['abc', 'visa', FALSE];
    $cases[] = ['123.0', 'visa', FALSE];
    $cases[] = ['1.2', 'visa', FALSE];
    $cases[] = ['123', 'discover', TRUE];
    $cases[] = ['4429', 'discover', FALSE];
    return $cases;
  }

  /**
   * Test CVV rule
   * @param string $cvv cvv for testing
   * @param string $type card type for testing
   * @param bool $expected expected outcome of the rule validation
   * @dataProvider cvvs
   */
  public function testCvvRule($cvv, $type, $expected): void {
    $this->assertEquals($expected, CRM_Utils_Rule::cvv($cvv, $type));
  }

  /**
   * Test CVV rule
   *
   * @param string $email
   * @param bool $expected expected outcome of the rule validation
   *
   * @dataProvider emails
   */
  public function testEmailRule(string $email, bool $expected): void {
    $this->assertEquals($expected, CRM_Utils_Rule::email($email));
  }

  /**
   * Test emails.
   *
   * @return array
   */
  public static function emails(): array {
    $cases = [];
    $cases['name.-o-.i.10@example.com'] = ['name.-o-.i.10@example.com', TRUE];
    $cases['test@ēxāmplē.co.nz'] = ['test@ēxāmplē.co.nz', TRUE];
    $cases['test@localhost'] = ['test@localhost', TRUE];
    $cases['test@ēxāmplē.co'] = ['test@exāmple', FALSE];
    return $cases;
  }

}
