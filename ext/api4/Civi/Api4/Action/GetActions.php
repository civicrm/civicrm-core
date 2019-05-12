<?php

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
      $includePaths = array_unique(explode(PATH_SEPARATOR, get_include_path()));
      // Search entity-specific actions (including those provided by extensions)
      foreach ($includePaths as $path) {
        $dir = \CRM_Utils_File::addTrailingSlash($path) . 'Civi/Api4/Action/' . $this->_entityName;
        $this->scanDir($dir);
      }
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
        $this->loadAction(lcfirst($actionName));
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
