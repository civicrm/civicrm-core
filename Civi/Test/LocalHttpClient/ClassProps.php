<?php
namespace Civi\Test\LocalHttpClient;

use Civi\Test\Invasive;

/**
 * @internal
 */
class ClassProps {

  /**
   * @var \ReflectionClass
   */
  protected $class;

  public function __construct(string $class) {
    $this->class = new \ReflectionClass($class);
  }

  public function getValues() {
    return $this->class->getStaticProperties() ?: [];
  }

  public function setValues(iterable $values): void {
    foreach ($values as $key => $value) {
      // In PHP 7.3, setStaticPropertyValue() fails for private properties.
      // $this->class->setStaticPropertyValue($key, $value);
      Invasive::set([$this->class->getName(), $key], $value);
    }
  }

  public function unsetKeys(iterable $keys): void {
    foreach ($keys as $key) {
      // In PHP 7.3, setStaticPropertyValue() fails for private properties.
      // $this->class->setStaticPropertyValue($key, NULL);
      Invasive::set([$this->class->getName(), $key], NULL);
    }
  }

}
