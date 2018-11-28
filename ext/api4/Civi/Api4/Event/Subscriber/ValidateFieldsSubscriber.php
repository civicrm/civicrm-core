<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Event\PrepareEvent;

/**
 * Validate field inputs based on annotations in the action class
 */
class ValidateFieldsSubscriber extends AbstractPrepareSubscriber {

  /**
   * @param PrepareEvent $event
   * @throws \Exception
   */
  public function onApiPrepare(PrepareEvent $event) {
    /** @var \Civi\Api4\Generic\AbstractAction $apiRequest */
    $apiRequest = $event->getApiRequest();
    if (is_a($apiRequest, 'Civi\Api4\Generic\AbstractAction')) {
      $paramInfo = $apiRequest->getParamInfo();
      foreach ($paramInfo as $param => $info) {
        $getParam = 'get' . ucfirst($param);
        $value = $apiRequest->$getParam();
        // Required fields
        if (!empty($info['required']) && (!$value && $value !== 0 && $value !== '0')) {
          throw new \API_Exception('Parameter "' . $param . '" is required.');
        }
        if (!empty($info['type']) && !self::checkType($value, $info['type'])) {
          throw new \API_Exception('Parameter "' . $param . '" is not of the correct type. Expecting ' . implode(' or ', $info['type']) . '.');
        }
      }
    }
  }

  /**
   * Validate variable type on input
   *
   * @param $value
   * @param $types
   * @return bool
   * @throws \API_Exception
   */
  public static function checkType($value, $types) {
    if ($value === NULL) {
      return TRUE;
    }
    foreach ($types as $type) {
      switch ($type) {
        case 'array':
        case 'bool':
        case 'string':
        case 'object':
          $tester = 'is_' . $type;
          if ($tester($value)) {
            return TRUE;
          }
          break;

        case 'int':
          if (\CRM_Utils_Rule::integer($value)) {
            return TRUE;
          }
          break;

        default:
          throw new \API_Exception('Unknown paramater type: ' . $type);
      }
    }
    return FALSE;
  }

}
