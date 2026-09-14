<?php

/**
 * @group headless
 */
class CRM_Core_FormTest extends CiviUnitTestCase {

  private $originalRequest;

  public function setUp(): void {
    $this->originalRequest = $_REQUEST;
    parent::setUp();
  }

  public function tearDown(): void {
    $_REQUEST = $this->originalRequest;
    parent::tearDown();
  }

  /**
   * Simulate opening various forms. All we're looking to do here is
   * see if any warnings or notices come up, the equivalent of red boxes
   * on the screen, but which are hidden when using popup forms.
   * So no assertions required.
   *
   * @param string $url
   *
   * @dataProvider formList
   * @throws \CRM_Core_Exception
   */
  public function testOpeningForms(string $url): void {
    $this->createLoggedInUser();
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    $_SERVER['REQUEST_URI'] = $url;
    $urlParts = explode('?', $url);
    $_GET['q'] = $urlParts[0];

    $parsed = [];
    parse_str($urlParts[1], $parsed);
    foreach ($parsed as $param => $value) {
      $_REQUEST[$param] = $value;
    }

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    ob_end_clean();
  }

  /**
   * Data provider for testOpeningForms().
   * TODO: Add more forms!
   *
   * @return array
   */
  public static function formList(): array {
    return [
      // Array key is descriptive term to make it clearer which form it is when it fails.
      'Add New Tag' => [
        'civicrm/tag/edit?action=add&parent_id=',
      ],
      'Location Type' => [
        'civicrm/admin/locationType?reset=1',
      ],
      'Assign Account to Financial Type' => [
        'civicrm/admin/financial/financialType/accounts?action=add&reset=1&aid=1',
      ],
      'Find Contacts' => [
        'civicrm/contact/search?reset=1',
      ],
      'Find Contributions' => [
        'civicrm/contribute/search?reset=1',
      ],
      'Fulltext search' => [
        'civicrm/contact/search/custom?csid=15&reset=1',
      ],
      'New Email' => [
        'civicrm/activity/email/add?atype=3&action=add&reset=1&context=standalone',
      ],
      'Message Templates' => [
        'civicrm/admin/messageTemplates?reset=1',
      ],
      'Scheduled Jobs' => [
        'civicrm/admin/job?reset=1',
      ],
    ];
  }

  public function testEntityIdInjectedAsHiddenField(): void {
    $this->createLoggedInUser();
    $event = $this->eventCreate([
      'title' => 'Test Event',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'start_date' => '2026-09-01',
      'is_online_registration' => 1,
    ]);
    $_REQUEST['id'] = $event['id'];
    $_GET['id'] = $event['id'];
    $form = new CRM_Event_Form_Registration_Register();
    $controller = new CRM_Event_Controller_Registration('Test Registration', CRM_Core_Action::ADD);
    \Civi\Test\Invasive::set([$controller, '_key'], 'test_key_123');
    $form->controller = $controller;
    $form->buildForm();
    $this->assertTrue($form->elementExists('id'));
    $this->assertEquals($event['id'], $form->getElementValue('id'));
  }

  /**
   * The failure this guards against: a submit that arrives with no entity id and
   * no usable session scope.
   *
   * CRM_Utils_Request::retrieve() looks in the request first and in the session
   * scope second. A freshly constructed controller has an empty scope, which is
   * what an expired session, a cleared cookie or a qfKey minted in another tab
   * amounts to - so with no id in the request there is nowhere left to read it
   * from and the registration is rejected before any page is built.
   *
   * Note for anyone comparing this against the stack trace in the ticket: since
   * f5993acec9 the exception no longer escapes as a fatal. It is caught in
   * CRM_Event_StateMachine_Registration and turned into a 400 "Missing Event ID".
   * The registrant still loses everything they typed; only the error page got
   * nicer, which is why this is hard to spot as a fatal on current master.
   */
  public function testRegistrationIsRejectedWithoutEntityIdWhenSessionScopeIsEmpty(): void {
    $this->createLoggedInUser();
    $this->eventCreate([
      'title' => 'Test Event',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'start_date' => '2026-09-01',
      'is_online_registration' => 1,
    ]);

    // The submit carries no id: without the hidden field the browser has nothing
    // to send, and the fresh controller below has nothing cached either.
    unset($_REQUEST['id'], $_GET['id'], $_POST['id']);

    try {
      new CRM_Event_Controller_Registration('Test Registration', CRM_Core_Action::ADD);
      $this->fail('Expected the registration controller to reject a request without an entity id.');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $response = $e->errorData['response'] ?? NULL;
      $this->assertNotNull($response, 'Expected a response to be sent instead of a page.');
      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame('Missing Event ID', (string) $response->getBody());
    }
  }

  /**
   * The same submit once the id travels along in the POST, which is what the
   * hidden field produces: retrieve() finds it in the request and never has to
   * fall back to the session scope, so an expired session is survivable.
   */
  public function testPostedEntityIdSurvivesAnEmptySessionScope(): void {
    $this->createLoggedInUser();
    $event = $this->eventCreate([
      'title' => 'Test Event',
      'event_type_id' => 1,
      'default_role_id' => 1,
      'start_date' => '2026-09-01',
      'is_online_registration' => 1,
    ]);

    // A real POST reaches $_REQUEST as well (request_order defaults to "GP"),
    // which is where CRM_Utils_Request::retrieve() looks.
    $_POST['id'] = $event['id'];
    $_REQUEST['id'] = $event['id'];
    unset($_GET['id']);

    $controller = new CRM_Event_Controller_Registration('Test Registration', CRM_Core_Action::ADD);

    $this->assertInstanceOf(CRM_Event_Controller_Registration::class, $controller);
    unset($_POST['id']);
  }

  public function testNewPriceField(): void {
    $this->createLoggedInUser();

    $priceSetId = $this->callAPISuccess('PriceSet', 'create', [
      'is_active' => 1,
      // extends contribution
      'extends' => 2,
      'is_quick_config' => 0,
      // donation
      'financial_type_id' => 1,
      'name' => 'priciest',
      'title' => 'Priciest Price Set',
    ])['id'];

    $_SERVER['REQUEST_URI'] = "civicrm/admin/price/field/edit?reset=1&action=add&sid={$priceSetId}";
    $_GET['q'] = 'civicrm/admin/price/field/edit';
    $_REQUEST['reset'] = 1;
    $_REQUEST['action'] = 'add';
    $_REQUEST['sid'] = $priceSetId;

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    ob_end_clean();

    $this->callAPISuccess('PriceSet', 'delete', ['id' => $priceSetId]);
  }

  /**
   * Test the getAuthenticatedUser function.
   *
   * It should return a checksum validated user, falling back to the logged in user.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetAuthenticatedUser(): void {
    $_REQUEST['cid'] = $this->individualCreate();
    $_REQUEST['cs'] = CRM_Contact_BAO_Contact_Utils::generateChecksum($_REQUEST['cid']);
    $form = $this->getFormObject('CRM_Core_Form');
    $this->assertEquals($_REQUEST['cid'], $form->getAuthenticatedContactID());

    $_REQUEST['cs'] = 'abc';
    $form = $this->getFormObject('CRM_Core_Form');
    $this->assertEquals(0, $form->getAuthenticatedContactID());

    $form = $this->getFormObject('CRM_Core_Form');
    $this->createLoggedInUser();
    $this->assertEquals($this->ids['Contact']['logged_in'], $form->getAuthenticatedContactID());
  }

}
