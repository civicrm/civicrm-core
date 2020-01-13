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
   */
  public function testCreateUpdateFinancialTypeCustomField() {
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
        return (!is_null($var) && $var != '');
      });
      $this->callAPISuccessGetSingle('FinancialType', [
        'id' => $financialType['id'],
      ], $expectedResult);
      $this->callAPISuccess('FinancialType', 'delete', ['id' => $financialType['id']]);
    }
  }

}
