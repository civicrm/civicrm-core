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

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Event\PrepareEvent;

/**
 * Validate field inputs based on annotations in the action class
 *
 * @service civi.api4.validateFields
 */
class ValidateFieldsSubscriber extends Generic\AbstractPrepareSubscriber {

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      // Validating between W_EARLY and W_MIDDLE allows other event subscribers to
      // alter params without constraint (only params added W_EARLY will be validated).
      'civi.api.prepare' => [['onApiPrepare', 50]],
    ];
  }

  /**
   * @param \Civi\API\Event\PrepareEvent $event
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
        if (!empty($info['required']) && ($value === NULL || $value === '' || $value === [] || $value === FALSE)) {
          throw new \CRM_Core_Exception('Parameter "' . $param . '" is required.');
        }
        if (!empty($info['type']) && !self::checkType($value, $info['type'])) {
          throw new \CRM_Core_Exception('Parameter "' . $param . '" is not of the correct type. Expecting ' . implode(' or ', $info['type']) . '.');
        }
        if (!empty($info['deprecated']) && isset($value)) {
          \CRM_Core_Error::deprecatedWarning('APIv4 ' . $apiRequest->getEntityName() . ".$param parameter is deprecated.");
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
   * @throws \CRM_Core_Exception
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

        case 'float':
          if (\CRM_Utils_Rule::numeric($value)) {
            return TRUE;
          }
          break;

        case 'mixed':
          return TRUE;

        default:
          throw new \CRM_Core_Exception('Unknown parameter type: ' . $type);
      }
    }
    return FALSE;
  }

}
