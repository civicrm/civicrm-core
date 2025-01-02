<?php

use Civi\Api4\IM;

/**
 * Class CRM_Core_BAO_IMTest
 * @group headless
 */
class CRM_Core_BAO_IMTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_im', 'civicrm_contact']);
    parent::tearDown();
  }

  /**
   * Create() method (create and update modes)
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreate(): void {
    $contactId = $this->individualCreate();

    $params = [
      'name' => 'jane.doe',
      'provider_id' => 1,
      'is_primary' => 1,
      'location_type_id' => 1,
      'contact_id' => $contactId,
    ];

    $this->createTestEntity('IM', $params);

    $imId = $this->assertDBNotNull('CRM_Core_DAO_IM', 'jane.doe', 'id', 'name',
      'Database check for created IM name.'
    );

    // Now call add() to modify an existing IM

    $params = [
      'id' => $imId,
      'contact_id' => $contactId,
      'provider_id' => 3,
      'name' => 'doe.jane',
    ];

    IM::update(FALSE)->addWhere('id', '=', $imId)->setValues($params)->execute();

    $isEditIM = $this->assertDBNotNull('CRM_Core_DAO_IM', $imId, 'provider_id', 'id', 'Database check on updated IM provider_name record.');
    $this->assertEquals(3, $isEditIM, 'Verify IM provider_id value is 3.');
    $isEditIM = $this->assertDBNotNull('CRM_Core_DAO_IM', $imId, 'name', 'id', 'Database check on updated IM name record.');
    $this->assertEquals('doe.jane', $isEditIM, 'Verify IM provider_id value is doe.jane.');
  }

}
