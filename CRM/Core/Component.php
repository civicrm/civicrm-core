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
 * Component stores all the static and dynamic information of the various
 * CiviCRM components
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Component {

  /**
   * End part (filename) of the component information class'es name
   * that needs to be present in components main directory.
   */
  const COMPONENT_INFO_CLASS = 'Info';

  /**
   * @param bool $force
   *
   * @return CRM_Core_Component_Info[]
   */
  private static function &_info($force = FALSE) {
    if (!isset(Civi::$statics[__CLASS__]['info']) || $force) {
      Civi::$statics[__CLASS__]['info'] = [];

      foreach (self::getComponents() as $name => $comp) {
        if (self::isEnabled($name)) {
          Civi::$statics[__CLASS__]['info'][$name] = $comp;
        }
      }
    }

    return Civi::$statics[__CLASS__]['info'];
  }

  /**
   * @param string $name
   * @param null $attribute
   *
   * @return mixed
   */
  public static function get($name, $attribute = NULL) {
    $comp = self::_info()[$name] ?? NULL;
    if ($attribute) {
      return $comp->info[$attribute] ?? NULL;
    }
    return $comp;
  }

  /**
   * @param bool $force
   *
   * @return CRM_Core_Component_Info[]
   * @throws CRM_Core_Exception
   */
  public static function &getComponents($force = FALSE) {
    if (!isset(Civi::$statics[__CLASS__]['all']) || $force) {
      Civi::$statics[__CLASS__]['all'] = [];

      $cr = new CRM_Core_DAO_Component();
      $cr->find(FALSE);
      while ($cr->fetch()) {
        $infoClass = $cr->namespace . '_' . self::COMPONENT_INFO_CLASS;
        $infoClassFile = str_replace('_', DIRECTORY_SEPARATOR, $infoClass) . '.php';
        if (!CRM_Utils_File::isIncludable($infoClassFile)) {
          continue;
        }
        require_once $infoClassFile;
        $infoObject = new $infoClass($cr->name, $cr->namespace, $cr->id);
        if ($infoObject->info['name'] !== $cr->name) {
          throw new CRM_Core_Exception("There is a discrepancy between name in component registry and in info file ({$cr->name}).");
        }
        Civi::$statics[__CLASS__]['all'][$cr->name] = $infoObject;
        unset($infoObject);
      }
    }

    return Civi::$statics[__CLASS__]['all'];
  }

  /**
   * @deprecated
   * @return array
   *   Array(string $name => int $id).
   */
  public static function &getComponentIDs() {
    CRM_Core_Error::deprecatedFunctionWarning('getComponents');
    $componentIDs = [];

    $cr = new CRM_Core_DAO_Component();
    $cr->find(FALSE);
    while ($cr->fetch()) {
      $componentIDs[$cr->name] = $cr->id;
    }

    return $componentIDs;
  }

  /**
   * @param bool $force
   *
   * @return CRM_Core_Component_Info[]
   */
  public static function &getEnabledComponents($force = FALSE) {
    return self::_info($force);
  }

  /**
   * @param bool $translated
   *
   * @return array
   */
  public static function &getNames($translated = FALSE) {
    $allComponents = self::getComponents();

    $names = [];
    foreach ($allComponents as $name => $comp) {
      if ($translated) {
        $names[$comp->componentID] = $comp->info['translatedName'];
      }
      else {
        $names[$comp->componentID] = $name;
      }
    }
    return $names;
  }

  /**
   * @param $args
   * @param $type
   *
   * @return bool
   */
  public static function invoke(&$args, $type) {
    $info = self::_info();

    $firstArg = $args[1] ?? '';
    $secondArg = $args[2] ?? '';
    foreach ($info as $name => $comp) {
      if (self::isEnabled($name) &&
        (($comp->info['url'] === $firstArg && $type == 'main') ||
          ($comp->info['url'] === $secondArg && $type == 'admin')
        )
      ) {
        if ($type == 'main') {
          // also set the smarty variables to the current component
          $template = CRM_Core_Smarty::singleton();
          $template->assign('activeComponent', $name);
          if (!empty($comp->info[$name]['formTpl'])) {
            $template->assign('formTpl', $comp->info[$name]['formTpl']);
          }
        }
        $inv = $comp->getInvokeObject();
        $inv->$type($args);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get menu files from all components
   * @return array
   */
  public static function xmlMenu() {
    $info = self::getComponents(TRUE);

    $files = [];
    foreach ($info as $comp) {
      $files = array_merge($files, $comp->menuFiles());
    }

    return $files;
  }

  /**
   * @param string $componentName
   *
   * @return int|null
   */
  public static function getComponentID($componentName) {
    $info = self::getComponents();
    if (!empty($info[$componentName])) {
      return $info[$componentName]->componentID;
    }
    return NULL;
  }

  /**
   * @param int $componentID
   *
   * @return string|null
   */
  public static function getComponentName($componentID) {
    foreach (self::getComponents() as $compName => $component) {
      if ($component->componentID == $componentID) {
        return $compName;
      }
    }
    return NULL;
  }

  /**
   * @return array
   */
  public static function &getQueryFields($checkPermission = TRUE) {
    $info = self::_info();
    $fields = [];
    foreach ($info as $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $flds = $bqr->getFields($checkPermission);
        $fields = array_merge($fields, $flds);
      }
    }
    return $fields;
  }

  /**
   * @param $query
   * @param string $fnName
   */
  public static function alterQuery(&$query, $fnName) {
    $info = self::_info();

    foreach ($info as $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $bqr->$fnName($query);
      }
    }
  }

  /**
   * @param string $fieldName
   * @param $mode
   * @param $side
   *
   * @return null
   */
  public static function from($fieldName, $mode, $side) {
    $info = self::_info();

    $from = NULL;
    foreach ($info as $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $from = $bqr->from($fieldName, $mode, $side);
        if ($from) {
          return $from;
        }
      }
    }
    return $from;
  }

  /**
   * @param $mode
   * @param bool $includeCustomFields
   *
   * @return null
   */
  public static function &defaultReturnProperties(
    $mode,
    $includeCustomFields = TRUE
  ) {
    $info = self::_info();

    $properties = NULL;
    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $properties = $bqr->defaultReturnProperties($mode, $includeCustomFields);
        if ($properties) {
          return $properties;
        }
      }
    }
    if (!$properties) {
      $properties = CRM_Contact_BAO_Query_Hook::singleton()->getDefaultReturnProperties($mode);
    }
    return $properties;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function &buildSearchForm(&$form) {
    $info = self::_info();

    foreach ($info as $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $bqr->buildSearchForm($form);
      }
    }
  }

  /**
   * @param $row
   * @param int $id
   */
  public static function searchAction(&$row, $id) {
    $info = self::_info();

    foreach ($info as $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $bqr->searchAction($row, $id);
      }
    }
  }

  /**
   * Unused function.
   *
   * @return array|null
   *
   * @deprecated
   */
  public static function contactSubTypes() {
    CRM_Core_Error::deprecatedWarning('unused');
    return [];
  }

  /**
   * Unused function.
   *
   * @param string $subType
   * @param string $op
   *
   * @return null|string
   *
   * @deprecated
   */
  public static function contactSubTypeProperties($subType, $op): ?string {
    CRM_Core_Error::deprecatedWarning('unused');
    $properties = self::contactSubTypes();
    if (array_key_exists($subType, $properties) &&
      array_key_exists($op, $properties[$subType])
    ) {
      return $properties[$subType][$op];
    }
    return NULL;
  }

  /**
   * Handle table dependencies of components.
   *
   * @param array $tables
   *   Array of tables.
   *
   */
  public static function tableNames(&$tables) {
    $info = self::_info();

    foreach ($info as $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $bqr->tableNames($tables);
      }
    }
  }

  /**
   * Get components info from info file.
   *
   * @param string $crmFolderDir
   *
   * @return array
   */
  public static function getComponentsFromFile($crmFolderDir) {
    $components = [];
    //traverse CRM folder and check for Info file
    if (is_dir($crmFolderDir) && $dir = opendir($crmFolderDir)) {
      while ($subDir = readdir($dir)) {
        // skip the extensions diretory since it has an Info.php file also
        if ($subDir === 'Extension') {
          continue;
        }

        $infoFile = $crmFolderDir . "/{$subDir}/" . self::COMPONENT_INFO_CLASS . '.php';
        if (file_exists($infoFile)) {
          $infoClass = 'CRM_' . $subDir . '_' . self::COMPONENT_INFO_CLASS;
          $infoObject = new $infoClass(NULL, NULL, NULL);
          $components[$infoObject->info['name']] = $infoObject;
          unset($infoObject);
        }
      }
    }

    return $components;
  }

  /**
   * Is the specified component enabled.
   *
   * @param string $component
   *   Component name - ie CiviMember, CiviContribute, CiviEvent...
   *
   * @return bool
   *   Is the component enabled.
   */
  public static function isEnabled(string $component): bool {
    return in_array($component, Civi::settings()->get('enable_components'), TRUE);
  }

  public static function isIdEnabled(int $id): bool {
    return self::isEnabled(self::getComponentName($id));
  }

  /**
   * Callback for the "enable_components" setting (pre change)
   *
   * Before a component is disabled, disable reverse-dependencies (all extensions dependent on it).
   *
   * This is imperfect because it only goes one-level deep:
   * it doesn't deal with any extensions that depend on the ones being disabled.
   * The proper fix for that would probably be something like a CASCADE mode for
   * disabling an extension with all its reverse dependencies (which would render this function moot).
   *
   * @param array $oldValue
   *   List of component names.
   * @param array $newValue
   *   List of component names.
   *
   * @throws \CRM_Core_Exception.
   */
  public static function preToggleComponents($oldValue, $newValue): void {
    if (is_array($oldValue) && is_array($newValue)) {
      $disabledComponents = array_diff($oldValue, $newValue);
    }
    if (empty($disabledComponents)) {
      return;
    }
    $disabledExtensions = array_map(['CRM_Utils_String', 'convertStringToSnakeCase'], $disabledComponents);
    $manager = CRM_Extension_System::singleton()->getManager();
    $extensions = $manager->getStatuses();
    foreach ($extensions as $extension => $status) {
      if ($status === CRM_Extension_Manager::STATUS_INSTALLED) {
        $info = $manager->mapper->keyToInfo($extension);
        if (array_intersect($info->requires, $disabledExtensions)) {
          static::protectTestEnv(
            fn() => $manager->disable($extension)
          );
        }
      }
    }
  }

  /**
   * Callback for the "enable_components" setting (post change)
   *
   * When a component is enabled or disabled, ensure the corresponding module-extension is also enabled/disabled.
   *
   * @param array $oldValue
   *   List of component names.
   * @param array $newValue
   *   List of component names.
   *
   * @throws \CRM_Core_Exception.
   */
  public static function postToggleComponents($oldValue, $newValue): void {
    if (CRM_Core_Config::isUpgradeMode()) {
      return;
    }
    $manager = CRM_Extension_System::singleton()->getManager();
    $toEnable = $toDisable = [];
    foreach (self::getComponents() as $component) {
      $componentEnabled = in_array($component->name, $newValue);
      $extName = $component->getExtensionName();
      $extensionEnabled = $manager->getStatus($extName) === $manager::STATUS_INSTALLED;
      if ($componentEnabled && !$extensionEnabled) {
        $toEnable[] = $extName;
      }
      elseif (!$componentEnabled && $extensionEnabled) {
        $toDisable[] = $extName;
      }
    }
    if ($toEnable) {
      static::protectTestEnv(
        fn() => CRM_Extension_System::singleton()->getManager()->install($toEnable)
      );
    }
    if ($toDisable) {
      static::protectTestEnv(
        fn() => CRM_Extension_System::singleton()->getManager()->disable($toDisable)
      );
    }
  }

  private static function protectTestEnv(callable $function): void {
    // Blerg. Consider a headless test like this (inspired by flakiness in CRM_Activity_BAO_ActivityTest):
    //
    // function testFoo() {
    //   CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM'];
    //   CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    //   $this->assertTrue(CRM_Core_Permission::check('administer CiviCRM'));
    // }
    //
    // The `enableComponent()` might be a nullop... or it might toggle the `civi_case` extension.
    // Toggling an extension triggers a general reset of many caches/data-structures... including temp perms...

    if (CIVICRM_UF === 'UnitTests') {
      $activePerms = CRM_Core_Config::singleton()->userPermissionClass->permissions;
    }
    try {
      $function();
    }
    finally {
      if (CIVICRM_UF === 'UnitTests') {
        CRM_Core_Config::singleton()->userPermissionClass->permissions = $activePerms;
      }
    }
  }

}
