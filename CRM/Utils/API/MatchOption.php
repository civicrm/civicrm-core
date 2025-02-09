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
 * Implement the "match" and "match-mandatory" options. If the submitted record doesn't have an ID
 * but a "match" key is specified, then we will automatically search for pre-existing record and
 * fill-in the missing ID. The "match" or "match-mandatory" can specified as a string (the name of the key
 * to match on) or array (the names of several keys to match on).
 *
 * Note that "match" and "match-mandatory" behave the same in the case where one matching record
 * exists (ie they update the record). They also behave the same if there are multiple matching
 * records (ie they throw an error).  However, if there is no matching record, they differ:
 *   - "match-mandatory" will generate an error
 *   - "match" will allow action to proceed -- thus inserting a new record
 *
 * ```
 * $result = civicrm_api('contact', 'create', array(
 *   'options' => array(
 *     'match' => array('last_name', 'first_name')
 *   ),
 *   'first_name' => 'Jeffrey',
 *   'last_name' => 'Lebowski',
 *   'nick_name' => 'The Dude',
 * ));
 * ```
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

require_once 'api/Wrapper.php';

/**
 * Class CRM_Utils_API_MatchOption
 */
class CRM_Utils_API_MatchOption implements API_Wrapper {

  /**
   * @var CRM_Utils_API_MatchOption
   */
  private static $_singleton = NULL;

  /**
   * Singleton function.
   *
   * @return CRM_Utils_API_MatchOption
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_API_MatchOption();
    }
    return self::$_singleton;
  }

  /**
   * @inheritDoc
   */
  public function fromApiInput($apiRequest) {

    // Parse options.match or options.match-mandatory
    $keys = NULL;
    if (isset($apiRequest['params'], $apiRequest['params']['options']) && is_array($apiRequest['params']['options'])) {
      if (isset($apiRequest['params']['options']['match-mandatory'])) {
        $isMandatory = TRUE;
        $keys = $apiRequest['params']['options']['match-mandatory'];
      }
      elseif (isset($apiRequest['params']['options']['match'])) {
        $isMandatory = FALSE;
        $keys = $apiRequest['params']['options']['match'];
      }
      if (is_string($keys)) {
        $keys = [$keys];
      }
    }

    // If one of the options was specified, then try to match records.
    // Matching logic differs for 'create' and 'replace' actions.
    if ($keys !== NULL) {
      switch ($apiRequest['action']) {
        case 'create':
          if (empty($apiRequest['params']['id'])) {
            $apiRequest['params'] = $this->match($apiRequest['entity'], $apiRequest['params'], $keys, $isMandatory);
          }
          break;

        case 'replace':
          // In addition to matching on the listed keys, also match on the set-definition keys.
          // For example, if the $apiRequest is to "replace the set of civicrm_emails for contact_id=123 while
          // matching emails on location_type_id", then we would need to search for pre-existing emails using
          // both 'contact_id' and 'location_type_id'
          $baseParams = _civicrm_api3_generic_replace_base_params($apiRequest['params']);
          $keys = array_unique(array_merge(
            array_keys($baseParams),
            $keys
          ));

          // attempt to match each replacement item
          foreach ($apiRequest['params']['values'] as $offset => $createParams) {
            $createParams = array_merge($baseParams, $createParams);
            $createParams = $this->match($apiRequest['entity'], $createParams, $keys, $isMandatory);
            $apiRequest['params']['values'][$offset] = $createParams;
          }
          break;

        default:
          // be forgiving of sloppy api calls
      }
    }

    return $apiRequest;
  }

  /**
   * Attempt to match a contact. This filters/updates the $createParams if there is a match.
   *
   * @param string $entity
   * @param array $createParams
   * @param array $keys
   * @param bool $isMandatory
   *
   * @return array
   *   revised $createParams, including 'id' if known
   * @throws CRM_Core_Exception
   */
  public function match($entity, $createParams, $keys, $isMandatory) {
    $getParams = $this->createGetParams($createParams, $keys);
    $getResult = civicrm_api3($entity, 'get', $getParams);
    if ($getResult['count'] == 0) {
      if ($isMandatory) {
        throw new CRM_Core_Exception("Failed to match existing record");
      }
      // OK, don't care
      return $createParams;
    }
    elseif ($getResult['count'] == 1) {
      $item = array_shift($getResult['values']);
      $createParams['id'] = $item['id'];
      return $createParams;
    }
    else {
      throw new CRM_Core_Exception("Ambiguous match criteria");
    }
  }

  /**
   * @inheritDoc
   */
  public function toApiOutput($apiRequest, $result) {
    return $result;
  }

  /**
   * Create APIv3 "get" parameters to lookup an existing record using $keys
   *
   * @param array $origParams
   *   Api request.
   * @param array $keys
   *   List of keys to match against.
   *
   * @return array
   *   APIv3 $params
   */
  public function createGetParams($origParams, $keys) {
    $params = ['version' => 3];
    foreach ($keys as $key) {
      $params[$key] = $origParams[$key] ?? '';
    }
    return $params;
  }

}
