<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

/**
 * Test class for API functions.
 *
 * @package CiviCRM_APIv3
 */
class api_v3_APITest extends CiviUnitTestCase {
  public $DBResetRequired = FALSE;

  protected $_apiversion = 3;

  public function testAPIReplaceVariables() {
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
    _civicrm_api_replace_variables($params, $result);
    $this->assertEquals(999, $params['number']);
    $this->assertEquals(8, $params['tag_id']);
    $this->assertEquals(6, $params['activity_type_id']);
    $this->assertEquals(7, $params['tag1_id']);
    $this->assertEquals('superman', $params['tag3_id']);
    $this->assertEquals('batman', $params['display']);
    $this->assertEquals('krypton', $params['big_rock']);
  }

  /**
   * Test that error doesn't occur for non-existent file.
   */
  public function testAPIWrapperIncludeNoFile() {
    $this->callAPIFailure(
      'RandomFile',
      'get',
      array(),
      'API (RandomFile,get) does not exist (join the API team and  implement it!)'
    );
  }

  public function testAPIWrapperCamelCaseFunction() {
    $this->callAPISuccess('OptionGroup', 'Get', array());
  }

  public function testAPIWrapperLcaseFunction() {
    $this->callAPISuccess('OptionGroup', 'get', array());
  }

  /**
   * Test resolver.
   */
  public function testAPIResolver() {
    $oldPath = get_include_path();
    set_include_path($oldPath . PATH_SEPARATOR . dirname(__FILE__) . '/dataset/resolver');

    $result = $this->callAPISuccess('contact', 'example_action1', array());
    $this->assertEquals($result['values'][0], 'civicrm_api3_generic_example_action1 is ok');
    $result = $this->callAPISuccess('contact', 'example_action2', array());
    $this->assertEquals($result['values'][0], 'civicrm_api3_contact_example_action2 is ok');
    $result = $this->callAPISuccess('test_entity', 'example_action3', array());
    $this->assertEquals($result['values'][0], 'civicrm_api3_test_entity_example_action3 is ok');

    set_include_path($oldPath);
  }

  public function testFromCamel() {
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

  public function testToCamel() {
    $cases = array(
      'Contribution' => 'Contribution',
      'contribution' => 'Contribution',
      'OptionValue' => 'OptionValue',
      'optionValue' => 'OptionValue',
      'option_value' => 'OptionValue',
      'UFJoin' => 'UFJoin',
      'uf_join' => 'UFJoin',
    );
    foreach ($cases as $input => $expected) {
      $actual = _civicrm_api_get_camel_name($input);
      $this->assertEquals($expected, $actual, sprintf('input=%s expected=%s actual=%s', $input, $expected, $actual));
    }
  }

  /**
   * Test that calling via wrapper works.
   */
  public function testv3Wrapper() {
    try {
      $result = civicrm_api3('contact', 'get', array());
    }
    catch (CRM_Exception $e) {
      $this->fail("This should have been a success test");
    }
    $this->assertTrue(is_array($result));
    $this->assertAPISuccess($result);
  }

  /**
   * Test exception is thrown.
   */
  public function testV3WrapperException() {
    try {
      civicrm_api3('contact', 'create', array('debug' => 1));
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->assertEquals('mandatory_missing', $e->getErrorCode());
      $this->assertEquals('Mandatory key(s) missing from params array: contact_type', $e->getMessage());
      $extra = $e->getExtraParams();
      $this->assertArrayHasKey('trace', $extra);
      return;
    }
    $this->fail('Exception was expected');
  }

  /**
   * Test result parsing for null.
   */
  public function testCreateNoStringNullResult() {
    // create an example contact
    // $contact = CRM_Core_DAO::createTestObject('CRM_Contribute_DAO_ContributionPage')->toArray();
    $result = $this->callAPISuccess('ContributionPage', 'create', array(
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'USD',
      'goal_amount' => 100,
    ));
    $contact = array_shift($result['values']);

    $this->assertTrue(is_numeric($contact['id']));
    $this->assertNotEmpty($contact['title']);
    // preferred_mail_format preferred_communication_method preferred_language gender_id
    // currency
    $this->assertNotEmpty($contact['currency']);

    // update the contact
    $result = $this->callAPISuccess('ContributionPage', 'create', array(
      'id' => $contact['id'],
      'title' => 'New title',
      'currency' => '',
    ));

    // Check return format.
    $this->assertEquals(1, $result['count']);
    foreach ($result['values'] as $resultValue) {
      $this->assertEquals('New title', $resultValue['title']);
      // BUG: $resultValue['location'] === 'null'.
      $this->assertEquals('', $resultValue['currency']);
    }
  }

}
