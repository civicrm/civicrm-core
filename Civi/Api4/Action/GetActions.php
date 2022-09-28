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

namespace Civi\Api4\Action;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Get all API actions for the $ENTITY entity.
 *
 * Includes a list of accepted parameters for each action, descriptions and other documentation.
 */
class GetActions extends BasicGetAction {

  private $_actions = [];

  private $_actionsToGet;

  protected function getRecords() {
    $this->_actionsToGet = $this->_itemsToGet('name');

    $className = CoreUtil::getApiClass($this->_entityName);
    $entityReflection = new \ReflectionClass($className);
    foreach ($entityReflection->getMethods(\ReflectionMethod::IS_STATIC | \ReflectionMethod::IS_PUBLIC) as $method) {
      $actionName = $method->getName();
      if ($actionName != 'permissions' && $actionName != 'getInfo' && $actionName[0] != '_') {
        $this->loadAction($actionName, $method);
      }
    }
    if (!$this->_actionsToGet || count($this->_actionsToGet) > count($this->_actions)) {
      // Search for entity-specific actions in extensions
      $nameSpace = str_replace('Civi\Api4\\', 'Civi\Api4\Action\\', $className);
      foreach (\CRM_Extension_System::singleton()->getMapper()->getActiveModuleFiles() as $ext) {
        $dir = \CRM_Utils_File::addTrailingSlash(dirname($ext['filePath']));
        $this->scanDir($dir, $nameSpace);
      }
      // Search for entity-specific actions in core
      global $civicrm_root;
      $this->scanDir(\CRM_Utils_File::addTrailingSlash($civicrm_root), $nameSpace);
    }
    ksort($this->_actions);
    return $this->_actions;
  }

  /**
   * @param string $dir
   * @param string $nameSpace
   */
  private function scanDir($dir, $nameSpace) {
    $dir .= str_replace('\\', '/', $nameSpace);
    if (is_dir($dir)) {
      foreach (glob("$dir/*.php") as $file) {
        $actionName = basename($file, '.php');
        $actionClass = new \ReflectionClass($nameSpace . '\\' . $actionName);
        if ($actionClass->isInstantiable() && $actionClass->isSubclassOf('\Civi\Api4\Generic\AbstractAction')) {
          $this->loadAction(lcfirst($actionName));
        }
      }
    }
  }

  /**
   * @param $actionName
   * @param \ReflectionMethod $method
   */
  private function loadAction($actionName, $method = NULL) {
    try {
      if (!isset($this->_actions[$actionName]) && (!$this->_actionsToGet || in_array($actionName, $this->_actionsToGet))) {
        $action = \Civi\API\Request::create($this->getEntityName(), $actionName, ['version' => 4]);
        if (is_object($action) && (!$this->checkPermissions || $action->isAuthorized(\CRM_Core_Session::singleton()->getLoggedInContactID()))) {
          $this->_actions[$actionName] = ['name' => $actionName];
          if ($this->_isFieldSelected('description', 'comment', 'see')) {
            $vars = ['entity' => $this->getEntityName(), 'action' => $actionName];
            // Docblock from action class
            $actionDocs = ReflectionUtils::getCodeDocs($action->reflect(), NULL, $vars);
            unset($actionDocs['method']);
            // Docblock from action factory function in entity class. This takes precedence since most action classes are generic.
            if ($method) {
              $methodDocs = ReflectionUtils::getCodeDocs($method, 'Method', $vars);
              // Allow method doc to inherit class doc
              if (strpos($method->getDocComment(), '@inheritDoc') !== FALSE && !empty($methodDocs['comment']) && !empty($actionDocs['comment'])) {
                $methodDocs['comment'] .= "\n\n" . $actionDocs['comment'];
              }
              $actionDocs = array_filter($methodDocs) + $actionDocs;
            }
            $this->_actions[$actionName] += $actionDocs;
          }
          if ($this->_isFieldSelected('params')) {
            $this->_actions[$actionName]['params'] = $action->getParamInfo();
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
        'description' => 'Action name',
      ],
      [
        'name' => 'description',
        'description' => 'Description from docblock',
      ],
      [
        'name' => 'comment',
        'description' => 'Comments from docblock',
      ],
      [
        'name' => 'see',
        'data_type' => 'Array',
        'description' => 'Any @see annotations from docblock',
      ],
      [
        'name' => 'params',
        'description' => 'List of all accepted parameters',
        'data_type' => 'Array',
      ],
    ];
  }

}
