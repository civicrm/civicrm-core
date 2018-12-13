<?php
/**
 * +--------------------------------------------------------------------+
 * | CiviCRM version 5                                                  |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 * Class api_v3_CustomValueTest
 * @group headless
 */
class api_v3_CustomValueTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $ids;
  protected $optionGroup;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
  }

  public function _populateOptionAndCustomGroup($type = NULL) {
    $dataValues = array(
      'integer' => array(1, 2, 3),
      'number' => array(10.11, 20.22, 30.33),
      'string' => array(substr(sha1(rand()), 0, 4) . '(', substr(sha1(rand()), 0, 3) . '|', substr(sha1(rand()), 0, 2) . ','),
      // 'country' => array_rand(CRM_Core_PseudoConstant::country(FALSE, FALSE), 3),
      // This does not work in the test at the moment due to caching issues.
      //'state_province' => array_rand(CRM_Core_PseudoConstant::stateProvince(FALSE, FALSE), 3),
      'date' => NULL,
      'contact' => NULL,
      'boolean' => NULL,
    );

    $dataValues = !empty($type) ? array($type => $dataValues[$type]) : $dataValues;

    foreach ($dataValues as $dataType => $values) {
      $this->optionGroup[$dataType] = array('values' => $values);
      if (!empty($values)) {
        $result = $this->callAPISuccess('OptionGroup', 'create',
          array(
            'name' => "{$dataType}_group",
            'api.option_value.create' => array('label' => "$dataType 1", 'value' => $values[0]),
            'api.option_value.create.1' => array('label' => "$dataType 2", 'value' => $values[1]),
            'api.option_value.create.2' => array('label' => "$dataType 3", 'value' => $values[2]),
          )
        );
        $this->optionGroup[$dataType]['id'] = $result['id'];
      }
      elseif ($dataType == 'contact') {
        for ($i = 0; $i < 3; $i++) {
          $result = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'email' => substr(sha1(rand()), 0, 7) . '@yahoo.com'));
          $this->optionGroup[$dataType]['values'][$i] = $result['id'];
        }
      }
      $this->ids[$dataType] = $this->entityCustomGroupWithSingleFieldCreate("$dataType Custom Group", 'Contacts');
    }

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

    // cleanup created option group for each custom-set before running next test
    if (!empty($this->optionGroup)) {
      foreach ($this->optionGroup as $type => $value) {
        if (!empty($value['id'])) {
          $count = $this->callAPISuccess('OptionGroup', 'get', array('id' => $value['id']));
          if ((bool) $count['count']) {
            $this->callAPISuccess('OptionGroup', 'delete', array('id' => $value['id']));
          }
        }
      }
    }
  }

  public function testCreateCustomValue() {
    $this->_populateOptionAndCustomGroup();
    $this->_customField = $this->customFieldCreate(array('custom_group_id' => $this->ids['string']['custom_group_id']));
    $this->_customFieldID = $this->_customField['id'];

    $customFieldDataType = CRM_Core_BAO_CustomField::dataType();
    $dataToHtmlTypes = CRM_Core_BAO_CustomField::dataToHtml();
    $count = 0;
    $optionSupportingHTMLTypes = array('Select', 'Radio', 'CheckBox', 'Autocomplete-Select', 'Multi-Select');

    foreach ($customFieldDataType as $dataType => $label) {
      switch ($dataType) {
        // case 'Country':
        // case 'StateProvince':
        case 'String':
        case 'Link':
        case 'Int':
        case 'Float':
        case 'Money':
        case 'Date':
        case 'Boolean':

          //Based on the custom field data-type choose desired SQL operators(to test with) and basic $type
          if (in_array($dataType, array('String', 'Link'))) {
            $validSQLOperators = array('=', '!=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE', 'IS NOT NULL', 'IS NULL');
            $type = 'string';
          }
          elseif ($dataType == 'Boolean') {
            $validSQLOperators = array('=', '!=', 'IS NOT NULL', 'IS NULL');
            $type = 'boolean';
          }
          else {
            if ($dataType == 'Country') {
              $type = 'country';
            }
            elseif ($dataType == 'StateProvince') {
              $type = 'state_province';
            }
            elseif ($dataType == 'ContactReference') {
              $type = 'contact';
            }
            elseif ($dataType == 'Date') {
              $type = 'date';
            }
            else {
              $type = $dataType == 'Int' ? 'integer' : 'number';
            }
            $validSQLOperators = array('=', '!=', 'IN', 'NOT IN', '<=', '>=', '>', '<', 'IS NOT NULL', 'IS NULL');
          }

          //Create custom field of $dataType and html-type $html
          foreach ($dataToHtmlTypes[$count] as $html) {
            // per CRM-18568 the like operator does not currently work for fields with options.
            // the LIKE operator could potentially bypass ACLs (as could IS NOT NULL) and some thought needs to be given
            // to it.
            if (in_array($html, $optionSupportingHTMLTypes)) {
              $validSQLOperators = array_diff($validSQLOperators, array('LIKE', 'NOT LIKE'));
            }
            $params = array(
              'custom_group_id' => $this->ids[$type]['custom_group_id'],
              'label' => "$dataType - $html",
              'data_type' => $dataType,
              'html_type' => $html,
              'default_value' => NULL,
            );
            if (!in_array($html, array('Text', 'TextArea')) && !in_array($dataType, array('Link', 'Date', 'ContactReference', 'Boolean'))) {
              $params += array('option_group_id' => $this->optionGroup[$type]['id']);
            }
            $customField = $this->customFieldCreate($params);
            //Now test with $validSQLOperator SQL operators against its custom value(s)
            $this->_testCustomValue($customField['values'][$customField['id']], $validSQLOperators, $type);
          }
          $count++;
          break;

        default:
          // skipping File data-type & state province due to caching issues
          $count++;
          break;
      }
    }
  }

  public function _testCustomValue($customField, $sqlOps, $type) {
    $isSerialized = CRM_Core_BAO_CustomField::isSerialized($customField);
    $customId = $customField['id'];
    $params = array(
      'contact_type' => 'Individual',
      'email' => substr(sha1(rand()), 0, 7) . 'man1@yahoo.com',
    );
    $result = $this->callAPISuccess('Contact', 'create', $params);
    $contactId = $result['id'];

    $count = rand(1, 2);

    if ($isSerialized) {
      $selectedValue = $this->optionGroup[$type]['values'];
      $notselectedValue = $selectedValue[$count];
      unset($selectedValue[$count]);
    }
    elseif ($customField['html_type'] == 'Link') {
      $selectedValue = "http://" . substr(sha1(rand()), 0, 7) . ".com";
      $notselectedValue = "http://" . substr(sha1(rand()), 0, 7) . ".com";
    }
    elseif ($type == 'date') {
      $selectedValue = date('Ymd');
      $notselectedValue = $lesserSelectedValue = date('Ymd', strtotime('yesterday'));
      $greaterSelectedValue = date('Ymd', strtotime('+ 1 day'));
    }
    elseif ($type == 'contact') {
      $selectedValue = $this->optionGroup[$type]['values'][1];
      $notselectedValue = $this->optionGroup[$type]['values'][0];
    }
    elseif ($type == 'boolean') {
      $selectedValue = 1;
      $notselectedValue = 0;
    }
    else {
      $selectedValue = $this->optionGroup[$type]['values'][0];
      $notselectedValue = $this->optionGroup[$type]['values'][$count];
      if (in_array(">", $sqlOps)) {
        $greaterSelectedValue = $selectedValue + 1;
        $lesserSelectedValue = $selectedValue - 1;
      }
    }

    $params = [
      'entity_id' => $contactId,
      'custom_' . $customId => $selectedValue,
      "custom_{$this->_customFieldID}" => "Test String Value for {$this->_customFieldID}",
    ];
    $this->callAPISuccess('CustomValue', 'create', $params);

    //Test for different return value syntax.
    $returnValues = [
      ['return' => "custom_{$customId}"],
      ['return' => ["custom_{$customId}"]],
      ["return.custom_{$customId}" => 1],
      ['return' => ["custom_{$customId}", "custom_{$this->_customFieldID}"]],
      ["return.custom_{$customId}" => 1, "return.custom_{$this->_customFieldID}" => 1],
    ];
    foreach ($returnValues as $key => $val) {
      $params = array_merge($val, [
        'entity_id' => $contactId,
      ]);
      $customValue = $this->callAPISuccess('CustomValue', 'get', $params);
      if (is_array($selectedValue)) {
        $expected = array_values($selectedValue);
        $this->checkArrayEquals($expected, $customValue['values'][$customId]['latest']);
      }
      elseif ($type == 'date') {
        $this->assertEquals($selectedValue, date('Ymd', strtotime(str_replace('.', '/', $customValue['values'][$customId]['latest']))));
      }
      else {
        $this->assertEquals($selectedValue, $customValue['values'][$customId]['latest']);
      }
      if ($key > 2) {
        $this->assertEquals("Test String Value for {$this->_customFieldID}", $customValue['values'][$this->_customFieldID]['latest']);
      }
    }

    foreach ($sqlOps as $op) {
      $qillOp = CRM_Utils_Array::value($op, CRM_Core_SelectValues::getSearchBuilderOperators(), $op);
      switch ($op) {
        case '=':
          $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => (is_array($selectedValue) ? implode(CRM_Core_DAO::VALUE_SEPARATOR, $selectedValue) : $selectedValue)));
          $this->assertEquals($contactId, $result['id']);
          break;

        case '!=':
          $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => $notselectedValue)));
          $this->assertEquals(TRUE, array_key_exists($contactId, $result['values']));
          break;

        case '>':
        case '<':
        case '>=':
        case '<=':
          if ($isSerialized) {
            continue;
          }
          // To be precise in for these operator we can't just rely on one contact,
          // hence creating multiple contact with custom value less/more then $selectedValue respectively
          $result = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'email' => substr(sha1(rand()), 0, 7) . 'man2@yahoo.com'));
          $contactId2 = $result['id'];
          $this->callAPISuccess('CustomValue', 'create', array('entity_id' => $contactId2, 'custom_' . $customId => $lesserSelectedValue));

          if ($op == '>') {
            $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => $lesserSelectedValue)));
            $this->assertEquals($contactId, $result['id']);
          }
          elseif ($op == '<') {
            $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => $selectedValue)));
            $this->assertEquals($contactId2, $result['id']);
          }
          else {
            $result = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'email' => substr(sha1(rand()), 0, 7) . 'man3@yahoo.com'));
            $contactId3 = $result['id'];
            $this->callAPISuccess('CustomValue', 'create', array('entity_id' => $contactId3, 'custom_' . $customId => $greaterSelectedValue));

            $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => $selectedValue)));

            $this->assertEquals($contactId, $result['values'][$contactId]['id']);
            if ($op == '>=') {
              $this->assertEquals($contactId3, $result['values'][$contactId3]['id']);
            }
            else {
              $this->assertEquals($contactId2, $result['values'][$contactId2]['id']);
            }
            $this->callAPISuccess('contact', 'delete', array('id' => $contactId3));
          }

          $this->callAPISuccess('contact', 'delete', array('id' => $contactId2));
          break;

        case 'IN':
          $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => (array) $selectedValue)));
          $this->assertEquals($contactId, $result['id']);
          break;

        case 'NOT IN':
          $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => (array) $notselectedValue)));
          $this->assertEquals($contactId, $result['id']);
          break;

        case 'LIKE':
          $selectedValue = is_array($selectedValue) ? $selectedValue[0] : $selectedValue;
          $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => "%$selectedValue%")));
          $this->assertEquals($contactId, $result['id']);
          break;

        case 'NOT LIKE':
          $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => $notselectedValue)));
          $this->assertEquals($contactId, $result['id']);
          break;

        case 'IS NULL':
          $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => 1)));
          $this->assertEquals(FALSE, array_key_exists($contactId, $result['values']));
          break;

        case 'IS NOT NULL':
          $result = $this->callAPISuccess('Contact', 'Get', array('custom_' . $customId => array($op => 1)));
          $this->assertEquals($contactId, $result['id']);
          break;
      }
    }

    $this->callAPISuccess('Contact', 'delete', array('id' => $contactId));
  }

  /**
   * Ensure custom data is updated when option values are modified
   *
   * @link https://issues.civicrm.org/jira/browse/CRM-11856
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testAlterOptionValue() {
    $this->_populateOptionAndCustomGroup('string');

    $selectField = $this->customFieldCreate(array(
      'custom_group_id' => $this->ids['string']['custom_group_id'],
      'label' => 'Custom Select',
      'html_type' => 'Select',
      'option_group_id' => $this->optionGroup['string']['id'],
    ));
    $selectField = civicrm_api3('customField', 'getsingle', array('id' => $selectField['id']));
    $radioField = $this->customFieldCreate(array(
      'custom_group_id' => $this->ids['string']['custom_group_id'],
      'label' => 'Custom Radio',
      'html_type' => 'Radio',
      'option_group_id' => $selectField['option_group_id'],
    ));
    $multiSelectField = $this->customFieldCreate(array(
      'custom_group_id' => $this->ids['string']['custom_group_id'],
      'label' => 'Custom Multi-Select',
      'html_type' => 'Multi-Select',
      'option_group_id' => $selectField['option_group_id'],
    ));
    $selectName = 'custom_' . $selectField['id'];
    $radioName = 'custom_' . $radioField['id'];
    $multiSelectName = 'custom_' . $multiSelectField['id'];
    $controlFieldName = 'custom_' . $this->ids['string']['custom_field_id'];

    $params = array(
      'first_name' => 'abc4',
      'last_name' => 'xyz4',
      'contact_type' => 'Individual',
      'email' => 'man4@yahoo.com',
      $selectName => $this->optionGroup['string']['values'][0],
      $multiSelectName => $this->optionGroup['string']['values'],
      $radioName => $this->optionGroup['string']['values'][1],
      // The control group in a science experiment should be unaffected
      $controlFieldName => $this->optionGroup['string']['values'][2],
    );

    $contact = $this->callAPISuccess('Contact', 'create', $params);

    $result = $this->callAPISuccess('Contact', 'getsingle', array(
      'id' => $contact['id'],
      'return' => array($selectName, $multiSelectName),
    ));
    $this->assertEquals($params[$selectName], $result[$selectName]);
    $this->assertEquals($params[$multiSelectName], $result[$multiSelectName]);

    $this->callAPISuccess('OptionValue', 'create', array(
      'value' => 'one-modified',
      'option_group_id' => $selectField['option_group_id'],
      'name' => 'string 1',
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
    $this->assertEquals(array('one-modified', $params[$radioName], $params[$controlFieldName]), $result[$multiSelectName]);
    // This field should not have changed because we didn't alter this option
    $this->assertEquals($params[$radioName], $result[$radioName]);
    // This should not have changed because this field doesn't use the affected option group
    $this->assertEquals($params[$controlFieldName], $result[$controlFieldName]);
    // Add test of proof that multivalue fields.
    $this->callAPISuccess('CustomValue', 'create', array(
      'entity_id' => $contact['id'],
      $multiSelectName => array($params[$radioName], $params[$controlFieldName]),
    ));
    $result = $this->callAPISuccess('Contact', 'getsingle', array(
      'id' => $contact['id'],
      'return' => array($selectName, $multiSelectName, $controlFieldName, $radioName),
    ));

    $this->assertEquals(array($params[$radioName], $params[$controlFieldName]), $result[$multiSelectName]);
  }

  public function testGettree() {
    $cg = $this->callAPISuccess('CustomGroup', 'create', array(
      'title' => 'TestGettree',
      'extends' => 'Individual',
    ));
    $cf = $this->callAPISuccess('CustomField', 'create', array(
      'custom_group_id' => $cg['id'],
      'label' => 'Got Options',
      'name' => 'got_options',
      "data_type" => "String",
      "html_type" => "Multi-Select",
      'option_values' => array('1' => 'One', '2' => 'Two', '3' => 'Three'),
    ));
    $fieldName = 'custom_' . $cf['id'];
    $contact = $this->individualCreate(array($fieldName => array('2', '3')));

    // Verify values are formatted correctly
    $tree = $this->callAPISuccess('CustomValue', 'gettree', array('entity_type' => 'Contact', 'entity_id' => $contact));
    $this->assertEquals(array('2', '3'), $tree['values']['TestGettree']['fields']['got_options']['value']['data']);
    $this->assertEquals('Two, Three', $tree['values']['TestGettree']['fields']['got_options']['value']['display']);

    // Try limiting the return params
    $tree = $this->callAPISuccess('CustomValue', 'gettree', array(
      'entity_type' => 'Contact',
      'entity_id' => $contact,
      'return' => array(
        'custom_group.id',
        'custom_field.id',
      ),
    ));
    $this->assertEquals(array('2', '3'), $tree['values']['TestGettree']['fields']['got_options']['value']['data']);
    $this->assertEquals('Two, Three', $tree['values']['TestGettree']['fields']['got_options']['value']['display']);
    $this->assertEquals(array('id', 'fields'), array_keys($tree['values']['TestGettree']));

    // Ensure display values are returned even if data is not
    $tree = $this->callAPISuccess('CustomValue', 'gettree', array(
      'entity_type' => 'Contact',
      'entity_id' => $contact,
      'return' => array(
        'custom_value.display',
      ),
    ));
    $this->assertEquals('Two, Three', $tree['values']['TestGettree']['fields']['got_options']['value']['display']);
    $this->assertFalse(isset($tree['values']['TestGettree']['fields']['got_options']['value']['data']));

    // Verify that custom set appears for individuals even who don't have any custom data
    $contact2 = $this->individualCreate();
    $tree = $this->callAPISuccess('CustomValue', 'gettree', array('entity_type' => 'Contact', 'entity_id' => $contact2));
    $this->assertArrayHasKey('TestGettree', $tree['values']);

    // Verify that custom set doesn't appear for other contact types
    $org = $this->organizationCreate();
    $tree = $this->callAPISuccess('CustomValue', 'gettree', array('entity_type' => 'Contact', 'entity_id' => $org));
    $this->assertArrayNotHasKey('TestGettree', $tree['values']);

  }

  public function testGettree_getfields() {
    $fields = $this->callAPISuccess('CustomValue', 'getfields', array('api_action' => 'gettree'));
    $fields = $fields['values'];
    $this->assertTrue((bool) $fields['entity_id']['api.required']);
    $this->assertTrue((bool) $fields['entity_type']['api.required']);
    $this->assertEquals('custom_group.id', $fields['custom_group.id']['name']);
    $this->assertEquals('custom_field.id', $fields['custom_field.id']['name']);
    $this->assertEquals('custom_value.id', $fields['custom_value.id']['name']);
  }

  /**
   * Test that custom fields in greeting strings are updated.
   */
  public function testUpdateCustomGreetings() {
    // Create a custom group with one field.
    $customGroupResult = $this->callAPISuccess('CustomGroup', 'create', array(
      'sequential' => 1,
      'title' => "test custom group",
      'extends' => "Individual",
    ));
    $customFieldResult = $this->callAPISuccess('CustomField', 'create', array(
      'custom_group_id' => $customGroupResult['id'],
      'label' => "greeting test",
      'data_type' => "String",
      'html_type' => "Text",
    ));
    $customFieldId = $customFieldResult['id'];

    // Create a contact with an email greeting format that includes the new custom field.
    $contactResult = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'email' => substr(sha1(rand()), 0, 7) . '@yahoo.com',
      'email_greeting_id' => "Customized",
      'email_greeting_custom' => "Dear {contact.custom_{$customFieldId}}",
    ));
    $cid = $contactResult['id'];

    // Define testing values.
    $uniq = uniqid();
    $testGreetingValue = "Dear $uniq";

    // Update contact's custom field with CustomValue.create
    $customValueResult = $this->callAPISuccess('CustomValue', 'create', array(
      'entity_id' => $cid,
      "custom_{$customFieldId}" => $uniq,
      'entity_table' => "civicrm_contact",
    ));

    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $cid, 'return' => 'email_greeting'));
    $this->assertEquals($testGreetingValue, $contact['email_greeting_display']);

  }

  /**
   * Creates a multi-valued custom field set and creates a contact with mutliple values for it.
   *
   * @return array
   */
  private function _testGetCustomValueMultiple() {
    $fieldIDs = $this->CustomGroupMultipleCreateWithFields();
    $customFieldValues = [];
    foreach ($fieldIDs['custom_field_id'] as $id) {
      $customFieldValues["custom_{$id}"] = "field_{$id}_value_1";
    }
    $this->assertNotEmpty($customFieldValues);
    $contactParams = [
      'first_name' => 'Jane',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
    ];
    $contact = $this->callAPISuccess('Contact', 'create', array_merge($contactParams, $customFieldValues));
    foreach ($fieldIDs['custom_field_id'] as $id) {
      $customFieldValues["custom_{$id}"] = "field_{$id}_value_2";
    }
    $result = $this->callAPISuccess('Contact', 'create', array_merge(['id' => $contact['id']], $customFieldValues));
    return [
      $contact['id'],
      $customFieldValues,
    ];
  }

  /**
   * Test that specific custom values can be retrieved while using return with comma separated values as genererated by the api explorer.
   * ['return' => 'custom_1,custom_2']
   */
  public function testGetCustomValueReturnMultipleApiExplorer() {
    list($cid, $customFieldValues) = $this->_testGetCustomValueMultiple();
    $result = $this->callAPISuccess('CustomValue', 'get', [
      'return' => implode(',', array_keys($customFieldValues)),
      'entity_id' => $cid,
    ]);
    $this->assertEquals(count($customFieldValues), $result['count']);
  }

  /**
   * Test that specific custom values can be retrieved while using return with array style syntax.
   * ['return => ['custom_1', 'custom_2']]
   */
  public function testGetCustomValueReturnMultipleArray() {
    list($cid, $customFieldValues) = $this->_testGetCustomValueMultiple();
    $result = $this->callAPISuccess('CustomValue', 'get', [
      'return' => array_keys($customFieldValues),
      'entity_id' => $cid,
    ]);
    $this->assertEquals(count($customFieldValues), $result['count']);
  }

  /**
   * Test that specific custom values can be retrieved while using a list of return parameters.
   * [['return.custom_1' => '1'], ['return.custom_2' => '1']]
   */
  public function testGetCustomValueReturnMultipleList() {
    list($cid, $customFieldValues) = $this->_testGetCustomValueMultiple();
    $returnArray = [];
    foreach ($customFieldValues as $field => $value) {
      $returnArray["return.{$field}"] = 1;
    }
    $result = $this->callAPISuccess('CustomValue', 'get', array_merge($returnArray, ['entity_id' => $cid]));
    $this->assertEquals(count($customFieldValues), $result['count']);
  }

}
