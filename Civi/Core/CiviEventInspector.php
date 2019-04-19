<?php
namespace Civi\Core;

/**
 * Class CiviEventInspector
 *
 * The event inspector is a development tool which provides metadata about events.
 * It can be used for code-generators and documentation-generators.
 *
 * @code
 * $i = new CiviEventInspector();
 * print_r(CRM_Utils_Array::collect('name', $i->getAll()));
 * @endCode
 *
 * An event definition includes these fields:
 *  - type: string, required. Ex: 'hook' or 'object'
 *  - name: string, required. Ex: 'hook_civicrm_post' or 'civi.dao.postInsert'
 *  - class: string, required. Ex: 'Civi\Core\Event\GenericHookEvent'.
 *  - signature: string, required FOR HOOKS. Ex: '$first, &$second'.
 *  - fields: array, required FOR HOOKS. List of hook parameters.
 *  - stub: ReflectionMethod, optional. An example function with docblocks/inputs.
 *
 * Note: The inspector is only designed for use in developer workflows, such
 * as code-generation and inspection. It should be not called by regular
 * runtime logic.
 */
class CiviEventInspector {

  /**
   * Register the default hooks defined by 'CRM_Utils_Hook'.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::eventDefs()
   */
  public static function findBuiltInEvents(\Civi\Core\Event\GenericHookEvent $e) {
    $skipList = ['singleton'];
    $e->inspector->addStaticStubs('CRM_Utils_Hook', 'hook_civicrm_',
      function ($eventDef, $method) use ($skipList) {
        return in_array($method->name, $skipList) ? NULL : $eventDef;
      });
  }

  /**
   * @var array
   *   Array(string $name => array $eventDef).
   *
   * Ex: $eventDefs['hook_civicrm_foo']['description_html'] = 'Hello world';
   */
  protected $eventDefs;

  /**
   * Perform a scan to identify/describe all events.
   *
   * @param bool $force
   * @return CiviEventInspector
   */
  public function build($force = FALSE) {
    if ($force || $this->eventDefs === NULL) {
      $this->eventDefs = [];
      \CRM_Utils_Hook::eventDefs($this);
      ksort($this->eventDefs);
    }
    return $this;
  }

  /**
   * Get a list of all events.
   *
   * @return array
   *   Array(string $name => array $eventDef).
   *   Ex: $result['hook_civicrm_foo']['description_html'] = 'Hello world';
   */
  public function getAll() {
    $this->build();
    return $this->eventDefs;
  }

  /**
   * Find any events that match a pattern.
   *
   * @param string $regex
   * @return array
   *   Array(string $name => array $eventDef).
   *   Ex: $result['hook_civicrm_foo']['description_html'] = 'Hello world';
   */
  public function find($regex) {
    $this->build();
    return array_filter($this->eventDefs, function ($e) use ($regex) {
      return preg_match($regex, $e['name']);
    });
  }

  /**
   * Get the definition of one event.
   *
   * @param string $name
   *   Ex: 'hook_civicrm_alterSettingsMetaData'.
   * @return array
   *   Ex: $result['description_html'] = 'Hello world';
   */
  public function get($name) {
    $this->build();
    return $this->eventDefs[$name];
  }

  /**
   * @param $eventDef
   * @return bool
   *   TRUE if valid.
   */
  public function validate($eventDef) {
    if (!is_array($eventDef) || empty($eventDef['name']) || !isset($eventDef['type'])) {
      return FALSE;
    }

    if (!in_array($eventDef['type'], ['hook', 'object'])) {
      return FALSE;
    }

    if ($eventDef['type'] === 'hook') {
      if (!isset($eventDef['signature']) || !is_array($eventDef['fields'])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Add a new event definition.
   *
   * @param array $eventDef
   * @return CiviEventInspector
   */
  public function add($eventDef) {
    $name = isset($eventDef['name']) ? $eventDef['name'] : NULL;

    if (!isset($eventDef['type'])) {
      $eventDef['type'] = preg_match('/^hook_/', $eventDef['name']) ? 'hook' : 'object';
    }

    if ($eventDef['type'] === 'hook' && empty($eventDef['signature'])) {
      $eventDef['signature'] = implode(', ', array_map(
        function ($field) {
          $sigil = $field['ref'] ? '&$' : '$';
          return $sigil . $field['name'];
        },
        $eventDef['fields']
      ));
    }

    if (TRUE !== $this->validate($eventDef)) {
      throw new \CRM_Core_Exception("Failed to register event ($name). Invalid definition.");
    }

    $this->eventDefs[$name] = $eventDef;
    return $this;
  }

  /**
   * Scan a Symfony event class for metadata, and add it.
   *
   * @param string $event
   *   Ex: 'civi.api.authorize'.
   * @param string $className
   *   Ex: 'Civi\API\Event\AuthorizeEvent'.
   * @return CiviEventInspector
   */
  public function addEventClass($event, $className) {
    $this->add([
      'name' => $event,
      'class' => $className,
    ]);
    return $this;
  }

  /**
   * Scan a class for hook stubs, and add all of them.
   *
   * @param string $className
   *   The name of a class which contains static stub functions.
   *   Ex: 'CRM_Utils_Hook'.
   * @param string $prefix
   *   A prefix to apply to all hook names.
   *   Ex: 'hook_civicrm_'.
   * @param null|callable $filter
   *   An optional function to filter/rewrite the metadata for each hook.
   * @return CiviEventInspector
   */
  public function addStaticStubs($className, $prefix, $filter = NULL) {
    $class = new \ReflectionClass($className);

    foreach ($class->getMethods(\ReflectionMethod::IS_STATIC) as $method) {
      if (!isset($method->name)) {
        continue;
      }

      $eventDef = [
        'name' => $prefix . $method->name,
        'description_html' => $method->getDocComment() ? \CRM_Admin_Page_APIExplorer::formatDocBlock($method->getDocComment()) : '',
        'fields' => [],
        'class' => 'Civi\Core\Event\GenericHookEvent',
        'stub' => $method,
      ];

      foreach ($method->getParameters() as $parameter) {
        $eventDef['fields'][$parameter->getName()] = [
          'name' => $parameter->getName(),
          'ref' => (bool) $parameter->isPassedByReference(),
          // WISHLIST: 'type' => 'mixed',
        ];
      }

      if ($filter !== NULL) {
        $eventDef = $filter($eventDef, $method);
        if ($eventDef === NULL) {
          continue;
        }
      }

      $this->add($eventDef);
    }

    return $this;
  }

}
