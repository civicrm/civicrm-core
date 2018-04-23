<?php

/**
 * The ManagedEntities system allows modules to add records to the database
 * declaratively.  Those records will be automatically inserted, updated,
 * deactivated, and deleted in tandem with their modules.
 */
class CRM_Core_ManagedEntities {

  /**
   * Get clean up options.
   *
   * @return array
   */
  public static function getCleanupOptions() {
    return array(
      'always' => ts('Always'),
      'never' => ts('Never'),
      'unused' => ts('If Unused'),
    );
  }

  /**
   * @var array
   *   Array($status => array($name => CRM_Core_Module)).
   */
  protected $moduleIndex;

  /**
   * @var array
   *   List of all entity declarations.
   * @see CRM_Utils_Hook::managed()
   */
  protected $declarations;

  /**
   * Get an instance.
   * @param bool $fresh
   * @return \CRM_Core_ManagedEntities
   */
  public static function singleton($fresh = FALSE) {
    static $singleton;
    if ($fresh || !$singleton) {
      $singleton = new CRM_Core_ManagedEntities(CRM_Core_Module::getAll(), NULL);
    }
    return $singleton;
  }

  /**
   * Perform an asynchronous reconciliation when the transaction ends.
   */
  public static function scheduleReconciliation() {
    CRM_Core_Transaction::addCallback(
      CRM_Core_Transaction::PHASE_POST_COMMIT,
      function () {
        CRM_Core_ManagedEntities::singleton(TRUE)->reconcile();
      },
      array(),
      'ManagedEntities::reconcile'
    );
  }

  /**
   * @param array $modules
   *   CRM_Core_Module.
   * @param array $declarations
   *   Per hook_civicrm_managed.
   */
  public function __construct($modules, $declarations) {
    $this->moduleIndex = self::createModuleIndex($modules);

    if ($declarations !== NULL) {
      $this->declarations = self::cleanDeclarations($declarations);
    }
    else {
      $this->declarations = NULL;
    }
  }

  /**
   * Read a managed entity using APIv3.
   *
   * @param string $moduleName
   *   The name of the module which declared entity.
   * @param string $name
   *   The symbolic name of the entity.
   * @return array|NULL
   *   API representation, or NULL if the entity does not exist
   */
  public function get($moduleName, $name) {
    $dao = new CRM_Core_DAO_Managed();
    $dao->module = $moduleName;
    $dao->name = $name;
    if ($dao->find(TRUE)) {
      $params = array(
        'id' => $dao->entity_id,
      );
      $result = NULL;
      try {
        $result = civicrm_api3($dao->entity_type, 'getsingle', $params);
      }
      catch (Exception $e) {
        $this->onApiError($dao->entity_type, 'getsingle', $params, $result);
      }
      return $result;
    }
    else {
      return NULL;
    }
  }

  /**
   * Identify any enabled/disabled modules. Add new entities, update
   * existing entities, and remove orphaned (stale) entities.
   *
   * @throws Exception
   */
  public function reconcile() {
    if ($error = $this->validate($this->getDeclarations())) {
      throw new Exception($error);
    }
    $this->reconcileEnabledModules();
    $this->reconcileDisabledModules();
    $this->reconcileUnknownModules();
  }

  /**
   * For all enabled modules, add new entities, update
   * existing entities, and remove orphaned (stale) entities.
   *
   * @throws Exception
   */
  public function reconcileEnabledModules() {
    // Note: any thing currently declared is necessarily from
    // an active module -- because we got it from a hook!

    // index by moduleName,name
    $decls = self::createDeclarationIndex($this->moduleIndex, $this->getDeclarations());
    foreach ($decls as $moduleName => $todos) {
      if (isset($this->moduleIndex[TRUE][$moduleName])) {
        $this->reconcileEnabledModule($this->moduleIndex[TRUE][$moduleName], $todos);
      }
      elseif (isset($this->moduleIndex[FALSE][$moduleName])) {
        // do nothing -- module should get swept up later
      }
      else {
        throw new Exception("Entity declaration references invalid or inactive module name [$moduleName]");
      }
    }
  }

  /**
   * For one enabled module, add new entities, update existing entities,
   * and remove orphaned (stale) entities.
   *
   * @param \CRM_Core_Module $module
   * @param array $todos
   *   List of entities currently declared by this module.
   *   array(string $name => array $entityDef).
   */
  public function reconcileEnabledModule(CRM_Core_Module $module, $todos) {
    $dao = new CRM_Core_DAO_Managed();
    $dao->module = $module->name;
    $dao->find();
    while ($dao->fetch()) {
      if (isset($todos[$dao->name]) && $todos[$dao->name]) {
        // update existing entity; remove from $todos
        $this->updateExistingEntity($dao, $todos[$dao->name]);
        unset($todos[$dao->name]);
      }
      else {
        // remove stale entity; not in $todos
        $this->removeStaleEntity($dao);
      }
    }

    // create new entities from leftover $todos
    foreach ($todos as $name => $todo) {
      $this->insertNewEntity($todo);
    }
  }

  /**
   * For all disabled modules, disable any managed entities.
   */
  public function reconcileDisabledModules() {
    if (empty($this->moduleIndex[FALSE])) {
      return;
    }

    $in = CRM_Core_DAO::escapeStrings(array_keys($this->moduleIndex[FALSE]));
    $dao = new CRM_Core_DAO_Managed();
    $dao->whereAdd("module in ($in)");
    $dao->orderBy('id DESC');
    $dao->find();
    while ($dao->fetch()) {
      $this->disableEntity($dao);

    }
  }

  /**
   * Remove any orphaned (stale) entities that are linked to
   * unknown modules.
   */
  public function reconcileUnknownModules() {
    $knownModules = array();
    if (array_key_exists(0, $this->moduleIndex) && is_array($this->moduleIndex[0])) {
      $knownModules = array_merge($knownModules, array_keys($this->moduleIndex[0]));
    }
    if (array_key_exists(1, $this->moduleIndex) && is_array($this->moduleIndex[1])) {
      $knownModules = array_merge($knownModules, array_keys($this->moduleIndex[1]));
    }

    $dao = new CRM_Core_DAO_Managed();
    if (!empty($knownModules)) {
      $in = CRM_Core_DAO::escapeStrings($knownModules);
      $dao->whereAdd("module NOT IN ($in)");
      $dao->orderBy('id DESC');
    }
    $dao->find();
    while ($dao->fetch()) {
      $this->removeStaleEntity($dao);
    }
  }

  /**
   * Create a new entity.
   *
   * @param array $todo
   *   Entity specification (per hook_civicrm_managedEntities).
   */
  public function insertNewEntity($todo) {
    $result = civicrm_api($todo['entity'], 'create', $todo['params']);
    if ($result['is_error']) {
      $this->onApiError($todo['entity'], 'create', $todo['params'], $result);
    }

    $dao = new CRM_Core_DAO_Managed();
    $dao->module = $todo['module'];
    $dao->name = $todo['name'];
    $dao->entity_type = $todo['entity'];
    $dao->entity_id = $result['id'];
    $dao->cleanup = CRM_Utils_Array::value('cleanup', $todo);
    $dao->save();
  }

  /**
   * Update an entity which (a) is believed to exist and which (b) ought to be active.
   *
   * @param CRM_Core_DAO_Managed $dao
   * @param array $todo
   *   Entity specification (per hook_civicrm_managedEntities).
   */
  public function updateExistingEntity($dao, $todo) {
    $policy = CRM_Utils_Array::value('update', $todo, 'always');
    $doUpdate = ($policy == 'always');

    if ($doUpdate) {
      $defaults = array(
        'id' => $dao->entity_id,
        'is_active' => 1, // FIXME: test whether is_active is valid
      );
      $params = array_merge($defaults, $todo['params']);
      $result = civicrm_api($dao->entity_type, 'create', $params);
      if ($result['is_error']) {
        $this->onApiError($dao->entity_type, 'create', $params, $result);
      }
    }

    if (isset($todo['cleanup'])) {
      $dao->cleanup = $todo['cleanup'];
      $dao->update();
    }
  }

  /**
   * Update an entity which (a) is believed to exist and which (b) ought to be
   * inactive.
   *
   * @param CRM_Core_DAO_Managed $dao
   */
  public function disableEntity($dao) {
    // FIXME: if ($dao->entity_type supports is_active) {
    if (TRUE) {
      // FIXME cascading for payproc types?
      $params = array(
        'version' => 3,
        'id' => $dao->entity_id,
        'is_active' => 0,
      );
      $result = civicrm_api($dao->entity_type, 'create', $params);
      if ($result['is_error']) {
        $this->onApiError($dao->entity_type, 'create', $params, $result);
      }
    }
  }

  /**
   * Remove a stale entity (if policy allows).
   *
   * @param CRM_Core_DAO_Managed $dao
   * @throws Exception
   */
  public function removeStaleEntity($dao) {
    $policy = empty($dao->cleanup) ? 'always' : $dao->cleanup;
    switch ($policy) {
      case 'always':
        $doDelete = TRUE;
        break;

      case 'never':
        $doDelete = FALSE;
        break;

      case 'unused':
        $getRefCount = civicrm_api3($dao->entity_type, 'getrefcount', array(
          'debug' => 1,
          'id' => $dao->entity_id,
        ));

        $total = 0;
        foreach ($getRefCount['values'] as $refCount) {
          $total += $refCount['count'];
        }

        $doDelete = ($total == 0);
        break;

      default:
        throw new \Exception('Unrecognized cleanup policy: ' . $policy);
    }

    if ($doDelete) {
      $params = array(
        'version' => 3,
        'id' => $dao->entity_id,
      );
      $check = civicrm_api3($dao->entity_type, 'get', $params);
      if ((bool) $check['count']) {
        $result = civicrm_api($dao->entity_type, 'delete', $params);
        if ($result['is_error']) {
          $this->onApiError($dao->entity_type, 'delete', $params, $result);
        }
      }
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_managed WHERE id = %1', array(
        1 => array($dao->id, 'Integer'),
      ));
    }
  }

  /**
   * Get declarations.
   *
   * @return array|null
   */
  public function getDeclarations() {
    if ($this->declarations === NULL) {
      $this->declarations = array();
      foreach (CRM_Core_Component::getEnabledComponents() as $component) {
        /** @var CRM_Core_Component_Info $component */
        $this->declarations = array_merge($this->declarations, $component->getManagedEntities());
      }
      CRM_Utils_Hook::managed($this->declarations);
      $this->declarations = self::cleanDeclarations($this->declarations);
    }
    return $this->declarations;
  }

  /**
   * @param array $modules
   *   Array<CRM_Core_Module>.
   *
   * @return array
   *   indexed by is_active,name
   */
  protected static function createModuleIndex($modules) {
    $result = array();
    foreach ($modules as $module) {
      $result[$module->is_active][$module->name] = $module;
    }
    return $result;
  }

  /**
   * @param array $moduleIndex
   * @param array $declarations
   *
   * @return array
   *   indexed by module,name
   */
  protected static function createDeclarationIndex($moduleIndex, $declarations) {
    $result = array();
    if (!isset($moduleIndex[TRUE])) {
      return $result;
    }
    foreach ($moduleIndex[TRUE] as $moduleName => $module) {
      if ($module->is_active) {
        // need an empty array() for all active modules, even if there are no current $declarations
        $result[$moduleName] = array();
      }
    }
    foreach ($declarations as $declaration) {
      $result[$declaration['module']][$declaration['name']] = $declaration;
    }
    return $result;
  }

  /**
   * @param $declarations
   *
   * @return string|bool
   *   string on error, or FALSE
   */
  protected static function validate($declarations) {
    foreach ($declarations as $declare) {
      foreach (array('name', 'module', 'entity', 'params') as $key) {
        if (empty($declare[$key])) {
          $str = print_r($declare, TRUE);
          return ("Managed Entity is missing field \"$key\": $str");
        }
      }
      // FIXME: validate that each 'module' is known
    }
    return FALSE;
  }

  /**
   * @param array $declarations
   *
   * @return array
   */
  protected static function cleanDeclarations($declarations) {
    foreach ($declarations as $name => &$declare) {
      if (!array_key_exists('name', $declare)) {
        $declare['name'] = $name;
      }
    }
    return $declarations;
  }

  /**
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param array $result
   *
   * @throws Exception
   */
  protected function onApiError($entity, $action, $params, $result) {
    CRM_Core_Error::debug_var('ManagedEntities_failed', array(
      'entity' => $entity,
      'action' => $action,
      'params' => $params,
      'result' => $result,
    ));
    throw new Exception('API error: ' . $result['error_message']);
  }

}
