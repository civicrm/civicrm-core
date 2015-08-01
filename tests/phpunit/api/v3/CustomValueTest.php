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

  /**
   * Ensure custom data is updated when option values are modified
   *
   * @link https://issues.civicrm.org/jira/browse/CRM-11856
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testAlterOptionValue() {
    $selectField = $this->customFieldCreate(array(
      'custom_group_id' => $this->ids['single']['custom_group_id'],
      'label' => 'Custom Select',
      'html_type' => 'Select',
      'option_values' => array(
        'one' => 'Option1',
        'two' => 'Option2',
        'notone' => 'OptionNotOne',
      ),
    ));
    $selectField = civicrm_api3('customField', 'getsingle', array('id' => $selectField['id']));
    $radioField = $this->customFieldCreate(array(
      'custom_group_id' => $this->ids['single']['custom_group_id'],
      'label' => 'Custom Radio',
      'html_type' => 'Radio',
      'option_group_id' => $selectField['option_group_id'],
    ));
    $multiSelectField = $this->customFieldCreate(array(
      'custom_group_id' => $this->ids['single']['custom_group_id'],
      'label' => 'Custom Multi-Select',
      'html_type' => 'Multi-Select',
      'option_group_id' => $selectField['option_group_id'],
    ));
    $selectName = 'custom_' . $selectField['id'];
    $radioName = 'custom_' . $radioField['id'];
    $multiSelectName = 'custom_' . $multiSelectField['id'];
    $controlFieldName = 'custom_' . $this->ids['single']['custom_field_id'];

    $params = array(
      'first_name' => 'abc4',
      'last_name' => 'xyz4',
      'contact_type' => 'Individual',
      'email' => 'man4@yahoo.com',
      $selectName => 'one',
      $multiSelectName => array('one', 'two', 'notone'),
      $radioName => 'notone',
      // The control group in a science experiment should be unaffected
      $controlFieldName => 'one',
    );

    $contact = $this->callAPISuccess('Contact', 'create', $params);

    $result = $this->callAPISuccess('Contact', 'getsingle', array(
      'id' => $contact['id'],
      'return' => array($selectName, $multiSelectName),
    ));
    $this->assertEquals('one', $result[$selectName]);
    $this->assertEquals(array('one', 'two', 'notone'), $result[$multiSelectName]);

    $this->callAPISuccess('OptionValue', 'create', array(
      'value' => 'one-modified',
      'option_group_id' => $selectField['option_group_id'],
      'name' => 'Option1',
      'options' => array(
        'match-mandatory' => array('option_group_id', 'name'),
      ),
    ));

    $result = $this->callAPISuccess('Contact', 'getsingle', array(
      'id' => $contact['id'],
      'return' => array($selectName, $multiSelectName, $controlFieldName, $radioName),
    ));
    // Ensure the relevant fields have been updated
    $this->assertEquals('one-modified', $result[$selectName]);
    $this->assertEquals(array('one-modified', 'two', 'notone'), $result[$multiSelectName]);
    // This field should not have changed because we didn't alter this option
    $this->assertEquals('notone', $result[$radioName]);
    // This should not have changed because this field doesn't use the affected option group
    $this->assertEquals('one', $result[$controlFieldName]);
  }

}
