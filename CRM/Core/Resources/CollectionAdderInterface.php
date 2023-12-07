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
 * The collection-adder interface provides write-only support for a collection.
 *
 * @see CRM_Core_Resources_CollectionAdderTrait
 */
interface CRM_Core_Resources_CollectionAdderInterface {

  /**
   * Add an item to the collection.
   *
   * @param array $snippet
   * @return array
   *   The full/computed snippet (with defaults applied).
   * @see CRM_Core_Resources_CollectionInterface::add()
   */
  public function add($snippet);

  // TODO public function addBundle($bundle);

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
   * @see CRM_Core_Resources_CollectionAdderInterface::addScript()
   */
  public function addMarkup(string $markup, ...$options);

  /**
   * Add an ECMAScript Module (ESM) to the current page (<SCRIPT TYPE=MODULE>).
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
   */
  public function addModule(string $code, ...$options);

  /**
   * Add an ECMAScript Module (ESM) from file to the current page (<SCRIPT TYPE=MODULE SRC=...>).
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
   */
  public function addModuleFile(string $ext, string $file, ...$options);

  /**
   * Add an ECMAScript Module (ESM) by URL to the current page (<SCRIPT TYPE=MODULE SRC=...>).
   *
   * Ex: addScriptUrl('http://example.com/foo.js', ['weight' => 123])
   *
   * @param string $url
   * @param array $options
   *   Open-ended list of key-value options. See CollectionInterface docs.
   *   Positional equivalence: addScriptUrl(string $url, int $weight, string $region).
   * @return static
   * @see CRM_Core_Resources_CollectionInterface
   */
  public function addModuleUrl(string $url, ...$options);

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
  public function addPermissions($permNames);

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
   */
  public function addScript(string $code, ...$options);

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
   */
  public function addScriptFile(string $ext, string $file, ...$options);

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
   */
  public function addScriptUrl(string $url, ...$options);

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
  public function addString($text, $domain = 'civicrm');

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
   */
  public function addStyle(string $code, ...$options);

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
   */
  public function addStyleFile(string $ext, string $file, ...$options);

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
   */
  public function addStyleUrl(string $url, ...$options);

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
   */
  public function addVars(string $nameSpace, array $vars, ...$options);

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
  public function addSetting(array $settings, ...$options);

  /**
   * Add JavaScript variables to the global CRM object via a callback function.
   *
   * @param callable $callable
   * @return static
   */
  public function addSettingsFactory($callable);

}
