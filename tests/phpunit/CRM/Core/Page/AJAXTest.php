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

}
