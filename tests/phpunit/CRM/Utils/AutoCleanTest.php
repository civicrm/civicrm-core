<?php

/**
 * Class CRM_Utils_AutoCleanTest
 * @group headless
 */
class CRM_Utils_AutoCleanTest extends CiviUnitTestCase {

  public $foo;

  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testAutoclean(): void {
    $this->foo = 'orig';
    $this->assertSame('orig', $this->foo);
    $this->nestedWithArrayCb();
    $this->assertSame('orig', $this->foo);
    $this->nestedWithFuncCb();
    $this->assertSame('orig', $this->foo);
    $this->nestedSwap();
    $this->assertSame('orig', $this->foo);
  }

  public function nestedWithArrayCb() {
    $this->foo = 'arraycb';
    $ac = CRM_Utils_AutoClean::with([$this, 'setFoo'], 'orig');
    $this->assertSame('arraycb', $this->foo);
  }

  public function nestedWithFuncCb() {
    $this->foo = 'funccb';

    $self = $this; /* php 5.3 */
    $ac = CRM_Utils_AutoClean::with(function () use ($self /* php 5.3 */) {
      $self->foo = 'orig';
    });

    $this->assertSame('funccb', $this->foo);
  }

  public function nestedSwap() {
    $ac = CRM_Utils_AutoClean::swap([$this, 'getFoo'], [$this, 'setFoo'], 'tmp');
    $this->assertSame('tmp', $this->foo);
  }

  public function getFoo() {
    return $this->foo;
  }

  public function setFoo($value) {
    $this->foo = $value;
  }

}
