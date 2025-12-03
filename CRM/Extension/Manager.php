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
 * The extension manager handles installing, disabling enabling, and
 * uninstalling extensions.
 *
 * You should obtain a singleton of this class via
 *
 * $manager = CRM_Extension_System::singleton()->getManager();
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Manager {
  /**
   * The extension is fully installed and enabled.
   */
  const STATUS_INSTALLED = 'installed';

  /**
   * The extension config has been applied to database but deactivated.
   */
  const STATUS_DISABLED = 'disabled';

  /**
   * The extension code is visible, but nothing has been applied to DB
   */
  const STATUS_UNINSTALLED = 'uninstalled';

  /**
   * The extension code is not locally accessible
   */
  const STATUS_UNKNOWN = 'unknown';

  /**
   * The extension is installed but the code is not accessible
   */
  const STATUS_INSTALLED_MISSING = 'installed-missing';

  /**
   * The extension was installed and is now disabled; the code is not accessible
   */
  const STATUS_DISABLED_MISSING = 'disabled-missing';

  /**
   * @var CRM_Extension_Container_Interface
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $fullContainer;

  /**
   * Default container.
   *
   * @var CRM_Extension_Container_Basic|false
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $defaultContainer;

  /**
   * Mapper.
   *
   * @var CRM_Extension_Mapper
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $mapper;

  /**
   * Type managers.
   *
   * @var array
   *
   * Format is (typeName => CRM_Extension_Manager_Interface)
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $typeManagers;

  /**
   * Statuses.
   *
   * @var array
   *
   * Format is (extensionKey => statusConstant)
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $statuses;

  /**
   * Live process(es) per extension.
   *
   * @var array
   *
   * Format is: {
   *   extensionKey => [
   *    ['operation' => 'install|enable|uninstall|disable', 'phase' => 'queued|live|completed'
   *     ...
   *   ],
   *   ...
   * }
   *
   * The inner array is a stack, so the most recent current operation is the
   * last entry. As this manager handles multiple extensions at once, here's
   * the flow for an install operation.
   *
   * $manager->install(['ext1', 'ext2']);
   *
   * 0. {}
   * 1. { ext1: ['install'], ext2: ['install'] }
   * 2. { ext1: ['install', 'installing'], ext2: ['install'] }
   * 3. { ext1: ['install'], ext2: ['install', 'installing'] }
   * 4. { ext1: ['install'], ext2: ['install'] }
   * 5. {}
   */
  protected $processes = [];

  /**
   * Class constructor.
   *
   * @param CRM_Extension_Container_Interface $fullContainer
   * @param CRM_Extension_Container_Basic|false $defaultContainer
   * @param CRM_Extension_Mapper $mapper
   * @param array $typeManagers
   */
  public function __construct(CRM_Extension_Container_Interface $fullContainer, $defaultContainer, CRM_Extension_Mapper $mapper, $typeManagers) {
    $this->fullContainer = $fullContainer;
    $this->defaultContainer = $defaultContainer;
    $this->mapper = $mapper;
    $this->typeManagers = $typeManagers;
  }

  /**
   * Install or upgrade the code for an extension -- and perform any
   * necessary database changes (eg replacing extension metadata).
   *
   * This only works if the extension is stored in the default container.
   *
   * @param string $tmpCodeDir
   *   Path to a local directory containing a copy of the new (inert) code.
   * @param string|null $backupCodeDir
   *   Optionally move the old code to $backupCodeDir
   * @param bool $refresh
   *   Whether to immediately rebuild system caches
   * @return string
   *   The final path where the extension has been loaded.
   * @throws CRM_Extension_Exception
   */
  public function replace($tmpCodeDir, ?string $backupCodeDir = NULL, bool $refresh = TRUE): string {
    if (!$this->defaultContainer) {
      throw new CRM_Extension_Exception("Default extension container is not configured");
    }

    $newInfo = CRM_Extension_Info::loadFromFile($tmpCodeDir . DIRECTORY_SEPARATOR . CRM_Extension_Info::FILENAME);
    $oldStatus = $this->getStatus($newInfo->key);

    // Find $oldInfo, $typeManager
    try {
      [$oldInfo, $typeManager] = $this->_getInfoTypeHandler($newInfo->key);
    }
    catch (CRM_Extension_Exception_MissingException $e) {
      if ($oldStatus === self::STATUS_INSTALLED_MISSING || $oldStatus === self::STATUS_DISABLED_MISSING) {
        [$oldInfo, $typeManager] = $this->_getMissingInfoTypeHandler($newInfo->key);
      }
      else {
        $oldInfo = NULL;
        $typeManager = $this->typeManagers[$newInfo->type];
      }
    }

    // find $tgtPath
    try {
      // We prefer to put the extension in the same place (where it already exists).
      $tgtPath = $this->fullContainer->getPath($newInfo->key);
    }
    catch (CRM_Extension_Exception_MissingException $e) {
      // the extension does not exist in any container; we're free to put it anywhere
      $tgtPath = $this->defaultContainer->getBaseDir() . DIRECTORY_SEPARATOR . $newInfo->key;
    }
    if (!CRM_Utils_File::isChildPath($this->defaultContainer->getBaseDir(), $tgtPath, FALSE)) {
      // But if we don't control the folder, then force installation in the default-container
      $oldPath = $tgtPath;
      $tgtPath = $this->defaultContainer->getBaseDir() . DIRECTORY_SEPARATOR . $newInfo->key;
      CRM_Core_Session::setStatus(ts('A copy of the extension (%1) is in a system folder (%2). The system copy will be preserved, but the new copy will be used.', [
        1 => $newInfo->key,
        2 => $oldPath,
      ]), '', 'alert', ['expires' => 0]);
    }

    if ($backupCodeDir && is_dir($tgtPath)) {
      if (!rename($tgtPath, $backupCodeDir)) {
        throw new CRM_Extension_Exception("Failed to move $tgtPath to backup $backupCodeDir");
      }
    }

    // move the code!
    switch ($oldStatus) {
      case self::STATUS_UNINSTALLED:
      case self::STATUS_UNKNOWN:
        // There are no DB records to worry about, so we'll just put the files in place
        if (!CRM_Utils_File::replaceDir($tmpCodeDir, $tgtPath)) {
          throw new CRM_Extension_Exception("Failed to move $tmpCodeDir to $tgtPath");
        }
        break;

      case self::STATUS_INSTALLED:
      case self::STATUS_INSTALLED_MISSING:
      case self::STATUS_DISABLED:
      case self::STATUS_DISABLED_MISSING:
        // There are DB records; coordinate the file placement with the DB updates
        $typeManager->onPreReplace($oldInfo, $newInfo);
        if (!CRM_Utils_File::replaceDir($tmpCodeDir, $tgtPath)) {
          throw new CRM_Extension_Exception("Failed to move $tmpCodeDir to $tgtPath");
        }
        $this->_updateExtensionEntry($newInfo);
        $typeManager->onPostReplace($oldInfo, $newInfo);
        break;

      default:
        throw new CRM_Extension_Exception("Cannot install or enable extension: {$newInfo->key}");
    }

    if ($refresh) {
      $this->refresh();
      // It might be useful to reset the container, but (given dev/core#3686) that's not likely to do much.
      // \Civi::reset();
      // \CRM_Core_Config::singleton(TRUE, TRUE);
      Civi::rebuild(['*' => TRUE, 'sessions' => FALSE])->execute();
    }

    return $tgtPath;
  }

  /**
   * Add records of the extension to the database -- and enable it
   *
   * @param string|array $keys
   *   One or more extension keys.
   * @param string $mode install|enable
   * @throws CRM_Extension_Exception
   */
  public function install($keys, $mode = 'install') {
    $keys = (array) $keys;
    while ($keys) {
      $this->_install($keys, $mode);
      $keys = $this->findInstallableSubmodules();
    }
  }

  private function _install(array $keys, $mode = 'install') {
    $origStatuses = $this->getStatuses();

    // TODO: to mitigate the risk of crashing during installation, scan
    // keys/statuses/types before doing anything

    // Check compatibility
    $incompatible = [];
    foreach ($keys as $key) {
      if ($this->isIncompatible($key)) {
        $incompatible[] = $key;
      }
    }
    if ($incompatible) {
      throw new CRM_Extension_Exception('Cannot install incompatible extension: ' . implode(', ', $incompatible));
    }

    // Keep state for these operations.
    $this->addProcess($keys, $mode);

    foreach ($keys as $key) {
      /** @var CRM_Extension_Info $info */
      /** @var CRM_Extension_Manager_Base $typeManager */
      [$info, $typeManager] = $this->_getInfoTypeHandler($key);

      switch ($origStatuses[$key]) {
        case self::STATUS_INSTALLED:
          // ok, nothing to do. As such the status of this process is no longer
          // 'install' install was the intent, which might have resulted in
          // changes but these changes will not be happening, so processes that
          // are sensitive to installs (like the managed entities reconcile
          // operation) should not assume that these changes have happened.
          $this->popProcess([$key]);
          break;

        case self::STATUS_DISABLED:
          // re-enable it
          $this->addProcess([$key], 'enabling');
          $typeManager->onPreEnable($info);
          $this->_setExtensionActive($info, 1);
          $typeManager->onPostEnable($info);

          // A full refresh would be preferrable but very slow. This at least allows
          // later extensions to access classes from earlier extensions.
          $this->statuses = NULL;
          $this->mapper->refresh();

          $this->popProcess([$key]);
          break;

        case self::STATUS_UNINSTALLED:
          // install anew
          $this->addProcess([$key], 'installing');
          $typeManager->onPreInstall($info);
          $this->_createExtensionEntry($info);
          $typeManager->onPostInstall($info);

          // A full refresh would be preferrable but very slow. This at least allows
          // later extensions to access classes from earlier extensions.
          $this->statuses = NULL;
          $this->mapper->refresh();

          $this->popProcess([$key]);
          break;

        case self::STATUS_UNKNOWN:
        default:
          throw new CRM_Extension_Exception("Cannot install or enable extension: $key");
      }
    }

    $this->statuses = NULL;
    $this->mapper->refresh();
    if (!CRM_Core_Config::isUpgradeMode()) {
      \Civi::reset();
      \CRM_Core_Config::singleton(TRUE, TRUE);
      Civi::rebuild(['*' => TRUE, 'sessions' => FALSE])->execute();

      $schema = new CRM_Logging_Schema();
      $schema->fixSchemaDifferences();
    }
    foreach ($keys as $key) {
      // throws Exception
      [$info, $typeManager] = $this->_getInfoTypeHandler($key);

      switch ($origStatuses[$key]) {
        case self::STATUS_INSTALLED:
          // ok, nothing to do
          break;

        case self::STATUS_DISABLED:
          // re-enable it
          break;

        case self::STATUS_UNINSTALLED:
          // install anew
          $this->addProcess([$key], 'installing');
          $typeManager->onPostPostInstall($info);
          $this->popProcess([$key]);
          break;

        case self::STATUS_UNKNOWN:
        default:
          throw new CRM_Extension_Exception("Cannot install or enable extension: $key");
      }
    }

    // All processes for these keys
    $this->popProcess($keys);
  }

  /**
   * Add records of the extension to the database -- and enable it
   *
   * @param array $keys
   *   List of extension keys.
   * @throws CRM_Extension_Exception
   */
  public function enable($keys) {
    $this->install($keys, 'enable');
  }

  /**
   * Disable extension without removing record from db.
   *
   * @param string|array $keys
   *   One or more extension keys.
   * @throws CRM_Extension_Exception
   */
  public function disable($keys) {
    $keys = (array) $keys;
    $origStatuses = $this->getStatuses();

    // TODO: to mitigate the risk of crashing during installation, scan
    // keys/statuses/types before doing anything

    sort($keys);
    $disableRequirements = $this->findDisableRequirements($keys);

    $requiredExtensions = $this->mapper->getKeysByTag('mgmt:required');
    $submodules = $this->mapper->getKeysByTag('mgmt:enable-when-satisfied');
    $blockedRequirements = array_diff($disableRequirements, $submodules, $keys);

    // This munges order, but makes it comparable.
    sort($blockedRequirements);
    if ($blockedRequirements) {
      throw new CRM_Extension_Exception_DependencyException("Cannot disable extension due to dependencies. Consider disabling all these: " . implode(',', $blockedRequirements));
    }

    // Nothing blocked -- so we have requested $keys and implied submodules. Uninstall in topological order.
    $keys = $disableRequirements;

    $this->addProcess($keys, 'disable');

    foreach ($keys as $key) {
      if (isset($origStatuses[$key])) {
        if (in_array($key, $requiredExtensions)) {
          throw new CRM_Extension_Exception("Cannot disable required extension: $key");
        }

        switch ($origStatuses[$key]) {
          case self::STATUS_INSTALLED:
            $this->addProcess([$key], 'disabling');
            // throws Exception
            [$info, $typeManager] = $this->_getInfoTypeHandler($key);
            $typeManager->onPreDisable($info);
            $this->_setExtensionActive($info, 0);
            $typeManager->onPostDisable($info);
            $this->popProcess([$key]);
            break;

          case self::STATUS_INSTALLED_MISSING:
            // throws Exception
            [$info, $typeManager] = $this->_getMissingInfoTypeHandler($key);
            $typeManager->onPreDisable($info);
            $this->_setExtensionActive($info, 0);
            $typeManager->onPostDisable($info);
            break;

          case self::STATUS_DISABLED:
          case self::STATUS_DISABLED_MISSING:
          case self::STATUS_UNINSTALLED:
            // ok, nothing to do
            // Remove the 'disable' process as we're not doing that.
            $this->popProcess([$key]);
            break;

          case self::STATUS_UNKNOWN:
          default:
            throw new CRM_Extension_Exception("Cannot disable unknown extension: $key");
        }
      }
      else {
        throw new CRM_Extension_Exception("Cannot disable unknown extension: $key");
      }
    }

    $this->statuses = NULL;
    $this->mapper->refresh();
    \Civi::reset();
    \CRM_Core_Config::singleton(TRUE, TRUE);
    Civi::rebuild(['*' => TRUE, 'sessions' => FALSE])->execute();

    $this->popProcess($keys);
  }

  /**
   * Remove all database references to an extension.
   *
   * @param string|array $keys
   *   One or more extension keys.
   * @throws CRM_Extension_Exception
   */
  public function uninstall($keys) {
    $keys = (array) $keys;
    $origStatuses = $this->getStatuses();

    // TODO: to mitigate the risk of crashing during installation, scan
    // keys/statuses/types before doing anything

    $keys = array_unique(array_merge($this->findChildSubmodules($keys), $keys));

    // Component data still lives inside of core-core. Uninstalling is nonsensical.
    $notUninstallable = array_intersect($keys, $this->mapper->getKeysByTag('component'));
    if (count($notUninstallable)) {
      throw new CRM_Extension_Exception("Cannot uninstall extensions which are tagged as components: " . implode(', ', $notUninstallable));
    }

    $this->addProcess($keys, 'uninstall');

    foreach ($keys as $key) {
      switch ($origStatuses[$key]) {
        case self::STATUS_INSTALLED:
        case self::STATUS_INSTALLED_MISSING:
          throw new CRM_Extension_Exception("Cannot uninstall extension; disable it first: $key");

        case self::STATUS_DISABLED:
          $this->addProcess([$key], 'uninstalling');
          // throws Exception
          [$info, $typeManager] = $this->_getInfoTypeHandler($key);
          $typeManager->onPreUninstall($info);
          $this->_removeExtensionEntry($info);
          $typeManager->onPostUninstall($info);
          break;

        case self::STATUS_DISABLED_MISSING:
          // throws Exception
          [$info, $typeManager] = $this->_getMissingInfoTypeHandler($key);
          $typeManager->onPreUninstall($info);
          $this->_removeExtensionEntry($info);
          $typeManager->onPostUninstall($info);
          break;

        case self::STATUS_UNINSTALLED:
          // ok, nothing to do
          // remove the 'uninstall' process since we're not doing that.
          $this->popProcess([$key]);
          break;

        case self::STATUS_UNKNOWN:
        default:
          throw new CRM_Extension_Exception("Cannot disable unknown extension: $key");
      }
    }

    $this->statuses = NULL;
    $this->mapper->refresh();
    // At the analogous step of `install()` or `disable()`, it would reset the container.
    // But here, the extension goes from "disabled=>uninstall". All we really need is to reconcile mgd's.
    Civi::rebuild(['*' => TRUE, 'sessions' => FALSE])->execute();
    $this->popProcess($keys);
  }

  /**
   * Determine the status of an extension.
   *
   * @param $key
   *
   * @return string
   *   constant self::STATUS_*
   */
  public function getStatus($key) {
    $statuses = $this->getStatuses();
    if (array_key_exists($key, $statuses)) {
      return $statuses[$key];
    }
    else {
      return self::STATUS_UNKNOWN;
    }
  }

  /**
   * Check if a given extension is installed and enabled
   *
   * @param $key
   *
   * @return bool
   */
  public function isEnabled($key) {
    return ($this->getStatus($key) === self::STATUS_INSTALLED);
  }

  /**
   * Check if a given extension is incompatible with this version of CiviCRM
   *
   * @param $key
   * @return bool|array
   */
  public function isIncompatible($key) {
    $info = CRM_Extension_System::getCompatibilityInfo();
    return $info[$key] ?? FALSE;
  }

  /**
   * Determine the status of all extensions.
   *
   * @return array
   *   ($key => status_constant)
   */
  public function getStatuses() {
    if (!is_array($this->statuses)) {
      $compat = CRM_Extension_System::getCompatibilityInfo();

      $this->statuses = [];

      foreach ($this->fullContainer->getKeys() as $key) {
        $this->statuses[$key] = self::STATUS_UNINSTALLED;
      }

      $sql = '
        SELECT full_name, is_active
        FROM civicrm_extension
      ';
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        try {
          $path = $this->fullContainer->getPath($dao->full_name);
          $codeExists = !empty($path) && is_dir($path);
        }
        catch (CRM_Extension_Exception $e) {
          $codeExists = FALSE;
        }
        if (!empty($compat[$dao->full_name]['force-uninstall'])) {
          $this->statuses[$dao->full_name] = self::STATUS_UNINSTALLED;
        }
        elseif ($dao->is_active) {
          $this->statuses[$dao->full_name] = $codeExists ? self::STATUS_INSTALLED : self::STATUS_INSTALLED_MISSING;
        }
        else {
          $this->statuses[$dao->full_name] = $codeExists ? self::STATUS_DISABLED : self::STATUS_DISABLED_MISSING;
        }
      }
    }
    return $this->statuses;
  }

  public function refresh() {
    $this->statuses = NULL;
    // and, indirectly, defaultContainer
    $this->fullContainer->refresh();
    $this->mapper->refresh();
  }

  /**
   * Return current processes for given extension.
   *
   * @param string $key extension key
   *
   * @return array
   */
  public function getActiveProcesses(string $key) :Array {
    return $this->processes[$key] ?? [];
  }

  /**
   * Determine if the extension specified is currently involved in an install
   * or enable process. Just sugar code to make things more readable.
   *
   * @param string $key extension key
   *
   * @return bool
   */
  public function extensionIsBeingInstalledOrEnabled($key) :bool {
    foreach ($this->getActiveProcesses($key) as $process) {
      if (in_array($process, ['install', 'installing', 'enable', 'enabling'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  // ----------------------

  /**
   * Find the $info and $typeManager for a $key
   *
   * @param $key
   *
   * @throws CRM_Extension_Exception
   * @return array
   *   [CRM_Extension_Info, CRM_Extension_Manager_Interface]
   */
  private function _getInfoTypeHandler($key) {
    // throws Exception
    $info = $this->mapper->keyToInfo($key);
    if (array_key_exists($info->type, $this->typeManagers)) {
      return [$info, $this->typeManagers[$info->type]];
    }
    else {
      throw new CRM_Extension_Exception("Unrecognized extension type: " . $info->type);
    }
  }

  /**
   * Find the $info and $typeManager for a $key
   *
   * @param $key
   *
   * @throws CRM_Extension_Exception
   * @return array
   *   [CRM_Extension_Info, CRM_Extension_Manager_Interface]
   */
  private function _getMissingInfoTypeHandler($key) {
    $info = $this->createInfoFromDB($key);
    if ($info) {
      if (array_key_exists($info->type, $this->typeManagers)) {
        return [$info, $this->typeManagers[$info->type]];
      }
      else {
        throw new CRM_Extension_Exception("Unrecognized extension type: " . $info->type);
      }
    }
    else {
      throw new CRM_Extension_Exception("Failed to reconstruct missing extension: " . $key);
    }
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @return bool
   */
  private function _createExtensionEntry(CRM_Extension_Info $info) {
    $dao = new CRM_Core_DAO_Extension();
    $dao->label = $info->label;
    $dao->name = $info->name;
    $dao->full_name = $info->key;
    $dao->type = $info->type;
    $dao->file = $info->file;
    $dao->is_active = 1;
    return (bool) ($dao->insert());
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @return bool
   */
  private function _updateExtensionEntry(CRM_Extension_Info $info) {
    $dao = new CRM_Core_DAO_Extension();
    $dao->full_name = $info->key;
    if ($dao->find(TRUE)) {
      $dao->label = $info->label;
      $dao->name = $info->name;
      $dao->full_name = $info->key;
      $dao->type = $info->type;
      $dao->file = $info->file;
      $dao->is_active = 1;
      return (bool) ($dao->update());
    }
    else {
      return $this->_createExtensionEntry($info);
    }
  }

  /**
   * @param CRM_Extension_Info $info
   *
   * @throws CRM_Extension_Exception
   */
  private function _removeExtensionEntry(CRM_Extension_Info $info) {
    $dao = new CRM_Core_DAO_Extension();
    $dao->full_name = $info->key;
    if ($dao->find(TRUE)) {
      try {
        CRM_Core_BAO_Extension::deleteRecord(['id' => $dao->id]);
      }
      catch (CRM_Core_Exception $e) {
        throw new CRM_Extension_Exception("Failed to remove extension entry $dao->id");
      }
    } // else: post-condition already satisified
  }

  /**
   * @param CRM_Extension_Info $info
   * @param $isActive
   */
  private function _setExtensionActive(CRM_Extension_Info $info, $isActive) {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_extension SET is_active = %1 where full_name = %2', [
      1 => [$isActive, 'Integer'],
      2 => [$info->key, 'String'],
    ]);
  }

  /**
   * Auto-generate a place-holder for a missing extension using info from
   * database.
   *
   * @param $key
   * @return CRM_Extension_Info|NULL
   */
  public function createInfoFromDB($key) {
    // System hasn't booted - and extension is missing. Need low-tech/no-hook SELECT to learn more about what's missing.
    $select = CRM_Utils_SQL_Select::from('civicrm_extension')
      ->where('full_name = @key', ['key' => $key])
      ->select('full_name, type, name, label, file');
    $dao = $select->execute();
    if ($dao->fetch()) {
      $info = new CRM_Extension_Info($dao->full_name, $dao->type, $dao->name, $dao->label, $dao->file);
      return $info;
    }
    else {
      return NULL;
    }
  }

  /**
   * Build a list of extensions to install, in an order that will satisfy dependencies.
   *
   * @param array $keys
   *   List of extensions to install.
   * @param \CRM_Extension_Info|CRM_Extension_Info[]|null $newInfos
   *   An extension info object that we should use instead of our local versions (eg. when checking for upgradeability).
   *
   * @return array
   *   List of extension keys, including dependencies, in order of installation.
   * @throws \CRM_Extension_Exception
   * @throws \MJS\TopSort\CircularDependencyException
   * @throws \MJS\TopSort\ElementNotFoundException
   */
  public function findInstallRequirements($keys, $newInfos = NULL) {
    if (is_object($newInfos)) {
      $infos[$newInfos->key] = $newInfos;
    }
    elseif (is_array($newInfos)) {
      $infos = $newInfos;
    }
    else {
      $infos = $this->mapper->getAllInfos();
    }
    // array(string $key).
    $todoKeys = array_unique($keys);
    // array(string $key => 1);
    $doneKeys = [];
    $sorter = new \MJS\TopSort\Implementations\FixedArraySort();

    while (!empty($todoKeys)) {
      $key = array_shift($todoKeys);
      if (isset($doneKeys[$key])) {
        continue;
      }
      $doneKeys[$key] = 1;

      /** @var CRM_Extension_Info $info */
      $info = @$infos[$key];

      if ($info && $info->requires) {
        $sorter->add($key, $info->requires);
        $todoKeys = array_merge($todoKeys, $info->requires);
      }
      else {
        $sorter->add($key, []);
      }
    }
    return $sorter->sort();
  }

  public function checkInstallRequirements(array $installKeys, $newInfos = NULL): array {
    $errors = [];
    $requiredExtensions = $this->findInstallRequirements($installKeys, $newInfos);
    $installKeysSummary = implode(',', $requiredExtensions);
    foreach ($requiredExtensions as $extension) {
      if ($this->getStatus($extension) !== CRM_Extension_Manager::STATUS_INSTALLED && !in_array($extension, $installKeys)) {
        $requiredExtensionInfo = CRM_Extension_System::singleton()->getBrowser()->getExtension($extension);
        $requiredExtensionInfoName = empty($requiredExtensionInfo->name) ? $extension : $requiredExtensionInfo->name;
        $errors[] = [
          'title' => ts('Missing Requirement: %1', [1 => $extension]),
          'message' => ts('You will not be able to install/upgrade %1 until you have installed the %2 extension.', [1 => $installKeysSummary, 2 => $requiredExtensionInfoName]),
        ];
      }
    }
    return $errors;
  }

  /**
   * Build a list of extensions to remove, in an order that will satisfy dependencies.
   *
   * @param array $keys
   *   List of extensions to install.
   * @return array
   *   List of extension keys, including dependencies, in order of removal.
   */
  public function findDisableRequirements($keys) {
    $INSTALLED = [
      self::STATUS_INSTALLED,
      self::STATUS_INSTALLED_MISSING,
    ];
    $installedInfos = $this->filterInfosByStatus($this->mapper->getAllInfos(), $INSTALLED);
    $revMap = CRM_Extension_Info::buildReverseMap($installedInfos);
    $todoKeys = array_unique($keys);
    $doneKeys = [];
    $sorter = new \MJS\TopSort\Implementations\FixedArraySort();

    while (!empty($todoKeys)) {
      $key = array_shift($todoKeys);
      if (isset($doneKeys[$key])) {
        continue;
      }
      $doneKeys[$key] = 1;

      if (isset($revMap[$key])) {
        $requiredBys = CRM_Utils_Array::collect('key',
          $this->filterInfosByStatus($revMap[$key], $INSTALLED));
        $sorter->add($key, $requiredBys);
        $todoKeys = array_merge($todoKeys, $requiredBys);
      }
      else {
        $sorter->add($key, []);
      }
    }
    return $sorter->sort();
  }

  /**
   * Get a list of submodules that are ready to be installed.
   *
   * @return array
   * @throws \CRM_Extension_Exception
   */
  protected function findInstallableSubmodules(): array {
    return array_filter(
      $this->mapper->getKeysByTag('mgmt:enable-when-satisfied'),
      fn($m) => !$this->isEnabled($m) && $this->mapper->keyToInfo($m)->isInstallable()
    );
  }

  /**
   * Find the immediate children for some list of extensions.
   *
   * @param string|array $parents
   *   List of extensions. For each, we want to know about its children.
   * @return array
   *   List of children
   * @throws \CRM_Extension_Exception
   */
  protected function findChildSubmodules($parents): array {
    $parents = (array) $parents;
    return array_filter(
      $this->mapper->getKeysByTag('mgmt:enable-when-satisfied'),
      fn($child) => in_array($this->mapper->keyToInfo($child)->parent, $parents)
    );
  }

  /**
   * Provides way to set processes property for phpunit tests - not for general use.
   *
   * @param $processes
   */
  public function setProcessesForTesting(array $processes) {
    $this->processes = $processes;
  }

  /**
   * @param $infos
   * @param $filterStatuses
   * @return array
   */
  protected function filterInfosByStatus($infos, $filterStatuses) {
    $matches = [];
    foreach ($infos as $k => $v) {
      if (in_array($this->getStatus($v->key), $filterStatuses)) {
        $matches[$k] = $v;
      }
    }
    return $matches;
  }

  /**
   * Add a process to the stacks for the extensions.
   *
   * @param array $keys extensionKey
   * @param string $process one of: install|uninstall|enable|disable|installing|uninstalling|enabling|disabling
   */
  protected function addProcess(array $keys, string $process) {
    foreach ($keys as $key) {
      $this->processes[$key][] = $process;
    }
  }

  /**
   * Pop the top op from the stacks for the extensions.
   *
   * @param array $keys extensionKey
   */
  protected function popProcess(array $keys) {
    foreach ($keys as $key) {
      if (!empty($this->process[$key])) {
        array_pop($this->process[$key]);
      }
    }
  }

}
