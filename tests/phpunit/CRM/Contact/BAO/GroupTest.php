<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.5                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2014                                |
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


require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Test class for CRM_Contact_BAO_Group BAO
 *
 *  @package   CiviCRM
 */
class CRM_Contact_BAO_GroupTest extends CiviUnitTestCase {

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   *
   * @access protected
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  protected function tearDown() {}

  /**
   * test case for add( )
   */
  function testAddSimple() {

    $checkParams = $params = array(
      'title' => 'Group Uno',
      'description' => 'Group One',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
    );

    $group = CRM_Contact_BAO_Group::create($params);

    $this->assertDBCompareValues(
      'CRM_Contact_DAO_Group',
      array('id' => $group->id),
      $checkParams
    );
  }

  function testAddSmart() {

    $checkParams = $params = array(
      'title' => 'Group Dos',
      'description' => 'Group Two',
      'visibility' => 'User and User Admin Only',
      'is_active' => 1,
      'formValues' => array('sort_name' => 'Adams'),
    );

    $group = CRM_Contact_BAO_Group::createSmartGroup($params);

    unset($checkParams['formValues']);
    $this->assertDBCompareValues(
      'CRM_Contact_DAO_Group',
      array('id' => $group->id),
      $checkParams
    );
  }
}

