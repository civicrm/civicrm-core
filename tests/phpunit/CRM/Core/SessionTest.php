<?php

/**
 * Class CRM_Core_SessionTest
 * @group headless
 */
class CRM_Core_SessionTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    // set null defaults
    foreach (['infoOptions', 'infoType', 'infoMessage', 'infoTitle'] as $info) {
      CRM_Core_Smarty::singleton()->assign($info);
    }
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
