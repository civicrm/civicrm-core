<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Utils_RuleTest extends CiviUnitTestCase {

  function get_info() {
    return array(
      'name'      => 'Rule Test',
      'description' => 'Test the validation rules',
      'group'      => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * @dataProvider integerDataProvider
   */
  function testInteger($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::integer($inputData));
  }

  function integerDataProvider() {
    return array(
      array(10, true),
      array('145E+3', false),
      array('10', true),
      array(-10, true),
      array('-10', true),
      array('-10foo', false),
    );
  }

  /**
   * @dataProvider positiveDataProvider
   */
  function testPositive($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::positiveInteger($inputData));
  }

  function positiveDataProvider() {
    return array(
      array(10, true),
      array('145.0E+3', false),
      array('10', true),
      array(-10, false),
      array('-10', false),
      array('-10foo', false),
    );
  }

  /**
   * @dataProvider numericDataProvider
   */
  function testNumeric($inputData, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Rule::numeric($inputData));
  }

  function numericDataProvider() {
    return array(
      array(10, true),
      array('145.0E+3', false),
      array('10', true),
      array(-10, true),
      array('-10', true),
      array('-10foo', false),
    );
  }


}
