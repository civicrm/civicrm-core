<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * This class is intended to test ACL permission using the multisite module
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 */
class api_v3_ACLCachingTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
  }

  /**
   * (non-PHPdoc)
   * @see CiviUnitTestCase::tearDown()
   */
  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_activity',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  public function testActivityCreateCustomBefore() {
    $values = $this->callAPISuccess('custom_field', 'getoptions', array('field' => 'custom_group_id'));
    $this->assertTrue($values['count'] == 0);
    $this->CustomGroupCreate(array('extends' => 'Activity'));
    $groupCount = $this->callAPISuccess('custom_group', 'getcount', array('extends' => 'activity'));
    $this->assertEquals($groupCount, 1, 'one group should now exist');
    $values = $this->callAPISuccess('custom_field', 'getoptions', array('field' => 'custom_group_id'));
    $this->assertTrue($values['count'] == 1, 'check that cached value is not retained for custom_group_id');
  }

}
