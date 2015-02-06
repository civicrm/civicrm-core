<?php
/**
 * +--------------------------------------------------------------------+
 * | CiviCRM version 4.6                                                |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2014                                |
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
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $result = $this->callAPISuccess('custom_value', 'get', $params);
  }

  public function testGetMultipleCustomValues() {

    $description = "/*this demonstrates the use of CustomValue get";

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
    $resultformatted = $this->callAPIAndDocument('CustomValue', 'Get', $params, __FUNCTION__, __FILE__, "utilises field names", 'formatFieldName');
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

  /**
   * Unit test for CRM-15915.
   *
   * The values for a multi-select custom field on a contact are returned as a 
   * list. This unit test should pass.
   */
  public function testMultiSelectCustomValuesContact() {
    $custom_group_result = $this->CallApiSuccess('CustomGroup', 'Create', array(
      'name' => 'custom_group_on_contact',
      'title' => 'Custom group on contact (test)',
      'extends' => 'Contact',
      'is_active' => 1,
      'sequential' => 1,
    ));

    $custom_field_result = $this->CallApiSuccess('CustomField', 'Create', array(
      'custom_group_id' => $custom_group_result['id'],
      'weight' => 1,
      'name' => 'my_custom_field',
      'label' => 'My custom field',
      'data_type' => 'String',
      'html_type' => 'Multi-Select',
      'option_values' => array(
        'A' => array(
          'weight' => 1,
          'value' => 'A',
          'label' => 'label A',
          'is_active' => 1,
        ),
        'B' => array(
          'weight' => 2,
          'value' => 'B',
          'label' => 'label B',
          'is_active' => 1,
        ),
        'C' => array(
          'weight' => 3,
          'value' => 'C',
          'label' => 'label C',
          'is_active' => 1,
        ),
      ),
      'is_active' => 1,
      'sequential' => 1,
    ));
    
    $contact_create_result = $this->CallApiSuccess('Contact', 'Create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Joe',
      'last_name' => 'Schmoe',
      'custom_'.$custom_field_result['id'] => array('B'),
      'sequential' => 1,
    ));

    $contact_get_result = $this->CallApiSuccess('Contact', 'GetSingle', array(
      'id' => $contact_create_result['id'],
      'return' => 'custom_'.$custom_field_result['id'],
      'sequential' => 1, 
    ));

    // Clean up first, then assert.

    $this->CallApiSuccess('Contact', 'Delete', array(
      'id' => $contact_get_result['id'],
    ));

    $this->CallApiSuccess('CustomField', 'Delete', array(
      'id' => $custom_field_result['id'],
    ));

    $this->CallApiSuccess('CustomGroup', 'Delete', array(
      'id' => $custom_group_result['id'],
    ));

    $this->AssertEquals('B',$contact_get_result['custom_'.$custom_field_result['id']][0]);
  }

  /**
   * Unit test for CRM-15915.
   *
   * The values for a multi-select custom field should be returned as a list.
   * This unit test will fail until CRM-15915 is fixed, because the custom field
   * applies to a relationship.
   */
  public function testMultiSelectCustomValuesRelationship() {
    $custom_group_result = $this->CallApiSuccess('CustomGroup', 'Create', array(
      'name' => 'custom_group_on_relationship',
      'title' => 'Custom group on relationship (test)',
      'extends' => 'Relationship',
      'is_active' => 1,
      'sequential' => 1,
    ));

    $custom_field_result = $this->CallApiSuccess('CustomField', 'Create', array(
      'custom_group_id' => $custom_group_result['id'],
      'weight' => 1,
      'name' => 'my_custom_field',
      'label' => 'My custom field',
      'data_type' => 'String',
      'html_type' => 'Multi-Select',
      'option_values' => array(
        'A' => array(
          'weight' => 1,
          'value' => 'A',
          'label' => 'label A',
          'is_active' => 1,
        ),
        'B' => array(
          'weight' => 2,
          'value' => 'B',
          'label' => 'label B',
          'is_active' => 1,
        ),
        'C' => array(
          'weight' => 3,
          'value' => 'C',
          'label' => 'label C',
          'is_active' => 1,
        ),
      ),
      'is_active' => 1,
      'sequential' => 1,
    ));

    $organization_create_result = $this->CallApiSuccess('Contact', 'Create', array(
      'contact_type' => 'Organization',
      'organization_name' => 'Schmoe inc.',
      'sequential' => 1,
    ));
    
    $contact_create_result = $this->CallApiSuccess('Contact', 'Create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Joe',
      'last_name' => 'Schmoe',
      'api.relationship.create' => array(
        'contact_id_a' => '$value.id',
        'contact_id_b' => $organization_create_result['id'],
        'relationship_type_id' => 5,  // works for
        'custom_'.$custom_field_result['id'] => array('B'),
      ),
      'sequential' => 1,
    ));
    
    $relationship_id=$contact_create_result['values'][0]['api.relationship.create']['id'];

    $relationship_get_result = $this->CallApiSuccess('Relationship', 'GetSingle', array(
      'id' => $relationship_id,
      'return' => 'custom_'.$custom_field_result['id'],
      'sequential' => 1, 
    ));

    // Clean up first, then assert.

    $this->CallApiSuccess('Contact', 'Delete', array(
      'id' => $contact_create_result['id'],
    ));
    
    $this->CallApiSuccess('Contact', 'Delete', array(
      'id' => $organization_create_result['id'],
    ));

    $this->CallApiSuccess('CustomField', 'Delete', array(
      'id' => $custom_field_result['id'],
    ));

    $this->CallApiSuccess('CustomGroup', 'Delete', array(
      'id' => $custom_group_result['id'],
    ));

    $this->AssertEquals('B',$relationship_get_result['custom_'.$custom_field_result['id']][0]);
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
