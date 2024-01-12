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
  public function formList(): array {
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
