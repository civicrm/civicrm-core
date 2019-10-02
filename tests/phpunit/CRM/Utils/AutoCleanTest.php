<?php

/**
 * Class CRM_Utils_AutoCleanTest
 * @group headless
 */
class CRM_Utils_AutoCleanTest extends CiviUnitTestCase {

  public $foo;

  protected function setUp() {
    $this->useTransaction();
    parent::setUp();
  }

  public function testAutoclean() {
    $this->foo = 'orig';
    $this->assertEquals('orig', $this->foo);
    $this->nestedWithArrayCb();
    $this->assertEquals('orig', $this->foo);
    $this->nestedWithFuncCb();
    $this->assertEquals('orig', $this->foo);
    $this->nestedSwap();
    $this->assertEquals('orig', $this->foo);
  }

  public function nestedWithArrayCb() {
    $this->foo = 'arraycb';
    $ac = CRM_Utils_AutoClean::with([$this, 'setFoo'], 'orig');
    $this->assertEquals('arraycb', $this->foo);
  }

  public function nestedWithFuncCb() {
    $this->foo = 'funccb';

    $self = $this; /* php 5.3 */
    $ac = CRM_Utils_AutoClean::with(function () use ($self /* php 5.3 */) {
      $self->foo = 'orig';
    });

    $this->assertEquals('funccb', $this->foo);
  }

  public function nestedSwap() {
    $ac = CRM_Utils_AutoClean::swap([$this, 'getFoo'], [$this, 'setFoo'], 'tmp');
    $this->assertEquals('tmp', $this->foo);
  }

  public function getFoo() {
    return $this->foo;
  }

  public function setFoo($value) {
    $this->foo = $value;
  }

}
