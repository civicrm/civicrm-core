<?php

/**
 * Class CRM_Utils_GlobalStackTest
 */
class CRM_Utils_GlobalStackTest extends CiviUnitTestCase {

  /**
   * Temporarily override global variables and ensure that the variable data.
   * is set as expected (before/during/after the override).
   */
  public function testPushPop() {
    global $_FOO, $_EXTRA;

    $_FOO['bar'] = 1;
    $_FOO['whiz'] = 1;
    $_EXTRA = 1;

    $this->assertEquals(1, $_FOO['bar']);
    $this->assertEquals(1, $_FOO['whiz']);
    $this->assertFalse(isset($_FOO['bang']));
    $this->assertEquals(1, $_EXTRA);

    CRM_Utils_GlobalStack::singleton()->push(array(
      '_FOO' => array(
        'bar' => 2,
        'bang' => 2,
      ),
      '_EXTRA' => 2,
    ));

    $this->assertEquals(2, $_FOO['bar']);
    $this->assertEquals(1, $_FOO['whiz']);
    $this->assertEquals(2, $_FOO['bang']);
    $this->assertEquals(2, $_EXTRA);

    CRM_Utils_GlobalStack::singleton()->pop();

    $this->assertEquals(1, $_FOO['bar']);
    $this->assertEquals(1, $_FOO['whiz']);
    $this->assertEquals(NULL, $_FOO['bang']);
    $this->assertEquals(1, $_EXTRA);
  }

}
