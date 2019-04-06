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
    return array(
      array(10, TRUE),
      array('145E+3', FALSE),
      array('10', TRUE),
      array(-10, TRUE),
      array('-10', TRUE),
      array('-10foo', FALSE),
    );
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
    return array(
      array(10, TRUE),
      array('145.0E+3', FALSE),
      array('10', TRUE),
      array(-10, FALSE),
      array('-10', FALSE),
      array('-10foo', FALSE),
    );
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
    return array(
      array(10, TRUE),
      array('145.0E+3', FALSE),
      array('10', TRUE),
      array(-10, TRUE),
      array('-10', TRUE),
      array('-10foo', FALSE),
    );
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
    return array(
      array(10, TRUE),
      array('145.0E+3', FALSE),
      array('10', TRUE),
      array(-10, TRUE),
      array('-10', TRUE),
      array('-10foo', FALSE),
      array('-10.0345619', TRUE),
      array('-10.010,4345619', TRUE),
      array('10.0104345619', TRUE),
      array('-0', TRUE),
      array('-.1', TRUE),
      array('.1', TRUE),
      // Test currency symbols too, default locale uses $, so if we wanted to test others we'd need to reconfigure locale
      array('$500.3333', TRUE),
      array('-$500.3333', TRUE),
      array('$-500.3333', TRUE),
    );
  }

  /**
   * @return array
   */
  public function extenionKeyTests() {
    $keys = array();
    $keys[] = array('org.civicrm.multisite', TRUE);
    $keys[] = array('au.org.contribute2016', TRUE);
    $keys[] = array('%3Csvg%20onload=alert(0)%3E', FALSE);
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
