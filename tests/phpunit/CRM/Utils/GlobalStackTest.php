<<<<<<< HEAD
<?php

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Utils_GlobalStackTest
 */
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
=======
<?php

/**
 * Class CRM_Utils_GlobalStackTest
 * @group headless
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
>>>>>>> refs/remotes/civicrm/master
