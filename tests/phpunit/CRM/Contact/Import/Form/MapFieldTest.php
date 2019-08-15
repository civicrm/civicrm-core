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
 * @file
 * File for the CRM_Contact_Import_Form_MapFieldTest class.
 */

/**
 *  Test contact import map field.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Import_Form_MapFieldTest extends CiviUnitTestCase {

  /**
   * Test the form loads without error / notice and mappings are assigned.
   *
   * (Added in conjunction with fixed noting on mapping assignment).
   *
   * @dataProvider getSubmitData
   *
   * @param array $params
   * @param array $mapper
   * @param array $expecteds
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmit($params, $mapper, $expecteds = []) {
    CRM_Core_DAO::executeQuery('CREATE TABLE IF NOT EXISTS civicrm_import_job_xxx (`nada` text, `first_name` text, `last_name` text, `address` text) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    $form = $this->getFormObject('CRM_Contact_Import_Form_MapField');
    /* @var CRM_Contact_Import_Form_MapField $form */
    $form->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
    $form->_columnNames = ['nada', 'first_name', 'last_name', 'address'];
    $form->set('importTableName', 'civicrm_import_job_xxx');
    $form->preProcess();
    $form->submit($params, $mapper);

    CRM_Core_DAO::executeQuery("DROP TABLE civicrm_import_job_xxx");
    if (!empty($expecteds)) {
      foreach ($expecteds as $expected) {
        $result = $this->callAPISuccess($expected['entity'], 'get', array_merge($expected['values'], ['sequential' => 1]));
        $this->assertEquals($expected['count'], $result['count']);
        if (isset($expected['result'])) {
          foreach ($expected['result'] as $key => $expectedValues) {
            foreach ($expectedValues as $valueKey => $value) {
              $this->assertEquals($value, $result['values'][$key][$valueKey]);
            }
          }
        }
      }
    }
    $this->quickCleanup(['civicrm_mapping', 'civicrm_mapping_field']);
  }

  /**
   * Get data to pass through submit function.
   *
   * @return array
   */
  public function getSubmitData() {
    return [
      'basic_data' => [
        [
          'saveMappingName' => '',
          'saveMappingDesc' => '',
        ],
        [
          0 => [0 => 'do_not_import'],
          1 => [0 => 'first_name'],
          2 => [0 => 'last_name'],
          3 => [0 => 'street_address', 1 => 2],
        ],
      ],
      'save_mapping' => [
        [
          'saveMappingName' => 'new mapping',
          'saveMappingDesc' => 'save it',
          'saveMapping' => 1,
        ],
        [
          0 => [0 => 'do_not_import'],
          1 => [0 => 'first_name'],
          2 => [0 => 'last_name'],
          3 => [0 => 'street_address', 1 => 2],
        ],
        [
          ['entity' => 'mapping', 'count' => 1, 'values' => ['name' => 'new mapping']],
          [
            'entity' =>
            'mapping_field',
            'count' => 4,
            'values' => [],
            'result' => [
              0 => ['name' => '- do not import -'],
              1 => ['name' => 'First Name'],
              2 => ['name' => 'Last Name'],
              3 => ['name' => 'Street Address', 'location_type_id' => 2],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Instantiate form object
   *
   * @param string $class
   * @param array $formValues
   * @param string $pageName
   * @return \CRM_Core_Form
   * @throws \CRM_Core_Exception
   */
  public function getFormObject($class, $formValues = [], $pageName = '') {
    $form = parent::getFormObject($class);
    $contactFields = CRM_Contact_BAO_Contact::importableFields();
    $fields = [];
    foreach ($contactFields as $name => $field) {
      $fields[$name] = $field['title'];
    }
    $form->set('fields', $fields);
    return $form;
  }

}
