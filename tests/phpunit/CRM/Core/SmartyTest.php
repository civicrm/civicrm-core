<?php

/**
 * Class CRM_Core_SmartyTest
 * @group headless
 *
 */
class CRM_Core_SmartyTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();

    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();

    $this->useTransaction();
  }

  /**
   * Check that temporary Smarty variables work.
   *
   * This overlaps with CrmScopeTest (which actually tests more diverse scenarios). However, here we specifically check the PHP APIs
   * (`fetchWith()`) and the correctness of different forms of emptiness.
   *
   * @see \CRM_Core_Smarty_plugins_CrmScopeTest
   */
  public function testFetchWith_CleanNonExistent(): void {
    $smarty = CRM_Core_Smarty::singleton();
    $this->assertFalse(array_key_exists('my_variable', $smarty->getTemplateVars()));

    $rendered = $smarty->fetchWith('string:({$my_variable})', [
      'my_variable' => 'temporary value',
    ]);
    $this->assertEquals('(temporary value)', $rendered);

    $this->assertFalse(array_key_exists('my_variable', $smarty->getTemplateVars()));
  }

  /**
   * Check that temporary Smarty variables work.
   *
   * This overlaps with CrmScopeTest (which actually tests more diverse scenarios). However, here we specifically check the PHP APIs
   * (`fetchWith()`) and the correctness of different forms of emptiness.
   *
   * @see \CRM_Core_Smarty_plugins_CrmScopeTest
   */
  public function testFetchWith_CleanNull(): void {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('my_variable', NULL);
    $this->assertEquals(NULL, $smarty->getTemplateVars()['my_variable']);

    $tpl = 'string:({$my_variable})';
    $this->assertEquals('()', $smarty->fetchWith($tpl, []));
    $this->assertEquals('(temporary value)', $smarty->fetchWith($tpl, [
      'my_variable' => 'temporary value',
    ]));

    // Assert global state
    $this->assertEquals(NULL, $smarty->getTemplateVars()['my_variable']);
  }

}
