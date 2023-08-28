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
 * @group headless
 */
class CRM_Contribute_Form_ContributionViewTest extends CiviUnitTestCase {

  /**
   * @var int
   */
  private $contact_id;

  /**
   * @var array
   */
  private $contribution;

  public function setUp(): void {
    parent::setUp();
    $this->contact_id = $this->individualCreate();
    $this->contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->contact_id,
      'financial_type_id' => 'Donation',
      'total_amount' => '10',
    ]);
  }

  public function tearDown(): void {
    $this->callAPISuccess('Contribution', 'delete', ['id' => $this->contribution['id']]);
    $this->callAPISuccess('Contact', 'delete', ['id' => $this->contact_id]);
    parent::tearDown();
  }

  /**
   * Test that can still view a contribution without full permissions.
   */
  public function testContributionViewLimitedPermissions(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'access all custom data',
      'edit all contacts',
      'access CiviContribute',
      'edit contributions',
      'delete in CiviContribute',
    ];

    $_SERVER['REQUEST_URI'] = "civicrm/contact/view/contribution?reset=1&action=view&id={$this->contribution['id']}&cid={$this->contact_id}";
    $_GET['q'] = $_REQUEST['q'] = 'civicrm/contact/view/contribution';
    $_GET['reset'] = $_REQUEST['reset'] = 1;
    $_GET['action'] = $_REQUEST['action'] = 'view';
    $_GET['id'] = $_REQUEST['id'] = $this->contribution['id'];
    $_GET['cid'] = $_REQUEST['cid'] = $this->contact_id;

    $item = CRM_Core_Invoke::getItem(['civicrm/contact/view/contribution']);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    unset($_GET['q'], $_REQUEST['q']);
    unset($_GET['reset'], $_REQUEST['reset']);
    unset($_GET['action'], $_REQUEST['action']);
    unset($_GET['id'], $_REQUEST['id']);
    unset($_GET['cid'], $_REQUEST['cid']);

    $this->assertMatchesRegularExpression('/Contribution Total:\s+\$10\.00/', $contents);
    $this->assertStringContainsString('Mr. Anthony Anderson II', $contents);
  }

  public function testInvoiceDownload(): void {
    Civi::settings()->set('invoicing', 1);

    $_SERVER['REQUEST_URI'] = "civicrm/contribute/invoice?reset=1&id={$this->contribution['id']}&cid={$this->contact_id}";
    $_GET['q'] = $_REQUEST['q'] = 'civicrm/contribute/invoice';
    $_GET['reset'] = $_REQUEST['reset'] = 1;
    $_GET['id'] = $_REQUEST['id'] = $this->contribution['id'];
    $_GET['cid'] = $_REQUEST['cid'] = $this->contact_id;

    $item = CRM_Core_Invoke::getItem(['civicrm/contribute/invoice']);
    ob_start();
    $isOk = FALSE;
    try {
      CRM_Core_Invoke::runItem($item);
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals(0, $e->errorData['error_code']);
      $this->assertEquals('pdf', $e->errorData['output']);
      $this->assertEquals("INV_{$this->contribution['id']}", $e->errorData['fileName']);
      $this->assertStringContainsString('INVOICE', $e->errorData['html']);
      $isOk = TRUE;
    }
    finally {
      ob_end_clean();

      unset($_GET['q'], $_REQUEST['q']);
      unset($_GET['reset'], $_REQUEST['reset']);
      unset($_GET['id'], $_REQUEST['id']);
      unset($_GET['cid'], $_REQUEST['cid']);
      Civi::settings()->set('invoicing', 0);
    }
    if (!$isOk) {
      $this->fail('It should have entered the catch block.');
    }
  }

}
