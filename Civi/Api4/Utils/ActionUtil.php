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


namespace Civi\Api4\Utils;

class ActionUtil {

  /**
   * @param $entityName
   * @param $actionName
   * @return \Civi\Api4\Generic\AbstractAction
   * @throws \Civi\API\Exception\NotImplementedException
   */
  public static function getAction($entityName, $actionName) {
    // For custom pseudo-entities
    if (strpos($entityName, 'Custom_') === 0) {
      return \Civi\Api4\CustomValue::$actionName(substr($entityName, 7));
    }
    else {
      $callable = ["\\Civi\\Api4\\$entityName", $actionName];
      if (!is_callable($callable)) {
        throw new \Civi\API\Exception\NotImplementedException("API ($entityName, $actionName) does not exist (join the API team and implement it!)");
      }
      return call_user_func($callable);
    }
  }

}
