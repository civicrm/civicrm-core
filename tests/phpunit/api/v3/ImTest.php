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
 *  Test APIv3 civicrm_im_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contact
 * @group headless
 */
class api_v3_ImTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * If no location is specified when creating a new IM, it should default to
   * the LocationType default
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateImDefaultLocation(int $version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id' => $this->organizationCreate(),
      'name' => 'My Yahoo IM Handle',
      'provider_id' => 1,
    ];
    $result = $this->callAPISuccess('Im', 'create', $params);
    $this->assertEquals(CRM_Core_BAO_LocationType::getDefault()->id, $result['values'][$result['id']]['location_type_id']);
    $this->callAPISuccess('Im', 'delete', ['id' => $result['id']]);
  }

}
