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
 * Class CRM_Core_Resources_CollectionTrait
 *
 * This is a building-block for creating classes which maintain a list of resources.
 *
 * The class is generally organized in two sections: First, we have core
 * bit that manages a list of '$snippets'. Second, we have a set of helper
 * functions which add some syntactic sugar for the snippets.
 */
trait CRM_Core_Resources_CollectionAdderTrait {

  /**
   * Add an item to the collection.
   *
   * @param array $snippet
   * @return array
   *   The full/computed snippet (with defaults applied).
   * @see CRM_Core_Resources_CollectionInterface::add()
   */
  abstract public function add($snippet);

  /**
   * Locate the 'settings' snippet.
   *
   * @param array $options
   * @return array
   */
  abstract protected function &findCreateSettingSnippet($options = []): array;

  /**
   * Export permission data to the client to enable smarter GUIs.
   *
   * Note: Application security stems from the server's enforcement
   * of the security logic (e.g. in the API permissions). There's no way
   * the client can use this info to make the app more secure; however,
   * it can produce a better-tuned (non-broken) UI.
   *
   * @param string|iterable $permNames
   *   List of permission names to check/export.
   * @return static
   */
  public function addPermissions($permNames) {
    // TODO: Maybe this should be its own resource type to allow smarter management?
    $permNames = is_scalar($permNames) ? [$permNames] : $permNames;

    $perms = [];
    foreach ($permNames as $permName) {
      $perms[$permName] = CRM_Core_Permission::check($permName);
    }
    return $this->addSetting([
      'permissions' => $perms,
    ]);
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $code
   *   JavaScript source code.
   * @param array $options
   *   Open-ended list of options (per add())
   *   Ex: ['weight' => 123]
   * @return static
   */
  public function addScript(string $code, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'script' => $code,
    ]));
    return $this;
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * Options may be use key-value format (preferred) or positional format (legacy).
   *
   * - addScriptFile('myext', 'my.js', ['weight' => 123, 'region' => 'page-footer'])
   * - addScriptFile('myext', 'my.js', 123, 'page-footer')
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string $file
   *   file path -- relative to the extension base dir.
   * @param array $options
   *   Open-ended list of options (per add()).
   *   Ex: ['weight' => 123]
   *   Accepts some additional options:
   *   - bool|string $translate: Whether to load translated strings for this file. Use one of:
   *     - FALSE: Do not load translated strings.
   *     - TRUE: Load translated strings. Use the $ext's default domain.
   *     - string: Load translated strings. Use a specific domain.
   *
   * @return static
   *
   * @throws \CRM_Core_Exception
   */
  public function addScriptFile(string $ext, string $file, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'scriptFile' => [$ext, $file],
    ]));
    return $this;
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * Options may be use key-value format (preferred) or positional format (legacy).
   *
   * - addScriptUrl('http://example.com/foo.js', ['weight' => 123, 'region' => 'page-footer'])
   * - addScriptUrl('http://example.com/foo.js', 123, 'page-footer')
   *
   * @param string $url
   * @param array $options
   *   Open-ended list of options (per add())
   * @return static
   */
  public function addScriptUrl(string $url, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'scriptUrl' => $url,
    ]));
    return $this;
  }

  /**
   * Add translated string to the js CRM object.
   * It can then be retrived from the client-side ts() function
   * Variable substitutions can happen from client-side
   *
   * Note: this function rarely needs to be called directly and is mostly for internal use.
   * See CRM_Core_Resources::addScriptFile which automatically adds translated strings from js files
   *
   * Simple example:
   * // From php:
   * CRM_Core_Resources::singleton()->addString('Hello');
   * // The string is now available to javascript code i.e.
   * ts('Hello');
   *
   * Example with client-side substitutions:
   * // From php:
   * CRM_Core_Resources::singleton()->addString('Your %1 has been %2');
   * // ts() in javascript works the same as in php, for example:
   * ts('Your %1 has been %2', {1: objectName, 2: actionTaken});
   *
   * NOTE: This function does not work with server-side substitutions
   * (as this might result in collisions and unwanted variable injections)
   * Instead, use code like:
   * CRM_Core_Resources::singleton()->addSetting(array('myNamespace' => array('myString' => ts('Your %1 has been %2', array(subs)))));
   * And from javascript access it at CRM.myNamespace.myString
   *
   * @param string|array $text
   * @param string|null $domain
   * @return static
   */
  public function addString($text, $domain = 'civicrm') {
    // TODO: Maybe this should be its own resource type to allow smarter management?

    foreach ((array) $text as $str) {
      $translated = ts($str, [
        'domain' => ($domain == 'civicrm') ? NULL : [$domain, NULL],
        'raw' => TRUE,
      ]);

      // We only need to push this string to client if the translation
      // is actually different from the original
      if ($translated != $str) {
        $bucket = $domain == 'civicrm' ? 'strings' : 'strings::' . $domain;
        $this->addSetting([
          $bucket => [$str => $translated],
        ]);
      }
    }
    return $this;
  }

  /**
   * Add a CSS content to the current page using <STYLE>.
   *
   * @param string $code
   *   CSS source code.
   * @param array $options
   *   Open-ended list of options (per add())
   *   Ex: ['weight' => 123]
   * @return static
   */
  public function addStyle(string $code, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'style' => $code,
    ]));
    return $this;
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string $file
   *   file path -- relative to the extension base dir.
   * @param array $options
   *   Open-ended list of options (per add())
   *   Ex: ['weight' => 123]
   * @return static
   */
  public function addStyleFile(string $ext, string $file, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'styleFile' => [$ext, $file],
    ]));
    return $this;
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param string $url
   * @param array $options
   *   Open-ended list of options (per add())
   *   Ex: ['weight' => 123]
   * @return static
   */
  public function addStyleUrl(string $url, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'styleUrl' => $url,
    ]));
    return $this;
  }

  /**
   * Add JavaScript variables to the root of the CRM object.
   * This function is usually reserved for low-level system use.
   * Extensions and components should generally use addVars instead.
   *
   * @param array $settings
   *   Data to export.
   * @param array $options
   *   Extra processing instructions on where/how to place the data.
   * @return static
   */
  public function addSetting(array $settings, ...$options) {
    $s = &$this->findCreateSettingSnippet($options);
    $s['settings'] = self::mergeSettings($s['settings'], $settings);
    return $this;
  }

  /**
   * Add JavaScript variables to the global CRM object via a callback function.
   *
   * @param callable $callable
   * @return static
   */
  public function addSettingsFactory($callable) {
    $s = &$this->findCreateSettingSnippet();
    $s['settingsFactories'][] = $callable;
    return $this;
  }

  /**
   * Add JavaScript variables to CRM.vars
   *
   * Example:
   *   From the server:
   *     CRM_Core_Resources::singleton()->addVars('myNamespace', array('foo' => 'bar'));
   *   Access var from javascript:
   *     CRM.vars.myNamespace.foo // "bar"
   *
   * @see https://docs.civicrm.org/dev/en/latest/standards/javascript/
   *
   * @param string $nameSpace
   *   Usually the name of your extension.
   * @param array $vars
   * @param array $options
   *   There are no supported options.
   * @return static
   */
  public function addVars(string $nameSpace, array $vars, ...$options) {
    $s = &$this->findCreateSettingSnippet($options);
    $s['settings']['vars'][$nameSpace] = self::mergeSettings(
      $s['settings']['vars'][$nameSpace] ?? [],
      $vars
    );
    return $this;
  }

  /**
   * Given the "$options" for "addScriptUrl()" (etal), normalize the contents
   * and potentially add more.
   *
   * @param array $splats
   *   A list of options, as represented by the splat mechanism ("...$options").
   *   This may appear in one of two ways:
   *   - New (String Index): as in `addFoo($foo, array $options)`
   *   - Old (Numeric Index): as in `addFoo($foo, int $weight = X, string $region = Y, bool $translate = X)`
   * @param array $defaults
   *   List of values to merge into $options.
   * @return array
   */
  public static function mergeStandardOptions(array $splats, array $defaults = []) {
    $count = count($splats);
    switch ($count) {
      case 0:
        // Common+simple case: No splat options. We can short-circuit.
        return $defaults;

      case 1:
        // Might be new format (key-value pairs) or old format
        $parsed = is_array($splats[0]) ? $splats[0] : ['weight' => $splats[0]];
        break;

      case 2:
        $parsed = ['weight' => $splats[0], 'region' => $splats[1]];
        break;

      case 3:
        $parsed = ['weight' => $splats[0], 'region' => $splats[1], 'translate' => $splats[2]];
        break;

      default:
        throw new \RuntimeException("Cannot resolve resource options. For clearest behavior, pass options in key-value format.");
    }

    return array_merge($defaults, $parsed);
  }

  /**
   * Given the "$options" for "addSetting()" (etal), normalize the contents
   * and potentially add more.
   *
   * @param array $splats
   *   A list of options, as represented by the splat mechanism ("...$options").
   *   This may appear in one of two ways:
   *   - New (String Index): as in `addFoo($foo, array $options)`
   *   - Old (Numeric Index): as in `addFoo($foo, int $weight = X, string $region = Y, bool $translate = X)`
   * @param array $defaults
   *   List of values to merge into $options.
   * @return array
   */
  public static function mergeSettingOptions(array $splats, array $defaults = []) {
    $count = count($splats);
    switch ($count) {
      case 0:
        // Common+simple case: No splat options. We can short-circuit.
        return $defaults;

      case 1:
        // Might be new format (key-value pairs) or old format
        $parsed = is_array($splats[0]) ? $splats[0] : ['region' => $splats[0]];
        break;

      default:
        throw new \RuntimeException("Cannot resolve resource options. For clearest behavior, pass options in key-value format.");
    }

    return array_merge($defaults, $parsed);
  }

  /**
   * @param array $settings
   * @param array $additions
   * @return array
   *   combination of $settings and $additions
   */
  public static function mergeSettings(array $settings, array $additions): array {
    foreach ($additions as $k => $v) {
      if (isset($settings[$k]) && is_array($settings[$k]) && is_array($v)) {
        $v += $settings[$k];
      }
      $settings[$k] = $v;
    }
    return $settings;
  }

}
