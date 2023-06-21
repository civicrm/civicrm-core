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
 * This trait is a building-block for creating classes which maintain a list of
 * resources. It defines a set of helper functions which provide syntactic sugar
 * for calling the add() method. It implements most of the `CollectionAdderInterface`.
 *
 * @see CRM_Core_Resources_CollectionAdderInterface
 */
trait CRM_Core_Resources_CollectionAdderTrait {

  /**
   * Add an item to the collection.
   *
   * @param array $snippet
   * @return array
   *   The full/computed snippet (with defaults applied).
   * @see CRM_Core_Resources_CollectionInterface::add()
   * @see CRM_Core_Resources_CollectionTrait::add()
   */
  abstract public function add($snippet);

  /**
   * Locate the 'settings' snippet.
   *
   * @param array $options
   * @return array
   * @see CRM_Core_Resources_CollectionTrait::findCreateSettingSnippet()
   */
  abstract public function &findCreateSettingSnippet($options = []): array;

  /**
   * Add an HTML blob.
   *
   * Ex: addMarkup('<p>Hello world!</p>', ['weight' => 123]);
   *
   * @param string $markup
   *   HTML code.
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addMarkup(string $code, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addMarkup()
   */
  public function addMarkup(string $markup, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'markup' => $markup,
    ]));
    return $this;
  }

  /**
   * Add an ECMAScript module (ESM) to the current page (<SCRIPT TYPE=MODULE>).
   *
   * Ex: addModule('alert("Hello world");', ['weight' => 123]);
   *
   * @param string $code
   *   JavaScript source code.
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addModule(string $code, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addModule()
   */
  public function addModule(string $code, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'esm' => TRUE,
      'script' => $code,
    ]));
    return $this;
  }

  /**
   * Add an ECMAScript Module (ESM) from file to the current page (<SCRIPT TYPE=MODULE SRC=...>).
   *
   * Ex: addModuleFile('myextension', 'myscript.js', ['weight' => 123]);
   *
   * @param string $ext
   *   Extension name; use 'civicrm' for core.
   * @param string $file
   *   File path -- relative to the extension base dir.
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addModuleFile(string $code, int $weight, string $region, mixed $translate).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addModuleFile()
   */
  public function addModuleFile(string $ext, string $file, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'esm' => TRUE,
      'scriptFile' => [$ext, $file],
      'name' => "$ext:$file",
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptFile() produce slightly different orderings.
    ]));
    return $this;
  }

  /**
   * Add an ECMAScript Module (ESM) by URL to the current page (<SCRIPT TYPE=MODULE SRC=...>).
   *
   * Ex: addModuleUrl('http://example.com/foo.js', ['weight' => 123])
   *
   * @param string $url
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addModuleUrl(string $url, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addModuleUrl()
   */
  public function addModuleUrl(string $url, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'esm' => TRUE,
      'scriptUrl' => $url,
      'name' => $url,
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptUrl() produce slightly different orderings.
    ]));
    return $this;
  }

  /**
   * Export permission data to the client to enable smarter GUIs.
   *
   * @param string|iterable $permNames
   *   List of permission names to check/export.
   * @return static
   * @see CRM_Core_Resources_CollectionAdderInterface::addPermissions()
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
   * Ex: addScript('alert("Hello world");', ['weight' => 123]);
   *
   * @param string $code
   *   JavaScript source code.
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addScript(string $code, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addScript()
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
   * Ex: addScriptFile('myextension', 'myscript.js', ['weight' => 123]);
   *
   * @param string $ext
   *   Extension name; use 'civicrm' for core.
   * @param string $file
   *   File path -- relative to the extension base dir.
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addScriptFile(string $code, int $weight, string $region, mixed $translate).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addScriptFile()
   */
  public function addScriptFile(string $ext, string $file, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'scriptFile' => [$ext, $file],
      'name' => "$ext:$file",
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptFile() produce slightly different orderings.
    ]));
    return $this;
  }

  /**
   * Add a JavaScript URL to the current page using <SCRIPT SRC>.
   *
   * Ex: addScriptUrl('http://example.com/foo.js', ['weight' => 123])
   *
   * @param string $url
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addScriptUrl(string $url, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addScriptUrl()
   */
  public function addScriptUrl(string $url, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'scriptUrl' => $url,
      'name' => $url,
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptUrl() produce slightly different orderings.
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
   * @see CRM_Core_Resources_CollectionAdderInterface::addString()
   */
  public function addString($text, $domain = 'civicrm') {
    // TODO: Maybe this should be its own resource type to allow smarter management?

    foreach ((array) $text as $str) {
      $translated = _ts($str, [
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
   * Ex: addStyle('p { color: red; }', ['weight' => 100]);
   *
   * @param string $code
   *   CSS source code.
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addStyle(string $code, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addStyle()
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
   * Ex: addStyleFile('myextension', 'mystyles.css', ['weight' => 100]);
   *
   * @param string $ext
   *   Extension name; use 'civicrm' for core.
   * @param string $file
   *   File path -- relative to the extension base dir.
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addStyle(string $code, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addStyleFile()
   */
  public function addStyleFile(string $ext, string $file, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'styleFile' => [$ext, $file],
      'name' => "$ext:$file",
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptUrl() produce slightly different orderings.
    ]));
    return $this;
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * Ex: addStyleUrl('http://example.com/foo.css', ['weight' => 100]);
   *
   * @param string $url
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addStyleUrl(string $code, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addStyleUrl()
   */
  public function addStyleUrl(string $url, ...$options) {
    $this->add(self::mergeStandardOptions($options, [
      'styleUrl' => $url,
      'name' => $url,
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptUrl() produce slightly different orderings.
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
   *   Not used.
   *   Positional equivalence: addSetting(array $settings, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addSetting()
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
   * @see CRM_Core_Resources_CollectionAdderInterface::addSettingsFactory()
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
   *   Data to export.
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addVars(string $namespace, array $vars, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionAdderInterface::addVars()
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
