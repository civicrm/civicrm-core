<?php

/**
 * Class CRM_Utils_AutoCleanTest
 * @group headless
 */
class CRM_Utils_AutoCleanTest extends CiviUnitTestCase {

  public $foo;

  /**
   * @var CRM_Utils_System_Base|null
   */
  private $originalUserSystem;

  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  protected function tearDown(): void {
    if ($this->originalUserSystem) {
      CRM_Core_Config::singleton()->userSystem = $this->originalUserSystem;
    }
    parent::tearDown();
  }

  /**
   * swapLocale() restores the CMS language that was active, even when it differs from the
   * CiviCRM locale.
   */
  public function testSwapLocaleRestoresUFLocale(): void {
    $this->originalUserSystem = CRM_Core_Config::singleton()->userSystem;
    $userSystem = new class() extends CRM_Utils_System_UnitTests {

      public $ufLocale = 'fr_FR';

      public function getUFLocale() {
        return $this->ufLocale;
      }

      public function setUFLocale($civicrm_language) {
        $this->ufLocale = $civicrm_language;
        return TRUE;
      }

    };
    CRM_Core_Config::singleton()->userSystem = $userSystem;
    $originalLocale = CRM_Core_I18n::getLocale();

    $swap = CRM_Utils_AutoClean::swapLocale('de_DE');
    $this->assertNotEquals('fr_FR', $userSystem->ufLocale);
    unset($swap);

    $this->assertEquals('fr_FR', $userSystem->ufLocale);
    $this->assertEquals($originalLocale, CRM_Core_I18n::getLocale());
  }

  public function testAutoclean(): void {
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
