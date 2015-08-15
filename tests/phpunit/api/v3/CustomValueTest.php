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
  protected $ids;
  protected $optionGroup;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();

    $this->_populateOptionAndCustomGroup();
  }

  public function _populateOptionAndCustomGroup() {
    $dataValues = array(
      'integer' => array(1, 2, 3),
      'number' => array(10.11, 20.22, 30.33),
      'string' => array(substr(sha1(rand()), 0, 4), substr(sha1(rand()), 0, 3), substr(sha1(rand()), 0, 2)),
      'country' => array_rand(CRM_Core_PseudoConstant::country(FALSE, FALSE), 3),
      'state_province' => array_rand(CRM_Core_PseudoConstant::stateProvince(FALSE, FALSE), 3),
      'date' => NULL,
    );

    foreach ($dataValues as $dataType => $values) {
      if (!empty($values)) {
        $this->optionGroup[$dataType] = array('values' => $values);
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
  }

  public function testCreateCustomValue() {
    $customFieldDataType = CRM_Core_BAO_CustomField::dataType();
    $dataToHtmlTypes = CRM_Core_BAO_CustomField::dataToHtml();
    $count = 0;

    foreach ($customFieldDataType as $dataType => $label) {
      $validSQLOperator = array('=', "!=", 'IN', 'NOT IN', 'IS NOT NULL', 'IS NULL');
      switch ($dataType) {
        case 'Date':
        case 'StateProvince';
        case 'String':
        case 'Link':
        case 'Int':
        case 'Float':
        case 'Money':

          if (in_array($dataType, array('String', 'Link'))) {
            $validSQLOperator += array('LIKE', "NOT LIKE");
            $type = 'string';
          }
          else {
            if ($dataType == 'Country') {
              $type == 'country';
            }
            elseif ($dataType == 'StateProvince') {
              $type = 'state_province';
            }
            else {
              $type = $dataType == 'Int' ? 'integer' : ($dataType == 'Date' ? 'date' : 'number');
            }
            $validSQLOperator += array('<=', '>=', '>', '<');
          }

          foreach ($dataToHtmlTypes[$count] as $html) {
            $params = array(
              'custom_group_id' => $this->ids[$type]['custom_group_id'],
              'label' => "$dataType - $html",
              'data_type' => $dataType,
              'html_type' => $html,
              'default_value' => NULL,
            );
            if (!in_array($html, array('Text', 'TextArea', 'Link', 'Select Date'))) {
              $params += array('option_group_id' => $this->optionGroup[$type]['id']);
            }
            $customField = $this->customFieldCreate($params);
            $this->_testCustomValue($customField['values'][$customField['id']], $validSQLOperator, $type);
          }
          $count++;
          break;

        case 'ContactReference':
          $validSQLOperator += array('<=', '>=', '>', '<');
          $count++;
          break;

        default:
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
      $notselectedValue = date('Ymd', strtotime('yesterday'));
    }
    else {
      $selectedValue = $this->optionGroup[$type]['values'][0];
      $notselectedValue = $this->optionGroup[$type]['values'][$count];
    }

    $params = array('entity_id' => $contactId, 'custom_' . $customId => $selectedValue);
    $this->callAPISuccess('CustomValue', 'create', $params);

    foreach ($sqlOps as $op) {
      $description = "Find Contact whose $customField[label] $op $notselectedValue";
      switch ($op) {
        case '!=':
          $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => $notselectedValue)), __FUNCTION__, __FILE__, $description);
          $this->assertEquals($contactId, $result['id']);
          break;

        case '>':
        case '<':
        case '>=':
        case '<=':
          // To be precise in for these operator we can't just rely on one contact,
          // hence creating multiple contact with custom value less/more then $selectedValue respectively
          $result = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'email' => substr(sha1(rand()), 0, 7) . 'man2@yahoo.com'));
          $contactId2 = $result['id'];
          $lesserSelectedValue = $type == 'date' ? date('Ymd', strtotime('- 1 day')) : $selectedValue - 1;
          $this->callAPISuccess('CustomValue', 'create', array('entity_id' => $contactId2, 'custom_' . $customId => $lesserSelectedValue));

          if ($op == '>') {
            $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => $lesserSelectedValue)), __FUNCTION__, __FILE__, $description);
            $this->assertEquals($contactId, $result['id']);
          }
          elseif ($op == '<') {
            $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => $selectedValue)), __FUNCTION__, __FILE__, $description);
            $this->assertEquals($contactId2, $result['id']);
          }
          else {
            $result = $this->callAPISuccess('Contact', 'create', array('contact_type' => 'Individual', 'email' => substr(sha1(rand()), 0, 7) . 'man3@yahoo.com'));
            $contactId3 = $result['id'];
            $greaterSelectedValue = $type == 'date' ? date('Ymd', strtotime('+ 1 day')) : $selectedValue + 1;
            $this->callAPISuccess('CustomValue', 'create', array('entity_id' => $contactId3, 'custom_' . $customId => $greaterSelectedValue));

            $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => $selectedValue)), __FUNCTION__, __FILE__, $description);

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
          $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => (array) $selectedValue)), __FUNCTION__, __FILE__, $description);
          $this->assertEquals($contactId, $result['id']);
          break;

        case 'NOT IN':
          $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => (array) $notselectedValue)), __FUNCTION__, __FILE__, $description);
          $this->assertEquals($contactId, $result['id']);
          break;

        case 'LIKE':
          $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => (is_array($selectedValue) ? "%" . $selectedValue[0] . "%" : $selectedValue),),), __FUNCTION__, __FILE__, $description);
          $this->assertEquals($contactId, $result['id']);
          break;

        case 'NOT LIKE':
          $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => $notselectedValue)), __FUNCTION__, __FILE__, $description);
          $this->assertEquals($contactId, $result['id']);
          break;

        case 'IS NULL':
          $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId  => array($op => 1)), __FUNCTION__, __FILE__, $description);
          $this->assertEquals(FALSE, array_key_exists($contactId, $result['values']));
          break;

        case 'IS NOT NULL':
          $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => array($op => 1)),__FUNCTION__, __FILE__, $description);
          $this->assertEquals($contactId, $result['id']);
          break;

        default:
          $result = $this->callAPIAndDocument('Contact', 'Get', array('custom_' . $customId => (is_array($selectedValue) ? implode($seperator, $selectedValue) : $selectedValue)), __FUNCTION__, __FILE__, $description);
          $this->assertEquals($contactId, $result['id']);
          break;
      }
    }

    $this->callAPISuccess('Contact', 'delete', array('id' => $contactId));
  }

}
