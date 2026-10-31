<?php

/**
 * Class CRM_Core_Page_AJAXTest
 * @group headless
 */
class CRM_Core_Page_AJAXTest extends CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
  }

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_relationship', 'civicrm_uf_group', 'civicrm_uf_join']);
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_REQUEST = [];
    $_GET = [];
    $_POST = [];
    parent::tearDown();
  }

  public function testCheckAuthz(): void {
    $cases = [];

    $cases[] = ['method', 'CRM_Foo', FALSE];
    $cases[] = ['method', 'CRM_Foo_Page_AJAX_Bar', FALSE];
    $cases[] = ['method', 'CRM_Contact_Page_AJAX', FALSE];
    $cases[] = ['method', 'CRM_Foo_Page_AJAX', FALSE];
    $cases[] = ['method', 'othermethod;CRM_Foo_Page_AJAX', FALSE];
    $cases[] = ['method', 'CRM_Foo_Page_AJAX;othermethod', FALSE];
    $cases[] = ['method', 'CRM_Foo_Page_Inline_Bar', FALSE];
    $cases[] = ['method', 'CRM_Foo->method', FALSE];

    $cases[] = ['page', 'CRM_Foo', FALSE];
    $cases[] = ['page', 'CRM_Foo_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Page', FALSE];
    $cases[] = ['page', 'CRM_Foo_Page_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Page_Inline', FALSE];
    $cases[] = ['page', 'CRM_Contact_Page_Inline_CommunicationPreferences', TRUE];
    $cases[] = ['page', 'CRM_Foo_Page_Inline_Bar_Bang', FALSE];
    $cases[] = ['page', 'othermethod;CRM_Foo_Page_Inline_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Page_Inline_Bar;othermethod', FALSE];
    $cases[] = ['page', 'CRM_Foo_Form', FALSE];
    $cases[] = ['page', 'CRM_Foo_Form_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Form_Inline', FALSE];
    $cases[] = ['page', 'CRM_Contact_Form_Inline_Email', TRUE];
    $cases[] = ['page', 'CRM_Foo_Form_Inline_Bar_Bang', FALSE];
    $cases[] = ['page', 'othermethod;CRM_Foo_Form_Inline_Bar', FALSE];
    $cases[] = ['page', 'CRM_Foo_Form_Inline_Bar;othermethod', FALSE];

    // aliases for 'page'
    $cases[] = ['class', 'CRM_Foo_Bar', FALSE];
    $cases[] = ['class', 'CRM_Contact_Page_Inline_Phone', TRUE];
    $cases[] = ['', 'CRM_Foo_Bar', FALSE];
    $cases[] = ['', 'CRM_Contact_Page_Inline_Demographics', TRUE];

    // invalid type
    $cases[] = ['invalidtype', 'CRM_Foo_Page_Inline_Bar', FALSE];
    $cases[] = ['invalidtype', 'CRM_Foo_Page_AJAX::method', FALSE];

    foreach ($cases as $case) {
      list ($type, $className, $expectedResult) = $case;
      $actualResult = CRM_Core_Page_AJAX::checkAuthz($type, $className);
      $this->assertEquals($expectedResult, $actualResult,
        sprintf('Check type=[%s] value=[%s]', $type, $className));
    }
  }

  /**
   * Test setIsQuickConfig succeeds for an event when user has permission.
   */
  public function testSetIsQuickConfigEventSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviEvent', 'view event info', 'edit all events']);

    $event = $this->createTestEntity('Event', [
      'title' => 'Test Event',
      'event_type_id:name' => 'Conference',
      'start_date' => 'now',
    ]);
    $priceSet = $this->createTestEntity('PriceSet', [
      'title' => 'Event Price Set',
      'name' => 'event_price_set',
      'extends' => ['CiviEvent'],
      'is_quick_config' => 1,
    ]);
    $this->createTestEntity('PriceSetEntity', [
      'entity_table' => 'civicrm_event',
      'entity_id' => $event['id'],
      'price_set_id' => $priceSet['id'],
    ]);

    $_GET = $_REQUEST = [
      'context' => 'civicrm_event',
      'id' => $event['id'],
    ];

    try {
      CRM_Core_Page_AJAX::setIsQuickConfig();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals($priceSet['id'], $e->errorData[0]);
    }

    $updatedPriceSet = \Civi\Api4\PriceSet::get(FALSE)
      ->addWhere('id', '=', $priceSet['id'])
      ->execute()
      ->first();
    $this->assertFalse((bool) $updatedPriceSet['is_quick_config']);
  }

  /**
   * Test setIsQuickConfig is denied for an event when user lacks permission.
   */
  public function testSetIsQuickConfigEventDeniedWithoutPermission(): void {
    $ownerId = $this->individualCreate([], 'owner');
    $event = $this->createTestEntity('Event', [
      'title' => 'Test Event',
      'event_type_id:name' => 'Conference',
      'start_date' => 'now',
      'created_id' => $ownerId,
    ]);

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviEvent', 'view event info']);

    $_GET = $_REQUEST = [
      'context' => 'civicrm_event',
      'id' => $event['id'],
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Core_Page_AJAX::setIsQuickConfig();
  }

  /**
   * Test setIsQuickConfig succeeds for a contribution page when user has permission.
   */
  public function testSetIsQuickConfigContributionPageSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute', 'administer CiviCRM']);

    $page = $this->createTestEntity('ContributionPage', [
      'title' => 'Test Contribution Page',
      'financial_type_id:name' => 'Donation',
    ]);
    $priceSet = $this->createTestEntity('PriceSet', [
      'title' => 'Contrib Price Set',
      'name' => 'contrib_price_set',
      'extends' => ['CiviContribute'],
      'is_quick_config' => 1,
    ]);
    $this->createTestEntity('PriceSetEntity', [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $page['id'],
      'price_set_id' => $priceSet['id'],
    ]);

    $_GET = $_REQUEST = [
      'context' => 'civicrm_contribution_page',
      'id' => $page['id'],
    ];

    try {
      CRM_Core_Page_AJAX::setIsQuickConfig();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals($priceSet['id'], $e->errorData[0]);
    }

    $updatedPriceSet = \Civi\Api4\PriceSet::get(FALSE)
      ->addWhere('id', '=', $priceSet['id'])
      ->execute()
      ->first();
    $this->assertFalse((bool) $updatedPriceSet['is_quick_config']);
  }

  /**
   * Test setIsQuickConfig is denied for a contribution page without administer CiviCRM.
   */
  public function testSetIsQuickConfigContributionPageDeniedWithoutPermission(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviContribute']);

    $page = $this->createTestEntity('ContributionPage', [
      'title' => 'Test Contribution Page',
      'financial_type_id:name' => 'Donation',
    ]);

    $_GET = $_REQUEST = [
      'context' => 'civicrm_contribution_page',
      'id' => $page['id'],
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Core_Page_AJAX::setIsQuickConfig();
  }

  /**
   * Test setIsQuickConfig is denied when given invalid context or ID.
   */
  public function testSetIsQuickConfigInvalidContextOrIdDenied(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviEvent', 'administer CiviCRM']);

    $_GET = $_REQUEST = [
      'context' => 'civicrm_contact',
      'id' => 1,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Core_Page_AJAX::setIsQuickConfig();
  }

  /**
   * Test updateMode is denied when user lacks edit permissions on the entity.
   */
  public function testUpdateModeEventWithoutPermissionDenied(): void {
    $otherContact = $this->individualCreate([], 'other_event_creator');
    $event = $this->createTestEntity('Event', [
      'title' => 'Test Recurring Event',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'start_date' => '2026-11-01 10:00:00',
      'created_id' => $otherContact,
    ], 'event_rec');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviEvent', 'view event info']);

    $_GET = $_REQUEST = [
      'mode' => 1,
      'entityId' => $event['id'],
      'entityTable' => 'civicrm_event',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Core_Page_AJAX_RecurringEntity::updateMode();
  }

  /**
   * Test updateMode succeeds when user has permission.
   */
  public function testUpdateModeEventWithPermissionSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviEvent', 'view event info', 'edit all events']);
    $event = $this->createTestEntity('Event', [
      'title' => 'Test Recurring Event Permitted',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'start_date' => '2026-11-01 10:00:00',
    ], 'event_rec2');

    $_GET = $_REQUEST = [
      'mode' => 1,
      'entityId' => $event['id'],
      'entityTable' => 'civicrm_event',
    ];

    try {
      CRM_Core_Page_AJAX_RecurringEntity::updateMode();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertIsArray($e->errorData);
    }
  }

  /**
   * Set up fixtures for getPermissionedLocation tests.
   *
   * @return array
   */
  private function setupPermissionedLocationFixtures(): array {
    $this->setPermissions(['make online contributions', 'profile create']);

    $org = $this->createTestEntity('Contact', [
      'contact_type' => 'Organization',
      'organization_name' => 'Perm Test Org',
    ], 'perm_org');

    $this->createTestEntity('Address', [
      'contact_id' => $org['id'],
      'street_address' => '123 Main St',
      'location_type_id:name' => 'Home',
    ], 'perm_org_address');

    $ind = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Perm',
      'last_name' => 'User',
    ], 'perm_ind');

    $this->createTestEntity('Relationship', [
      'contact_id_a' => $ind['id'],
      'contact_id_b' => $org['id'],
      'relationship_type_id:name' => 'Employee of',
      'is_permission_a_b' => 1,
      'is_active' => 1,
    ], 'perm_rel');

    $ufGroup = $this->createTestEntity('UFGroup', [
      'title' => 'Perm Location Profile',
      'is_active' => 1,
    ], 'perm_ufg');

    return [$org['id'], $ind['id'], $ufGroup['id']];
  }

  /**
   * Test getPermissionedLocation is denied when an unauthenticated request supplies uid without checksum.
   */
  public function testGetPermissionedLocationDeniedWithoutChecksumForUnauthenticatedUid(): void {
    [$orgId, $indId, $ufId] = $this->setupPermissionedLocationFixtures();

    $_GET = $_REQUEST = [
      'uid' => $indId,
      'cid' => $orgId,
      'ufID' => $ufId,
    ];

    try {
      CRM_Core_Page_AJAX_Location::getPermissionedLocation();
      $this->fail('Expected premature exit without data');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['error_code' => 0], $e->errorData);
    }
  }

  /**
   * Test getPermissionedLocation is denied when an invalid checksum is supplied.
   */
  public function testGetPermissionedLocationDeniedWithInvalidChecksum(): void {
    [$orgId, $indId, $ufId] = $this->setupPermissionedLocationFixtures();

    $_GET = $_REQUEST = [
      'uid' => $indId,
      'cid' => $orgId,
      'ufID' => $ufId,
      'cs' => 'invalid_checksum',
    ];

    try {
      CRM_Core_Page_AJAX_Location::getPermissionedLocation();
      $this->fail('Expected premature exit without data');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['error_code' => 0], $e->errorData);
    }
  }

  /**
   * Test getPermissionedLocation is denied when unauthenticated and no uid or checksum is provided.
   */
  public function testGetPermissionedLocationDeniedWhenUnauthenticatedWithoutUidOrChecksum(): void {
    [$orgId, , $ufId] = $this->setupPermissionedLocationFixtures();

    $_GET = $_REQUEST = [
      'cid' => $orgId,
      'ufID' => $ufId,
    ];

    try {
      CRM_Core_Page_AJAX_Location::getPermissionedLocation();
      $this->fail('Expected premature exit without data');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['error_code' => 0], $e->errorData);
    }
  }

  /**
   * Test getPermissionedLocation is denied when checksum is provided without uid.
   */
  public function testGetPermissionedLocationDeniedWithChecksumOnlyNoUid(): void {
    [$orgId, $indId, $ufId] = $this->setupPermissionedLocationFixtures();
    $validChecksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($indId);

    $_GET = $_REQUEST = [
      'cid' => $orgId,
      'ufID' => $ufId,
      'cs' => $validChecksum,
    ];

    try {
      CRM_Core_Page_AJAX_Location::getPermissionedLocation();
      $this->fail('Expected premature exit without data');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['error_code' => 0], $e->errorData);
    }
  }

  /**
   * Test getPermissionedLocation succeeds when authenticated with a valid checksum.
   */
  public function testGetPermissionedLocationSuccessWithValidChecksum(): void {
    [$orgId, $indId, $ufId] = $this->setupPermissionedLocationFixtures();
    $validChecksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($indId);

    $_GET = $_REQUEST = [
      'uid' => $indId,
      'cid' => $orgId,
      'ufID' => $ufId,
      'cs' => $validChecksum,
    ];

    try {
      CRM_Core_Page_AJAX_Location::getPermissionedLocation();
      $this->fail('Expected premature exit with output');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertIsArray($e->errorData);
      $this->assertEquals('123 Main St', $e->errorData['onbehalf_street_address-' . $this->getLocationTypeID('Home')]['value'] ?? NULL);
    }
  }

  /**
   * Test getPermissionedLocation succeeds for a logged-in user with permission on the organization.
   */
  public function testGetPermissionedLocationSuccessForLoggedInUser(): void {
    [$orgId, $indId, $ufId] = $this->setupPermissionedLocationFixtures();
    CRM_Core_Session::singleton()->set('userID', $indId);

    $_GET = $_REQUEST = [
      'cid' => $orgId,
      'ufID' => $ufId,
    ];

    try {
      CRM_Core_Page_AJAX_Location::getPermissionedLocation();
      $this->fail('Expected premature exit with output');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertIsArray($e->errorData);
      $this->assertEquals('123 Main St', $e->errorData['onbehalf_street_address-' . $this->getLocationTypeID('Home')]['value'] ?? NULL);
    }
  }

  /**
   * Test getPermissionedLocation is denied when logged in user lacks permission on the organization.
   */
  public function testGetPermissionedLocationDeniedForLoggedInUserWithoutPermissionOnOrg(): void {
    [, $indId, $ufId] = $this->setupPermissionedLocationFixtures();
    $otherOrg = $this->createTestEntity('Contact', [
      'contact_type' => 'Organization',
      'organization_name' => 'Unrelated Org',
    ], 'unrelated_org');

    CRM_Core_Session::singleton()->set('userID', $indId);

    $_GET = $_REQUEST = [
      'cid' => $otherOrg['id'],
      'ufID' => $ufId,
    ];

    try {
      CRM_Core_Page_AJAX_Location::getPermissionedLocation();
      $this->fail('Expected premature exit without data');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['error_code' => 0], $e->errorData);
    }
  }

  /**
   * Test getPermissionedLocation is denied when logged in user passes another uid without checksum.
   */
  public function testGetPermissionedLocationDeniedForLoggedInUserSpecifyingOtherUidWithoutChecksum(): void {
    [$orgId, $indId, $ufId] = $this->setupPermissionedLocationFixtures();
    $loggedUser = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Logged',
      'last_name' => 'In',
    ], 'logged_user');

    CRM_Core_Session::singleton()->set('userID', $loggedUser['id']);

    $_GET = $_REQUEST = [
      'uid' => $indId,
      'cid' => $orgId,
      'ufID' => $ufId,
    ];

    try {
      CRM_Core_Page_AJAX_Location::getPermissionedLocation();
      $this->fail('Expected premature exit without data');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(['error_code' => 0], $e->errorData);
    }
  }

}
