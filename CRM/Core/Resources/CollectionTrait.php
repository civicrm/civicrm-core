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

use Civi\Core\Event\GenericHookEvent;

/**
 * Class CRM_Core_Resources_CollectionTrait
 *
 * This is a building-block for creating classes which maintain a list of resources.
 * It implements of the `CollectionInterface`.
 *
 * @see CRM_Core_Resources_CollectionInterface
 */
trait CRM_Core_Resources_CollectionTrait {

  use CRM_Core_Resources_CollectionAdderTrait;

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
   * Add an item to the collection.
   *
   * @param array $snippet
   *   Resource options. See CollectionInterface docs.
   * @return array
   *   The full/computed snippet (with defaults applied).
   * @see CRM_Core_Resources_CollectionInterface
   * @see CRM_Core_Resources_CollectionInterface::add()
   */
  public function add($snippet) {
    $snippet = array_merge($this->defaults, $snippet);
    $snippet['id'] = $this->nextId();
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
          $snippet['sortId'] = $snippet['id'];
          $snippet['name'] = $snippet[$snippet['type']];
          break;

        case 'scriptFile':
        case 'styleFile':
          $snippet['sortId'] = $snippet['id'];
          $snippet['name'] = implode(':', $snippet[$snippet['type']]);
          break;

        default:
          $snippet['sortId'] = $snippet['id'];
          $snippet['name'] = $snippet['sortId'];
          break;
      }
    }
    if (!empty($snippet['esm'])) {
      Civi::dispatcher()->dispatch('civi.esm.useModule', GenericHookEvent::create(['snippet' => &$snippet]));
    }

    if ($snippet['type'] === 'scriptFile' && !isset($snippet['scriptFileUrls'])) {
      $res = Civi::resources();
      list ($ext, $file) = $snippet['scriptFile'];

      $snippet['translate'] ??= TRUE;
      if ($snippet['translate']) {
        $domain = ($snippet['translate'] === TRUE) ? $ext : $snippet['translate'];
        // Is this too early?
        $this->addString(Civi::service('resources.js_strings')->get($domain, $res->getPath($ext, $file), 'text/javascript'), $domain);
      }
      $snippet['scriptFileUrls'] = [$res->getUrl($ext, $res->filterMinify($ext, $file), TRUE)];
    }
    if ($snippet['type'] === 'scriptFile' && !isset($snippet['aliases'])) {
      $snippet['aliases'] = $snippet['scriptFileUrls'];
    }

    if ($snippet['type'] === 'styleFile' && !isset($snippet['styleFileUrls'])) {
      /** @var Civi\Core\Themes $theme */
      $theme = Civi::service('themes');
      list ($ext, $file) = $snippet['styleFile'];
      $snippet['styleFileUrls'] = $theme->resolveUrls($theme->getActiveThemeKey(), $ext, $file);
    }
    if ($snippet['type'] === 'styleFile' && !isset($snippet['aliases'])) {
      $snippet['aliases'] = $snippet['styleFileUrls'];
    }

    if (isset($snippet['aliases']) && !is_array($snippet['aliases'])) {
      $snippet['aliases'] = [$snippet['aliases']];
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
   * Update specific properties of a snippet.
   *
   * @param string $name
   *   Symbolic of the resource/snippet to update.
   * @param array $snippet
   *   Resource options. See CollectionInterface docs.
   * @return static
   * @see CRM_Core_Resources_CollectionInterface::update()
   */
  public function update($name, $snippet) {
    foreach ($this->resolveName($name) as $realName) {
      $this->snippets[$realName] = array_merge($this->snippets[$realName], $snippet);
      $this->isSorted = FALSE;
      return $this;
    }

    Civi::log()->warning('Failed to update resource by name ({name})', [
      'name' => $name,
    ]);
    return $this;
  }

  /**
   * Remove all snippets.
   *
   * @return static
   * @see CRM_Core_Resources_CollectionInterface::clear()
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
   * @see CRM_Core_Resources_CollectionInterface::get()
   */
  public function &get($name) {
    foreach ($this->resolveName($name) as $realName) {
      return $this->snippets[$realName];
    }

    $null = NULL;
    return $null;
  }

  /**
   * Get a list of all snippets in this collection.
   *
   * @return iterable
   * @see CRM_Core_Resources_CollectionInterface::getAll()
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
   * @see CRM_Core_Resources_CollectionInterface::filter()
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
   *   The callback is invoked once for each member in the collection.
   *   The callback may return one of three values:
   *   - TRUE: The item is OK and belongs in the collection.
   *   - FALSE: The item is not OK and should be omitted from the collection.
   * @return iterable
   *   List of matching snippets.
   * @see CRM_Core_Resources_CollectionInterface::find()
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
   * @param string $name
   *   Name or alias.
   * return array
   *   List of real names.
   */
  protected function resolveName($name) {
    if (isset($this->snippets[$name])) {
      return [$name];
    }
    foreach ($this->snippets as $snippetName => $snippet) {
      if (isset($snippet['aliases']) && in_array($name, $snippet['aliases'])) {
        return [$snippetName];
      }
    }
    return [];
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
   * Assimilate all the resources listed in a bundle.
   *
   * @param iterable|string|\CRM_Core_Resources_Bundle $bundle
   *   Either bundle object, or the symbolic name of a bundle.
   *   Note: For symbolic names, the bundle must be a container service ('bundle.FOO').
   * @return static
   */
  public function addBundle($bundle) {
    if (is_iterable($bundle)) {
      foreach ($bundle as $b) {
        $this->addBundle($b);
      }
      return $this;
    }
    if (is_string($bundle)) {
      $bundle = Civi::service('bundle.' . $bundle);
    }
    return $this->merge($bundle->getAll());
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
      $result = self::mergeSettings($result, $callable());
    }
    CRM_Utils_Hook::alterResourceSettings($result);
    return $result;
  }

  /**
   * @return array
   */
  public function &findCreateSettingSnippet($options = []): array {
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
