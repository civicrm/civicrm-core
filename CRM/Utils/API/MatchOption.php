<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * Implement the "match" and "match-mandatory" options. If the submitted record doesn't have an ID
 * but a "match" key is specified, then we will automatically search for pre-existing record and
 * update it.
 *
 * Note that "match" and "match-mandatory" behave the same in the case where one matching record
 * exists (ie they update the record). They also behave the same if there are multiple matching
 * records (ie they throw an error).  However, if there is no matching record, they differ:
 *   - "match-mandatory" will generate an error
 *   - "match" will allow action to proceed -- thus inserting a new record
 *
 * @code
 * $result = civicrm_api('contact', 'create', array(
 *   'options' => array(
 *     'match' => array('last_name', 'first_name')
 *   ),
 *   'first_name' => 'Jeffrey',
 *   'last_name' => 'Lebowski',
 *   'nick_name' => 'The Dude',
 * ));
 * @endcode
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 */

require_once 'api/Wrapper.php';
class CRM_Utils_API_MatchOption implements API_Wrapper {

  /**
   * @var CRM_Utils_API_MatchOption
   */
  private static $_singleton = NULL;

  /**
   * @return CRM_Utils_API_MatchOption
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_API_MatchOption();
    }
    return self::$_singleton;
  }

  /**
   * {@inheritDoc}
   */
  public function fromApiInput($apiRequest) {
    if ($apiRequest['action'] === 'create' && empty($apiRequest['params']['id']) && isset($apiRequest['params'], $apiRequest['params']['options'])) {
      $keys = NULL;
      if (isset($apiRequest['params']['options']['match-mandatory'])) {
        $isMandatory = TRUE;
        $keys = $apiRequest['params']['options']['match-mandatory'];
      }
      elseif ($apiRequest['params']['options']['match']) {
        $isMandatory = FALSE;
        $keys = $apiRequest['params']['options']['match'];
      }

      if (!empty($keys)) {
        $getParams = $this->createGetParams($apiRequest, $keys);
        $getResult = civicrm_api3($apiRequest['entity'], 'get', $getParams);
        if ($getResult['count'] == 0) {
          if ($isMandatory) throw new API_Exception("Failed to match existing record");
          // OK, don't care
        } elseif ($getResult['count'] == 1) {
          $item = array_shift($getResult['values']);
          $apiRequest['params']['id'] = $item['id'];
        } else {
          throw new API_Exception("Ambiguous match criteria");
        }
      }
    }
    return $apiRequest;
  }

  /**
   * {@inheritDoc}
   */
  public function toApiOutput($apiRequest, $result) {
    return $result;
  }

  /**
   * Create APIv3 "get" parameters to lookup an existing record using $keys
   *
   * @param array $apiRequest
   * @param array $keys list of keys to match against
   * @return array APIv3 $params
   */
  function createGetParams($apiRequest, $keys) {
    $params = array('version' => 3);
    foreach ($keys as $key) {
      $params[$key] = CRM_Utils_Array::value($key, $apiRequest['params'], '');
    }
    return $params;
  }
}
