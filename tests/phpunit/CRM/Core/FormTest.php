<?php

/**
 * @group headless
 */
class CRM_Core_FormTest extends CiviUnitTestCase {

  /**
   * Simulate opening various forms. All we're looking to do here is
   * see if any warnings or notices come up, the equivalent of red boxes
   * on the screen, but which are hidden when using popup forms.
   * So no assertions required.
   *
   * @param string $url
   *
   * @dataProvider formList
   */
  public function testOpeningForms(string $url) {
    $this->createLoggedInUser();

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

    foreach ($parsed as $param => $dontcare) {
      unset($_REQUEST[$param]);
    }
  }

  /**
   * Dataprovider for testOpeningForms().
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
      'Assign Account to Financial Type' => [
        'civicrm/admin/financial/financialType/accounts?action=add&reset=1&aid=1',
      ],
      'Find Contacts' => [
        'civicrm/contact/search?reset=1',
      ],
      'Fulltext search' => [
        'civicrm/contact/search/custom?csid=15&reset=1',
      ],
      'New Email' => [
        'civicrm/activity/email/add?atype=3&action=add&reset=1&context=standalone',
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

    $_SERVER['REQUEST_URI'] = "civicrm/admin/price/field?reset=1&action=add&sid={$priceSetId}";
    $_GET['q'] = 'civicrm/admin/price/field';
    $_REQUEST['reset'] = 1;
    $_REQUEST['action'] = 'add';
    $_REQUEST['sid'] = $priceSetId;

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    ob_end_clean();

    unset($_REQUEST['reset']);
    unset($_REQUEST['action']);
    unset($_REQUEST['sid']);

    $this->callAPISuccess('PriceSet', 'delete', ['id' => $priceSetId]);
  }

}
