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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Utils_AutoClean
 *
 * Automatically cleanup state when the object handle is released.
 * This is useful for unordered cleanup when a function has many
 * different exit scenarios (eg multiple returns, exceptions).
 */
class CRM_Utils_AutoClean {
  protected $callback;
  protected $args;

  /**
   * Call a cleanup function when the current context shuts down.
   *
   * ```
   * function doStuff() {
   *   $ac = CRM_Utils_AutoClean::with(function(){
   *     MyCleanup::doIt();
   *   });
   *   ...
   * }
   * ```
   *
   * @param mixed $callback
   * @return CRM_Utils_AutoClean
   */
  public static function with($callback) {
    $ac = new CRM_Utils_AutoClean();
    $ac->args = func_get_args();
    $ac->callback = array_shift($ac->args);
    return $ac;
  }

  /**
   * Temporarily set the active locale. Cleanup locale when the autoclean handle disappears.
   *
   * @param string|null $newLocale
   *   Ex: 'fr_CA'
   * @return \CRM_Utils_AutoClean|null
   */
  public static function swapLocale(?string $newLocale) {
    $oldLocale = $GLOBALS['tsLocale'] ?? NULL;
    if ($oldLocale === $newLocale) {
      return NULL;
    }

    $i18n = \CRM_Core_I18n::singleton();
    $i18n->setLocale($newLocale);
    return static::with(function() use ($i18n, $oldLocale) {
      $i18n->setLocale($oldLocale);
    });
  }

  /**
   * Temporarily swap values using callback functions, and cleanup
   * when the current context shuts down.
   *
   * ```
   * function doStuff() {
   *   $ac = CRM_Utils_AutoClean::swap('My::get', 'My::set', 'tmpValue');
   *   ...
   * }
   * ```
   *
   * @param mixed $getter
   *   Function to lookup current value.
   * @param mixed $setter
   *   Function to set new value.
   * @param mixed $tmpValue
   *   The value to temporarily use.
   * @return CRM_Utils_AutoClean
   * @see \Civi\Core\Resolver
   */
  public static function swap($getter, $setter, $tmpValue) {
    $resolver = \Civi\Core\Resolver::singleton();

    $origValue = $resolver->call($getter, []);

    $ac = new CRM_Utils_AutoClean();
    $ac->callback = $setter;
    $ac->args = [$origValue];

    $resolver->call($setter, [$tmpValue]);

    return $ac;
  }

  public function __destruct() {
    \Civi\Core\Resolver::singleton()->call($this->callback, $this->args);
  }

  /**
   * Prohibit (de)serialization of CRM_Utils_AutoClean.
   *
   * The generic nature of AutoClean makes it a potential target for escalating
   * serialization vulnerabilities, and there's no good reason for serializing it.
   */
  public function __sleep() {
    throw new \RuntimeException("CRM_Utils_AutoClean is a runtime helper. It is not intended for serialization.");
  }

  /**
   * Prohibit (de)serialization of CRM_Utils_AutoClean.
   *
   * The generic nature of AutoClean makes it a potential target for escalating
   * serialization vulnerabilities, and there's no good reason for deserializing it.
   */
  public function __wakeup() {
    throw new \RuntimeException("CRM_Utils_AutoClean is a runtime helper. It is not intended for deserialization.");
  }

}
