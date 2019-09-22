<?php

namespace api\v4\Action;

use Civi\Api4\MockBasicEntity;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class EvaluateConditionTest extends UnitTestCase {

  public function testEvaluateCondition() {
    $action = MockBasicEntity::get();
    $reflection = new \ReflectionClass($action);
    $method = $reflection->getMethod('evaluateCondition');
    $method->setAccessible(TRUE);

    $data = [
      'nada' => 0,
      'uno' => 1,
      'dos' => 2,
      'apple' => 'red',
      'banana' => 'yellow',
      'values' => ['one' => 1, 'two' => 2, 'three' => 3],
    ];

    $this->assertFalse($method->invoke($action, '$uno > $dos', $data));
    $this->assertTrue($method->invoke($action, '$uno < $dos', $data));
    $this->assertTrue($method->invoke($action, '$apple == "red" && $banana != "red"', $data));
    $this->assertFalse($method->invoke($action, '$apple == "red" && $banana != "yellow"', $data));
    $this->assertTrue($method->invoke($action, '$values.one == $uno', $data));
    $this->assertTrue($method->invoke($action, '$values.one + $dos == $values.three', $data));
    $this->assertTrue($method->invoke($action, 'empty($nada)', $data));
    $this->assertFalse($method->invoke($action, 'empty($values)', $data));
  }

}
