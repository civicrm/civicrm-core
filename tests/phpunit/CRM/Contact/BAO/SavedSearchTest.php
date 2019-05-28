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
    $this->quickCleanup(array(
      'civicrm_mapping_field',
      'civicrm_mapping',
      'civicrm_group',
      'civicrm_saved_search',
    ));
  }

  /**
   * Test setDefaults for privacy radio buttons.
   */
  public function testDefaultValues() {
    $sg = new CRM_Contact_Form_Search_Advanced();
    $sg->controller = new CRM_Core_Controller();
    $sg->_formValues = array(
      'group_search_selected' => 'group',
      'privacy_options' => array('do_not_email'),
      'privacy_operator' => 'OR',
      'privacy_toggle' => 2,
      'operator' => 'AND',
      'component_mode' => 1,
    );
    CRM_Core_DAO::executeQuery(
      "INSERT INTO civicrm_saved_search (form_values) VALUES('" . serialize($sg->_formValues) . "')"
    );
    $ssID = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    $sg->set('ssID', $ssID);

    $defaults = $sg->setDefaultValues();

    $this->checkArrayEquals($defaults, $sg->_formValues);
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
    $this->assertEquals(array('membership_type_id', 'membership_status_id'), array_keys($result));
    foreach ($result as $key => $value) {
      $this->assertEquals($expectedResult, $value, 'failure on set ' . $searchDescription);
    }
  }

  /**
   * Test if dates ranges are stored correctly
   * in civicrm_saved_search table and are
   * extracted properly.
   */
  public function testDateRange() {
    $savedSearch = new CRM_Contact_BAO_SavedSearch();
    $formValues = array(
      'hidden_basic' => 1,
      'group_search_selected' => 'group',
      'component_mode' => 1,
      'operator' => 'AND',
      'privacy_operator' => 'OR',
      'privacy_toggle' => 1,
      'participant_register_date_low' => '01/01/2009',
      'participant_register_date_high' => '01/01/2018',
      'radio_ts' => 'ts_all',
      'title' => 'bah bah bah',
    );

    $queryParams = array(
      0 => array(
        0 => 'participant_register_date_low',
        1 => '=',
        2 => '01/01/2009',
        3 => 0,
        4 => 0,
      ),
      1 => array(
        0 => 'participant_register_date_high',
        1 => '=',
        2 => '01/01/2018',
        3 => 0,
        4 => 0,
      ),
    );

    CRM_Contact_BAO_SavedSearch::saveRelativeDates($queryParams, $formValues);
    CRM_Contact_BAO_SavedSearch::saveSkippedElement($queryParams, $formValues);
    $savedSearch->form_values = serialize($queryParams);
    $savedSearch->save();

    $result = CRM_Contact_BAO_SavedSearch::getFormValues(CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()'));
    $this->assertEquals('01/01/2009', $result['participant_register_date_low']);
    $this->assertEquals('01/01/2018', $result['participant_register_date_high']);
  }

  /**
   * Test if skipped elements are correctly
   * stored and retrieved as formvalues.
   */
  public function testSkippedElements() {
    $relTypeID = $this->relationshipTypeCreate();
    $savedSearch = new CRM_Contact_BAO_SavedSearch();
    $formValues = array(
      'operator' => 'AND',
      'title' => 'testsmart',
      'radio_ts' => 'ts_all',
      'component_mode' => CRM_Contact_BAO_Query::MODE_CONTACTS,
      'display_relationship_type' => "{$relTypeID}_a_b",
      'uf_group_id' => 1,
    );
    $queryParams = array();
    CRM_Contact_BAO_SavedSearch::saveSkippedElement($queryParams, $formValues);
    $savedSearch->form_values = serialize($queryParams);
    $savedSearch->save();

    $result = CRM_Contact_BAO_SavedSearch::getFormValues(CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()'));
    $expectedResult = array(
      'operator' => 'AND',
      'component_mode' => CRM_Contact_BAO_Query::MODE_CONTACTS,
      'display_relationship_type' => "{$relTypeID}_a_b",
      'uf_group_id' => 1,
    );
    $this->checkArrayEquals($result, $expectedResult);
  }

  /**
   * Test if relative dates are stored correctly
   * in civicrm_saved_search table.
   */
  public function testRelativeDateValues() {
    $savedSearch = new CRM_Contact_BAO_SavedSearch();
    $formValues = array(
      'operator' => 'AND',
      'event_relative' => 'this.month',
      'participant_relative' => 'today',
      'contribution_date_relative' => 'this.week',
      'participant_test' => 0,
      'title' => 'testsmart',
      'radio_ts' => 'ts_all',
    );
    $queryParams = array();
    CRM_Contact_BAO_SavedSearch::saveRelativeDates($queryParams, $formValues);
    CRM_Contact_BAO_SavedSearch::saveSkippedElement($queryParams, $formValues);
    $savedSearch->form_values = serialize($queryParams);
    $savedSearch->save();

    $result = CRM_Contact_BAO_SavedSearch::getFormValues(CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()'));
    $expectedResult = array(
      'event' => 'this.month',
      'participant' => 'today',
      'contribution' => 'this.week',
    );
    $this->checkArrayEquals($result['relative_dates'], $expectedResult);
  }

  /**
   * Test relative dates
   *
   * The function saveRelativeDates should detect whether a field is using
   * a relative date range and include in the fromValues a relative_date
   * index so it is properly detects when executed.
   */
  public function testCustomFieldRelativeDates() {
    // Create a custom field.
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual', 'title' => 'relative_date_test_group'));
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_datefield',
      'label' => 'Date Field for Testing',
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'default_value' => NULL,
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 1,
      'date_format' => 'mm/dd/yyyy',
      'is_active' => 1,
    );
    $customField = $this->callAPIAndDocument('custom_field', 'create', $params, __FUNCTION__, __FILE__);
    $id = $customField['id'];

    $queryParams = array(
      0 => array(
        0 => "custom_${id}_low",
        1 => '=',
        2 => '20170425000000',
      ),
      1 => array(
        0 => "custom_${id}_high",
        1 => '=',
        2 => '20170501235959',
      ),
    );
    $formValues = array(
      "custom_${id}_relative" => 'ending.week',
    );
    CRM_Contact_BAO_SavedSearch::saveRelativeDates($queryParams, $formValues);
    // Since custom_13 doesn't have the word 'date' in it, the key is
    // set to 0, rather than the field name.
    $err = 'Relative date in custom field smart group creation failed.';
    $this->assertArrayHasKey('relative_dates', $queryParams, $err);
    $dropCustomValueTables = TRUE;
    $this->quickCleanup(array('civicrm_saved_search'), $dropCustomValueTables);
  }

  /**
   * Get variants of the fields we want to test.
   *
   * @return array
   */
  public function getSavedSearches() {
    $return = array();
    $searches = $this->getSearches();
    foreach ($searches as $key => $search) {
      $return[] = array($search['form_values'], $search['expected'], $key);
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
    return array(
      'checkbox_format_1_first' => array(
        'form_values' => array(
          'member_membership_type_id' => array(1 => 1, 2 => 1),
          'member_status_id' => array(1 => 1, 2 => 1),
        ),
        'expected' => array(1, 2),
      ),
      'checkbox_format_1_later' => array(
        'form_values' => array(
          'member_membership_type_id' => array(2 => 1, 1 => 1),
          'member_status_id' => array(2 => 1, 1 => 1),
        ),
        'expected' => array(2, 1),
      ),
      'checkbox_format_single_use_1' => array(
        'form_values' => array(
          'member_membership_type_id' => array(1 => 1),
          'member_status_id' => array(1 => 1),
        ),
        'expected' => array(1),
      ),
      'checkbox_format_single_not_1' => array(
        'form_values' => array(
          'member_membership_type_id' => array(2 => 1),
          'member_status_id' => array(2 => 1),
        ),
        'expected' => array(2),
      ),
      'array_format' => array(
        'form_values' => array(
          'member_membership_type_id' => array(1, 2),
          'member_status_id' => array(1, 2),
        ),
        'expected' => array(1, 2),
      ),
      'array_format_1_later' => array(
        'form_values' => array(
          'member_membership_type_id' => array(2, 1),
          'member_status_id' => array(2, 1),
        ),
        'expected' => array(2, 1),
      ),
      'array_format_single_use_1' => array(
        'form_values' => array(
          'member_membership_type_id' => array(1),
          'member_status_id' => array(1),
        ),
        'expected' => array(1),
      ),
      'array_format_single_not_1' => array(
        'form_values' => array(
          'member_membership_type_id' => array(2),
          'member_status_id' => array(2),
        ),
        'expected' => array(2),
      ),
      'IN_format_single_not_1' => array(
        'form_values' => array(
          'membership_type_id' => array('IN' => array(2)),
          'membership_status_id' => array('IN' => array(2)),
        ),
        'expected' => array(2),
      ),
      'IN_format_1_later' => array(
        'form_values' => array(
          'membership_type_id' => array('IN' => array(2, 1)),
          'membership_status_id' => array('IN' => array(2, 1)),
        ),
        'expected' => array(2, 1),
      ),
    );
  }

}
