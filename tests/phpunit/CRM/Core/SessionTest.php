<?php

/**
 * Class CRM_Core_SessionTest
 * @group headless
 */
class CRM_Core_SessionTest extends CiviUnitTestCase {

  /**
   * @var CRM_Utils_System_Base|null
   */
  private $originalUserSystem;

  public function setUp(): void {
    parent::setUp();
    // set null defaults
    foreach (['infoOptions', 'infoType', 'infoMessage', 'infoTitle'] as $info) {
      CRM_Core_Smarty::singleton()->assign($info);
    }
  }

  public function tearDown(): void {
    if ($this->originalUserSystem) {
      CRM_Core_Config::singleton()->userSystem = $this->originalUserSystem;
    }
    CRM_Core_Session::singleton()->resetScope(CRM_Core_Session::USER_CONTEXT);
    parent::tearDown();
  }

  /**
   * User contexts are adapted to the current CMS language when read.
   */
  public function testUserContextIsLocalized(): void {
    $this->originalUserSystem = CRM_Core_Config::singleton()->userSystem;
    CRM_Core_Config::singleton()->userSystem = new class extends CRM_Utils_System_UnitTests {

      public function localizeUrl(string $url): string {
        return 'localized:' . $url;
      }

    };
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext('/civicrm/contact/view?reset=1&cid=1');

    $this->assertEquals('localized:/civicrm/contact/view?reset=1&cid=1', $session->readUserContext());
    $this->assertEquals('localized:/civicrm/contact/view?reset=1&cid=1', $session->popUserContext());
  }

  /**
   * Test that the template setStatus uses gives reasonable output.
   * Test with text only.
   *
   * @throws \SmartyException
   */
  public function testSetStatusWithTextOnly(): void {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('infoMessage', 'Your refrigerator door is open.');
    $output = $smarty->fetch('CRM/common/info.tpl');
    $this->assertStringContainsString('Your refrigerator door is open.', $output);
  }

  /**
   * Test that the template setStatus uses gives reasonable output.
   * Test with title only.
   *
   * @throws \SmartyException
   */
  public function testSetStatusWithTitleOnly(): void {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('infoTitle', 'Error Error Error.');
    $output = $smarty->fetch('CRM/common/info.tpl');
    $this->assertStringContainsString('Error Error Error.', $output);
  }

  /**
   * Test that the template setStatus uses gives reasonable output.
   * Test with both text and title.
   *
   * @throws \SmartyException
   */
  public function testSetStatusWithBoth(): void {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('infoTitle', 'Spoiler alert!');
    $smarty->assign('infoMessage', 'Your refrigerator door is open.');
    $output = $smarty->fetch('CRM/common/info.tpl');
    $this->assertStringContainsString('Spoiler alert!', $output);
    $this->assertStringContainsString('Your refrigerator door is open.', $output);
  }

}
