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
   * @param string $code
   *   JavaScript source code.
   * @param array $options
   *   Open-ended list of options (per add())
   *   Ex: ['weight' => 123]
   * @return static
   */
  public function addScript(string $code, ...$options);

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
   *     - bool|string $translate: Whether to load translated strings for this file. Use one of:
   *     - FALSE: Do not load translated strings.
   *     - TRUE: Load translated strings. Use the $ext's default domain.
   *     - string: Load translated strings. Use a specific domain.
   *
   * @return static
   *
   * @throws \CRM_Core_Exception
   */
  public function addScriptFile(string $ext, string $file, ...$options);

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
   * @param string $code
   *   CSS source code.
   * @param array $options
   *   Open-ended list of options (per add())
   *   Ex: ['weight' => 123]
   * @return static
   */
  public function addStyle(string $code, ...$options);

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
  public function addStyleFile(string $ext, string $file, ...$options);

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param string $url
   * @param array $options
   *   Open-ended list of options (per add())
   *   Ex: ['weight' => 123]
   * @return static
   */
  public function addStyleUrl(string $url, ...$options);

  /**
   * Add JavaScript variables to CRM.vars
   *
   * Example:
   * From the server:
   * CRM_Core_Resources::singleton()->addVars('myNamespace', array('foo' => 'bar'));
   * Access var from javascript:
   * CRM.vars.myNamespace.foo // "bar"
   *
   * @see https://docs.civicrm.org/dev/en/latest/standards/javascript/
   *
   * @param string $nameSpace
   *   Usually the name of your extension.
   * @param array $vars
   *   Data to export.
   * @param array $options
   *   Extra processing instructions on where/how to place the data.
   * @return static
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
   *   Extra processing instructions on where/how to place the data.
   * @return static
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
