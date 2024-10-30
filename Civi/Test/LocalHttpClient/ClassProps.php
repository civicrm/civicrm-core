<?php
namespace Civi\Test\LocalHttpClient;

/**
 * @internal
 */
class ClassProps {

  protected \ReflectionClass $class;

  public function __construct(string $class) {
    $this->class = new \ReflectionClass($class);
  }

  public function getValues() {
    return $this->class->getStaticProperties() ?: [];
  }

  public function setValues(iterable $values): void {
    foreach ($values as $key => $value) {
      $this->class->setStaticPropertyValue($key, $value);
    }
  }

  public function unsetKeys(iterable $keys): void {
    foreach ($keys as $key) {
      $this->class->setStaticPropertyValue($key, NULL);
    }
  }

}
