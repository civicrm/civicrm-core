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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


namespace Civi\Api4\Action;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Utils\ActionUtil;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Get actions for an entity with a list of accepted params
 */
class GetActions extends BasicGetAction {

  private $_actions = [];

  private $_actionsToGet;

  protected function getRecords() {
    $this->_actionsToGet = $this->_itemsToGet('name');

    $entityReflection = new \ReflectionClass('\Civi\Api4\\' . $this->_entityName);
    foreach ($entityReflection->getMethods(\ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC) as $method) {
      $actionName = $method->getName();
      if ($actionName != 'permissions' && $actionName[0] != '_') {
        $this->loadAction($actionName);
      }
    }
    if (!$this->_actionsToGet || count($this->_actionsToGet) > count($this->_actions)) {
      // Search for entity-specific actions in extensions
      foreach (\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles() as $ext) {
        $dir = \CRM_Utils_File::addTrailingSlash(dirname($ext['filePath']));
        $this->scanDir($dir . 'Civi/Api4/Action/' . $this->_entityName);
      }
      // Search for entity-specific actions in core
      $this->scanDir(\CRM_Utils_File::addTrailingSlash(__DIR__) . $this->_entityName);
    }
    ksort($this->_actions);
    return $this->_actions;
  }

  /**
   * @param $dir
   */
  private function scanDir($dir) {
    if (is_dir($dir)) {
      foreach (glob("$dir/*.php") as $file) {
        $matches = [];
        preg_match('/(\w*).php/', $file, $matches);
        $actionName = array_pop($matches);
        $actionClass = new \ReflectionClass('\\Civi\\Api4\\Action\\' . $this->_entityName . '\\' . $actionName);
        if ($actionClass->isInstantiable() && $actionClass->isSubclassOf('\\Civi\\Api4\\Generic\\AbstractAction')) {
          $this->loadAction(lcfirst($actionName));
        }
      }
    }
  }

  /**
   * @param $actionName
   */
  private function loadAction($actionName) {
    try {
      if (!isset($this->_actions[$actionName]) && (!$this->_actionsToGet || in_array($actionName, $this->_actionsToGet))) {
        $action = ActionUtil::getAction($this->getEntityName(), $actionName);
        if (is_object($action)) {
          $this->_actions[$actionName] = ['name' => $actionName];
          if ($this->_isFieldSelected('description') || $this->_isFieldSelected('comment')) {
            $actionReflection = new \ReflectionClass($action);
            $actionInfo = ReflectionUtils::getCodeDocs($actionReflection);
            unset($actionInfo['method']);
            $this->_actions[$actionName] += $actionInfo;
          }
          if ($this->_isFieldSelected('params')) {
            $this->_actions[$actionName]['params'] = $action->getParamInfo();
            // Language param is only relevant on multilingual sites
            $languageLimit = (array) \Civi::settings()->get('languageLimit');
            if (count($languageLimit) < 2) {
              unset($this->_actions[$actionName]['params']['language']);
            }
            elseif (isset($this->_actions[$actionName]['params']['language'])) {
              $this->_actions[$actionName]['params']['language']['options'] = array_keys($languageLimit);
            }
          }
        }
      }
    }
    catch (NotImplementedException $e) {
    }
  }

  public function fields() {
    return [
      [
        'name' => 'name',
        'data_type' => 'String',
      ],
      [
        'name' => 'description',
        'data_type' => 'String',
      ],
      [
        'name' => 'comment',
        'data_type' => 'String',
      ],
      [
        'name' => 'params',
        'data_type' => 'Array',
      ],
    ];
  }

}
