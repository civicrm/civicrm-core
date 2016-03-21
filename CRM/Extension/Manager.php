<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * The extension manager handles installing, disabling enabling, and
 * uninstalling extensions.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
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
   * The extension is fully installed and enabled
   */
  const STATUS_INSTALLED_MISSING = 'installed-missing';

  /**
   * The extension is fully installed and enabled
   */
  const STATUS_DISABLED_MISSING = 'disabled-missing';

  /**
   * @var CRM_Extension_Container_Interface
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $fullContainer;

  /**
   * @var CRM_Extension_Container_Basic|FALSE
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $defaultContainer;

  /**
   * @var CRM_Extension_Mapper
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $mapper;

  /**
   * @var array (typeName => CRM_Extension_Manager_Interface)
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $typeManagers;

  /**
   * @var array (extensionKey => statusConstant)
   *
   * Note: Treat as private. This is only public to facilitate debugging.
   */
  public $statuses;

  /**
   * @param CRM_Extension_Container_Interface $fullContainer
   * @param CRM_Extension_Container_Basic|FALSE $defaultContainer
   * @param CRM_Extension_Mapper $mapper
   * @param $typeManagers
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
   * @throws CRM_Extension_Exception
   */
  public function replace($tmpCodeDir) {
    if (!$this->defaultContainer) {
      throw new CRM_Extension_Exception("Default extension container is not configured");
    }

    $newInfo = CRM_Extension_Info::loadFromFile($tmpCodeDir . DIRECTORY_SEPARATOR . CRM_Extension_Info::FILENAME);
    $oldStatus = $this->getStatus($newInfo->key);

    // find $tgtPath, $oldInfo, $typeManager
    switch ($oldStatus) {
      case self::STATUS_UNINSTALLED:
      case self::STATUS_INSTALLED:
      case self::STATUS_DISABLED:
        // There is an old copy of the extension. Try to install in the same place -- but it must go somewhere in the default-container
        list ($oldInfo, $typeManager) = $this->_getInfoTypeHandler($newInfo->key); // throws Exception
        $tgtPath = $this->fullContainer->getPath($newInfo->key);
        if (!CRM_Utils_File::isChildPath($this->defaultContainer->getBaseDir(), $tgtPath)) {
          // force installation in the default-container
          $oldPath = $tgtPath;
          $tgtPath = $this->defaultContainer->getBaseDir() . DIRECTORY_SEPARATOR . $newInfo->key;
          CRM_Core_Session::setStatus(ts('A copy of the extension (%1) is in a system folder (%2). The system copy will be preserved, but the new copy will be used.', array(
            1 => $newInfo->key,
            2 => $oldPath,
          )));
        }
        break;

      case self::STATUS_INSTALLED_MISSING:
      case self::STATUS_DISABLED_MISSING:
        // the extension does not exist in any container; we're free to put it anywhere
        $tgtPath = $this->defaultContainer->getBaseDir() . DIRECTORY_SEPARATOR . $newInfo->key;
        list ($oldInfo, $typeManager) = $this->_getMissingInfoTypeHandler($newInfo->key); // throws Exception
        break;

      case self::STATUS_UNKNOWN:
        // the extension does not exist in any container; we're free to put it anywhere
        $tgtPath = $this->defaultContainer->getBaseDir() . DIRECTORY_SEPARATOR . $newInfo->key;
        $oldInfo = $typeManager = NULL;
        break;

      default:
        throw new CRM_Extension_Exception("Cannot install or enable extension: {$newInfo->key}");
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

    $this->refresh();
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  }

  /**
   * Add records of the extension to the database -- and enable it
   *
   * @param array $keys
   *   List of extension keys.
   * @throws CRM_Extension_Exception
   */
  public function install($keys) {
    $origStatuses = $this->getStatuses();

    // TODO: to mitigate the risk of crashing during installation, scan
    // keys/statuses/types before doing anything

    foreach ($keys as $key) {
      list ($info, $typeManager) = $this->_getInfoTypeHandler($key); // throws Exception

      switch ($origStatuses[$key]) {
        case self::STATUS_INSTALLED:
          // ok, nothing to do
          break;

        case self::STATUS_DISABLED:
          // re-enable it
          $typeManager->onPreEnable($info);
          $this->_setExtensionActive($info, 1);
          $typeManager->onPostEnable($info);
          break;

        case self::STATUS_UNINSTALLED:
          // install anew
          $typeManager->onPreInstall($info);
          $this->_createExtensionEntry($info);
          $typeManager->onPostInstall($info);
          break;

        case self::STATUS_UNKNOWN:
        default:
          throw new CRM_Extension_Exception("Cannot install or enable extension: $key");
      }
    }

    $this->statuses = NULL;
    $this->mapper->refresh();
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
    $schema = new CRM_Logging_Schema();
    $schema->fixSchemaDifferences();

    foreach ($keys as $key) {
      list ($info, $typeManager) = $this->_getInfoTypeHandler($key); // throws Exception

      switch ($origStatuses[$key]) {
        case self::STATUS_INSTALLED:
          // ok, nothing to do
          break;

        case self::STATUS_DISABLED:
          // re-enable it
          break;

        case self::STATUS_UNINSTALLED:
          // install anew
          $typeManager->onPostPostInstall($info);
          break;

        case self::STATUS_UNKNOWN:
        default:
          throw new CRM_Extension_Exception("Cannot install or enable extension: $key");
      }
    }

  }

  /**
   * Add records of the extension to the database -- and enable it
   *
   * @param array $keys
   *   List of extension keys.
   * @throws CRM_Extension_Exception
   */
  public function enable($keys) {
    $this->install($keys);
  }

  /**
   * Add records of the extension to the database -- and enable it
   *
   * @param array $keys
   *   List of extension keys.
   * @throws CRM_Extension_Exception
   */
  public function disable($keys) {
    $origStatuses = $this->getStatuses();

    // TODO: to mitigate the risk of crashing during installation, scan
    // keys/statuses/types before doing anything

    foreach ($keys as $key) {
      switch ($origStatuses[$key]) {
        case self::STATUS_INSTALLED:
          list ($info, $typeManager) = $this->_getInfoTypeHandler($key); // throws Exception
          $typeManager->onPreDisable($info);
          $this->_setExtensionActive($info, 0);
          $typeManager->onPostDisable($info);
          break;

        case self::STATUS_INSTALLED_MISSING:
          list ($info, $typeManager) = $this->_getMissingInfoTypeHandler($key); // throws Exception
          $typeManager->onPreDisable($info);
          $this->_setExtensionActive($info, 0);
          $typeManager->onPostDisable($info);
          break;

        case self::STATUS_DISABLED:
        case self::STATUS_DISABLED_MISSING:
        case self::STATUS_UNINSTALLED:
          // ok, nothing to do
          break;

        case self::STATUS_UNKNOWN:
        default:
          throw new CRM_Extension_Exception("Cannot disable unknown extension: $key");
      }
    }

    $this->statuses = NULL;
    $this->mapper->refresh();
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  }

  /**
   * Remove all database references to an extension.
   *
   * Add records of the extension to the database -- and enable it
   *
   * @param array $keys
   *   List of extension keys.
   * @throws CRM_Extension_Exception
   */
  public function uninstall($keys) {
    $origStatuses = $this->getStatuses();

    // TODO: to mitigate the risk of crashing during installation, scan
    // keys/statuses/types before doing anything

    foreach ($keys as $key) {
      switch ($origStatuses[$key]) {
        case self::STATUS_INSTALLED:
        case self::STATUS_INSTALLED_MISSING:
          throw new CRM_Extension_Exception("Cannot uninstall extension; disable it first: $key");

        case self::STATUS_DISABLED:
          list ($info, $typeManager) = $this->_getInfoTypeHandler($key); // throws Exception
          $typeManager->onPreUninstall($info);
          $this->_removeExtensionEntry($info);
          $typeManager->onPostUninstall($info);
          break;

        case self::STATUS_DISABLED_MISSING:
          list ($info, $typeManager) = $this->_getMissingInfoTypeHandler($key); // throws Exception
          $typeManager->onPreUninstall($info);
          $this->_removeExtensionEntry($info);
          $typeManager->onPostUninstall($info);
          break;

        case self::STATUS_UNINSTALLED:
          // ok, nothing to do
          break;

        case self::STATUS_UNKNOWN:
        default:
          throw new CRM_Extension_Exception("Cannot disable unknown extension: $key");
      }
    }

    $this->statuses = NULL;
    $this->mapper->refresh();
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE);
  }

  /**
   * Determine the status of an extension.
   *
   * @param $key
   *
   * @return string
   *   constant (STATUS_INSTALLED, STATUS_DISABLED, STATUS_UNINSTALLED, STATUS_UNKNOWN)
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
   * Determine the status of all extensions.
   *
   * @return array
   *   ($key => status_constant)
   */
  public function getStatuses() {
    if (!is_array($this->statuses)) {
      $this->statuses = array();

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
        if ($dao->is_active) {
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
    $this->fullContainer->refresh(); // and, indirectly, defaultContainer
    $this->mapper->refresh();
  }

  // ----------------------

  /**
   * Find the $info and $typeManager for a $key
   *
   * @param $key
   *
   * @throws CRM_Extension_Exception
   * @return array
   *   (0 => CRM_Extension_Info, 1 => CRM_Extension_Manager_Interface)
   */
  private function _getInfoTypeHandler($key) {
    $info = $this->mapper->keyToInfo($key); // throws Exception
    if (array_key_exists($info->type, $this->typeManagers)) {
      return array($info, $this->typeManagers[$info->type]);
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
   *   (0 => CRM_Extension_Info, 1 => CRM_Extension_Manager_Interface)
   */
  private function _getMissingInfoTypeHandler($key) {
    $info = $this->createInfoFromDB($key);
    if ($info) {
      if (array_key_exists($info->type, $this->typeManagers)) {
        return array($info, $this->typeManagers[$info->type]);
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
      if (CRM_Core_BAO_Extension::del($dao->id)) {
        CRM_Core_Session::setStatus(ts('Selected option value has been deleted.'), ts('Deleted'), 'success');
      }
      else {
        throw new CRM_Extension_Exception("Failed to remove extension entry");
      }
    } // else: post-condition already satisified
  }

  /**
   * @param CRM_Extension_Info $info
   * @param $isActive
   */
  private function _setExtensionActive(CRM_Extension_Info $info, $isActive) {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_extension SET is_active = %1 where full_name = %2', array(
      1 => array($isActive, 'Integer'),
      2 => array($info->key, 'String'),
    ));
  }

  /**
   * Auto-generate a place-holder for a missing extension using info from
   * database.
   *
   * @param $key
   * @return CRM_Extension_Info|NULL
   */
  public function createInfoFromDB($key) {
    $dao = new CRM_Core_DAO_Extension();
    $dao->full_name = $key;
    if ($dao->find(TRUE)) {
      $info = new CRM_Extension_Info($dao->full_name, $dao->type, $dao->name, $dao->label, $dao->file);
      return $info;
    }
    else {
      return NULL;
    }
  }

}
