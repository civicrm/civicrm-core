<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Test class for API functions
 *
 *  @package CiviCRM_APIv3
 */
class api_v3_APITest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;
  public $_eNoticeCompliant = TRUE;

  protected $_apiversion;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {}

  function testAPIReplaceVariables() {
    $result = array();
    $result['testfield'] = 6;
    $result['api.tag.get'] = 999;
    $result['api.tag.create']['id'] = 8;
    $result['api.entity.create.0']['id'] = 7;
    $result['api.tag.create'][2]['id'] = 'superman';
    $result['api.tag.create']['values']['0']['display'] = 'batman';
    $result['api.tag.create.api.tag.create']['values']['0']['display'] = 'krypton';
    $result['api.tag.create']['values']['0']['api_tag_get'] = 'darth vader';
    $params = array(
      'activity_type_id' => '$value.testfield',
      'tag_id' => '$value.api.tag.create.id',
      'tag1_id' => '$value.api.entity.create.0.id',
      'tag3_id' => '$value.api.tag.create.2.id',
      'display' => '$value.api.tag.create.values.0.display',
      'number' => '$value.api.tag.get',
      'big_rock' => '$value.api.tag.create.api.tag.create.values.0.display',
      'villain' => '$value.api.tag.create.values.0.api_tag_get.display',
    );
    _civicrm_api_replace_variables('Activity', 'Get', $params, $result);
    $this->assertEquals(999, $params['number']);
    $this->assertEquals(8, $params['tag_id']);
    $this->assertEquals(6, $params['activity_type_id']);
    $this->assertEquals(7, $params['tag1_id']);
    $this->assertEquals('superman', $params['tag3_id']);
    $this->assertEquals('batman', $params['display']);
    $this->assertEquals('krypton', $params['big_rock']);
  }

  /*
    * test that error doesn't occur for non-existant file
    */
  function testAPIWrapperIncludeNoFile() {


    $result = civicrm_api('RandomFile', 'get', array('version' => 3));
    $this->assertAPIFailure($result);
    $this->assertEquals($result['error_message'], 'API (RandomFile,get) does not exist (join the API team and implement it!)');
  }

  function testAPIWrapperCamelCaseFunction() {
    $result = civicrm_api('OptionGroup', 'Get', array(
      'version' => 3,
      ));
    $this->assertEquals(0, $result['is_error']);
  }

  function testAPIWrapperLcaseFunction() {
    $result = civicrm_api('OptionGroup', 'get', array(
      'version' => 3,
      ));
    $this->assertEquals(0, $result['is_error']);
  }

  function testAPIResolver() {
    $oldpath = get_include_path();
    set_include_path($oldpath . PATH_SEPARATOR . dirname(__FILE__) . '/dataset/resolver');

    $result = civicrm_api('contact', 'example_action1', array(
        'version' => 3,
      ));
    $this->assertEquals($result['values'][0], 'civicrm_api3_generic_example_action1 is ok');
    $result = civicrm_api('contact', 'example_action2', array(
        'version' => 3,
      ));
    $this->assertEquals($result['values'][0], 'civicrm_api3_contact_example_action2 is ok');
    $result = civicrm_api('test_entity', 'example_action3', array(
        'version' => 3,
      ));
    $this->assertEquals($result['values'][0], 'civicrm_api3_test_entity_example_action3 is ok');

    set_include_path($oldpath);
  }

  function testFromCamel() {
    $cases = array(
      'Contribution' => 'contribution',
      'contribution' => 'contribution',
      'OptionValue' => 'option_value',
      'optionValue' => 'option_value',
      'option_value' => 'option_value',
      'UFJoin' => 'uf_join',
      'ufJoin' => 'uf_join',
      'uf_join' => 'uf_join',
    );
    foreach ($cases as $input => $expected) {
      $actual = _civicrm_api_get_entity_name_from_camel($input);
      $this->assertEquals($expected, $actual, sprintf('input=%s expected=%s actual=%s', $input, $expected, $actual));
    }
  }

  function testToCamel() {
    $cases = array(
      'Contribution' => 'Contribution',
      'contribution' => 'Contribution',
      'OptionValue' => 'OptionValue',
      'optionValue' => 'OptionValue',
      'option_value' => 'OptionValue',
      'UFJoin' => 'UFJoin',
      // dommage 'ufJoin' => 'UFJoin',
      'uf_join' => 'UFJoin',
    );
    foreach ($cases as $input => $expected) {
      $actual = _civicrm_api_get_camel_name($input);
      $this->assertEquals($expected, $actual, sprintf('input=%s expected=%s actual=%s', $input, $expected, $actual));
    }
  }
}

