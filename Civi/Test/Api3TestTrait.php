<?php

namespace Civi\Test;

use Civi\API\Exception\NotImplementedException;

require_once 'api/v3/utils.php';
/**
 * Class Api3TestTrait
 * @package Civi\Test
 *
 * This trait defines a number of helper functions for testing APIv3. Commonly
 * used helpers include `callAPISuccess()`, `callAPIFailure()`,
 * `assertAPISuccess()`, and `assertAPIFailure()`.
 *
 * This trait is intended for use with PHPUnit-based test cases.
 */
trait Api3TestTrait {

  /**
   * Get the api versions to test.
   *
   * @return array
   */
  public function versionThreeAndFour() {
    return [
      'APIv3' => [3],
      'APIv4' => [4],
    ];
  }

  /**
   * Api version - easier to override than just a define
   * @var int
   */
  protected $_apiversion = 3;

  /**
   * Check that api returned 'is_error' => 1
   * else provide full message
   * @param array $result
   * @param $expected
   * @param array $valuesToExclude
   * @param string $prefix
   *   Extra test to add to message.
   */
  public function assertAPIArrayComparison($result, $expected, $valuesToExclude = [], $prefix = '') {
    $valuesToExclude = array_merge($valuesToExclude, ['debug', 'xdebug', 'sequential']);
    foreach ($valuesToExclude as $value) {
      if (isset($result[$value])) {
        unset($result[$value]);
      }
      if (isset($expected[$value])) {
        unset($expected[$value]);
      }
    }
    $this->assertEquals($result, $expected, 'api result array comparison failed ' . $prefix . print_r($result, TRUE) . ' was compared to ' . print_r($expected, TRUE));
  }

  /**
   * Check that a deleted item has been deleted.
   *
   * @param $entity
   * @param $id
   */
  public function assertAPIDeleted($entity, $id) {
    $this->callAPISuccess($entity, 'getcount', ['id' => $id], 0);
  }

  /**
   * Check that api returned 'is_error' => 1.
   *
   * @param array $apiResult
   *   Api result.
   * @param string $prefix
   *   Extra test to add to message.
   * @param string|null $expectedError
   */
  public function assertAPIFailure(array $apiResult, string $prefix = '', ?string $expectedError = NULL): void {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    if ($expectedError && !empty($apiResult['is_error'])) {
      $this->assertStringContainsString($expectedError, $apiResult['error_message'], 'api error message not as expected' . $prefix);
    }
    if (!$apiResult['is_error']) {
      // This section only called when it is going to fail - that means we don't have to parse the print_r in the message
      // if it is not going to be used anyway. It's really helpful for debugging when needed, but potentially expensive otherwise.
      $this->fail('api call should have failed but it succeeded ' . $prefix . (print_r($apiResult, TRUE)));
    }
    $this->assertNotEmpty($apiResult['error_message']);
  }

  /**
   * Check that api returned 'is_error' => 0.
   *
   * @param array $apiResult
   *   Api result.
   * @param string $prefix
   *   Extra test to add to message.
   */
  public function assertAPISuccess($apiResult, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $errorMessage = empty($apiResult['error_message']) ? '' : " " . $apiResult['error_message'];

    if (!empty($apiResult['debug_information'])) {
      $errorMessage .= "\n " . print_r($apiResult['debug_information'], TRUE);
    }
    if (!empty($apiResult['trace'])) {
      $errorMessage .= "\n" . print_r($apiResult['trace'], TRUE);
    }
    $this->assertEmpty($apiResult['is_error'] ?? NULL, $prefix . $errorMessage);
  }

  /**
   * This function exists to wrap api functions.
   * so we can ensure they fail where expected & throw exceptions without litterering the test with checks
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param string $expectedErrorMessage
   *   Error.
   * @param null $extraOutput
   * @return array|int
   */
  public function callAPIFailure($entity, $action, $params = [], $expectedErrorMessage = NULL, $extraOutput = NULL) {
    if (is_array($params)) {
      $params += [
        'version' => $this->_apiversion,
      ];
    }
    try {
      $result = $this->civicrm_api($entity, $action, $params);
    }
    catch (\CRM_Core_Exception $e) {
      // api v4 call failed and threw an exception.
      return [];
    }
    $this->assertAPIFailure($result, "We expected a failure for $entity $action but got a success", $expectedErrorMessage);
    return $result;
  }

  /**
   * @deprecated
   * @param string $entity
   * @param string $action
   * @param array $params
   * @return array|int
   */
  public function callAPIAndDocument($entity, $action, $params) {
    return $this->callAPISuccess($entity, $action, $params);
  }

  /**
   * wrap api functions.
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   *
   * @param string $entity
   * @param string $action
   * @param array $params
   * @param mixed $checkAgainst
   *   Optional value to check result against, implemented for getvalue,.
   *   getcount, getsingle. Note that for getvalue the type is checked rather than the value
   *   for getsingle the array is compared against an array passed in - the id is not compared (for
   *   better or worse )
   *
   * @return array|int
   */
  public function callAPISuccess($entity, $action, $params = [], $checkAgainst = NULL) {
    $params = array_merge([
      'version' => $this->_apiversion,
      'debug' => 1,
    ],
      $params
    );
    switch (strtolower($action)) {
      case 'getvalue':
        return $this->callAPISuccessGetValue($entity, $params, $checkAgainst);

      case 'getsingle':
        return $this->callAPISuccessGetSingle($entity, $params, $checkAgainst);

      case 'getcount':
        return $this->callAPISuccessGetCount($entity, $params, $checkAgainst);
    }
    $result = $this->civicrm_api($entity, $action, $params);
    $this->assertAPISuccess($result, "Failure in api call for $entity $action");
    return $result;
  }

  /**
   * This function exists to wrap api getValue function & check the result
   * so we can ensure they succeed & throw exceptions without littering the test with checks
   * There is a type check in this
   *
   * @param string $entity
   * @param array $params
   * @param int $count
   *
   * @return array|int
   */
  public function callAPISuccessGetCount($entity, $params, $count = NULL) {
    $params += [
      'version' => $this->_apiversion,
      'debug' => 1,
    ];
    $result = $this->civicrm_api($entity, 'getcount', $params);
    if (!is_int($result) || !empty($result['is_error']) || isset($result['values'])) {
      $this->fail('Invalid getcount result : ' . print_r($result, TRUE) . ' type :' . gettype($result));
    }
    if (is_int($count)) {
      $this->assertEquals($count, $result, "incorrect count returned from $entity getcount");
    }
    return $result;
  }

  /**
   * This function exists to wrap api getsingle function & check the result
   * so we can ensure they succeed & throw exceptions without litterering the test with checks
   *
   * @param string $entity
   * @param array $params
   * @param array $checkAgainst
   *   Array to compare result against.
   *   - boolean
   *   - integer
   *   - double
   *   - string
   *   - array
   *   - object
   *
   * @return array|int
   */
  public function callAPISuccessGetSingle($entity, $params, $checkAgainst = NULL) {
    $params += [
      'version' => $this->_apiversion,
    ];
    if (!empty($this->isGetSafe) && !isset($params['return'])) {
      $params['return'] = 'id';
    }
    $result = $this->civicrm_api($entity, 'getsingle', $params);
    if (!is_array($result) || !empty($result['is_error']) || isset($result['values'])) {
      $unfilteredResult = $this->civicrm_api($entity, 'get', ['version' => $this->_apiversion]);
      $this->fail(
        'Invalid getsingle result' . print_r($result, TRUE)
        . "\n entity: $entity . \n params \n " . print_r($params, TRUE)
        . "\n entities retrieved with blank params \n" . print_r($unfilteredResult, TRUE)
      );
    }
    if ($checkAgainst) {
      // @todo - have gone with the fn that unsets id? should we check id?
      $this->checkArrayEquals($result, $checkAgainst);
    }
    return $result;
  }

  /**
   * This function wraps the getValue api and checks the result.
   *
   * @param string $entity
   * @param array $params
   * @param string $type
   *   Per http://php.net/manual/en/function.gettype.php possible types.
   *   - boolean
   *   - integer
   *   - double
   *   - string
   *   - array
   *   - object
   *
   * @return array|int
   */
  public function callAPISuccessGetValue($entity, $params, $type = NULL) {
    $params += [
      'version' => $this->_apiversion,
    ];
    $result = $this->civicrm_api($entity, 'getvalue', $params);
    if (is_array($result) && (!empty($result['is_error']) || isset($result['values']))) {
      $this->fail('Invalid getvalue result' . print_r($result, TRUE));
    }
    if ($type) {
      if ($type === 'integer') {
        // api seems to return integers as strings
        $this->assertTrue(is_numeric($result), "expected a numeric value but got " . print_r($result, 1));
      }
      else {
        $this->assertType($type, $result, "returned result should have been of type $type but was ");
      }
    }
    return $result;
  }

  /**
   * A stub for the API interface. This can be overriden by subclasses to change how the API is called.
   *
   * @param $entity
   * @param $action
   * @param array $params
   * @return array|int
   */
  public function civicrm_api($entity, $action, $params = []) {
    if (($params['version'] ?? 0) == 4) {
      return $this->runApi4Legacy($entity, $action, $params);
    }
    return civicrm_api($entity, $action, $params);
  }

  /**
   * Emulate v3 syntax so we can run api3 tests on v4
   *
   * @param $v3Entity
   * @param $v3Action
   * @param array $v3Params
   * @return array|int
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function runApi4Legacy($v3Entity, $v3Action, $v3Params = []) {
    $v4Entity = \CRM_Core_DAO_AllCoreTables::convertEntityNameToCamel($v3Entity);
    $v4Action = $v3Action = strtolower($v3Action);
    $v4Params = ['checkPermissions' => isset($v3Params['check_permissions']) ? (bool) $v3Params['check_permissions'] : FALSE];
    $sequential = !empty($v3Params['sequential']);
    $options = \_civicrm_api3_get_options_from_params($v3Params, in_array($v4Entity, ['Contact', 'Participant', 'Event', 'Group', 'Contribution', 'Membership']));
    $indexBy = in_array($v3Action, ['get', 'create', 'replace']) && !$sequential ? 'id' : NULL;
    $onlyId = !empty($v3Params['format.only_id']);
    $onlySuccess = !empty($v3Params['format.is_success']);
    if (!empty($v3Params['filters']['is_current']) || !empty($v3Params['isCurrent'])) {
      $v3Params['is_current'] = 1;
      unset($v3Params['filters']['is_current'], $v3Params['isCurrent']);
    }
    $language = $v3Params['options']['language'] ?? $v3Params['option.language'] ?? NULL;
    if ($language) {
      $v4Params['language'] = $language;
    }
    $toRemove = ['option.', 'return', 'api.', 'format.'];
    $chains = $custom = [];
    foreach ($v3Params as $key => $val) {
      foreach ($toRemove as $remove) {
        if (str_starts_with($key, $remove)) {
          if ($remove == 'api.') {
            $chains[$key] = $val;
          }
          unset($v3Params[$key]);
        }
      }
    }

    $v3Fields = civicrm_api3($v3Entity, 'getfields', ['action' => $v3Action])['values'];

    // Fix 'null'
    foreach ($v3Params as $key => $val) {
      if ($val === 'null') {
        $v3Params[$key] = NULL;
      }
    }

    if ($v4Entity == 'Setting') {
      $indexBy = NULL;
      $v4Params['domainId'] = $v3Params['domain_id'] ?? NULL;
      if ($v3Action == 'getfields') {
        if (!empty($v3Params['name'])) {
          $v3Params['filters']['name'] = $v3Params['name'];
        }
        foreach ($v3Params['filters'] ?? [] as $filter => $val) {
          $v4Params['where'][] = [$filter, '=', $val];
        }
      }
      if ($v3Action == 'create') {
        $v4Action = 'set';
      }
      if ($v3Action == 'revert') {
        $v4Params['select'] = (array) $v3Params['name'];
      }
      if ($v3Action == 'getvalue') {
        $options['return'] = [$v3Params['name'] => 1];
        $v3Params = [];
      }
      \CRM_Utils_Array::remove($v3Params, 'domain_id', 'name');
    }

    \CRM_Utils_Array::remove($v3Params, 'options', 'debug', 'version', 'sort', 'offset', 'rowCount', 'check_permissions', 'sequential', 'filters', 'isCurrent');

    // Work around ugly hack in v3 Domain api
    if ($v4Entity == 'Domain') {
      $v3Fields['version'] = ['name' => 'version', 'api.aliases' => ['domain_version']];
      unset($v3Fields['domain_version']);
    }
    if ($v4Entity == 'Payment') {
      unset($v3Fields['entity_id']);
      if (!empty($v3Params['contribution_id']) && $v4Action === 'get') {
        $v4Params['contributionID'] = $v3Params['contribution_id'];
        \CRM_Utils_Array::remove($v3Params, 'contribution_id');
      }
    }
    foreach ($v3Fields as $name => $field) {
      // Resolve v3 aliases
      foreach ($field['api.aliases'] ?? [] as $alias) {
        if (isset($v3Params[$alias])) {
          $v3Params[$field['name']] = $v3Params[$alias];
          unset($v3Params[$alias]);
        }
      }
      // Convert custom field names
      if (str_starts_with($name, 'custom_') && is_numeric($name[7])) {
        // Strictly speaking, using titles instead of names is incorrect, but it works for
        // unit tests where names and titles are identical and saves an extra db lookup.
        $custom[$field['groupTitle']][$field['title']] = $name;
        $v4FieldName = $field['groupTitle'] . '.' . $field['title'];
        if (isset($v3Params[$name])) {
          $v3Params[$v4FieldName] = $v3Params[$name];
          unset($v3Params[$name]);
        }
        if (isset($options['return'][$name])) {
          $options['return'][$v4FieldName] = 1;
          unset($options['return'][$name]);
        }
      }

      if ($name === 'option_group_id' && isset($v3Params[$name]) && !is_numeric($v3Params[$name])) {
        // This is a per field hack (bad) but we can't solve everything at once
        // & a cleverer way turned out to be too much for this round.
        // Being in the test class it's tested....
        $v3Params['option_group_id.name'] = $v3Params['option_group_id'];
        unset($v3Params['option_group_id']);
      }
      if (isset($field['pseudoconstant'], $v3Params[$name]) && $field['type'] === \CRM_Utils_Type::T_INT && !is_numeric($v3Params[$name]) && is_string($v3Params[$name])) {
        $v3Params[$name] = \CRM_Core_PseudoConstant::getKey(\CRM_Core_DAO_AllCoreTables::getDAONameForEntity($v4Entity), $name, $v3Params[$name]);
      }
    }

    switch ($v3Action) {
      case 'getcount':
        $v4Params['select'] = ['row_count'];
        // No break - keep processing as get
      case 'getsingle':
      case 'getvalue':
        $v4Action = 'get';
        // No break - keep processing as get
      case 'get':
        if ($options['return'] && $v3Action !== 'getcount') {
          $v4Params['select'] = array_keys($options['return']);
          // Ensure id field is returned as v3 always expects it
          if ($v4Entity != 'Setting' && !in_array('id', $v4Params['select'])) {
            $v4Params['select'][] = 'id';
          }
          // Convert 'custom' to 'custom.*'
          $selectCustom = array_search('custom', $v4Params['select']);
          if ($selectCustom !== FALSE) {
            $v4Params['select'][$selectCustom] = 'custom.*';
          }
        }
        if ($options['limit'] && $v4Entity != 'Setting') {
          $v4Params['limit'] = $options['limit'];
        }
        if ($options['offset']) {
          $v4Params['offset'] = $options['offset'];
        }
        if ($options['sort']) {
          foreach (explode(',', $options['sort']) as $sort) {
            [$sortField, $sortDir] = array_pad(explode(' ', trim($sort)), 2, 'ASC');
            $v4Params['orderBy'][$sortField] = $sortDir;
          }
        }
        break;

      case 'replace':
        if (empty($v3Params['values'])) {
          $v4Action = 'delete';
        }
        else {
          $v4Params['records'] = $v3Params['values'];
        }
        unset($v3Params['values']);
        break;

      case 'create':
      case 'update':
        if (!empty($v3Params['id'])) {
          $v4Action = 'update';
          $v4Params['where'][] = ['id', '=', $v3Params['id']];
        }

        $v4Params['values'] = $v3Params;
        unset($v4Params['values']['id']);
        break;

      case 'delete':
        if (isset($v3Params['id'])) {
          $v4Params['where'][] = ['id', '=', $v3Params['id']];
        }
        break;

      case 'merge':
        $v4Action = 'mergeDuplicates';
        $v3Params['contact_id'] = $v3Params['to_keep_id'];
        $v3Params['duplicate_id'] = $v3Params['to_remove_id'];
        break;

      case 'getoptions':
        $indexBy = 0;
        $v4Action = 'getFields';
        $v4Params += [
          'where' => [['name', '=', $v3Params['field']]],
          'loadOptions' => TRUE,
        ];
        break;

      case 'getfields':
        $v4Action = 'getFields';
        if (!empty($v3Params['action']) || !empty($v3Params['api_action'])) {
          $v4Params['action'] = !empty($v3Params['action']) ? $v3Params['action'] : $v3Params['api_action'];
        }
        $indexBy = !$sequential ? 'name' : NULL;
        break;
    }

    // Ensure this api4 entity/action exists
    try {
      $actionInfo = \civicrm_api4($v4Entity, 'getActions', ['checkPermissions' => FALSE, 'where' => [['name', '=', $v4Action]]]);
    }
    catch (NotImplementedException $e) {
    }
    if (!isset($actionInfo[0])) {
      throw new \Exception("Api4 $v4Entity $v4Action does not exist.");
    }

    // Migrate special params like fix_address
    foreach ($actionInfo[0]['params'] as $v4ParamName => $paramInfo) {
      // camelCase in api4, lower_case in api3
      $v3ParamName = strtolower(preg_replace('/(?=[A-Z])/', '_$0', $v4ParamName));
      if (isset($v3Params[$v3ParamName])) {
        $v4Params[$v4ParamName] = $v3Params[$v3ParamName];
        unset($v3Params[$v3ParamName]);
        if ($paramInfo['type'][0] == 'bool') {
          $v4Params[$v4ParamName] = (bool) $v4Params[$v4ParamName];
        }
      }
    }

    if (isset($actionInfo[0]['params']['useTrash'])) {
      $v4Params['useTrash'] = empty($v3Params['skip_undelete']);
    }

    // Build where clause for 'getcount', 'getsingle', 'getvalue', 'get' & 'replace'
    if ($v4Action == 'get' || $v3Action == 'replace') {
      foreach ($v3Params as $key => $val) {
        $op = '=';
        if (is_array($val) && count($val) == 1 && array_intersect_key($val, array_flip(\CRM_Core_DAO::acceptedSQLOperators()))) {
          foreach ($val as $op => $newVal) {
            $val = $newVal;
          }
        }
        $v4Params['where'][] = [$key, $op, $val];
      }
    }

    try {
      $result = \civicrm_api4($v4Entity, $v4Action, $v4Params, $indexBy);
    }
    catch (\Exception $e) {
      return $onlySuccess ? 0 : [
        'is_error' => 1,
        'error_message' => $e->getMessage(),
        'version' => 4,
      ];
    }

    if (($v3Action == 'getsingle' || $v3Action == 'getvalue' || $v3Action == 'delete') && count($result) != 1) {
      return $onlySuccess ? 0 : [
        'is_error' => 1,
        'error_message' => "Expected one $v4Entity but found " . count($result),
        'count' => count($result),
      ];
    }

    if ($onlySuccess) {
      return 1;
    }

    if ($v3Action == 'getcount') {
      return $result->count();
    }

    if ($onlyId) {
      return $result->first()['id'];
    }

    if ($v3Action == 'getvalue' && $v4Entity == 'Setting') {
      return $result->first()['value'] ?? NULL;
    }

    if ($v3Action == 'getvalue') {
      $returnKey = array_keys($options['return'])[0];
      return $result->first()[$returnKey] ?? NULL;
    }

    // Mimic api3 behavior when using 'replace' action to delete all
    if ($v3Action == 'replace' && $v4Action == 'delete') {
      $result->exchangeArray([]);
    }

    if ($v3Action == 'getoptions') {
      return [
        'is_error' => 0,
        'count' => $result['options'] ? count($result['options']) : 0,
        'values' => $result['options'] ?: [],
        'version' => 4,
      ];
    }

    // Emulate the weird return format of api3 settings
    if (($v3Action == 'get' || $v3Action == 'create') && $v4Entity == 'Setting') {
      $settings = [];
      foreach ($result as $item) {
        $settings[$item['domain_id']][$item['name']] = $item['value'];
      }
      $result->exchangeArray($sequential ? array_values($settings) : $settings);
    }

    foreach ($result as $index => $row) {
      // Run chains
      foreach ($chains as $key => $params) {
        $result[$index][$key] = $this->runApi4LegacyChain($key, $params, $v4Entity, $row, $sequential);
      }
      // Resolve custom field names
      foreach ($custom as $group => $fields) {
        foreach ($fields as $field => $v3FieldName) {
          if (isset($row["$group.$field"])) {
            $result[$index][$v3FieldName] = $row["$group.$field"];
            unset($result[$index]["$group.$field"]);
          }
        }
      }
    }

    if ($v3Action == 'getsingle') {
      return $result->first();
    }

    return [
      'is_error' => 0,
      'version' => 4,
      'count' => count($result),
      'values' => (array) $result,
      'id' => is_object($result) && count($result) == 1 ? ($result->first()['id'] ?? NULL) : NULL,
    ];
  }

  /**
   * @param string $key
   * @param mixed $params
   * @param string $mainEntity
   * @param array $result
   * @param bool $sequential
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function runApi4LegacyChain($key, $params, $mainEntity, $result, $sequential) {
    // Handle an array of multiple calls using recursion
    if (is_array($params) && isset($params[0]) && is_array($params[0])) {
      $results = [];
      foreach ($params as $chain) {
        $results[] = $this->runApi4LegacyChain($key, $chain, $mainEntity, $result, $sequential);
      }
      return $results;
    }

    // Handle single api call
    [, $chainEntity, $chainAction] = explode('.', $key);
    $lcChainEntity = \CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($chainEntity);
    $chainEntity = \CRM_Core_DAO_AllCoreTables::convertEntityNameToCamel($chainEntity);
    $lcMainEntity = \CRM_Core_DAO_AllCoreTables::convertEntityNameToLower($mainEntity);
    $params = is_array($params) ? $params : [];

    // Api3 expects this to be inherited
    $params += ['sequential' => $sequential];

    // Replace $value.field_name
    foreach ($params as $name => $param) {
      if (is_string($param) && str_starts_with($param, '$value.')) {
        $param = substr($param, 7);
        $params[$name] = $result[$param] ?? NULL;
      }
    }

    try {
      $getFields = civicrm_api4($chainEntity, 'getFields', ['select' => ['name']], 'name');
    }
    catch (NotImplementedException $e) {
      $this->markTestIncomplete($e->getMessage());
    }

    // Emulate the string-fu guesswork that api3 does
    if ($chainEntity == $mainEntity && empty($params['id']) &&  !empty($result['id'])) {
      $params['id'] = $result['id'];
    }
    elseif (empty($params['id']) && !empty($result[$lcChainEntity . '_id'])) {
      $params['id'] = $result[$lcChainEntity . '_id'];
    }
    elseif (!empty($result['id']) && isset($getFields[$lcMainEntity . '_id']) && empty($params[$lcMainEntity . '_id'])) {
      $params[$lcMainEntity . '_id'] = $result['id'];
    }
    return $this->runApi4Legacy($chainEntity, $chainAction, $params);
  }

}
