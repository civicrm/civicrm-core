<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 5                                                  |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2018                                |
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
