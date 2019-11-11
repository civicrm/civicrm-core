<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2020                                |
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
 * Test class for CRM_Contact_BAO_Group BAO
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_SavedSearchTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   *
   * This method is called before a test is executed.
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown() {
    $this->quickCleanup([
      'civicrm_mapping_field',
      'civicrm_mapping',
      'civicrm_group',
      'civicrm_saved_search',
    ]);
  }

  /**
   * Test setDefaults for privacy radio buttons.
   *
   * @throws \Exception
   */
  public function testDefaultValues() {
    $sg = new CRM_Contact_Form_Search_Advanced();
    $sg->controller = new CRM_Core_Controller();
    $formValues = [
      'group_search_selected' => 'group',
      'privacy_options' => ['do_not_email'],
      'privacy_operator' => 'OR',
      'privacy_toggle' => 2,
      'operator' => 'AND',
      'component_mode' => 1,
    ];
    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_saved_search (form_values) VALUES('" . serialize($formValues) . "')"
    );
    $ssID = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    $sg->set('ssID', $ssID);
    $sg->set('formValues', $formValues);

    $defaults = $sg->setDefaultValues();

    $this->checkArrayEquals($defaults, $formValues);
  }

  /**
   * Test fixValues function.
   *
   * @dataProvider getSavedSearches
   */
  public function testGetFormValues($formValues, $expectedResult, $searchDescription) {
    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_saved_search (form_values) VALUES('" . serialize($formValues) . "')"
    );
    $result = CRM_Contact_BAO_SavedSearch::getFormValues(CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()'));
    $this->assertEquals(['membership_type_id', 'membership_status_id'], array_keys($result));
    foreach ($result as $key => $value) {
      $this->assertEquals($expectedResult, $value, 'failure on set ' . $searchDescription);
    }
  }

  /**
   * Test if skipped elements are correctly
   * stored and retrieved as formvalues.
   */
  public function testSkippedElements() {
    $relTypeID = $this->relationshipTypeCreate();
    $savedSearch = new CRM_Contact_BAO_SavedSearch();
    $formValues = [
      'operator' => 'AND',
      'title' => 'testsmart',
      'radio_ts' => 'ts_all',
      'component_mode' => CRM_Contact_BAO_Query::MODE_CONTACTS,
      'display_relationship_type' => "{$relTypeID}_a_b",
      'uf_group_id' => 1,
    ];
    $queryParams = [];
    CRM_Contact_BAO_SavedSearch::saveSkippedElement($queryParams, $formValues);
    $savedSearch->form_values = serialize($queryParams);
    $savedSearch->save();

    $result = CRM_Contact_BAO_SavedSearch::getFormValues(CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()'));
    $expectedResult = [
      'operator' => 'AND',
      'component_mode' => CRM_Contact_BAO_Query::MODE_CONTACTS,
      'display_relationship_type' => "{$relTypeID}_a_b",
      'uf_group_id' => 1,
    ];
    $this->checkArrayEquals($result, $expectedResult);
  }

  /**
   * Get variants of the fields we want to test.
   *
   * @return array
   */
  public function getSavedSearches() {
    $return = [];
    $searches = $this->getSearches();
    foreach ($searches as $key => $search) {
      $return[] = [$search['form_values'], $search['expected'], $key];
    }
    return $return;
  }

  /**
   * Get variants of potential saved form values.
   *
   * Note that we include 1 in various ways to cover the possibility that 1 is treated as a boolean.
   *
   * @return array
   */
  public function getSearches() {
    return [
      'checkbox_format_1_first' => [
        'form_values' => [
          'member_membership_type_id' => [1 => 1, 2 => 1],
          'member_status_id' => [1 => 1, 2 => 1],
        ],
        'expected' => [1, 2],
      ],
      'checkbox_format_1_later' => [
        'form_values' => [
          'member_membership_type_id' => [2 => 1, 1 => 1],
          'member_status_id' => [2 => 1, 1 => 1],
        ],
        'expected' => [2, 1],
      ],
      'checkbox_format_single_use_1' => [
        'form_values' => [
          'member_membership_type_id' => [1 => 1],
          'member_status_id' => [1 => 1],
        ],
        'expected' => [1],
      ],
      'checkbox_format_single_not_1' => [
        'form_values' => [
          'member_membership_type_id' => [2 => 1],
          'member_status_id' => [2 => 1],
        ],
        'expected' => [2],
      ],
      'array_format' => [
        'form_values' => [
          'member_membership_type_id' => [1, 2],
          'member_status_id' => [1, 2],
        ],
        'expected' => [1, 2],
      ],
      'array_format_1_later' => [
        'form_values' => [
          'member_membership_type_id' => [2, 1],
          'member_status_id' => [2, 1],
        ],
        'expected' => [2, 1],
      ],
      'array_format_single_use_1' => [
        'form_values' => [
          'member_membership_type_id' => [1],
          'member_status_id' => [1],
        ],
        'expected' => [1],
      ],
      'array_format_single_not_1' => [
        'form_values' => [
          'member_membership_type_id' => [2],
          'member_status_id' => [2],
        ],
        'expected' => [2],
      ],
      'IN_format_single_not_1' => [
        'form_values' => [
          'membership_type_id' => ['IN' => [2]],
          'membership_status_id' => ['IN' => [2]],
        ],
        'expected' => [2],
      ],
      'IN_format_1_later' => [
        'form_values' => [
          'membership_type_id' => ['IN' => [2, 1]],
          'membership_status_id' => ['IN' => [2, 1]],
        ],
        'expected' => [2, 1],
      ],
    ];
  }

}
