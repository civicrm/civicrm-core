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
 * A resource collection is a mix of *resources* (or *snippets* or *assets*) that can be
 * added to a page. A fully-formed resource might look like this:
 *
 * ```
 * array(
 *   'name' => 'jQuery',
 *   'region' => 'html-header',
 *   'weight' => 100,
 *   'type' => 'scriptUrl',
 *   'scriptUrl' => 'https://example.com/js/jquery.min.js'
 * )
 * ```
 *
 * Typically, a resource is created with just one option, eg
 *
 * ```
 * // Add resources in array notation
 * $c->add(['script' => 'alert("Hello");']);
 * $c->add(['scriptFile' => ['civicrm', 'js/crm.ckeditor.js']]);
 * $c->add(['scriptUrl' => 'https://example.com/js/jquery.min.js']);
 * $c->add(['style' => 'p { font-size: 4em; }']);
 * $c->add(['styleFile' => ['civicrm', 'css/dashboard.css']]);
 * $c->add(['styleUrl' => 'https://example.com/css/foobar.css']);
 *
 * // Add resources with helper methods
 * $c->addScript('alert("Hello");');
 * $c->addScriptFile('civicrm', 'js/crm.ckeditor.js');
 * $c->addScriptUrl('https://example.com/js/jquery.min.js');
 * $c->addStyle('p { font-size: 4em; }');
 * $c->addStyleFile('civicrm', 'css/dashboard.css');
 * $c->addStyleUrl('https://example.com/css/foobar.css');
 * ```
 *
 * The other properties are automatically computed (dependent upon context),
 * but they may be set explicitly. These options include:
 *
 *   - type: string (markup, template, callback, script, scriptFile, scriptUrl, jquery, style, styleFile, styleUrl)
 *   - name: string, symbolic identifier for this resource
 *   - aliases: string[], list of alternative names for this resource
 *   - weight: int, default=1. Lower weights come before higher weights.
 *     (If two resources have the same weight, then a secondary ordering will be
 *     used to ensure reproducibility. However, the secondary ordering is
 *     not guaranteed among versions/implementations.)
 *   - disabled: int, default=0
 *   - region: string
 *   - esm: bool, enable ECMAScript Module (ESM) support for "script","scriptFile","scriptUrl"
 *   - translate: bool|string, Autoload translations. (Only applies to 'scriptFile')
 *       - FALSE: Do not load translated strings.
 *       - TRUE: Load translated strings. Use the $ext's default domain.
 *       - string: Load translated strings. Use a specific domain.
 *
 * For example, the following are equivalent ways to set the 'weight' option:
 *
 * ```php
 * $c->add([
 *   'script' => 'alert("Hello");',
 *   'weight' => 100,
 * ]);
 * $c->addScript('alert("Hello");', ['weight' => 100]);
 * ```
 *
 * Passing options in array (key-value) notation is clearest. For backward
 * compatibility, some methods (eg `addScript()`) accept options in positional form.
 * Where applicable, the docblock of each `addFoo()` will include a comment about positional form.
 *
 * @see CRM_Core_Resources_CollectionTrait
 */
interface CRM_Core_Resources_CollectionInterface {

  /**
   * Add an item to the collection. For example, when working with 'page-header' collection:
   *
   * Note: This function does not perform any extra encoding of markup, script code, or etc. If
   * you're passing in user-data, you must clean it yourself.
   *
   * @param array $snippet
   *   The resource to add. For a full list of properties, see CRM_Core_Resources_CollectionInterface.
   * @return array
   *   The full/computed snippet (with defaults applied).
   * @see CRM_Core_Resources_CollectionInterface
   */
  public function add($snippet);

  /**
   * Update specific properties of a snippet.
   *
   * Ex: $region->update('default', ['disabled' => TRUE]);
   *
   * @param string $name
   *   Symbolic of the resource/snippet to update.
   * @param array $snippet
   *   Resource options. See CollectionInterface docs.
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
