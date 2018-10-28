<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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

namespace Civi\Api4\Action;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Get actions for an entity with a list of accepted params
 */
class GetActions extends AbstractAction {

  /**
   * Override default to allow open access
   * @inheritDoc
   */
  protected $checkPermissions = FALSE;

  private $_actions = [];

  public function _run(Result $result) {
    $includePaths = array_unique(explode(PATH_SEPARATOR, get_include_path()));
    $entityReflection = new \ReflectionClass('\Civi\Api4\\' . $this->getEntity());
    // First search entity-specific actions (including those provided by extensions
    foreach ($includePaths as $path) {
      $dir = \CRM_Utils_File::addTrailingSlash($path) . 'Civi/Api4/Action/' . $this->getEntity();
      $this->scanDir($dir);
    }
    // Scan all generic actions unless this entity does not extend generic entity
    if ($entityReflection->getParentClass()) {
      foreach ($includePaths as $path) {
        $dir = \CRM_Utils_File::addTrailingSlash($path) . 'Civi/Api4/Action';
        $this->scanDir($dir);
      }
    }
    // For oddball entities, just return their static methods
    else {
      foreach ($entityReflection->getMethods(\ReflectionMethod::IS_STATIC) as $method) {
        $this->loadAction($method->getName());
      }
    }
    $result->exchangeArray(array_values($this->_actions));
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
        if ($actionName !== 'AbstractAction') {
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
      if (!isset($this->_actions[$actionName])) {
        /* @var AbstractAction $action */
        $action = call_user_func(["\\Civi\\Api4\\" . $this->getEntity(), $actionName]);
        if (is_object($action)) {
          $actionReflection = new \ReflectionClass($action);
          $actionInfo = ReflectionUtils::getCodeDocs($actionReflection);
          unset($actionInfo['method']);
          $this->_actions[$actionName] = ['name' => $actionName] + $actionInfo;
          $this->_actions[$actionName]['params'] = $action->getParamInfo();
        }
      }
    }
    catch (NotImplementedException $e) {
    }
  }

}
