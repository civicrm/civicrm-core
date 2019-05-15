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
 * Implement the "reload" option. This option can be used with "create" to force
 * the API to reload a clean copy of the entity before returning the result.
 *
 * @code
 * $clean = civicrm_api('myentity', 'create', array(
 *   'options' => array(
 *     'reload' => 1
 *   ),
 * ));
 * @endcode
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
      if (!CRM_Utils_Array::value('is_error', $result, FALSE)) {
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
          throw new API_Exception($reloadResult['error_message']);
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
        throw new API_Exception("Unknown reload mode " . $reloadMode);
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
