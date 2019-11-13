<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * This class is intended to test ACL permission using the multisite module
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_ACLCachingTest extends CiviUnitTestCase {
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
    $tablesToTruncate = [
      'civicrm_activity',
    ];
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testActivityCreateCustomBefore($version) {
    $this->_apiversion = $version;
    $values = $this->callAPISuccess('custom_field', 'getoptions', ['field' => 'custom_group_id']);
    $this->assertTrue($values['count'] == 0);
    $this->CustomGroupCreate(['extends' => 'Activity']);
    $groupCount = $this->callAPISuccess('custom_group', 'getcount', ['extends' => 'activity']);
    $this->assertEquals($groupCount, 1, 'one group should now exist');
    $values = $this->callAPISuccess('custom_field', 'getoptions', ['field' => 'custom_group_id']);
    $this->assertTrue($values['count'] == 1, 'check that cached value is not retained for custom_group_id');
  }

}
