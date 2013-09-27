<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Utils_TypeTest extends CiviUnitTestCase {

  function get_info() {
    return array(
      'name'      => 'Type Test',
      'description' => 'Test the validate function',
      'group'      => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * @dataProvider validateDataProvider
   */
  function testValidate($inputData, $inputType, $expectedResult) {
    $this->assertEquals($expectedResult, CRM_Utils_Type::validate($inputData, $inputType, FALSE));
  }

  function validateDataProvider() {
    return array(
      array(10, 'Int', 10),
      array('145E+3', 'Int', NULL),
      array('10', 'Integer', 10),
      array(-10, 'Int', -10),
      array('-10', 'Integer', -10),
      array('-10foo', 'Int', NULL),
      array(10, 'Positive', 10),
      array('145.0E+3', 'Positive', NULL),
      array('10', 'Positive', 10),
      array(-10, 'Positive', NULL),
      array('-10', 'Positive', NULL),
      array('-10foo', 'Positive', NULL),
    );
  }
}
