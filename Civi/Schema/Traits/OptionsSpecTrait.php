<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Schema\Traits;

/**
 * Describe what values are allowed to be stored in this field.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
trait OptionsSpecTrait {

  /**
   * @var array|bool
   */
  public $options;

  /**
   * @var array|null
   */
  public $suffixes;

  /**
   * @var callable
   */
  private $optionsCallback;

  /**
   * @param array $values
   * @param array|bool $return
   * @param bool $checkPermissions
   *
   * @return array
   */
  public function getOptions($values = [], $return = TRUE, $checkPermissions = TRUE) {
    if (!isset($this->options)) {
      if ($this->optionsCallback) {
        $this->options = ($this->optionsCallback)($this, $values, $return, $checkPermissions);
      }
      else {
        $this->options = FALSE;
      }
    }
    return $this->options;
  }

  /**
   * @param array|bool $options
   *
   * @return $this
   */
  public function setOptions($options) {
    $this->options = $options;
    return $this;
  }

  /**
   * @param array $suffixes
   *
   * @return $this
   */
  public function setSuffixes($suffixes) {
    $this->suffixes = $suffixes;
    return $this;
  }

  /**
   * @param callable $callback
   *
   * @return $this
   */
  public function setOptionsCallback($callback) {
    $this->optionsCallback = $callback;
    return $this;
  }

}
