<?php

/**
 * Class CRM_Utils_SQLTest
 * @group headless
 */
class CRM_Utils_SQLTest extends CiviUnitTestCase {

  public function testInterpolate() {
    // This function is a thin wrapper for `CRM_Utils_SQL_BaseParamQuery::interpolate()`, which already has
    // lots of coverage in other test classes. This test just checks the basic wiring.
    $sql = CRM_Utils_SQL::interpolate('FROBNICATE some_table WITH MAX(!dynamicField) OVER #times USING (@list) OR (#ids) OR @item', [
      '!dynamicField' => 'the(field)',
      '#times' => 123,
      '@list' => ['abc def', '45'],
      '#ids' => [6, 7, 8],
      '@item' => "it's text",
    ]);
    $this->assertEquals('FROBNICATE some_table WITH MAX(the(field)) OVER 123 USING ("abc def", "45") OR (6, 7, 8) OR "it\\\'s text"', $sql);
  }

  public function testInterpolateBad() {
    try {
      CRM_Utils_SQL::interpolate("UPDATE !the_table SET !the_field = @THE_VALUE", [
        // MISSING: 'the_table'
        'the_field' => 'my_field',
        'the_value' => 'ny value',
      ]);
    }
    catch (CRM_Core_Exception $e) {
      $this->assertRegExp(';Cannot build query. Variable "!the_table" is unknown.;', $e->getMessage());
    }
  }

}
