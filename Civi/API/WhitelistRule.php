<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
namespace Civi\API;

/**
 * A WhitelistRule is used to determine if an API call is authorized.
 * For example:
 *
 * @code
 * new WhitelistRule(array(
 *   'entity' => 'Contact',
 *   'actions' => array('get','getsingle'),
 *   'required' => array('contact_type' => 'Organization'),
 *   'fields' => array('id', 'display_name', 'sort_name', 'created_date'),
 * ));
 * @endcode
 *
 * This rule would allow API requests that attempt to get contacts of type "Organization",
 * but only a handful of fields ('id', 'display_name', 'sort_name', 'created_date')
 * can be filtered or returned.
 *
 * Class WhitelistRule
 * @package Civi\API\Subscriber
 */
class WhitelistRule {

  static $IGNORE_FIELDS = array(
    'check_permissions',
    'debug',
    'offset',
    'option_offset',
    'option_limit',
    'option_sort',
    'options',
    'return',
    'rowCount',
    'sequential',
    'sort',
    'version',
  );

  /**
   * Create a batch of rules from an array.
   *
   * @param array $rules
   * @return array
   */
  public static function createAll($rules) {
    $whitelist = array();
    foreach ($rules as $rule) {
      $whitelist[] = new WhitelistRule($rule);
    }
    return $whitelist;
  }

  /**
   * @var int
   */
  public $version;

  /**
   * Entity name or '*' (all entities)
   *
   * @var string
   */
  public $entity;

  /**
   * List of actions which match, or '*' (all actions)
   *
   * @var string|array
   */
  public $actions;

  /**
   * List of key=>value pairs that *must* appear in $params.
   *
   * If there are no required fields, use an empty array.
   *
   * @var array
   */
  public $required;

  /**
   * List of fields which may be optionally inputted or returned, or '*" (all fields)
   *
   * @var array
   */
  public $fields;

  public function __construct($ruleSpec) {
    $this->version = $ruleSpec['version'];

    if ($ruleSpec['entity'] === '*') {
      $this->entity = '*';
    }
    else {
      $this->entity = Request::normalizeEntityName($ruleSpec['entity'], $ruleSpec['version']);
    }

    if ($ruleSpec['actions'] === '*') {
      $this->actions = '*';
    }
    else {
      $this->actions = array();
      foreach ((array) $ruleSpec['actions'] as $action) {
        $this->actions[] = Request::normalizeActionName($action, $ruleSpec['version']);
      }
    }

    $this->required = $ruleSpec['required'];
    $this->fields = $ruleSpec['fields'];
  }

  /**
   * @return bool
   */
  public function isValid() {
    if (empty($this->version)) {
      return FALSE;
    }
    if (empty($this->entity)) {
      return FALSE;
    }
    if (!is_array($this->actions) && $this->actions !== '*') {
      return FALSE;
    }
    if (!is_array($this->fields) && $this->fields !== '*') {
      return FALSE;
    }
    if (!is_array($this->required)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param array $apiRequest
   *   Parsed API request.
   * @return string|TRUE
   *   If match, return TRUE. Otherwise, return a string with an error code.
   */
  public function matches($apiRequest) {
    if (!$this->isValid()) {
      return 'invalid';
    }

    if ($this->version != $apiRequest['version']) {
      return 'version';
    }
    if ($this->entity !== '*' && $this->entity !== $apiRequest['entity']) {
      return 'entity';
    }
    if ($this->actions !== '*' && !in_array($apiRequest['action'], $this->actions)) {
      return 'action';
    }

    // These params *must* be included for the API request to proceed.
    foreach ($this->required as $param => $value) {
      if (!isset($apiRequest['params'][$param])) {
        return 'required-missing-' . $param;
      }
      if ($value !== '*' && $apiRequest['params'][$param] != $value) {
        return 'required-wrong-' . $param;
      }
    }

    // These params *may* be included at the caller's discretion
    if ($this->fields !== '*') {
      $activatedFields = array_keys($apiRequest['params']);
      $activatedFields = preg_grep('/^api\./', $activatedFields, PREG_GREP_INVERT);
      if ($apiRequest['action'] == 'get') {
        // Kind'a silly we need to (re(re))parse here for each rule; would be more
        // performant if pre-parsed by Request::create().
        $options = _civicrm_api3_get_options_from_params($apiRequest['params'], TRUE, $apiRequest['entity'], 'get');
        $return = \CRM_Utils_Array::value('return', $options, array());
        $activatedFields = array_merge($activatedFields, array_keys($return));
      }

      $unknowns = array_diff(
        $activatedFields,
        array_keys($this->required),
        $this->fields,
        self::$IGNORE_FIELDS
      );

      if (!empty($unknowns)) {
        return 'unknown-' . implode(',', $unknowns);
      }
    }

    return TRUE;
  }

  /**
   * Ensure that the return values comply with the whitelist's
   * "fields" policy.
   *
   * Most API's follow a convention where the result includes
   * a 'values' array (which in turn is a list of records). Unfortunately,
   * some don't. If the API result doesn't meet our expectation,
   * then we probably don't know what's going on, so we abort the
   * request.
   *
   * This will probably break some of the layered-sugar APIs (like
   * getsingle, getvalue). Just use the meat-and-potatoes API instead.
   * Or craft a suitably targeted patch.
   *
   * @param array $apiRequest
   *   API request.
   * @param array $apiResult
   *   API result.
   * @return array
   *   Modified API result.
   * @throws \API_Exception
   */
  public function filter($apiRequest, $apiResult) {
    if ($this->fields === '*') {
      return $apiResult;
    }
    if (isset($apiResult['values']) && empty($apiResult['values'])) {
      // No data; filtering doesn't matter.
      return $apiResult;
    }
    if (is_array($apiResult['values'])) {
      $firstRow = \CRM_Utils_Array::first($apiResult['values']);
      if (is_array($firstRow)) {
        $fields = $this->filterFields(array_keys($firstRow));
        $apiResult['values'] = \CRM_Utils_Array::filterColumns($apiResult['values'], $fields);
        return $apiResult;
      }
    }
    throw new \API_Exception(sprintf('Filtering failed for %s.%s. Unrecognized result format.', $apiRequest['entity'], $apiRequest['action']));
  }

  /**
   * Determine which elements in $keys are acceptable under
   * the whitelist policy.
   *
   * @param array $keys
   *   List of possible keys.
   * @return array
   *   List of acceptable keys.
   */
  protected function filterFields($keys) {
    $r = array();
    foreach ($keys as $key) {
      if (in_array($key, $this->fields)) {
        $r[] = $key;
      }
      elseif (preg_match('/^api\./', $key)) {
        $r[] = $key;
      }
    }
    return $r;
  }

}
