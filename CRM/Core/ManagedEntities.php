<?php

use Civi\Api4\Managed;

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
    return [
      'always' => ts('Always'),
      'never' => ts('Never'),
      'unused' => ts('If Unused'),
    ];
  }

  /**
   * @var array
   *   Array($status => array($name => CRM_Core_Module)).
   */
  protected $moduleIndex;

  /**
   * Get an instance.
   * @param bool $fresh
   * @return \CRM_Core_ManagedEntities
   */
  public static function singleton($fresh = FALSE) {
    static $singleton;
    if ($fresh || !$singleton) {
      $singleton = new CRM_Core_ManagedEntities(CRM_Core_Module::getAll());
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
      [],
      'ManagedEntities::reconcile'
    );
  }

  /**
   * @param array $modules
   *   CRM_Core_Module.
   */
  public function __construct(array $modules) {
    $this->moduleIndex = $this->createModuleIndex($modules);
  }

  /**
   * Read a managed entity using APIv3.
   *
   * @deprecated
   *
   * @param string $moduleName
   *   The name of the module which declared entity.
   * @param string $name
   *   The symbolic name of the entity.
   * @return array|NULL
   *   API representation, or NULL if the entity does not exist
   */
  public function get($moduleName, $name) {
    CRM_Core_Error::deprecatedFunctionWarning('api');
    $dao = new CRM_Core_DAO_Managed();
    $dao->module = $moduleName;
    $dao->name = $name;
    if ($dao->find(TRUE)) {
      $params = [
        'id' => $dao->entity_id,
      ];
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
   * @param array $modules
   *   Limits scope of reconciliation to specific module(s).
   * @throws \CRM_Core_Exception
   */
  public function reconcile($modules = NULL) {
    $modules = $modules ? (array) $modules : NULL;
    $declarations = $this->getDeclarations($modules);
    $plan = $this->createPlan($declarations, $modules);
    $this->reconcileEntities($plan);
  }

  /**
   * Force-revert a record back to its original state.
   * @param array $params
   *   Key->value properties of CRM_Core_DAO_Managed used to match an existing record
   */
  public function revert(array $params) {
    $mgd = new \CRM_Core_DAO_Managed();
    $mgd->copyValues($params);
    $mgd->find(TRUE);
    $declarations = $this->getDeclarations([$mgd->module]);
    $declarations = CRM_Utils_Array::findAll($declarations, [
      'module' => $mgd->module,
      'name' => $mgd->name,
      'entity' => $mgd->entity_type,
    ]);
    if ($mgd->id && isset($declarations[0])) {
      $this->updateExistingEntity(['update' => 'always'] + $declarations[0] + $mgd->toArray());
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Take appropriate action on every managed entity.
   *
   * @param array[] $plan
   */
  private function reconcileEntities(array $plan): void {
    foreach ($this->filterPlanByAction($plan, 'update') as $item) {
      $this->updateExistingEntity($item);
    }
    // reverse-order so that child entities are cleaned up before their parents
    foreach (array_reverse($this->filterPlanByAction($plan, 'delete')) as $item) {
      $this->removeStaleEntity($item);
    }
    foreach ($this->filterPlanByAction($plan, 'create') as $item) {
      $this->insertNewEntity($item);
    }
    foreach ($this->filterPlanByAction($plan, 'disable') as $item) {
      $this->disableEntity($item);
    }
  }

  /**
   * Get the managed entities that fit the criteria.
   *
   * @param array[] $plan
   * @param string $action
   *
   * @return array
   */
  private function filterPlanByAction(array $plan, string $action): array {
    return CRM_Utils_Array::findAll($plan, ['managed_action' => $action]);
  }

  /**
   * Create a new entity.
   *
   * @param array $item
   *   Entity specification (per hook_civicrm_managedEntities).
   */
  protected function insertNewEntity(array $item) {
    $params = $item['params'];
    // APIv4
    if ($params['version'] == 4) {
      $params['checkPermissions'] = FALSE;
      // Use "save" instead of "create" action to accommodate a "match" param
      $params['records'] = [$params['values']];
      unset($params['values']);
      $result = civicrm_api4($item['entity_type'], 'save', $params);
      $id = $result->first()['id'];
    }
    // APIv3
    else {
      $result = civicrm_api($item['entity_type'], 'create', $params);
      if (!empty($result['is_error'])) {
        $this->onApiError($item['entity_type'], 'create', $params, $result);
      }
      $id = $result['id'];
    }

    $dao = new CRM_Core_DAO_Managed();
    $dao->module = $item['module'];
    $dao->name = $item['name'];
    $dao->entity_type = $item['entity_type'];
    $dao->entity_id = $id;
    $dao->cleanup = $item['cleanup'] ?? NULL;
    $dao->save();
  }

  /**
   * Update an entity which is believed to exist.
   *
   * @param array $item
   *   Entity specification (per hook_civicrm_managedEntities).
   */
  private function updateExistingEntity(array $item) {
    $policy = $item['update'] ?? 'always';
    $doUpdate = ($policy === 'always');

    if ($policy === 'unmodified') {
      // If this is not an APIv4 managed entity, the entity_modified_date will always be null
      if (!CRM_Core_BAO_Managed::isApi4ManagedType($item['entity_type'])) {
        Civi::log()->warning('ManagedEntity update policy "unmodified" specified for entity type ' . $item['entity_type'] . ' which is not an APIv4 ManagedEntity. Falling back to policy "always".');
      }
      $doUpdate = empty($item['entity_modified_date']);
    }

    if ($doUpdate && $item['params']['version'] == 3) {
      $defaults = ['id' => $item['entity_id']];
      if ($this->isActivationSupported($item['entity_type'])) {
        $defaults['is_active'] = 1;
      }
      $params = array_merge($defaults, $item['params']);

      $manager = CRM_Extension_System::singleton()->getManager();
      if ($item['entity_type'] === 'Job' && !$manager->extensionIsBeingInstalledOrEnabled($item['module'])) {
        // Special treatment for scheduled jobs:
        //
        // If we're being called as part of enabling/installing a module then
        // we want the default behaviour of setting is_active = 1.
        //
        // However, if we're just being called by a normal cache flush then we
        // should not re-enable a job that an administrator has decided to disable.
        //
        // Without this logic there was a problem: site admin might disable
        // a job, but then when there was a flush op, the job was re-enabled
        // which can cause significant embarrassment, depending on the job
        // ("Don't worry, sending mailings is disabled right now...").
        unset($params['is_active']);
      }

      $result = civicrm_api($item['entity_type'], 'create', $params);
      if ($result['is_error']) {
        $this->onApiError($item['entity_type'], 'create', $params, $result);
      }
    }
    elseif ($doUpdate && $item['params']['version'] == 4) {
      $params = ['checkPermissions' => FALSE] + $item['params'];
      $params['values']['id'] = $item['entity_id'];
      // 'match' param doesn't apply to "update" action
      unset($params['match']);
      civicrm_api4($item['entity_type'], 'update', $params);
    }

    if (isset($item['cleanup']) || $doUpdate) {
      $dao = new CRM_Core_DAO_Managed();
      $dao->id = $item['id'];
      $dao->cleanup = $item['cleanup'] ?? NULL;
      // Reset the `entity_modified_date` timestamp if reverting record.
      $dao->entity_modified_date = $doUpdate ? 'null' : NULL;
      $dao->update();
    }
  }

  /**
   * Update an entity which (a) is believed to exist and which (b) ought to be
   * inactive.
   *
   * @param array $item
   *
   * @throws \CRM_Core_Exception
   */
  protected function disableEntity(array $item): void {
    $entity_type = $item['entity_type'];
    if ($this->isActivationSupported($entity_type)) {
      // FIXME cascading for payproc types?
      $params = [
        'version' => 3,
        'id' => $item['entity_id'],
        'is_active' => 0,
      ];
      $result = civicrm_api($item['entity_type'], 'create', $params);
      if ($result['is_error']) {
        $this->onApiError($item['entity_type'], 'create', $params, $result);
      }
      // Reset the `entity_modified_date` timestamp to indicate that the entity has not been modified by the user.
      $dao = new CRM_Core_DAO_Managed();
      $dao->id = $item['id'];
      $dao->entity_modified_date = 'null';
      $dao->update();
    }
  }

  /**
   * Remove a stale entity (if policy allows).
   *
   * @param array $item
   * @throws CRM_Core_Exception
   */
  protected function removeStaleEntity(array $item) {
    $policy = empty($item['cleanup']) ? 'always' : $item['cleanup'];
    switch ($policy) {
      case 'always':
        $doDelete = TRUE;
        break;

      case 'never':
        $doDelete = FALSE;
        break;

      case 'unused':
        if (CRM_Core_BAO_Managed::isApi4ManagedType($item['entity_type'])) {
          $getRefCount = \Civi\Api4\Utils\CoreUtil::getRefCount($item['entity_type'], $item['entity_id']);
        }
        else {
          $getRefCount = civicrm_api3($item['entity_type'], 'getrefcount', [
            'id' => $item['entity_id'],
          ])['values'];
        }

        // FIXME: This extra counting should be unnecessary, because getRefCount only returns values if count > 0
        $total = 0;
        foreach ($getRefCount as $refCount) {
          $total += $refCount['count'];
        }

        $doDelete = ($total == 0);
        break;

      default:
        throw new CRM_Core_Exception('Unrecognized cleanup policy: ' . $policy);
    }

    // Delete the entity and the managed record
    if ($doDelete) {
      // APIv4 delete
      if (CRM_Core_BAO_Managed::isApi4ManagedType($item['entity_type'])) {
        civicrm_api4($item['entity_type'], 'delete', [
          'checkPermissions' => FALSE,
          'where' => [['id', '=', $item['entity_id']]],
        ]);
      }
      // APIv3 delete
      else {
        $params = [
          'version' => 3,
          'id' => $item['entity_id'],
        ];
        $check = civicrm_api3($item['entity_type'], 'get', $params);
        if ($check['count']) {
          $result = civicrm_api($item['entity_type'], 'delete', $params);
          if ($result['is_error']) {
            if (isset($item['name'])) {
              $params['name'] = $item['name'];
            }
            $this->onApiError($item['entity_type'], 'delete', $params, $result);
          }
        }
      }
      // Ensure managed record is deleted.
      // Note: in many cases CRM_Core_BAO_Managed::on_hook_civicrm_post() will take care of
      // deleting it, but there may be edge cases, such as the source record no longer existing,
      // so just to be sure - we need to do this as the final step.
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_managed WHERE id = %1', [
        1 => [$item['id'], 'Integer'],
      ]);
    }
  }

  /**
   * @param array $modules
   *   Array<CRM_Core_Module>.
   *
   * @return array
   *   indexed by is_active,name
   */
  protected function createModuleIndex($modules) {
    $result = [];
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
  protected function createDeclarationIndex($moduleIndex, $declarations) {
    $result = [];
    if (!isset($moduleIndex[TRUE])) {
      return $result;
    }
    foreach ($moduleIndex[TRUE] as $moduleName => $module) {
      if ($module->is_active) {
        // need an empty array() for all active modules, even if there are no current $declarations
        $result[$moduleName] = [];
      }
    }
    foreach ($declarations as $declaration) {
      $result[$declaration['module']][$declaration['name']] = $declaration;
    }
    return $result;
  }

  /**
   * @param array $declarations
   *
   * @throws CRM_Core_Exception
   */
  protected function validate($declarations) {
    foreach ($declarations as $module => $declare) {
      foreach (['name', 'module', 'entity', 'params'] as $key) {
        if (empty($declare[$key])) {
          $str = print_r($declare, TRUE);
          throw new CRM_Core_Exception(ts('Managed Entity (%1) is missing field "%2": %3', [$module, $key, $str]));
        }
      }
      if (!$this->isModuleRecognised($declare['module'])) {
        throw new CRM_Core_Exception(ts('Entity declaration references invalid or inactive module name [%1]', [$declare['module']]));
      }
    }
  }

  /**
   * Is the module recognised (as an enabled or disabled extension in the system).
   *
   * @param string $module
   *
   * @return bool
   */
  protected function isModuleRecognised(string $module): bool {
    return $this->isModuleDisabled($module) || $this->isModuleEnabled($module);
  }

  /**
   * Is the module enabled.
   *
   * @param string $module
   *
   * @return bool
   */
  protected function isModuleEnabled(string $module): bool {
    return isset($this->moduleIndex[TRUE][$module]);
  }

  /**
   * Is the module disabled.
   *
   * @param string $module
   *
   * @return bool
   */
  protected function isModuleDisabled(string $module): bool {
    return isset($this->moduleIndex[FALSE][$module]);
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
    CRM_Core_Error::debug_var('ManagedEntities_failed', [
      'entity' => $entity,
      'action' => $action,
      'params' => $params,
      'result' => $result,
    ]);
    throw new Exception('API error: ' . $result['error_message'] . ' on ' . $entity . '.' . $action
      . (!empty($params['name']) ? '( entity name ' . $params['name'] . ')' : '')
    );
  }

  /**
   * Determine if an entity supports APIv3-based activation/de-activation.
   * @param string $entity_type
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  private function isActivationSupported(string $entity_type): bool {
    if (!isset(Civi::$statics[__CLASS__][__FUNCTION__][$entity_type])) {
      $actions = civicrm_api3($entity_type, 'getactions', [])['values'];
      Civi::$statics[__CLASS__][__FUNCTION__][$entity_type] = FALSE;
      if (in_array('create', $actions, TRUE) && in_array('getfields', $actions)) {
        $fields = civicrm_api3($entity_type, 'getfields', ['action' => 'create'])['values'];
        Civi::$statics[__CLASS__][__FUNCTION__][$entity_type] = array_key_exists('is_active', $fields);
      }
    }
    return Civi::$statics[__CLASS__][__FUNCTION__][$entity_type];
  }

  /**
   * Load managed entity declarations.
   *
   * This picks it up from hooks and enabled components.
   *
   * @param array|null $modules
   *   Limit reconciliation specified modules.
   * @return array[]
   */
  protected function getDeclarations($modules = NULL): array {
    $declarations = [];
    // Exclude components if given a module name.
    if (!$modules || $modules === ['civicrm']) {
      foreach (CRM_Core_Component::getEnabledComponents() as $component) {
        $declarations = array_merge($declarations, $component->getManagedEntities());
      }
    }
    CRM_Utils_Hook::managed($declarations, $modules);
    $this->validate($declarations);
    foreach (array_keys($declarations) as $name) {
      $declarations[$name] += ['name' => $name];
    }
    return $declarations;
  }

  /**
   * Builds $this->managedActions array
   *
   * @param array $declarations
   * @param array|null $modules
   * @return array[]
   */
  protected function createPlan(array $declarations, $modules = NULL): array {
    $where = $modules ? [['module', 'IN', $modules]] : [];
    $managedEntities = Managed::get(FALSE)
      ->setWhere($where)
      ->execute();
    $plan = [];
    foreach ($managedEntities as $managedEntity) {
      $key = "{$managedEntity['module']}_{$managedEntity['name']}_{$managedEntity['entity_type']}";
      // Set to disable or delete if module is disabled or missing - it will be overwritten below module is active.
      $action = $this->isModuleDisabled($managedEntity['module']) ? 'disable' : 'delete';
      $plan[$key] = array_merge($managedEntity, ['managed_action' => $action]);
    }
    foreach ($declarations as $declaration) {
      $key = "{$declaration['module']}_{$declaration['name']}_{$declaration['entity']}";
      if (isset($plan[$key])) {
        $plan[$key]['params'] = $declaration['params'];
        $plan[$key]['managed_action'] = 'update';
        $plan[$key]['cleanup'] = $declaration['cleanup'] ?? NULL;
        $plan[$key]['update'] = $declaration['update'] ?? 'always';
      }
      else {
        $plan[$key] = [
          'module' => $declaration['module'],
          'name' => $declaration['name'],
          'entity_type' => $declaration['entity'],
          'managed_action' => 'create',
          'params' => $declaration['params'],
          'cleanup' => $declaration['cleanup'] ?? NULL,
          'update' => $declaration['update'] ?? 'always',
        ];
      }
    }
    return $plan;
  }

}
