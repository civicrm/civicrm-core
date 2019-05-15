<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
    }
  }

}
