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
 *  Test Activity report outcome
 *
 * @package CiviCRM
 */
class CRM_Report_Form_ActivityTest extends CiviReportTestCase {
  protected $_tablesToTruncate = array(
    'civicrm_contact',
    'civicrm_email',
    'civicrm_phone',
    'civicrm_address',
    'civicrm_contribution',
  );

  public function setUp() {
    parent::setUp();
    $this->quickCleanup($this->_tablesToTruncate);
  }

  public function tearDown() {
    parent::tearDown();
    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS civireport_activity_temp_target');
  }

  /**
   * Ensure long custom field names don't result in errors.
   */
  public function testLongCustomFieldNames() {
    // Create custom group with long name and custom field with long name.
    $long_name = 'this is a very very very very long name with 65 characters in it';
    $group_params = array(
      'title' => $long_name,
      'extends' => 'Activity',
    );
    $result = $this->customGroupCreate($group_params);
    $custom_group_id = $result['id'];
    $field_params = array(
      'custom_group_id' => $custom_group_id,
      'label' => $long_name,
    );
    $result = $this->customFieldCreate($field_params);
    $custom_field_id = $result['id'];
    $input = array(
      'fields' => array(
        'custom_' . $custom_field_id,
      ),
    );
    $obj = $this->getReportObject('CRM_Report_Form_Activity', $input);
    //$params = $obj->_params;
    //$params['fields'] = array('custom_' . $custom_field_id);
    //$obj->setParams($params);
    $obj->getResultSet();
    $this->assertTrue(TRUE, "Testo");
  }

  /**
   * Ensure that activity detail report only shows addres fields of target contact
   */
  public function testTargetAddressFields() {
    $countryNames = array_flip(CRM_Core_PseudoConstant::country());
    // Create contact 1 and 2 with address fields, later considered as target contacts for activity
    $contactID1 = $this->individualCreate(array(
      'api.Address.create' => array(
        'contact_id' => '$value.id',
        'location_type_id' => 'Home',
        'city' => 'ABC',
        'country_id' => $countryNames['India'],
      ),
    ));
    $contactID2 = $this->individualCreate(array(
      'api.Address.create' => array(
        'contact_id' => '$value.id',
        'location_type_id' => 'Home',
        'city' => 'DEF',
        'country_id' => $countryNames['United States'],
      ),
    ));
    // Create Contact 3 later considered as assignee contact of activity
    $contactID3 = $this->individualCreate(array(
      'api.Address.create' => array(
        'contact_id' => '$value.id',
        'location_type_id' => 'Home',
        'city' => 'GHI',
        'country_id' => $countryNames['China'],
      ),
    ));

    // create dummy activity type
    $activityTypeID = CRM_Utils_Array::value('id', $this->callAPISuccess('option_value', 'create', array(
      'option_group_id' => 'activity_type',
      'name' => 'Test activity type',
      'label' => 'Test activity type',
    )));
    // create activity
    $result = $this->callAPISuccess('activity', 'create', array(
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => date('Ymd'),
      'duration' => 120,
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => 1,
      'activity_type_id' => 'Test activity type',
      'source_contact_id' => $this->individualCreate(),
      'target_contact_id' => array($contactID1, $contactID2),
      'assignee_contact_id' => $contactID3,
    ));
    // display city and country field so that we can check its value
    $input = array(
      'fields' => array(
        'city',
        'country_id',
      ),
      'order_bys' => array(
        'city' => array(),
        'country_id' => array('default' => TRUE),
      ),
    );
    // generate result
    $obj = $this->getReportObject('CRM_Report_Form_Activity', $input);
    $rows = $obj->getResultSet();

    // ensure that only 1 activity is created
    $this->assertEquals(1, count($rows));
    // ensure that country values of respective target contacts are only shown
    $this->assertTrue(in_array($rows[0]['civicrm_address_country_id'], ['India;United States', 'United States;India']));
    // ensure that city values of respective target contacts are only shown
    $this->assertTrue(in_array($rows[0]['civicrm_address_city'], ['ABC;DEF', 'DEF;ABC']));
  }

}
