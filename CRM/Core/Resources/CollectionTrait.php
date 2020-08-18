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
trait CRM_Core_Resources_CollectionTrait {

  /**
   * Static defaults - a list of options to apply to any new snippets.
   *
   * @var array
   */
  protected $defaults = ['weight' => 1, 'disabled' => FALSE];

  /**
   * List of snippets to inject within region.
   *
   * e.g. $this->_snippets[3]['type'] = 'template';
   *
   * @var array
   */
  protected $snippets = [];

  /**
   * Whether the snippets array has been sorted
   *
   * @var bool
   */
  protected $isSorted = TRUE;

  /**
   * Whitelist of supported types.
   *
   * @var array
   */
  protected $types = [];

  /**
   * Add an item to the collection. For example, when working with 'page-header' collection:
   *
   * ```
   * CRM_Core_Region::instance('page-header')->add(array(
   *   'markup' => '<div style="color:red">Hello!</div>',
   * ));
   * CRM_Core_Region::instance('page-header')->add(array(
   *   'script' => 'alert("Hello");',
   * ));
   * CRM_Core_Region::instance('page-header')->add(array(
   *   'template' => 'CRM/Myextension/Extra.tpl',
   * ));
   * CRM_Core_Region::instance('page-header')->add(array(
   *   'callback' => 'myextension_callback_function',
   * ));
   * ```
   *
   * Note: This function does not perform any extra encoding of markup, script code, or etc. If
   * you're passing in user-data, you must clean it yourself.
   *
   * @param array $snippet
   *   Array; keys:.
   *   - type: string (auto-detected for markup, template, callback, script, scriptUrl, jquery, style, styleUrl)
   *   - name: string, optional
   *   - weight: int, optional; default=1
   *   - disabled: int, optional; default=0
   *   - markup: string, HTML; required (for type==markup)
   *   - template: string, path; required (for type==template)
   *   - callback: mixed; required (for type==callback)
   *   - arguments: array, optional (for type==callback)
   *   - script: string, Javascript code
   *   - scriptUrl: string, URL of a Javascript file
   *   - jquery: string, Javascript code which runs inside a jQuery(function($){...}); block
   *   - settings: array, list of static values to convey.
   *   - style: string, CSS code
   *   - styleUrl: string, URL of a CSS file
   *
   * @return array
   *   The full/computed snippet (with defaults applied).
   */
  public function add($snippet) {
    $snippet = array_merge($this->defaults, $snippet);
    if (!isset($snippet['type'])) {
      foreach ($this->types as $type) {
        // auto-detect
        if (isset($snippet[$type])) {
          $snippet['type'] = $type;
          break;
        }
      }
    }
    if (!in_array($snippet['type'] ?? NULL, $this->types)) {
      $typeExpr = $snippet['type'] ?? '(' . implode(',', array_keys($snippet)) . ')';
      throw new \RuntimeException("Unsupported snippet type: $typeExpr");
    }
    // Traditional behavior: sort by (1) weight and (2) either name or natural position. This second thing is called 'sortId'.
    if (isset($snippet['name'])) {
      $snippet['sortId'] = $snippet['name'];
    }
    else {
      switch ($snippet['type']) {
        case 'scriptUrl':
        case 'styleUrl':
          $snippet['sortId'] = $this->nextId();
          $snippet['name'] = $snippet[$snippet['type']];
          break;

        case 'scriptFile':
        case 'styleFile':
          $snippet['sortId'] = $this->nextId();
          $snippet['name'] = implode(':', $snippet[$snippet['type']]);
          break;

        default:
          $snippet['sortId'] = $this->nextId();
          $snippet['name'] = $snippet['sortId'];
          break;
      }
    }

    if ($snippet['type'] === 'scriptFile' && !isset($snippet['scriptFileUrls'])) {
      $res = Civi::resources();
      list ($ext, $file) = $snippet['scriptFile'];

      $snippet['translate'] = $snippet['translate'] ?? TRUE;
      if ($snippet['translate']) {
        $domain = ($snippet['translate'] === TRUE) ? $ext : $snippet['translate'];
        // Is this too early?
        $this->addString(Civi::service('resources.js_strings')->get($domain, $res->getPath($ext, $file), 'text/javascript'), $domain);
      }
      $snippet['scriptFileUrls'] = [$res->getUrl($ext, $res->filterMinify($ext, $file), TRUE)];
    }

    if ($snippet['type'] === 'styleFile' && !isset($snippet['styleFileUrls'])) {
      /** @var Civi\Core\Themes $theme */
      $theme = Civi::service('themes');
      list ($ext, $file) = $snippet['styleFile'];
      $snippet['styleFileUrls'] = $theme->resolveUrls($theme->getActiveThemeKey(), $ext, $file);
    }

    $this->snippets[$snippet['name']] = $snippet;
    $this->isSorted = FALSE;
    return $snippet;
  }

  protected function nextId() {
    if (!isset(Civi::$statics['CRM_Core_Resource_Count'])) {
      $resId = Civi::$statics['CRM_Core_Resource_Count'] = 1;
    }
    else {
      $resId = ++Civi::$statics['CRM_Core_Resource_Count'];
    }

    return $resId;
  }

  /**
   * @param string $name
   * @param $snippet
   */
  public function update($name, $snippet) {
    $this->snippets[$name] = array_merge($this->snippets[$name], $snippet);
    $this->isSorted = FALSE;
  }

  /**
   * Remove all snippets.
   *
   * @return static
   */
  public function clear() {
    $this->snippets = [];
    $this->isSorted = TRUE;
    return $this;
  }

  /**
   * Get snippet.
   *
   * @param string $name
   * @return array|NULL
   */
  public function &get($name) {
    return $this->snippets[$name];
  }

  /**
   * Get a list of all snippets in this collection.
   *
   * @return iterable
   */
  public function getAll(): iterable {
    $this->sort();
    return $this->snippets;
  }

  /**
   * Alter the contents of the collection.
   *
   * @param callable $callback
   *   The callback is invoked once for each member in the collection.
   *   The callback may return one of three values:
   *   - TRUE: The item is OK and belongs in the collection.
   *   - FALSE: The item is not OK and should be omitted from the collection.
   *   - Array: The item should be revised (using the returned value).
   * @return static
   */
  public function filter($callback) {
    $this->sort();
    $names = array_keys($this->snippets);
    foreach ($names as $name) {
      $ret = $callback($this->snippets[$name]);
      if ($ret === TRUE) {
        // OK
      }
      elseif ($ret === FALSE) {
        unset($this->snippets[$name]);
      }
      elseif (is_array($ret)) {
        $this->snippets[$name] = $ret;
        $this->isSorted = FALSE;
      }
      else {
        throw new \RuntimeException("CollectionTrait::filter() - Callback returned invalid value");
      }
    }
    return $this;
  }

  /**
   * Find all snippets which match the given criterion.
   *
   * @param callable $callback
   * @return iterable
   *   List of matching snippets.
   */
  public function find($callback): iterable {
    $r = [];
    $this->sort();
    foreach ($this->snippets as $name => $snippet) {
      if ($callback($snippet)) {
        $r[$name] = $snippet;
      }
    }
    return $r;
  }

  /**
   * Assimilate a list of resources into this list.
   *
   * @param iterable $snippets
   *   List of snippets to add.
   * @return static
   * @see CRM_Core_Resources_CollectionInterface::merge()
   */
  public function merge(iterable $snippets) {
    foreach ($snippets as $next) {
      $name = $next['name'];
      $current = $this->snippets[$name] ?? NULL;
      if ($current === NULL) {
        $this->add($next);
      }
      elseif ($current['type'] === 'settings' && $next['type'] === 'settings') {
        $this->addSetting($next['settings']);
        foreach ($next['settingsFactories'] as $factory) {
          $this->addSettingsFactory($factory);
        }
        $this->isSorted = FALSE;
      }
      elseif ($current['type'] === 'settings' || $next['type'] === 'settings') {
        throw new \RuntimeException(sprintf("Cannot merge snippets of types [%s] and [%s]" . $current['type'], $next['type']));
      }
      else {
        $this->add($next);
      }
    }
    return $this;
  }

  /**
   * Ensure that the collection is sorted.
   *
   * @return static
   */
  protected function sort() {
    if (!$this->isSorted) {
      uasort($this->snippets, [__CLASS__, '_cmpSnippet']);
      $this->isSorted = TRUE;
    }
    return $this;
  }

  /**
   * @param $a
   * @param $b
   *
   * @return int
   */
  public static function _cmpSnippet($a, $b) {
    if ($a['weight'] < $b['weight']) {
      return -1;
    }
    if ($a['weight'] > $b['weight']) {
      return 1;
    }
    // fallback to name sort; don't really want to do this, but it makes results more stable
    if ($a['sortId'] < $b['sortId']) {
      return -1;
    }
    if ($a['sortId'] > $b['sortId']) {
      return 1;
    }
    return 0;
  }

  // -----------------------------------------------

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
  public function addScript(string $code, array $options = []) {
    $this->add($options + ['script' => $code]);
    return $this;
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
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
  public function addScriptFile(string $ext, string $file, array $options = []) {
    $this->add($options + ['scriptFile' => [$ext, $file]]);
    return $this;
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $url
   * @param array $options
   *   Open-ended list of options (per add())
   *   Ex: ['weight' => 123]
   * @return static
   */
  public function addScriptUrl(string $url, array $options = []) {
    $this->add($options + ['scriptUrl' => $url]);
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
  public function addStyle(string $code, array $options = []) {
    $this->add($options + ['style' => $code]);
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
  public function addStyleFile(string $ext, string $file, array $options = []) {
    $this->add($options + ['styleFile' => [$ext, $file]]);
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
  public function addStyleUrl(string $url, array $options = []) {
    $this->add($options + ['styleUrl' => $url]);
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
   * @return static
   */
  public function addVars(string $nameSpace, array $vars) {
    $s = &$this->findCreateSettingSnippet();
    $s['settings']['vars'][$nameSpace] = $this->mergeSettings(
      $s['settings']['vars'][$nameSpace] ?? [],
      $vars
    );
    return $this;
  }

  /**
   * Add JavaScript variables to the root of the CRM object.
   * This function is usually reserved for low-level system use.
   * Extensions and components should generally use addVars instead.
   *
   * @param array $settings
   * @return static
   */
  public function addSetting(array $settings) {
    $s = &$this->findCreateSettingSnippet();
    $s['settings'] = $this->mergeSettings($s['settings'], $settings);
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
   * Get a fully-formed/altered list of settings, including the results of
   * any callbacks/listeners.
   *
   * @return array
   */
  public function getSettings(): array {
    $s = &$this->findCreateSettingSnippet();
    $result = $s['settings'];
    foreach ($s['settingsFactories'] as $callable) {
      $result = $this->mergeSettings($result, $callable());
    }
    CRM_Utils_Hook::alterResourceSettings($result);
    return $result;
  }

  /**
   * @param array $settings
   * @param array $additions
   * @return array
   *   combination of $settings and $additions
   */
  private function mergeSettings(array $settings, array $additions): array {
    foreach ($additions as $k => $v) {
      if (isset($settings[$k]) && is_array($settings[$k]) && is_array($v)) {
        $v += $settings[$k];
      }
      $settings[$k] = $v;
    }
    return $settings;
  }

  /**
   * @return array
   */
  private function &findCreateSettingSnippet(): array {
    $snippet = &$this->get('settings');
    if ($snippet !== NULL) {
      return $snippet;
    }

    $this->add([
      'name' => 'settings',
      'type' => 'settings',
      'settings' => [],
      'settingsFactories' => [],
      'weight' => -100000,
    ]);
    return $this->get('settings');
  }

}
