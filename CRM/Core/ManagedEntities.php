<?php

/**
 * The ManagedEntities system allows modules to add records to the database
 * declaratively.  Those records will be automatically inserted, updated,
 * deactivated, and deleted in tandem with their modules.
 */
class CRM_Core_ManagedEntities {
  /**
   * @var array($status => array($name => CRM_Core_Module))
   */
  public $moduleIndex;

  /**
   * @var array per hook_civicrm_managed
   */
  public $declarations;

  /**
   * Get an instance
   */
  public static function singleton($fresh = FALSE) {
    static $singleton;
    if ($fresh || !$singleton) {
      $declarations = array();
      CRM_Utils_Hook::managed($declarations);
      $singleton = new CRM_Core_ManagedEntities(CRM_Core_Module::getAll(), $declarations);
    }
    return $singleton;
  }

  /**
   * @param $modules array CRM_Core_Module
   * @param $declarations array per hook_civicrm_managed
   */
  public function __construct($modules, $declarations) {
    $this->moduleIndex = self::createModuleIndex($modules);
    $this->declarations = self::cleanDeclarations($declarations);
  }

  /**
   * Read the managed entity
   */
  public function get($moduleName, $name) {
    $dao = new CRM_Core_DAO_Managed();
    $dao->module = $moduleName;
    $dao->name = $name;
    if ($dao->find(TRUE)) {
      $params = array(
        'version' => 3,
        'id' => $dao->entity_id,
      );
      $result = civicrm_api($dao->entity_type, 'getsingle', $params);
      if ($result['is_error']) {
        $this->onApiError($params, $result);
      } else {
        return $result;
      }
    } else {
      return NULL;
    }
  }

  public function reconcile() {
    if ($error = $this->validate($this->declarations)) {
      throw new Exception($error);
    }
    $this->reconcileEnabledModules();
    $this->reconcileDisabledModules();
    $this->reconcileUnknownModules();
  }


  public function reconcileEnabledModules() {
    // Note: any thing currently declared is necessarily from
    // an active module -- because we got it from a hook!

    // index by moduleName,name
    $decls = self::createDeclarationIndex($this->moduleIndex, $this->declarations);
    foreach ($decls as $moduleName => $todos) {
      if (isset($this->moduleIndex[TRUE][$moduleName])) {
        $this->reconcileEnabledModule($this->moduleIndex[TRUE][$moduleName], $todos);
      } elseif (isset($this->moduleIndex[FALSE][$moduleName])) {
        // do nothing -- module should get swept up later
      } else {
        throw new Exception("Entity declaration references invalid or inactive module name [$moduleName]");
      }
    }
  }

  /**
   * Create, update, and delete entities declared by an active module
   *
   * @param $module string
   * @param $todos array $name => array()
   */
  public function reconcileEnabledModule(CRM_Core_Module $module, $todos) {
    $dao = new CRM_Core_DAO_Managed();
    $dao->module = $module->name;
    $dao->find();
    while ($dao->fetch()) {
      if ($todos[$dao->name]) {
        // update existing entity; remove from $todos
        $defaults = array('id' => $dao->entity_id, 'is_active' => 1); // FIXME: test whether is_active is valid
        $params = array_merge($defaults, $todos[$dao->name]['params']);
        $result = civicrm_api($dao->entity_type, 'create', $params);
        if ($result['is_error']) {
          $this->onApiError($params, $result);
        }

        unset($todos[$dao->name]);
      } else {
        // remove stale entity; not in $todos
        $params = array(
          'version' => 3,
          'id' => $dao->entity_id,
        );
        $result = civicrm_api($dao->entity_type, 'delete', $params);
        if ($result['is_error']) {
          $this->onApiError($params, $result);
        }

        CRM_Core_DAO::executeQuery('DELETE FROM civicrm_managed WHERE id = %1', array(
          1 => array($dao->id, 'Integer')
        ));
      }
    }

    // create new entities from leftover $todos
    foreach ($todos as $name => $todo) {
      $result = civicrm_api($todo['entity'], 'create', $todo['params']);
      if ($result['is_error']) {
        $this->onApiError($todo['params'], $result);
      }

      $dao = new CRM_Core_DAO_Managed();
      $dao->module = $todo['module'];
      $dao->name = $todo['name'];
      $dao->entity_type = $todo['entity'];
      $dao->entity_id = $result['id'];
      $dao->save();
    }
  }

  public function reconcileDisabledModules() {
    if (empty($this->moduleIndex[FALSE])) {
      return;
    }

    $in = CRM_Core_DAO::escapeStrings(array_keys($this->moduleIndex[FALSE]));
    $dao = new CRM_Core_DAO_Managed();
    $dao->whereAdd("module in ($in)");
    $dao->find();
    while ($dao->fetch()) {
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
          $this->onApiError($params, $result);
        }
      }
    }
  }

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
    }
    $dao->find();
    while ($dao->fetch()) {
      $params = array(
        'version' => 3,
        'id' => $dao->entity_id,
      );
      $result = civicrm_api($dao->entity_type, 'delete', $params);
      if ($result['is_error']) {
        $this->onApiError($params, $result);
      }

      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_managed WHERE id = %1', array(
        1 => array($dao->id, 'Integer')
      ));
    }
  }

  /**
   * @return array indexed by is_active,name
   */
  protected static function createModuleIndex($modules) {
    $result = array();
    foreach ($modules as $module) {
      $result[$module->is_active][$module->name] = $module;
    }
    return $result;
  }

  /**
   * @return array indexed by module,name
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
   * @return mixed string on error, or FALSE
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

  protected static function cleanDeclarations($declarations) {
    foreach ($declarations as $name => &$declare) {
      if (!array_key_exists('name', $declare)) {
        $declare['name'] = $name;
      }
    }
    return $declarations;
  }

  protected function onApiError($params, $result) {
    CRM_Core_Error::debug_var('ManagedEntities_failed', array(
      'params' => $params,
      'result' => $result,
    ));
    throw new Exception('API error: ' . $result['error_message']);
  }  
}

