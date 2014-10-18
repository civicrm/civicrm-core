<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Core_Component {

  /*
     * End part (filename) of the component information class'es name
     * that needs to be present in components main directory.
     */
  CONST COMPONENT_INFO_CLASS = 'Info';

  private static $_info = NULL;

  static $_contactSubTypes = NULL;

  /**
   * @param bool $force
   *
   * @return array|null
   */
  private static function &_info($force = FALSE) {
    if (self::$_info == NULL || $force) {
      self::$_info = array();
      $c = array();

      $config = CRM_Core_Config::singleton();
      $c = self::getComponents();

      foreach ($c as $name => $comp) {
        if (in_array($name, $config->enableComponents)) {
          self::$_info[$name] = $comp;
        }
      }
    }

    return self::$_info;
  }

  /**
   * @param $name
   * @param null $attribute
   *
   * @return mixed
   */
  static function get($name, $attribute = NULL) {
    $comp = CRM_Utils_Array::value($name, self::_info());
    if ($attribute) {
      return CRM_Utils_Array::value($attribute, $comp->info);
    }
    return $comp;
  }

  /**
   * @param bool $force
   *
   * @return array
   * @throws Exception
   */
  public static function &getComponents($force = FALSE) {
    static $_cache = NULL;

    if (!$_cache || $force) {
      $_cache = array();

      $cr = new CRM_Core_DAO_Component();
      $cr->find(FALSE);
      while ($cr->fetch()) {
        $infoClass = $cr->namespace . '_' . self::COMPONENT_INFO_CLASS;
        require_once (str_replace('_', DIRECTORY_SEPARATOR, $infoClass) . '.php');
        $infoObject = new $infoClass($cr->name, $cr->namespace, $cr->id);
        if ($infoObject->info['name'] !== $cr->name) {
          CRM_Core_Error::fatal("There is a discrepancy between name in component registry and in info file ({$cr->name}).");
        }
        $_cache[$cr->name] = $infoObject;
        unset($infoObject);
      }
    }

    return $_cache;
  }

  /**
   * @param bool $force
   *
   * @return array|null
   */
  static public function &getEnabledComponents($force = FALSE) {
    return self::_info($force);
  }

  static public function flushEnabledComponents() {
    self::getEnabledComponents(TRUE);
  }

  /**
   * @param bool $translated
   *
   * @return array
   */
  public static function &getNames($translated = FALSE) {
    $allComponents = self::getComponents();

    $names = array();
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
  static function invoke(&$args, $type) {
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
  static function xmlMenu() {

    // lets build the menu for all components
    $info = self::getComponents(TRUE);

    $files = array();
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
  static function &menu() {
    $info = self::_info();
    $items = array();
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
   * @param $config
   * @param bool $oldMode
   */
  static function addConfig(&$config, $oldMode = FALSE) {
    $info = self::_info();

    foreach ($info as $name => $comp) {
      $cfg = $comp->getConfigObject();
      $cfg->add($config, $oldMode);
    }
    return;
  }

  /**
   * @param $componentName
   *
   * @return mixed
   */
  static function getComponentID($componentName) {
    $info = self::_info();
    if (!empty($info[$componentName])) {
      return $info[$componentName]->componentID;
    }
    else {
      return;
    }
  }

  /**
   * @param $componentID
   *
   * @return int|null|string
   */
  static function getComponentName($componentID) {
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
  static function &getQueryFields() {
    $info = self::_info();
    $fields = array();
    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr    = $comp->getBAOQueryObject();
        $flds   = $bqr->getFields();
        $fields = array_merge($fields, $flds);
      }
    }
    return $fields;
  }

  /**
   * @param $query
   * @param $fnName
   */
  static function alterQuery(&$query, $fnName) {
    $info = self::_info();

    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $bqr->$fnName($query);
      }
    }
  }

  /**
   * @param $fieldName
   * @param $mode
   * @param $side
   *
   * @return null
   */
  static function from($fieldName, $mode, $side) {
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
  static function &defaultReturnProperties($mode,
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
   * @param $form
   */
  static function &buildSearchForm(&$form) {
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
   * @param $id
   */
  static function searchAction(&$row, $id) {
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
  static function &contactSubTypes() {
    if (self::$_contactSubTypes == NULL) {
      self::$_contactSubTypes = array();
    }
    return self::$_contactSubTypes;
  }


  /**
   * @param $subType
   * @param $op
   *
   * @return null
   */
  static function &contactSubTypeProperties($subType, $op) {
    $properties = self::contactSubTypes();
    if (array_key_exists($subType, $properties) &&
      array_key_exists($op, $properties[$subType])
    ) {
      return $properties[$subType][$op];
    }
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * FIXME: This function does not appear to do anything. The is_array() check runs on a bunch of objects and (always?) returns false
   */
  static function &taskList() {
    $info = self::_info();

    $tasks = array();
    foreach ($info as $name => $value) {
      if (is_array($info[$name]) && isset($info[$name]['task'])) {
        $tasks += $info[$name]['task'];
      }
    }
    return $tasks;
  }

  /**
   * Function to handle table dependencies of components
   *
   * @param array $tables  array of tables
   *
   * @return null
   * @access public
   * @static
   */
  static function tableNames(&$tables) {
    $info = self::_info();

    foreach ($info as $name => $comp) {
      if ($comp->usesSearch()) {
        $bqr = $comp->getBAOQueryObject();
        $bqr->tableNames($tables);
      }
    }
  }

  /**
   * Function to get components info from info file
   *
   */
  static function getComponentsFromFile($crmFolderDir) {
    $components = array();
    //traverse CRM folder and check for Info file
    if (is_dir($crmFolderDir)) {
      $dir = opendir($crmFolderDir);
      while ($subDir = readdir($dir)) {
        // skip the extensions diretory since it has an Info.php file also
        if ($subDir == 'Extension') {
          continue;
        }

        $infoFile = $crmFolderDir . "/{$subDir}/" . self::COMPONENT_INFO_CLASS . '.php';
        if (file_exists($infoFile)) {
          $infoClass = 'CRM_' . $subDir . '_' . self::COMPONENT_INFO_CLASS;
          require_once (str_replace('_', DIRECTORY_SEPARATOR, $infoClass) . '.php');
          $infoObject = new $infoClass(NULL, NULL, NULL);
          $components[$infoObject->info['name']] = $infoObject;
          unset($infoObject);
        }
      }
    }

    return $components;
  }
}

