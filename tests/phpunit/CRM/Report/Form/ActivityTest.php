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
    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS civireport_contribution_detail_temp1');
    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS civireport_contribution_detail_temp2');
    CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE IF EXISTS civireport_contribution_detail_temp3');
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

}
