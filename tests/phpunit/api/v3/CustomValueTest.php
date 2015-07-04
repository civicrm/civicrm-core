<?php
/**
 * +--------------------------------------------------------------------+
 * | CiviCRM version 4.6                                                |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2015                                |
 * +--------------------------------------------------------------------+
 * | This file is a part of CiviCRM.                                    |
 * |                                                                    |
 * | CiviCRM is free software; you can copy, modify, and distribute it  |
 * | under the terms of the GNU Affero General Public License           |
 * | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 * |                                                                    |
 * | CiviCRM is distributed in the hope that it will be useful, but     |
 * | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 * | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 * | See the GNU Affero General Public License for more details.        |
 * |                                                                    |
 * | You should have received a copy of the GNU Affero General Public   |
 * | License and the CiviCRM Licensing Exception along                  |
 * | with this program; if not, contact CiviCRM LLC                     |
 * | at info[AT]civicrm[DOT]org. If you have questions about the        |
 * | GNU Affero General Public License or the licensing of CiviCRM,     |
 * | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 * +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class api_v3_CustomValueTest
 */
class api_v3_CustomValueTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $individual;
  protected $params;
  protected $ids;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->individual = $this->individualCreate();
    $this->params = array(
      'entity_id' => $this->individual,
    );
    $this->ids['single'] = $this->entityCustomGroupWithSingleFieldCreate('mySingleField', 'Contacts');
    $this->ids['multi'] = $this->CustomGroupMultipleCreateWithFields();
    $this->ids['multi2'] = $this->CustomGroupMultipleCreateWithFields(array('title' => 'group2'));
  }

  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_email',
      'civicrm_custom_field',
      'civicrm_custom_group',
      'civicrm_contact',
    );

    // true tells quickCleanup to drop any tables that might have been created in the test
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  public function testCreateCustomValue() {
    $params = array(
      'custom_' . $this->ids['single']['custom_field_id'] => 'customString',
    ) + $this->params;

    $result = $this->callAPIAndDocument('custom_value', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $result = $this->callAPISuccess('custom_value', 'get', $params);
  }

  public function testGetMultipleCustomValues() {

    $description = "This demonstrates the use of CustomValue get to fetch single and multi-valued custom data.";

    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'custom_' . $this->ids['single']['custom_field_id'] => "value 1",
      'custom_' . $this->ids['multi']['custom_field_id'][0] => "value 2",
      'custom_' . $this->ids['multi']['custom_field_id'][1] => "warm beer",
      'custom_' . $this->ids['multi']['custom_field_id'][2] => "fl* w*",
      'custom_' . $this->ids['multi2']['custom_field_id'][2] => "vegemite",
    );

    $result = $this->callAPISuccess('Contact', 'create', $params);
    $contact_id = $result['id'];
    $firstCustomField = $this->ids['multi']['custom_field_id'][0];
    $secondCustomField = $this->ids['multi2']['custom_field_id'][0];
    $thirdCustomField = $this->ids['multi2']['custom_field_id'][1];
    $createParams = array(
      'contact_type' => 'Individual',
      'id' => $contact_id,
      'custom_' . $firstCustomField => "value 3",
      'custom_' . $secondCustomField => "coffee",
      'custom_' . $thirdCustomField => "value 4",
    );
    $result = $this->callAPISuccess('Contact', 'create', $createParams);

    $params = array(
      'id' => $result['id'],
      'entity_id' => $result['id'],
    );

    $result = $this->callAPIAndDocument('CustomValue', 'Get', $params, __FUNCTION__, __FILE__, $description);
    $params['format.field_names'] = 1;
    $resultformatted = $this->callAPIAndDocument('CustomValue', 'Get', $params, __FUNCTION__, __FILE__, "utilises field names", 'FormatFieldName');
    // delete the contact
    $this->callAPISuccess('contact', 'delete', array('id' => $contact_id));
    $this->assertEquals('coffee', $result['values'][$secondCustomField]['2']);
    $this->assertEquals('coffee', $result['values'][$secondCustomField]['latest']);
    $this->assertEquals($secondCustomField, $result['values'][$secondCustomField]['id']);
    $this->assertEquals('defaultValue', $result['values'][$secondCustomField]['1']);
    $this->assertEquals($contact_id, $result['values'][$secondCustomField]['entity_id']);
    $this->assertEquals('value 1', $result['values'][$this->ids['single']['custom_field_id']]['0']);
    $this->assertEquals('value 1', $result['values'][$this->ids['single']['custom_field_id']]['latest']);
    $this->assertEquals('value 1', $resultformatted['values']['mySingleField']['latest']);
    $this->assertEquals('', $result['values'][$thirdCustomField]['1']);
    $this->assertEquals('value 4', $result['values'][$thirdCustomField]['2']);
  }

  public function testMultipleCustomValues() {
    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'custom_' . $this->ids['single']['custom_field_id'] => "value 1",
      'custom_' . $this->ids['multi']['custom_field_id'][0] . '-1' => "multi value 1",
      'custom_' . $this->ids['multi']['custom_field_id'][0] . '-2' => "multi value 2",
      'custom_' . $this->ids['multi']['custom_field_id'][1] => "second multi value 1",
    );

    $result = $this->callAPISuccess('Contact', 'create', $params);
    $contact_id = $result['id'];
    $firstCustomField = $this->ids['multi']['custom_field_id'][1];
    $secondCustomField = $this->ids['single']['custom_field_id'];
    $thirdCustomField = $this->ids['multi']['custom_field_id'][0];

    $createParams = array(
      'contact_type' => 'Individual',
      'id' => $contact_id,
      'custom_' . $firstCustomField . '-1' => "second multi value 2",
      'custom_' . $firstCustomField . '-2' => "second multi value 3",
    );
    $result = $this->callAPISuccess('Contact', 'create', $createParams);

    $params = array(
      'id' => $result['id'],
      'entity_id' => $result['id'],
    );

    $result = $this->callAPISuccess('CustomValue', 'Get', $params);
    // delete the contact
    $this->callAPISuccess('contact', 'delete', array('id' => $contact_id));

    $this->assertEquals($contact_id, $result['values'][$secondCustomField]['entity_id']);
    $this->assertEquals('value 1', $result['values'][$secondCustomField]['latest']);
    $this->assertEquals('value 1', $result['values'][$secondCustomField][0]);

    $this->assertEquals($contact_id, $result['values'][$thirdCustomField]['entity_id']);
    $this->assertEquals('multi value 1', $result['values'][$thirdCustomField][1]);
    $this->assertEquals('multi value 2', $result['values'][$thirdCustomField][2]);

    $this->assertEquals($contact_id, $result['values'][$firstCustomField]['entity_id']);
    $this->assertEquals('second multi value 1', $result['values'][$firstCustomField][1]);
    $this->assertEquals('', $result['values'][$firstCustomField][2]);
    $this->assertEquals('second multi value 2', $result['values'][$firstCustomField][3]);
    $this->assertEquals('second multi value 3', $result['values'][$firstCustomField][4]);
    $this->assertEquals('second multi value 3', $result['values'][$firstCustomField]['latest']);
  }

}
