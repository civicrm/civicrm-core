<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

namespace Civi\Core\Event;

/**
 * Class GenericHookEvent
 * @package Civi\API\Event
 *
 * The GenericHookEvent is used to expose all traditional hooks to the
 * Symfony EventDispatcher.
 *
 * The traditional notation for a hook is based on a function signature:
 *
 *   function hook_civicrm_foo($bar, &$whiz, &$bang);
 *
 * The notation for Symfony Events is based on a class with properties
 * and methods. This requires some kind of mapping. `GenericHookEvent`
 * maps each parameter to a field (using magic methods):
 *
 * @code
 * // Creating an event object.
 * $event = GenericHookEvent::create(array(
 *   'bar' => 'abc',
 *   'whiz' => &$whiz,
 *   'bang' => &$bang,
 * );
 *
 * // Accessing event properties.
 * echo $event->bar;
 * $event->whiz['array_field'] = 123;
 * $event->bang->objProperty = 'abcd';
 *
 * // Dispatching an event.
 * Civi::service('dispatcher')->dispatch('hook_civicrm_foo', $event);
 * @endCode
 *
 * Design Discussion:
 *
 * 1. Implementing new event classes for every hook would produce a
 * large amount of boilerplate. Symfony Events have an interesting solution to
 * that problem: use `GenericEvent` instead of custom event classes.
 * `GenericHookEvent` is conceptually similar to `GenericEvent`, but it adds
 * support for (a) altering properties and (b) mapping properties to hook notation
 * (an ordered parameter list).
 *
 * 2. A handful of hooks define a return-value. The return-value is treated
 * as an array, and all the returned values are merged into one big array.
 * You can add and retrieve return-values using these methods:
 *
 * @code
 * $event->addReturnValues(array(...));
 * foreach ($event->getReturnValues() as $retVal) { ... }
 * @endCode
 */
class GenericHookEvent extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @var array
   *   Ex: array(0 => &$contactID, 1 => &$contentPlacement).
   */
  protected $hookValues;

  /**
   * @var array
   *   Ex: array(0 => 'contactID', 1 => 'contentPlacement').
   */
  protected $hookFields;

  /**
   * @var array
   *   Ex: array('contactID' => 0, 'contentPlacement' => 1).
   */
  protected $hookFieldsFlip;

  /**
   * Some legacy hooks expect listener-functions to return a value.
   * OOP listeners may set the $returnValue.
   *
   * This field is not recommended for use in new hooks. The return-value
   * convention is not portable across different implementations of the hook
   * system. Instead, it's more portable to provide an alterable, named field.
   *
   * @var mixed
   * @deprecated
   */
  private $returnValues = [];

  /**
   * List of field names that are prohibited due to conflicts
   * in the class-hierarchy.
   *
   * @var array
   */
  private static $BLACKLIST = [
    'name',
    'dispatcher',
    'propagationStopped',
    'hookBlacklist',
    'hookValues',
    'hookFields',
    'hookFieldsFlip',
  ];

  /**
   * Create a GenericHookEvent using key-value pairs.
   *
   * @param array $params
   *   Ex: array('contactID' => &$contactID, 'contentPlacement' => &$contentPlacement).
   * @return \Civi\Core\Event\GenericHookEvent
   */
  public static function create($params) {
    $e = new static();
    $e->hookValues = array_values($params);
    $e->hookFields = array_keys($params);
    $e->hookFieldsFlip = array_flip($e->hookFields);
    self::assertValidHookFields($e->hookFields);
    return $e;
  }

  /**
   * Create a GenericHookEvent using ordered parameters.
   *
   * @param array $hookFields
   *   Ex: array(0 => 'contactID', 1 => 'contentPlacement').
   * @param array $hookValues
   *   Ex: array(0 => &$contactID, 1 => &$contentPlacement).
   * @return \Civi\Core\Event\GenericHookEvent
   */
  public static function createOrdered($hookFields, $hookValues) {
    $e = new static();
    if (count($hookValues) > count($hookFields)) {
      $hookValues = array_slice($hookValues, 0, count($hookFields));
    }
    $e->hookValues = $hookValues;
    $e->hookFields = $hookFields;
    $e->hookFieldsFlip = array_flip($e->hookFields);
    self::assertValidHookFields($e->hookFields);
    return $e;
  }

  /**
   * @param array $fields
   *   List of field names.
   */
  private static function assertValidHookFields($fields) {
    $bad = array_intersect($fields, self::$BLACKLIST);
    if ($bad) {
      throw new \RuntimeException("Hook relies on conflicted field names: "
        . implode(', ', $bad));
    }
  }

  /**
   * @return array
   *   Ex: array(0 => &$contactID, 1 => &$contentPlacement).
   */
  public function getHookValues() {
    return $this->hookValues;
  }

  /**
   * @return mixed
   * @deprecated
   */
  public function getReturnValues() {
    return empty($this->returnValues) ? TRUE : $this->returnValues;
  }

  /**
   * @param mixed $fResult
   * @return GenericHookEvent
   * @deprecated
   */
  public function addReturnValues($fResult) {
    if (!empty($fResult) && is_array($fResult)) {
      $this->returnValues = array_merge($this->returnValues, $fResult);
    }
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function &__get($name) {
    if (isset($this->hookFieldsFlip[$name])) {
      return $this->hookValues[$this->hookFieldsFlip[$name]];
    }
  }

  /**
   * @inheritDoc
   */
  public function __set($name, $value) {
    if (isset($this->hookFieldsFlip[$name])) {
      $this->hookValues[$this->hookFieldsFlip[$name]] = $value;
    }
  }

  /**
   * @inheritDoc
   */
  public function __isset($name) {
    return isset($this->hookFieldsFlip[$name])
      && isset($this->hookValues[$this->hookFieldsFlip[$name]]);
  }

  /**
   * @inheritDoc
   */
  public function __unset($name) {
    if (isset($this->hookFieldsFlip[$name])) {
      // Unset while preserving order.
      $this->hookValues[$this->hookFieldsFlip[$name]] = NULL;
    }
  }

  /**
   * Determine whether the hook supports the given field.
   *
   * The field may or may not be empty. Use isset() or empty() to
   * check that.
   *
   * @param string $name
   * @return bool
   */
  public function hasField($name) {
    return isset($this->hookFieldsFlip[$name]);
  }

}
