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
 * Implement the "reload" option. This option can be used with "create" to force
 * the API to reload a clean copy of the entity before returning the result.
 *
 * ```
 * $clean = civicrm_api('myentity', 'create', array(
 *   'options' => array(
 *     'reload' => 1
 *   ),
 * ));
 * ```
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

require_once 'api/Wrapper.php';

/**
 * Class CRM_Utils_API_ReloadOption
 */
class CRM_Utils_API_ReloadOption implements API_Wrapper {

  /**
   * @var CRM_Utils_API_ReloadOption
   */
  private static $_singleton = NULL;

  /**
   * @return CRM_Utils_API_ReloadOption
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_API_ReloadOption();
    }
    return self::$_singleton;
  }

  /**
   * @inheritDoc
   */
  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }

  /**
   * @inheritDoc
   */
  public function toApiOutput($apiRequest, $result) {
    $reloadMode = NULL;
    if ($apiRequest['action'] === 'create' && isset($apiRequest['params'], $apiRequest['params']['options']) && is_array($apiRequest['params']['options']) && isset($apiRequest['params']['options']['reload'])) {
      if (empty($result['is_error'])) {
        $reloadMode = $apiRequest['params']['options']['reload'];
      }
      $id = (!empty($apiRequest['params']['sequential'])) ? 0 : $result['id'];
    }

    switch ($reloadMode) {
      case NULL:
      case '0':
      case 'null':
      case '':
        return $result;

      case '1':
      case 'default':
        $params = [
          'id' => $result['id'],
        ];
        $reloadResult = civicrm_api3($apiRequest['entity'], 'get', $params);
        if ($reloadResult['is_error']) {
          throw new CRM_Core_Exception($reloadResult['error_message']);
        }
        $result['values'][$id] = array_merge($result['values'][$id], $reloadResult['values'][$result['id']]);
        return $result;

      case 'selected':
        $params = [
          'id' => $id,
          'return' => $this->pickReturnFields($apiRequest),
        ];
        $reloadResult = civicrm_api3($apiRequest['entity'], 'get', $params);
        $result['values'][$id] = array_merge($result['values'][$id], $reloadResult['values'][$id]);
        return $result;

      default:
        throw new CRM_Core_Exception("Unknown reload mode " . $reloadMode);
    }
  }

  /**
   * Identify the fields which should be returned.
   *
   * @param $apiRequest
   * @return array
   */
  public function pickReturnFields($apiRequest) {
    $fields = civicrm_api3($apiRequest['entity'], 'getfields', []);
    $returnKeys = array_intersect(
      array_keys($apiRequest['params']),
      array_keys($fields['values'])
    );
    return $returnKeys;
  }

}
