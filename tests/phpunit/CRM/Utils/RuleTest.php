<?php

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Utils_RuleTest
 */
class CRM_Utils_RuleTest extends CiviUnitTestCase {

  function setUp() {
    parent::setUp();
  }

  /**
   * @dataProvider integerDataProvider
   */
  function testInteger($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::integer($inputData));
  }

  /**
   * @return array
   */
  function integerDataProvider() {
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
   */
  function testPositive($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::positiveInteger($inputData));
  }

  /**
   * @return array
   */
  function positiveDataProvider() {
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
   */
  function testNumeric($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::numeric($inputData));
  }

  /**
   * @return array
   */
  function numericDataProvider() {
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
