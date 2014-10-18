<?php

/**
 * This is a quick-and-dirty way to define a vaguely-class-ish structure. It's non-performant, abnormal,
 * and not a complete OOP system. Only use for testing/mocking.
 *
 * @code
 * $object = new CRM_Utils_FakeObject(array(
 *   'doIt' => function() {  print "It!\n"; }
 * ));
 * $object->doIt();
 * @endcode
 */
class CRM_Utils_FakeObject {
  /**
   * @param $array
   */
  function __construct($array) {
    $this->array = $array;
  }

  /**
   * @param $name
   * @param $arguments
   *
   * @throws Exception
   */
  function __call($name, $arguments) {
    if (isset($this->array[$name]) && is_callable($this->array[$name])) {
      return call_user_func_array($this->array[$name], $arguments);
    } else {
      throw new Exception("Call to unimplemented method: $name");
    }
  }
}
