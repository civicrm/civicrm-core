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

namespace Civi\WorkflowMessage;

/**
 * @group headless
 */
class FieldSpecTest extends \CiviUnitTestCase {

  protected function setUp(): void {
    $this->useTransaction();
    parent::setUp();
  }

  public function getScopeExamples() {
    // When the naming convention is more established, one might improve readability by inlining these constants.
    $tpl = 'tplParams';
    $tok = 'tokenContext';

    $exs = [];
    $exs[] = [$tpl, [$tpl => 'foo']];
    $exs[] = ["$tpl as foo.bar", [$tpl => 'foo.bar']];
    $exs[] = ["$tpl, $tok", [$tpl => 'foo', $tok => 'foo']];
    $exs[] = ["$tok, $tpl as foo.bar", [$tok => 'foo', $tpl => 'foo.bar']];
    $exs[] = ["$tok as fooBar, $tpl", [$tok => 'fooBar', $tpl => 'foo']];
    return $exs;
  }

  /**
   * Test that the setScope()/getScope() normalization works.
   *
   * @param mixed $input
   *   The value to pass into `setScope()`
   * @param array $expect
   *   The resulting value to expect from `getScope()`.
   * @dataProvider getScopeExamples
   */
  public function testSetScope($input, $expect) {
    $f = new FieldSpec();
    $f->setName('foo');

    // Check that the inputs are translated
    $f->setScope($input);
    $getScope = $f->getScope();
    $this->assertEquals($expect, $getScope);

    // The output of translation should be stable/convergent.
    $f->setScope($getScope);
    $this->assertEquals($expect, $f->getScope());
  }

}
