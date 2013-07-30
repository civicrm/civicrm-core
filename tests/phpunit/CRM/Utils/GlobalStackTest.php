<?php

require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_Utils_GlobalStackTest extends CiviUnitTestCase {

  public function testPushPop() {
    global $FOO;

    $FOO['bar'] = 1;
    $FOO['whiz'] = 1;

    $this->assertEquals(1, $FOO['bar']);
    $this->assertEquals(1, $FOO['whiz']);
    $this->assertFalse(isset($FOO['bang']));

    CRM_Utils_GlobalStack::singleton()->push(array(
      'FOO' => array(
        'bar' => 2,
        'bang' => 2,
      ),
    ));

    $this->assertEquals(2, $FOO['bar']);
    $this->assertEquals(1, $FOO['whiz']);
    $this->assertEquals(2, $FOO['bang']);

    CRM_Utils_GlobalStack::singleton()->pop();

    $this->assertEquals(1, $FOO['bar']);
    $this->assertEquals(1, $FOO['whiz']);
    $this->assertEquals(NULL, $FOO['bang']);
  }
}
