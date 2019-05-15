<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmScopeTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_CrmScopeTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    require_once 'CRM/Core/Smarty.php';

    // Templates should normally be file names, but for unit-testing it's handy to use "string:" notation
    require_once 'CRM/Core/Smarty/resources/String.php';
    civicrm_smarty_register_string_resource();
  }

  /**
   * @return array
   */
  public function scopeCases() {
    $cases = array();
    $cases[] = array('', '{crmScope}{/crmScope}');
    $cases[] = array('', '{crmScope x=1}{/crmScope}');
    $cases[] = array('x=', 'x={$x}');
    $cases[] = array('x=1', '{crmScope x=1}x={$x}{/crmScope}');
    $cases[] = array('x=1', '{$x}{crmScope x=1}x={$x}{/crmScope}{$x}');
    $cases[] = array('x=1 x=2 x=1', '{crmScope x=1}x={$x} {crmScope x=2}x={$x}{/crmScope} x={$x}{/crmScope}');
    $cases[] = array(
      'x=1 x=2 x=3 x=2 x=1',
      '{crmScope x=1}x={$x} {crmScope x=2}x={$x} {crmScope x=3}x={$x}{/crmScope} x={$x}{/crmScope} x={$x}{/crmScope}',
    );
    $cases[] = array('x=1,y=9', '{crmScope x=1 y=9}x={$x},y={$y}{/crmScope}');
    $cases[] = array(
      'x=1,y=9 x=1,y=8 x=1,y=9',
      '{crmScope x=1 y=9}x={$x},y={$y} {crmScope y=8}x={$x},y={$y}{/crmScope} x={$x},y={$y}{/crmScope}',
    );
    $cases[] = array('x=', 'x={$x}');
    return $cases;
  }

  /**
   * @dataProvider scopeCases
   * @param $expected
   * @param $input
   */
  public function testBlank($expected, $input) {
    $smarty = CRM_Core_Smarty::singleton();
    $actual = $smarty->fetch('string:' . $input);
    $this->assertEquals($expected, $actual, "Process input=[$input]");
  }

}
