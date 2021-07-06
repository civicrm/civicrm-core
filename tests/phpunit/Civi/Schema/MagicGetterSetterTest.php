<?php

namespace Civi\Schema;

use Civi\Schema\Traits\MagicGetterSetterTrait;

class MagicGetterSetterTest extends \CiviUnitTestCase {

  public function createExample() {
    return new class() {

      use MagicGetterSetterTrait;

      protected $protectedField;
      public $publicField;
      protected $_obscureProtectedField;
      public $_obscurePublicField;
      protected $overriddenProtectedField;
      protected $set;
      protected $get;

      /**
       * @return mixed
       */
      public function getOverriddenProtectedField() {
        return $this->overriddenProtectedField . '_and_get';
      }

      /**
       * @param mixed $overriddenProtectedField
       * @return $this
       */
      public function setOverriddenProtectedField($overriddenProtectedField) {
        $this->overriddenProtectedField = $overriddenProtectedField . '_and_set';
        return $this;
      }

    };
  }

  public function testExample() {
    $ex = $this->createExample();
    $this->assertEquals(NULL, $ex->setProtectedField(NULL)->getProtectedField());
    $this->assertEquals('apple', $ex->setProtectedField('apple')->getProtectedField());
    $this->assertEquals('banana', $ex->setPublicField('banana')->getPublicField());
    $this->assertEquals('cherry', $ex->setSet('cherry')->getSet());
    $this->assertEquals('date', $ex->setGet('date')->getGet());
    $this->assertEquals('base_and_set_and_get', $ex->setOverriddenProtectedField('base')->getOverriddenProtectedField());

    $nonMethods = [
      'goozfraba',

      // Typos
      'seProtectedField',
      'geProtectedField',
      'istProtectedField',

      // Obscure fields
      'set_obscureProtectedField',
      'get_obscureProtectedField',
      'is_obscureProtectedField',
      'setObscureProtectedField',
      'getObscureProtectedField',
      'isObscureProtectedField',
      'set_obscurePublicField',
      'get_obscurePublicField',
      'is_obscurePublicField',
      'setObscurePublicField',
      'getObscurePublicField',
      'isObscurePublicField',

      // Funny substrings
      'i',
      'g',
      's',
      'set',
      'get',
      'is',
      'istanbul',
      'getter',
      'setter',
    ];
    foreach ($nonMethods as $nonMethod) {
      try {
        $ex->{$nonMethod}();
        $this->fail("Method $nonMethod() should raise exception.");
      }
      catch (\CRM_Core_Exception $e) {
        $message = $e->getMessage();
        $this->assertRegExp('/Unknown method.*::' . $nonMethod . '()/', $message);
      }
    }
  }

}
