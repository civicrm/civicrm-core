<?php

use Civi\Api4\Managed;
use Civi\Api4\Utils\CoreUtil;

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
   * @param array|null $modules
   */
  public static function scheduleReconciliation(?array $modules = NULL) {
    CRM_Core_Transaction::addCallback(
      CRM_Core_Transaction::PHASE_POST_COMMIT,
      function ($modules) {
        CRM_Core_ManagedEntities::singleton(TRUE)->reconcile($modules);
      },
      [$modules],
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
    if (!CRM_Core_Config::isUpgradeMode()) {
      $plan = $this->optimizePlan($plan);
      // Loosely: The optimizer omits UPDATEs if the declaration-checksum is unchanged.

      // NOTE: For records with `update=>always`, you still need _some_ occasion to re-save
      // (to undo local-edits). System-upgrades are an OK time: frequent enough to make a difference,
      // but not so frequent as to be a drag.
      // FUTURE OPTIMIZATION: If we can actively prevent runtime edits for records with `update=>always`,
      // then maybe expand optimizer and use it during upgrade-mode.
    }
    $this->reconcileEntities($plan);
  }

  /**
   * Force-revert a record back to its original state.
   * @param string $entityType
   * @param $entityId
   * @return bool
   */
  public function revert(string $entityType, $entityId): bool {
    $mgd = new \CRM_Core_DAO_Managed();
    $mgd->entity_type = $entityType;
    $mgd->entity_id = $entityId;
    $mgd->find(TRUE);
    $declarations = $this->getDeclarations([$mgd->module]);
    $declarations = CRM_Utils_Array::findAll($declarations, [
      'module' => $mgd->module,
      'name' => $mgd->name,
      'entity' => $mgd->entity_type,
    ]);
    if ($mgd->id && isset($declarations[0])) {
      $item = ['update' => 'always'] + $declarations[0] + $mgd->toArray();
      $item['declaration_checksum'] = $declarations[0]['checksum'];
      $this->backfillDefaults($item);
      $this->updateExistingEntity($item);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Backfill default values to restore record to a pristine state
   *
   * @param array $item Managed APIv4 record
   */
  private function backfillDefaults(array &$item): void {
    if ($item['params']['version'] != 4) {
      return;
    }
    // Fetch default values for fields that are writeable
    $condition = [['type', '=', 'Field'], ['readonly', 'IS EMPTY'], ['default_value', '!=', 'now']];
    // Exclude "weight" as that auto-adjusts
    if (CoreUtil::isType($item['entity_type'], 'SortableEntity')) {
      $weightCol = CoreUtil::getInfoItem($item['entity_type'], 'order_by');
      $condition[] = ['name', '!=', $weightCol];
    }
    $getFields = civicrm_api4($item['entity_type'], 'getFields', [
      'checkPermissions' => FALSE,
      'action' => 'create',
      'where' => $condition,
    ]);
    $defaultValues = $getFields->column('default_value', 'name');
    $item['params']['values'] += $defaultValues;
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
   * Examine the steps in the plan. Identify any steps that are likely to be extraneous/redundant.
   *
   * @param array $plan
   * @return array
   *   Updated plan
   */
  private function optimizePlan(array $plan): array {
    $extManager = CRM_Extension_System::singleton()->getManager();

    $isLive = function(array $item) use ($extManager) {
      // Keep all INSERTs and DELETEs. Only UPDATEs can be optimized-out.
      if ($item['managed_action'] !== 'update') {
        return TRUE;
      }

      // When toggling an extension, let's evaluate its mgds fully.
      if ($extManager->getActiveProcesses($item['module'])) {
        return TRUE;
      }

      // For ordinary UPDATE plans, we only care if the checksum has changed.
      return $item['declaration_checksum'] !== $item['checksum'];
    };

    $newPlan = array_filter($plan, $isLive);
    return $newPlan;
  }

  protected function computeChecksum(array $declaration) {
    if (isset($declaration['checksum'])) {
      throw new \LogicException("Checksums cannot be assigned to hook_managed declarations. They must be singularly computed at runtime.");
    }
    CRM_Utils_Array::deepSort($declaration, fn(array &$a) => ksort($a));
    $serialized = json_encode($declaration, JSON_UNESCAPED_SLASHES);
    return base64_encode(hash('sha256', $serialized, TRUE));
  }

  /**
   * Create a new entity (if policy allows).
   *
   * @param array $item
   *   Entity specification (per hook_civicrm_managedEntities).
   */
  protected function insertNewEntity(array $item) {
    // If entity has previously been created, only re-insert if 'update' policy is 'always'
    // NOTE: $item[id] is the id of the `civicrm_managed` row not the entity itself
    // If that id exists, then we know the entity was inserted previously and subsequently deleted.
    if (!empty($item['id']) && $item['update'] !== 'always') {
      return;
    }
    $params = $item['params'];
    // APIv4
    if ($params['version'] == 4) {
      $params['checkPermissions'] = FALSE;
      // Use "save" instead of "create" action to accommodate a "match" param
      $params['records'] = [$params['values']];
      unset($params['values']);
      try {
        $result = civicrm_api4($item['entity_type'], 'save', $params);
      }
      catch (CRM_Core_Exception $e) {
        $this->onApiError($item['module'], $item['name'], 'save', $e->getMessage(), $e);
        return;
      }
      $id = $result->first()['id'];
    }
    // APIv3
    else {
      $result = civicrm_api($item['entity_type'], 'create', $params);
      if (!empty($result['is_error'])) {
        $this->onApiError($item['module'], $item['name'], 'create', $result['error_message']);
        return;
      }
      $id = $result['id'];
    }

    $dao = new CRM_Core_DAO_Managed();
    // If re-inserting the entity, we'll update instead of create the managed record.
    $dao->id = $item['id'] ?? NULL;
    $dao->module = $item['module'];
    $dao->name = $item['name'];
    $dao->entity_type = $item['entity_type'];
    $dao->entity_id = $id;
    $dao->cleanup = $item['cleanup'] ?? 'always';
    $dao->checksum = $item['declaration_checksum'];
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
        $this->onApiError($item['module'], $item['name'], 'create', $result['error_message']);
        return;
      }
    }
    elseif ($doUpdate && $item['params']['version'] == 4) {
      $params = ['checkPermissions' => FALSE] + $item['params'];
      $idField = CoreUtil::getIdFieldName($item['entity_type']);
      $params['values'][$idField] = $item['entity_id'];
      // Exclude "weight" as that auto-adjusts
      if (CoreUtil::isType($item['entity_type'], 'SortableEntity')) {
        $weightCol = CoreUtil::getInfoItem($item['entity_type'], 'order_by');
        unset($params['values'][$weightCol]);
      }
      // 'match' param doesn't apply to "update" action
      unset($params['match']);
      try {
        civicrm_api4($item['entity_type'], 'update', $params);
      }
      catch (CRM_Core_Exception $e) {
        $this->onApiError($item['module'], $item['name'], 'update', $e->getMessage(), $e);
        return;
      }
    }

    if (isset($item['cleanup']) || $doUpdate) {
      $dao = new CRM_Core_DAO_Managed();
      $dao->id = $item['id'];
      $dao->cleanup = $item['cleanup'] ?? NULL;
      // Reset the `entity_modified_date` timestamp if reverting record.
      $dao->entity_modified_date = $doUpdate ? 'null' : NULL;
      $dao->checksum = $item['declaration_checksum'];
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
        $this->onApiError($item['module'], $item['name'], 'create', $result['error_message']);
        return;
      }
      // Reset the `entity_modified_date` timestamp to indicate that the entity has not been modified by the user.
      $dao = new CRM_Core_DAO_Managed();
      $dao->id = $item['id'];
      $dao->entity_modified_date = 'null';
      $dao->checksum = 'disabled';
      $dao->update();
    }
    else {
      $dao = new CRM_Core_DAO_Managed();
      $dao->id = $item['id'];
      $dao->checksum = 'disabled';
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
        if (!$item['entity_id']) {
          $getRefCount = [];
        }
        elseif (CRM_Core_BAO_Managed::isApi4ManagedType($item['entity_type'])) {
          $getRefCount = CoreUtil::getRefCount($item['entity_type'], $item['entity_id']);
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
      try {
        // APIv4 delete
        if (CRM_Core_BAO_Managed::isApi4ManagedType($item['entity_type'])) {
          civicrm_api4($item['entity_type'], 'delete', [
            'checkPermissions' => FALSE,
            'where' => [['id', '=', $item['entity_id']]],
          ]);
        }
        // APIv3 delete
        else {
          $check = civicrm_api3($item['entity_type'], 'get', ['id' => $item['entity_id']]);
          if ($check['count']) {
            civicrm_api3($item['entity_type'], 'delete', ['id' => $item['entity_id']]);
          }
        }
      }
      catch (CRM_Core_Exception $e) {
        $this->onApiError($item['module'], $item['name'], 'delete', $e->getMessage(), $e);
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
   * @param string $moduleName
   * @param string $managedEntityName
   * @param string $actionName
   * @param string $errorMessage
   * @param Throwable|null $exception
   */
  protected function onApiError(string $moduleName, string $managedEntityName, string $actionName, string $errorMessage, ?Throwable $exception = NULL): void {
    // During install/upgrade this problem might be due to an about-to-be-installed extension
    // So only log the error if it persists outside of upgrade mode
    if (CRM_Core_Config::isUpgradeMode() || defined('CIVI_SETUP')) {
      return;
    }

    $message = sprintf('(%s) Unable to %s managed entity "%s": %s', $moduleName, $actionName, $managedEntityName, $errorMessage);
    $context = $exception ? ['exception' => $exception] : [];
    Civi::log()->error($message, $context);
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
    CRM_Utils_Hook::managed($declarations, $modules);
    $this->validate($declarations);
    // FIXME: Some well-meaning developer added this a long time ago to support associative arrays
    // that use the array index as the declaration name. But it probably never worked, because by the time it gets to this point,
    // lots of implementations of `hook_civicrm_managed()` would have run `$declarations = array_merge($declarations, [...])`
    // which would have reset the indexes.
    // Adding a noisy deprecation notice for now, then we should remove this block:
    foreach ($declarations as $index => $declaration) {
      if (empty($declaration['name'])) {
        CRM_Core_Error::deprecatedWarning(sprintf('Managed entity "%s" declared by extension "%s" without a name.', $index, $declaration['module']));
        $declarations[$index] += ['name' => $index];
      }
    }
    foreach ($declarations as $index => $declaration) {
      $declarations[$index]['checksum'] = $this->computeChecksum($declaration);
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
      // Set to disable or delete if module is disabled or missing - it will be overwritten below if module is active.
      $action = $this->isModuleDisabled($managedEntity['module']) ? 'disable' : 'delete';
      $plan[$key] = array_merge($managedEntity, ['managed_action' => $action]);
      $plan[$key]['checksum'] ??= NULL; /* Pre-upgrade, field may not exist in DB */
    }
    foreach ($declarations as $declaration) {
      $key = "{$declaration['module']}_{$declaration['name']}_{$declaration['entity']}";
      // Set action to update if already managed
      $plan[$key]['managed_action'] = empty($plan[$key]['entity_id']) ? 'create' : 'update';
      $plan[$key]['module'] = $declaration['module'];
      $plan[$key]['name'] = $declaration['name'];
      $plan[$key]['entity_type'] = $declaration['entity'];
      $plan[$key]['params'] = $declaration['params'];
      $plan[$key]['cleanup'] = $declaration['cleanup'] ?? NULL;
      $plan[$key]['update'] = $declaration['update'] ?? 'always';
      $plan[$key]['declaration_checksum'] = $declaration['checksum'];
    }
    return $plan;
  }

}
