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

}
