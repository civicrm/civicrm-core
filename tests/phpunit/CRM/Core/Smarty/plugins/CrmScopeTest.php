<?php

/**
 * Class CRM_Core_Smarty_plugins_CrmScopeTest
 * @group headless
 */
class CRM_Core_Smarty_plugins_CrmScopeTest extends CiviUnitTestCase {

  /**
   * @return array
   */
  public function scopeCases(): array {
    $cases = [];
    $cases[] = ['', '{crmScope}{/crmScope}'];
    $cases[] = ['', '{crmScope x=1}{/crmScope}'];
    $cases[] = ['x=0', 'x={$x}'];
    $cases[] = ['x=1', '{crmScope x=1}x={$x}{/crmScope}'];
    $cases[] = ['x=0 x=1 x=0', 'x={$x} {crmScope x=1}x={$x}{/crmScope} x={$x}'];
    $cases[] = ['x=1 x=2 x=1', '{crmScope x=1}x={$x} {crmScope x=2}x={$x}{/crmScope} x={$x}{/crmScope}'];
    $cases[] = [
      'x=1 x=2 x=3 x=2 x=1',
      '{crmScope x=1}x={$x} {crmScope x=2}x={$x} {crmScope x=3}x={$x}{/crmScope} x={$x}{/crmScope} x={$x}{/crmScope}',
    ];
    $cases[] = ['x=1,y=9', '{crmScope x=1 y=9}x={$x},y={$y}{/crmScope}'];
    $cases[] = [
      'x=1,y=9 x=1,y=8 x=1,y=9',
      '{crmScope x=1 y=9}x={$x},y={$y} {crmScope y=8}x={$x},y={$y}{/crmScope} x={$x},y={$y}{/crmScope}',
    ];
    $cases[] = ['x=0', 'x={$x}'];
    return $cases;
  }

  /**
   * @dataProvider scopeCases
   * @param $expected
   * @param $input
   */
  public function testBlank($expected, $input) {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('x', 0);
    $actual = $smarty->fetch('eval:' . $input);
    $this->assertEquals($expected, $actual, "Process input=[$input]");
  }

}
