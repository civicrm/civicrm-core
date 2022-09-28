<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CiviCRM_APIv3
 */
class api_v3_FinancialTypeTest extends CiviUnitTestCase {

  /**
   * Test Create, Read, Update Financial type with custom field.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateFinancialTypeCustomField(): void {
    $this->callAPISuccess('OptionValue', 'create', [
      'label' => ts('Financial Type'),
      'name' => 'civicrm_financial_type',
      'value' => 'FinancialType',
      'option_group_id' => 'cg_extend_objects',
      'is_active' => 1,
    ]);
    // create custom group and custom field
    $customFieldIds = $this->CustomGroupMultipleCreateWithFields([
      'name' => 'Test_Group_Financial_type',
      'title' => 'Test_Group_Financial_type',
      'extends' => 'FinancialType',
      'is_multiple' => FALSE,
    ]);
    $financialTypeData = [
      'Financial Type' . substr(sha1(rand()), 0, 4) => [
        ['Test-1', 'Test-2', NULL],
        [NULL, '', 'Test_3'],
      ],
      'Financial Type' . substr(sha1(rand()), 0, 4) => [
        [NULL, NULL, NULL],
        ['Test_1', NULL, 'Test_3'],
      ],
    ];
    foreach ($financialTypeData as $financialTypeName => $data) {
      $params = [
        'name' => $financialTypeName,
        'is_deductible' => '1',
        'is_reserved' => '0',
        'is_active' => '1',
      ];
      $customFields = [];
      foreach ($data[0] as $key => $value) {
        $customFields['custom_' . $customFieldIds['custom_field_id'][$key]] = $value;
      }

      // create financial type with custom field
      $financialType = $this->callAPISuccess('FinancialType', 'create', array_merge($params, $customFields));
      $this->callAPISuccessGetSingle('FinancialType', ['name' => $financialTypeName]);

      // get financial type to check custom field value
      $expectedResult = array_filter(array_merge($params, $customFields), function($var) {
        return (!is_null($var) && $var != '');
      });
      $this->callAPISuccessGetSingle('FinancialType', [
        'id' => $financialType['id'],
      ], $expectedResult);

      // updated financial type with custom field
      $updateCustomFields = [];
      foreach ($data[1] as $key => $value) {
        $updateCustomFields['custom_' . $customFieldIds['custom_field_id'][$key]] = $value;
        if (!is_null($value)) {
          $customFields['custom_' . $customFieldIds['custom_field_id'][$key]] = $value;
        }
      }
      $this->callAPISuccess('FinancialType', 'create', array_merge([
        'id' => $financialType['id'],
      ], $updateCustomFields));

      // get financial type to check custom field value
      $expectedResult = array_filter(array_merge($params, $customFields), function($var) {
        return (!is_null($var) && $var !== '');
      });
      $this->callAPISuccessGetSingle('FinancialType', [
        'id' => $financialType['id'],
      ], $expectedResult);
      $this->callAPISuccess('FinancialType', 'delete', ['id' => $financialType['id']]);
    }
  }

  /**
   * Enforce the creation of an associated financial account when a financial
   * type is created through the api.
   * @dataProvider versionThreeAndFour
   */
  public function testAssociatedFinancialAccountGetsCreated($apiVersion) {
    $this->callAPISuccess('FinancialType', 'create', [
      'version' => $apiVersion,
      'name' => 'Lottery Tickets',
      'is_deductible' => FALSE,
      'is_reserved' => FALSE,
      'is_active' => TRUE,
    ]);
    // There should be an account (as opposed to type) with the same name that gets autocreated.
    $result = $this->callAPISuccess('FinancialAccount', 'getsingle', [
      'version' => $apiVersion,
      'name' => 'Lottery Tickets',
    ]);
    $this->assertNotEmpty($result['id'], 'Financial account with same name as type did not get created.');
    $this->assertEquals('INC', $result['account_type_code'], 'Financial account created is not an income account.');
  }

  public function testMatchFinancialTypeOptions() {
    // Just a string name, should be simple to match on
    $nonNumericOption = $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'StringName',
    ])['id'];
    // A numeric name, but a number that won't match any existing id
    $numericOptionUnique = $this->callAPISuccess('FinancialType', 'create', [
      'name' => '999',
    ])['id'];
    // Here's the kicker, a numeric name that matches an existing id!
    $numericOptionMatchingExistingId = $this->callAPISuccess('FinancialType', 'create', [
      'name' => $nonNumericOption,
    ])['id'];
    $cid = $this->individualCreate();

    // Create a contribution matching non-numeric name
    $contributionWithNonNumericType = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => 'StringName',
      'total_amount' => 100,
      'contact_id' => $cid,
      'sequential' => TRUE,
    ]);
    $this->assertEquals($nonNumericOption, $contributionWithNonNumericType['values'][0]['financial_type_id']);

    // Create a contribution matching unique numeric name
    $contributionWithUniqueNumericType = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => '999',
      'total_amount' => 100,
      'contact_id' => $cid,
      'sequential' => TRUE,
    ]);
    $this->assertEquals($numericOptionUnique, $contributionWithUniqueNumericType['values'][0]['financial_type_id']);

    // Create a contribution matching the id of the non-numeric option, which is ambiguously the name of another option
    $contributionWithAmbiguousNumericType = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => "$nonNumericOption",
      'total_amount' => 100,
      'contact_id' => $cid,
      'sequential' => TRUE,
    ]);
    // The id should have taken priority over matching by name
    $this->assertEquals($nonNumericOption, $contributionWithAmbiguousNumericType['values'][0]['financial_type_id']);
  }

}
