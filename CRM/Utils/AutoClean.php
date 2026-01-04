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
   * Have we run this cleanup method yet?
   *
   * @var bool
   */
  protected $isDone = FALSE;

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
   * Temporarily override the values for system settings.
   *
   * Note: This was written for use with unit-tests. Give a hard think before using it at runtime.
   *
   * @param array $newSettings
   *   List of new settings (key-value pairs).
   * @return \CRM_Utils_AutoClean
   */
  public static function swapSettings(array $newSettings): CRM_Utils_AutoClean {
    // Overwrite the `civicrm_setting` and (later on) rewrite the original values to `civicrm_setting`.
    // This process could be simpler if SettingsBag::$mandatory supported multiple layers of overrides.
    $settings = \Civi::settings();

    // Backup the old settings
    $oldExplicitSettings = [];
    foreach ($newSettings as $name => $newSetting) {
      if ($settings->hasExplicit($name)) {
        $oldExplicitSettings[$name] = $settings->getExplicit($name);
      }
      if ($settings->getMandatory($name) !== NULL) {
        throw new \CRM_Core_Exception("Cannot override mandatory setting ($name)");
      }
    }

    // Apply the new settings
    $settings->add($newSettings);

    // Auto-restore the original settings
    return CRM_Utils_AutoClean::with(function() use ($newSettings, $oldExplicitSettings) {
      $settings = \Civi::settings();
      // Restoring may mean `revert()` or `add()` (depending on the original disposition of the setting).
      foreach ($newSettings as $name => $newSetting) {
        if (!array_key_exists($name, $oldExplicitSettings)) {
          \Civi::settings()->revert($name);
        }
      }
      $settings->add($oldExplicitSettings);
    });
  }

  public static function swapMaxExecutionTime(int $newTime): CRM_Utils_AutoClean {
    $originalTimeLimit = CRM_Core_DAO::setMaxExecutionTime($newTime);
    $ac = new CRM_Utils_AutoClean();
    $ac->args = [$originalTimeLimit];
    $ac->callback = ['CRM_Core_DAO', 'setMaxExecutionTime'];
    CRM_Core_DAO::setMaxExecutionTime($newTime);
    return $ac;
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
    $this->cleanup();
  }

  /**
   * Explicitly apply the cleanup.
   *
   * Use this if you want to do the cleanup work immediately.
   *
   * @return void
   */
  public function cleanup(): void {
    if ($this->isDone) {
      return;
    }
    $this->isDone = TRUE;
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
