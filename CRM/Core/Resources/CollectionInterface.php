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
 * Class CRM_Core_Resources_CollectionInterface
 *
 * This is a building-block for creating classes which maintain a list of resources.
 */
interface CRM_Core_Resources_CollectionInterface {

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
   *   - type: string (auto-detected for markup, template, callback, script, scriptFile, scriptUrl, jquery, style, styleFile, styleUrl)
   *   - name: string, optional
   *   - weight: int, optional; default=1
   *   - disabled: int, optional; default=0
   *   - markup: string, HTML; required (for type==markup)
   *   - template: string, path; required (for type==template)
   *   - callback: mixed; required (for type==callback)
   *   - arguments: array, optional (for type==callback)
   *   - script: string, Javascript code
   *   - scriptFile: array, the name of the extension and file. Ex: ['civicrm', 'js/foo.js']
   *   - scriptUrl: string, URL of a Javascript file
   *   - jquery: string, Javascript code which runs inside a jQuery(function($){...}); block
   *   - settings: array, list of static values to convey.
   *   - style: string, CSS code
   *   - styleFile: array, the name of the extension and file. Ex: ['civicrm', 'js/foo.js']
   *   - styleUrl: string, URL of a CSS file
   *
   * @return array
   *   The full/computed snippet (with defaults applied).
   */
  public function add($snippet);

  /**
   * Update specific properties of a snippet.
   *
   * Ex: $region->update('default', ['disabled' => TRUE]);
   *
   * @param string $name
   * @param $snippet
   * @return static
   */
  public function update($name, $snippet);

  /**
   * Remove all snippets.
   *
   * @return static
   */
  public function clear();

  /**
   * Get snippet.
   *
   * @param string $name
   * @return array|NULL
   */
  public function &get($name);

  /**
   * Get a list of all snippets in this collection.
   *
   * @return iterable
   */
  public function getAll(): iterable;

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
  public function filter($callback);

  /**
   * Find all snippets which match the given criterion.
   *
   * @param callable $callback
   *   The callback is invoked once for each member in the collection.
   *   The callback may return one of two values:
   *   - TRUE: The item is OK and belongs in the collection.
   *   - FALSE: The item is not OK and should be omitted from the collection.
   * @return iterable
   *   List of matching snippets.
   */
  public function find($callback): iterable;

  /**
   * Assimilate a list of resources into this list.
   *
   * @param iterable $others
   *   List of snippets to add.
   * @return static
   * @see CRM_Core_Resources_CollectionInterface::merge()
   */
  public function merge(iterable $others);

}
