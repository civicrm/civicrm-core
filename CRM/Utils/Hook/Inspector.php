<?php

/**
 * Class CRM_Utils_Hook_Inspector
 *
 * The hook inspector is a development tool which provides metadata about hooks.
 * It can be used for code-generators and documentation-generators.
 *
 * @code
 * $i = new CRM_Utils_Hook_Inspector();
 * print_r(CRM_Utils_Array::collect('name', $i->getHooks()));
 * @endCode
 *
 * Note: The inspector is only designed for use in developer workflows, such
 * as code-generation and inspection. It should be not called by regular
 * runtime logic.
 */
class CRM_Utils_Hook_Inspector {

  /**
   * Register the default hooks defined by 'CRM_Utils_Hook'.
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see CRM_Utils_Hook::hooks()
   */
  public static function findBuiltInHooks(\Civi\Core\Event\GenericHookEvent $e) {
    $skipList = array('singleton');
    $e->inspector->addStaticStubs('CRM_Utils_Hook', 'hook_civicrm_',
      function ($hook, $method) use ($skipList) {
        return in_array($method->name, $skipList) ? NULL : $hook;
      });
  }

  /**
   * @var array
   *   Array(string $name => array $hookDef).
   *
   * Ex: $hooks['hook_civicrm_foo']['description_html'] = 'Hello world';
   */
  protected $hooks;

  /**
   * Perform a scan to identify/describe all hooks.
   *
   * @param bool $force
   * @return CRM_Utils_Hook_Inspector
   */
  public function build($force = FALSE) {
    if ($force || $this->hooks === NULL) {
      $this->hooks = array();
      CRM_Utils_Hook::hooks($this);
      ksort($this->hooks);
    }
    return $this;
  }

  /**
   * Get a list of all hooks.
   *
   * @return array
   *   Array(string $name => array $hookDef).
   *   Ex: $hooks['hook_civicrm_foo']['description_html'] = 'Hello world';
   */
  public function getAll() {
    $this->build();
    return $this->hooks;
  }

  /**
   * Get the definition of one hook.
   *
   * @param string $name
   *   Ex: 'hook_civicrm_alterSettingsMetaData'.
   * @return array
   *   Ex: $hook['description_html'] = 'Hello world';
   */
  public function get($name) {
    $this->build();
    return $this->hooks[$name];
  }

  /**
   * @param $hook
   * @return bool
   *   TRUE if valid.
   */
  public function validate($hook) {
    return
      is_array($hook)
      && !empty($hook['name'])
      && isset($hook['signature'])
      && is_array($hook['fields']);
  }

  /**
   * Add a new hook definition.
   *
   * @param array $hook
   * @return CRM_Utils_Hook_Inspector
   */
  public function add($hook) {
    $name = isset($hook['name']) ? $hook['name'] : NULL;

    if (empty($hook['signature'])) {
      $hook['signature'] = implode(', ', array_map(
        function ($field) {
          $sigil = $field['ref'] ? '&$' : '$';
          return $sigil . $field['name'];
        },
        $hook['fields']
      ));
    }

    if (TRUE !== $this->validate($hook)) {
      throw new CRM_Core_Exception("Failed to register hook ($name). Invalid definition.");
    }

    $this->hooks[$name] = $hook;
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
   * @return CRM_Utils_Hook_Inspector
   */
  public function addStaticStubs($className, $prefix = 'hook_', $filter = NULL) {
    $class = new ReflectionClass($className);

    foreach ($class->getMethods(ReflectionMethod::IS_STATIC) as $method) {
      if (!isset($method->name)) {
        continue;
      }

      $hook = array(
        'name' => $prefix . $method->name,
        'description_html' => $method->getDocComment() ? CRM_Admin_Page_APIExplorer::formatDocBlock($method->getDocComment()) : '',
        'fields' => array(),
        'class' => 'Civi\Core\Event\GenericHookEvent',
      );

      foreach ($method->getParameters() as $parameter) {
        $hook['fields'][$parameter->getName()] = array(
          'name' => $parameter->getName(),
          'ref' => (bool) $parameter->isPassedByReference(),
          // WISHLIST: 'type' => 'mixed',
        );
      }

      if ($filter !== NULL) {
        $hook = $filter($hook, $method);
        if ($hook === NULL) {
          continue;
        }
      }

      $this->add($hook);
    }

    return $this;
  }

}
