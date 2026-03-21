<?php

namespace Civi\Schema;

use Civi\Schema\Traits\MagicGetterSetterTrait;
use Civi\Test\Invasive;

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

  public function createAltExample() {
    return new class() {
      use MagicGetterSetterTrait;
      protected $altField;

    };
  }

  public function createEmptyExample() {
    return new class() {
      use MagicGetterSetterTrait;
    };
  }

  public function testExample(): void {
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
        $this->assertMatchesRegularExpression('/Unknown method.*::' . $nonMethod . '()/', $message);
      }
    }
  }

  public function testImplIndependence(): void {
    $ex1 = $this->createExample();
    $ex2 = $this->createAltExample();
    $ex3 = $this->createEmptyExample();

    // Multiple alternating calls. Caches remain independent.
    foreach (range(0, 2) as $i) {
      $this->assertEquals(
        ['protectedField', 'publicField', 'overriddenProtectedField', 'set', 'get'],
        array_keys(Invasive::call([$ex1, 'getMagicProperties']))
      );
      $this->assertEquals(['altField'], array_keys(Invasive::call([$ex2, 'getMagicProperties'])));
      $this->assertEquals([], array_keys(Invasive::call([$ex3, 'getMagicProperties'])));
    }
  }

  /**
   * Test that cache memory management prevents exhaustion.
   *
   * This test creates many classes to verify the cache limiting logic
   * prevents unbounded growth that could cause 66GB allocation attempts.
   */
  public function testCacheMemoryManagement(): void {
    $initialMemory = memory_get_usage(TRUE);
    // Create many anonymous classes to test cache limiting
    // Without the fix, this would cause unbounded memory growth
    $objects = [];
    for ($i = 0; $i < 600; $i++) {
      $objects[] = new class() {
        use MagicGetterSetterTrait;

        /**
         * @var mixed
         */
        protected $field1;

        /**
         * @var mixed
         */
        protected $field2;

      };

      // Access properties to populate cache
      $objects[$i]->setField1('test' . $i)->setField2('value' . $i);
      $objects[$i]->getField1();
      $objects[$i]->getField2();
    }

    $finalMemory = memory_get_usage(TRUE);
    $memoryIncrease = $finalMemory - $initialMemory;

    // Memory increase should be reasonable (under 100MB for 600 objects)
    // Without the fix, this could be gigabytes
    $this->assertLessThan(100 * 1024 * 1024, $memoryIncrease,
      'Memory usage should remain bounded with cache limiting');

    // Verify basic functionality still works
    $this->assertEquals('test0', $objects[0]->getField1());
    $this->assertEquals('value0', $objects[0]->getField2());
    $this->assertEquals('test599', $objects[599]->getField1());
    $this->assertEquals('value599', $objects[599]->getField2());
  }

  /**
   * Test memory limit parsing handles various formats correctly.
   */
  public function testMemoryLimitParsing(): void {
    // Use reflection to test the private static parseMemoryLimit method
    $example = $this->createExample();
    $reflection = new \ReflectionClass($example);
    $method = $reflection->getMethod('parseMemoryLimit');
    $method->setAccessible(TRUE);

    // Test various memory limit formats by temporarily setting ini values
    $originalLimit = ini_get('memory_limit');
    $currentMemory = memory_get_usage(TRUE);

    // Calculate safe memory limits that are higher than current usage
    $safeMemoryMB = max(256, ceil($currentMemory / (1024 * 1024)) + 50);

    try {
      // Test unlimited
      ini_set('memory_limit', '-1');
      $this->assertEquals(0, $method->invoke(NULL));

      // Test formats with safe memory limits
      ini_set('memory_limit', $safeMemoryMB . 'M');
      $this->assertEquals($safeMemoryMB * 1024 * 1024, $method->invoke(NULL));

      // Skip MB/GB formats on PHP 8.2+ as ini_parse_quantity() doesn't support them
      // and generates warnings. Our regex fallback handles them correctly.
      if (!function_exists('ini_parse_quantity')) {
        ini_set('memory_limit', $safeMemoryMB . 'MB');
        $this->assertEquals($safeMemoryMB * 1024 * 1024, $method->invoke(NULL));
      }

      ini_set('memory_limit', '1G');
      $this->assertEquals(1024 * 1024 * 1024, $method->invoke(NULL));

      if (!function_exists('ini_parse_quantity')) {
        ini_set('memory_limit', '2GB');
        $this->assertEquals(2 * 1024 * 1024 * 1024, $method->invoke(NULL));
      }

      $safeMemoryBytes = $safeMemoryMB * 1024 * 1024;
      // Raw bytes
      ini_set('memory_limit', (string) $safeMemoryBytes);
      $this->assertEquals($safeMemoryBytes, $method->invoke(NULL));

    } finally {
      // Restore original limit
      ini_set('memory_limit', $originalLimit);
    }
  }

}
