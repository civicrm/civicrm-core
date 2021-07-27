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

namespace Civi\WorkflowMessage;

use Civi\Test\Invasive;

/**
 * @internal
 */
class Examples {

  /**
   * @var \CRM_Utils_Cache_Interface
   */
  private $cache;

  /**
   * @var string
   */
  private $cacheKey;

  private $heavyCache = [];

  /**
   * ExampleScanner constructor.
   * @param \CRM_Utils_Cache_Interface|NULL $cache
   */
  public function __construct(?\CRM_Utils_Cache_Interface $cache = NULL) {
    $this->cache = $cache ?: \Civi::cache('short' /* long */);
    $this->cacheKey = \CRM_Utils_String::munge(__CLASS__);
  }

  /**
   * Get a list of all examples, including basic metadata (name, title, workflow).
   *
   * @return array
   *   Ex: ['my_example' => ['title' => ..., 'workflow' => ..., 'tags' => ...]]
   * @throws \ReflectionException
   */
  public function findAll(): array {
    $all = $this->cache->get($this->cacheKey);
    if ($all === NULL) {
      $all = [];
      $wfClasses = Invasive::call([WorkflowMessage::class, 'getWorkflowNameClassMap']);
      foreach ($wfClasses as $workflow => $class) {
        try {
          $classFile = (new \ReflectionClass($class))->getFileName();
        }
        catch (\ReflectionException $e) {
          throw new \RuntimeException("Failed to locate workflow class ($class)", 0, $e);
        }
        $classDir = preg_replace('/\.php$/', '', $classFile);
        if (is_dir($classDir)) {
          $all = array_merge($all, $this->scanDir($classDir, $workflow));
        }
      }
    }
    return $all;
  }

  /**
   * @param string $dir
   * @param string $workflow
   * @return array
   *   Ex: ['my_example' => ['title' => ..., 'workflow' => ..., 'tags' => ...]]
   */
  protected function scanDir($dir, $workflow) {
    $all = [];
    $files = (array) glob($dir . "/*.ex.php");
    foreach ($files as $file) {
      $name = $workflow . '.' . preg_replace('/\.ex.php/', '', basename($file));
      $scanRecord = [
        'name' => $name,
        'title' => $name,
        'workflow' => $workflow,
        'tags' => [],
        'file' => $file,
        // ^^ relativize?
      ];
      $rawRecord = $this->loadFile($file);
      $all[$name] = array_merge($scanRecord, \CRM_Utils_Array::subset($rawRecord, ['name', 'title', 'workflow', 'tags']));
    }
    return $all;
  }

  /**
   * Load an example data file (based on its file path).
   *
   * @param string $_exFile
   *   Loadable PHP filename.
   * @return array
   *   The raw/unevaluated dataset.
   */
  public function loadFile($_exFile): array {
    // Isolate variables.
    // If you need export values, use something like `extract($_tplVars);`
    return require $_exFile;
  }

  /**
   * Get example data (based on its symbolic name).
   *
   * @param string|string[] $nameOrPath
   *   Ex: "foo" -> load all the data from example "foo"
   *   Ex: "foo.b.a.r" -> load the example "foo" and pull out the data from $foo['b']['a']['r']
   *   Ex: ["foo","b","a","r"] - Same as above. But there is no ambiguity with nested dots.
   * @return array
   */
  public function get($nameOrPath) {
    $path = is_array($nameOrPath) ? $nameOrPath : explode('.', $nameOrPath);
    $exampleName = array_shift($path) . '.' . array_shift($path);
    return \CRM_Utils_Array::pathGet($this->getHeavy($exampleName), $path);
  }

  /**
   * Get one of the "heavy" properties.
   *
   * @param string $name
   * @return array
   * @throws \ReflectionException
   */
  public function getHeavy(string $name): array {
    if (isset($this->heavyCache[$name])) {
      return $this->heavyCache[$name];

    }
    $all = $this->findAll();
    if (!isset($all[$name])) {
      throw new \RuntimeException("Cannot load example ($name)");
    }
    $heavyRecord = $all[$name];
    $loaded = $this->loadFile($all[$name]['file']);
    foreach (['data', 'asserts'] as $heavyField) {
      if (isset($loaded[$heavyField])) {
        $heavyRecord[$heavyField] = $loaded[$heavyField] instanceof \Closure
          ? call_user_func($loaded[$heavyField], $this)
          : $loaded[$heavyField];
      }
    }

    $this->heavyCache[$name] = $heavyRecord;
    return $this->heavyCache[$name];
  }

  /**
   * Get an example and merge/extend it with more data.
   *
   * @param string|string[] $nameOrPath
   *   Ex: "foo" -> load all the data from example "foo"
   *   Ex: "foo.b.a.r" -> load the example "foo" and pull out the data from $foo['b']['a']['r']
   *   Ex: ["foo","b","a","r"] - Same as above. But there is no ambiguity with nested dots.
   * @param array $overrides
   *   Data to add.
   * @return array
   *   The result of merging the original example with the $overrides.
   */
  public function extend($nameOrPath, $overrides = []) {
    $data = $this->get($nameOrPath);
    \CRM_Utils_Array::extend($data, $overrides);
    return $data;
  }

}
