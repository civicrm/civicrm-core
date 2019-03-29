<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Component stores all the static and dynamic information of the various
 * CiviCRM components
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */
class CRM_Core_Component {

  /**
   * End part (filename) of the component information class'es name
   * that needs to be present in components main directory.
   */
  const COMPONENT_INFO_CLASS = 'Info';

  static $_contactSubTypes = NULL;

  /**
   * @param bool $force
   *
   * @return CRM_Core_Component_Info[]
   */
  private static function &_info($force = FALSE) {
    if (!isset(Civi::$statics[__CLASS__]['info'])|| $force) {
      Civi::$statics[__CLASS__]['info'] = [];
      $c = [];

      $config = CRM_Core_Config::singleton();
      $c = self::getComponents();

      foreach ($c as $name => $comp) {
        if (in_array($name, $config->enableComponents)) {
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
    $comp = CRM_Utils_Array::value($name, self::_info());
    if ($attribute) {
      return CRM_Utils_Array::value($attribute, $comp->info);
    }
    return $comp;
  }

  /**
   * @param bool $force
   *
   * @return CRM_Core_Component_Info[]
   * @throws Exception
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
          CRM_Core_Error::fatal("There is a discrepancy between name in component registry and in info file ({$cr->name}).");
        }
        Civi::$statics[__CLASS__]['all'][$cr->name] = $infoObject;
        unset($infoObject);
      }
    }

    return Civi::$statics[__CLASS__]['all'];
  }

  /**
   * @return array
   *   Array(string $name => int $id).
   */
  public static function &getComponentIDs() {
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
  static public function &getEnabledComponents($force = FALSE) {
    return self::_info($force);
  }

  static public function flushEnabledComponents() {
    unset(Civi::$statics[__CLASS__]);
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
    $config = CRM_Core_Config::singleton();

    $firstArg = CRM_Utils_Array::value(1, $args, '');
    $secondArg = CRM_Utils_Array::value(2, $args, '');
    foreach ($info as $name => $comp) {
      if (in_array($name, $config->enableComponents) &&
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
          if (!empty($comp->info[$name]['css'])) {
            $styleSheets = '<style type="text/css">@import url(' . "{$config->resourceBase}css/{$comp->info[$name]['css']});</style>";
            CRM_Utils_System::addHTMLHead($styleSheet);
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
   * @return array
   */
  public static function xmlMenu() {

    // lets build the menu for all components
    $info = self::getComponents(TRUE);

    $files = [];
    foreach ($info as $name => $comp) {
      $files = array_merge($files,
        $comp->menuFiles()
      );
    }

    return $files;
  }

  /**
   * @return array
   */
  public static function &menu() {
    $info = self::_info();
    $items = [];
    foreach ($info as $name => $comp) {
      $mnu = $comp->getMenuObject();

      $ret = $mnu->permissioned();
      $items = array_merge($items, $ret);

      $ret = $mnu->main($task);
      $items = array_merge($items, $ret);
    }
    return $items;
  }

  /**
   * @param string $componentName
   *
   * @return mixed
   */
  public static function getComponentID($componentName) {
    $info = self::_info();
    if (!empty($info[$componentName])) {
      return $info[$componentName]->componentID;
    }
    else {
      return;
    }
  }

  /**
   * @param int $componentID
   *
   * @return int|null|string
   */
  public static function getComponentName($componentID) {
    $info = self::_info();

    $componentName = NULL;
    foreach ($info as $compName => $component) {
      if ($component->componentID == $componentID) {
        $componentName = $compName;
        break;
      }
    }

    return $componentName;
  }

  /**
   * @return array
   */
  public static function &getQueryFields($checkPermission = TRUE) {
    $info = self::_info();
    $fields = [];
    foreach ($info as $name => $comp) {
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

    foreach ($info as $name => $comp) {
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
    foreach ($info as $name => $comp) {
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
    return $properties;
  }

  /**
   * @param CRM_Core_Form $form
   */
  public static function &buildSearchForm(&$form) {
    $info = self::_info();

    foreach ($info as $name => $comp) {
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

    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $bqr->searchAction($row, $id);
      }
    }
  }

  /**
   * @return array|null
   */
  public static function &contactSubTypes() {
    if (self::$_contactSubTypes == NULL) {
      self::$_contactSubTypes = [];
    }
    return self::$_contactSubTypes;
  }


  /**
   * @param $subType
   * @param $op
   *
   * @return null
   */
  public static function &contactSubTypeProperties($subType, $op) {
    $properties = self::contactSubTypes();
    if (array_key_exists($subType, $properties) &&
      array_key_exists($op, $properties[$subType])
    ) {
      return $properties[$subType][$op];
    }
    return CRM_Core_DAO::$_nullObject;
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

    foreach ($info as $name => $comp) {
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
        if ($subDir == 'Extension') {
          continue;
        }

        $infoFile = $crmFolderDir . "/{$subDir}/" . self::COMPONENT_INFO_CLASS . '.php';
        if (file_exists($infoFile)) {
          $infoClass = 'CRM_' . $subDir . '_' . self::COMPONENT_INFO_CLASS;
          require_once str_replace('_', DIRECTORY_SEPARATOR, $infoClass) . '.php';
          $infoObject = new $infoClass(NULL, NULL, NULL);
          $components[$infoObject->info['name']] = $infoObject;
          unset($infoObject);
        }
      }
    }

    return $components;
  }

}
