<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2016                                |
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
