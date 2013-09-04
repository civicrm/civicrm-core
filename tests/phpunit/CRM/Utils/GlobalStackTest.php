<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Utils_GlobalStackTest extends CiviUnitTestCase {

  public function testPushPop() {
    global $FOO, $EXTRA;

    $FOO['bar'] = 1;
    $FOO['whiz'] = 1;
    $EXTRA = 1;

    $this->assertEquals(1, $FOO['bar']);
    $this->assertEquals(1, $FOO['whiz']);
    $this->assertFalse(isset($FOO['bang']));
    $this->assertEquals(1, $EXTRA);

    CRM_Utils_GlobalStack::singleton()->push(array(
      'FOO' => array(
        'bar' => 2,
        'bang' => 2,
      ),
      'EXTRA' => 2,
    ));

    $this->assertEquals(2, $FOO['bar']);
    $this->assertEquals(1, $FOO['whiz']);
    $this->assertEquals(2, $FOO['bang']);
    $this->assertEquals(2, $EXTRA);

    CRM_Utils_GlobalStack::singleton()->pop();

    $this->assertEquals(1, $FOO['bar']);
    $this->assertEquals(1, $FOO['whiz']);
    $this->assertEquals(NULL, $FOO['bang']);
    $this->assertEquals(1, $EXTRA);
  }
}
