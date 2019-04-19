<?php
namespace Civi\Angular;

class ChangeSet implements ChangeSetInterface {

  /**
   * Update a listing of resources.
   *
   * @param array $changeSets
   *   Array(ChangeSet).
   * @param string $resourceType
   *   Ex: 'requires', 'settings'
   * @param array $resources
   *   The list of resources.
   * @return mixed
   */
  public static function applyResourceFilters($changeSets, $resourceType, $resources) {
    if ($resourceType === 'partials') {
      return self::applyHtmlFilters($changeSets, $resources);
    }
    foreach ($changeSets as $changeSet) {
      /** @var ChangeSet $changeSet */
      foreach ($changeSet->resFilters as $filter) {
        if ($filter['resourceType'] === $resourceType) {
          $resources = call_user_func($filter['callback'], $resources);
        }
      }
    }
    return $resources;
  }

  /**
   * Update a set of HTML snippets.
   *
   * @param array $changeSets
   *   Array(ChangeSet).
   * @param array $strings
   *   Array(string $path => string $html).
   * @return array
   *   Updated list of $strings.
   * @throws \CRM_Core_Exception
   */
  private static function applyHtmlFilters($changeSets, $strings) {
    $coder = new Coder();

    foreach ($strings as $path => $html) {
      /** @var \phpQueryObject $doc */
      $doc = NULL;

      // Most docs don't need phpQueryObject. Initialize phpQuery on first match.

      foreach ($changeSets as $changeSet) {
        /** @var ChangeSet $changeSet */
        foreach ($changeSet->htmlFilters as $filter) {
          if (preg_match($filter['regex'], $path)) {
            if ($doc === NULL) {
              $doc = \phpQuery::newDocument($html, 'text/html');
              if (\CRM_Core_Config::singleton()->debug && !$coder->checkConsistentHtml($html)) {
                throw new \CRM_Core_Exception("Cannot process $path: inconsistent markup. Use check-angular.php to investigate.");
              }
            }
            call_user_func($filter['callback'], $doc, $path);
          }
        }
      }

      if ($doc !== NULL) {
        $strings[$path] = $coder->encode($doc);
      }
    }
    return $strings;
  }

  /**
   * @var string
   */
  protected $name;

  /**
   * @var array
   *   Each item is an array with keys:
   *     - resourceType: string
   *     - callback: function
   */
  protected $resFilters = [];

  /**
   * @var array
   *   Each item is an array with keys:
   *     - regex: string
   *     - callback: function
   */
  protected $htmlFilters = [];

  /**
   * @param string $name
   *   Symbolic name for this changeset.
   * @return \Civi\Angular\ChangeSetInterface
   */
  public static function create($name) {
    $changeSet = new ChangeSet();
    $changeSet->name = $name;
    return $changeSet;
  }

  /**
   * Declare that $module requires additional dependencies.
   *
   * @param string $module
   * @param string|array $dependencies
   * @return ChangeSet
   */
  public function requires($module, $dependencies) {
    $dependencies = (array) $dependencies;
    return $this->alterResource('requires',
      function ($values) use ($module, $dependencies) {
        if (!isset($values[$module])) {
          $values[$module] = [];
        }
        $values[$module] = array_unique(array_merge($values[$module], $dependencies));
        return $values;
      });
  }

  /**
   * Declare a change to a resource.
   *
   * @param string $resourceType
   * @param callable $callback
   * @return ChangeSet
   */
  public function alterResource($resourceType, $callback) {
    $this->resFilters[] = [
      'resourceType' => $resourceType,
      'callback' => $callback,
    ];
    return $this;
  }

  /**
   * Declare a change to HTML.
   *
   * @param string $file
   *   A file name, wildcard, or regex.
   *   Ex: '~/crmHello/intro.html' (filename)
   *   Ex: '~/crmHello/*.html' (wildcard)
   *   Ex: ';(Edit|List)Ctrl\.html$;' (regex)
   * @param callable $callback
   *   Function which accepts up to two parameters:
   *    - phpQueryObject $doc
   *    - string $path
   * @return ChangeSet
   */
  public function alterHtml($file, $callback) {
    $this->htmlFilters[] = [
      'regex' => ($file{0} === ';') ? $file : $this->createRegex($file),
      'callback' => $callback,
    ];
    return $this;
  }

  /**
   * Convert a string with a wildcard (*) to a regex.
   *
   * @param string $filterExpr
   *   Ex: "/foo/*.bar"
   * @return string
   *   Ex: ";^/foo/[^/]*\.bar$;"
   */
  protected function createRegex($filterExpr) {
    $regex = preg_quote($filterExpr, ';');
    $regex = str_replace('\\*', '[^/]*', $regex);
    $regex = ";^$regex$;";
    return $regex;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }

}
