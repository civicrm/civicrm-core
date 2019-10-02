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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
