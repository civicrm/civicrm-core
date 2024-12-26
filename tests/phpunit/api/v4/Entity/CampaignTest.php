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

namespace api\v4\Entity;

use api\v4\Api4TestBase;

/**
 * @group headless
 */
class CampaignTest extends Api4TestBase {

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
  }

  public function testCampaignAndSurveyUpdate(): void {
    $cid1 = $this->createLoggedInUser();

    foreach (['Campaign', 'Survey'] as $entityName) {
      $entity = $this->createTestRecord($entityName);
      $created[$entityName] = civicrm_api4($entityName, 'get', [
        'where' => [['id', '=', $entity['id']]],
      ])->single();
      $this->assertEquals($cid1, $created[$entityName]['created_id']);
      $this->assertEquals($cid1, $created[$entityName]['last_modified_id']);
      $this->assertNotNull($created[$entityName]['created_date']);
      $this->assertEquals($created[$entityName]['created_date'], $created[$entityName]['last_modified_date']);
    }

    // Switch user, update time
    $cid2 = $this->createLoggedInUser();
    sleep(1);

    // Ensure updated record reflects new user id and modified_date
    foreach (['Campaign', 'Survey'] as $entityName) {
      $updated[$entityName] = civicrm_api4($entityName, 'update', [
        'checkPermissions' => FALSE,
        'values' => ['title' => 'new', 'id' => $created[$entityName]['id']],
        'reload' => TRUE,
      ])->single();

      $this->assertEquals('new', $updated[$entityName]['title']);
      $this->assertEquals($cid1, $updated[$entityName]['created_id']);
      $this->assertEquals($cid2, $updated[$entityName]['last_modified_id']);
      $this->assertEquals($created[$entityName]['created_date'], $updated[$entityName]['created_date']);
      $this->assertGreaterThan($updated[$entityName]['created_date'], $updated[$entityName]['last_modified_date']);
    }
  }

}
