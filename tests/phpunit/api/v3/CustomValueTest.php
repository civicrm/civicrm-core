<<<<<<< HEAD
<?php
/**
 +--------------------------------------------------------------------+
| CiviCRM version 4.5                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * Class api_v3_CustomValueTest
 */
class api_v3_CustomValueTest extends CiviUnitTestCase {
  protected $_apiversion =3;
  protected $individual;
  protected $params;
  protected $ids;

  public $DBResetRequired = FALSE;

  function setUp() {
    parent::setUp();
    $this->individual  = $this->individualCreate();
    $this->params      = array(
      'entity_id' => $this->individual,
    );
    $this->ids['single'] = $this->entityCustomGroupWithSingleFieldCreate('mySingleField', 'Contacts');
    $this->ids['multi']  = $this->CustomGroupMultipleCreateWithFields();
    $this->ids['multi2'] = $this->CustomGroupMultipleCreateWithFields(array('title' => 'group2'));
  }

  function tearDown() {
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
      'custom_' . $this->ids['single']['custom_field_id'] => 'customString') + $this->params;

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
=======
<?php
/**
 * +--------------------------------------------------------------------+
 * | CiviCRM version 4.7                                                |
 * +--------------------------------------------------------------------+
 * | Copyright CiviCRM LLC (c) 2004-2016                                |
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
      'string' => array(substr(sha1(rand()), 0, 4), substr(sha1(rand()), 0, 3), substr(sha1(rand()), 0, 2)),
      'country' => array_rand(CRM_Core_PseudoConstant::country(FALSE, FALSE), 3),
      'state_province' => array_rand(CRM_Core_PseudoConstant::stateProvince(FALSE, FALSE), 3),
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
          $this->callAPISuccess('OptionGroup', 'delete', array('id' => $value['id']));
        }
      }
    }
  }

  public function testCreateCustomValue() {
    $this->_populateOptionAndCustomGroup();

    $customFieldDataType = CRM_Core_BAO_CustomField::dataType();
    $dataToHtmlTypes = CRM_Core_BAO_CustomField::dataToHtml();
    $count = 0;

    foreach ($customFieldDataType as $dataType => $label) {
      switch ($dataType) {
        case 'Country':
        case 'StateProvince':
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
          // skipping File data-type
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
    $seperator = CRM_Core_DAO::VALUE_SEPARATOR;
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

    $params = array('entity_id' => $contactId, 'custom_' . $customId => $selectedValue);
    $this->callAPISuccess('CustomValue', 'create', $params);

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
  }

}
>>>>>>> refs/remotes/civicrm/master
