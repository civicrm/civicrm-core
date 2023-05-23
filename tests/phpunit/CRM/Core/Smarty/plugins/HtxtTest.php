<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmScopeTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_HtxtTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    require_once 'CRM/Core/Smarty.php';

    // Templates should normally be file names, but for unit-testing it's handy to use "string:" notation
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();
  }

  /**
   * @return array
   */
  public function supportedCases() {
    $cases = [];
    $cases[] = ['yum yum apple_pie!', '{htxt id="apple_pie"}yum yum apple_pie!{/htxt}', ['id' => 'apple_pie']];
    $cases[] = ['yum yum Apple-Pie!', '{htxt id=\'Apple-Pie\'}yum yum Apple-Pie!{/htxt}', ['id' => 'Apple-Pie']];
    $cases[] = ['yum yum apple!', '{htxt id="apple" other=stuff}yum yum apple!{/htxt}', ['id' => 'apple']];
    $cases[] = ['', '{htxt id="apple"}yum yum apple!{/htxt}', ['id' => 'not me']];
    $cases[] = ['yum yum banana!', '{htxt id=$dynamic}yum yum {$dynamic}!{/htxt}', ['id' => 'banana', 'dynamic' => 'banana']];
    $cases[] = ['yum yum banana!', '{htxt id=$dynamic other=stuff}yum yum {$dynamic}!{/htxt}', ['id' => 'banana', 'dynamic' => 'banana']];
    $cases[] = ['', '{htxt id=$dynamic}yum yum {$dynamic}!{/htxt}', ['id' => 'apple', 'dynamic' => 'banana']];
    // More advanced forms of dynamic-id's might be nice, but this is currently the ceiling on what's needed.
    return $cases;
  }

  public function unsupportedCases() {
    $cases = [];
    $cases[] = ['{htxt id=$dynamic.zx["$f{b}"]}not supported{/htxt}', []];
    $cases[] = ['{htxt id=\'dragonfruit"}not supported{/htxt}', []];
    $cases[] = ['{htxt id=\'apple\'"banana"]}not supported{/htxt}', []];
    $cases[] = ['{htxt id=\'apple\'.banana]}not supported{/htxt}', []];
    return $cases;
  }

  /**
   * @dataProvider supportedCases
   * @param string $expected
   * @param string $input
   * @param array $vars
   */
  public function testSupported(string $expected, string $input, array $vars) {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->pushScope($vars);
    try {
      $actual = $smarty->fetch('string:' . $input);
      $this->assertEquals($expected, $actual, "Process input=[$input]");
    }
    finally {
      $smarty->popScope();
    }
  }

  /**
   * @dataProvider unsupportedCases
   * @param string $input
   * @param array $vars
   */
  public function testUnsupported(string $input, array $vars) {
    $smarty = CRM_Core_Smarty::singleton();
    try {
      $smarty->fetch('string:' . $input);
      $this->fail("That should have thrown an error. Are you working on a better parsing rule?");
    }
    catch (Throwable $t) {
      ob_end_flush();
      $this->assertTrue(str_contains($t->getMessage(), 'Invalid {htxt} tag'));
    }
  }

}
