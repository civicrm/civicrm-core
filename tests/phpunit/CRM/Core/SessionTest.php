<?php

/**
 * Class CRM_Core_SessionTest
 * @group headless
 */
class CRM_Core_SessionTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    CRM_Core_Smarty::singleton()->clearTemplateVars();
    // set null defaults
    foreach (['infoOptions', 'infoType', 'infoMessage', 'infoTitle'] as $info) {
      CRM_Core_Smarty::singleton()->assign($info, NULL);
    }
  }

  /**
   * Test that the template setStatus uses gives reasonable output.
   * Test with text only.
   */
  public function testSetStatusWithTextOnly() {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('infoMessage', 'Your refridgerator door is open.');
    $output = $smarty->fetch('CRM/common/info.tpl');
    $this->assertStringContainsString('Your refridgerator door is open.', $output);
  }

  /**
   * Test that the template setStatus uses gives reasonable output.
   * Test with title only.
   */
  public function testSetStatusWithTitleOnly() {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('infoTitle', 'Error Error Error.');
    $output = $smarty->fetch('CRM/common/info.tpl');
    $this->assertStringContainsString('Error Error Error.', $output);
  }

  /**
   * Test that the template setStatus uses gives reasonable output.
   * Test with both text and title.
   */
  public function testSetStatusWithBoth() {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('infoTitle', 'Spoiler alert!');
    $smarty->assign('infoMessage', 'Your refridgerator door is open.');
    $output = $smarty->fetch('CRM/common/info.tpl');
    $this->assertStringContainsString('Spoiler alert!', $output);
    $this->assertStringContainsString('Your refridgerator door is open.', $output);
  }

}
