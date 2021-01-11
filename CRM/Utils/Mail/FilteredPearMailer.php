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

/**
 * The filtered-mailer is a utility to wrap an existing PEAR Mail class
 * and apply extra filters. It is primarily intended for resolving
 * quirks in the standard implementations.
 *
 * This wrapper acts a bit like a chameleon, passing-through properties
 * from the underlying object. Consequently, internal properties are
 * prefixed with `_` to avoid conflict.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Mail_FilteredPearMailer extends Mail {

  /**
   * @var string
   *   Ex: 'smtp' or 'sendmail'
   */
  protected $_driver;

  /**
   * @var array
   */
  protected $_params;

  /**
   * @var Mail
   */
  protected $_delegate;

  /**
   * @var callable[]
   */
  protected $_filters = [];

  /**
   * CRM_Utils_Mail_FilteredPearMailer constructor.
   * @param string $driver
   * @param array $params
   * @param Mail $mailer
   */
  public function __construct($driver, $params, $mailer) {
    $this->_driver = $driver;
    $this->_params = $params;
    $this->_delegate = $mailer;
  }

  public function __destruct() {
    try {
      unset($this->_delegate);
    }
    catch (Exception $e) {
      Civi::log()->error($e->getMessage());
    }
  }

  public function send($recipients, $headers, $body) {
    $filterArgs = [$this, &$recipients, &$headers, &$body];
    foreach ($this->_filters as $filter) {
      $result = call_user_func_array($filter, $filterArgs);
      if ($result !== NULL) {
        return $result;
      }
    }

    return $this->_delegate->send($recipients, $headers, $body);
  }

  /**
   * @param string $id
   *   Unique ID for this filter. Filters are sorted by ID.
   *   Suggestion: '{nnnn}_{name}', where '{nnnn}' is a number.
   *   Filters are sorted and executed in order.
   * @param callable $func
   *   function(FilteredPearMailer $mailer, mixed $recipients, array $headers, string $body).
   *   The return value should generally be null/void. However, if you wish to
   *   short-circuit execution of the filters, then return a concrete value.
   * @return static
   */
  public function addFilter($id, $func) {
    $this->_filters[$id] = $func;
    ksort($this->_filters);
    return $this;
  }

  /**
   * @return string
   *   Ex: 'smtp', 'sendmail', 'mail'.
   */
  public function getDriver() {
    return $this->_driver;
  }

  public function &__get($name) {
    return $this->_delegate->{$name};
  }

  public function __set($name, $value) {
    return $this->_delegate->{$name} = $value;
  }

  public function __isset($name) {
    return isset($this->_delegate->{$name});
  }

  public function __unset($name) {
    unset($this->_delegate->{$name});
  }

  public function disconnect() {
    if (is_callable([$this->_delegate, 'disconnect'])) {
      return $this->_delegate->disconnect();
    }
    return TRUE;
  }

}
