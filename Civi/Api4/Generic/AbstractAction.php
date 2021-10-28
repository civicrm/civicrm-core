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
namespace Civi\Api4\Generic;

use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Utils\ReflectionUtils;

/**
 * Base class for all api actions.
 *
 * An api Action object stores the parameters of the api call, and defines a _run function to execute the action.
 *
 * Every `protected` class var is considered a parameter (unless it starts with an underscore).
 *
 * Adding a `protected` var to your Action named e.g. `$thing` will automatically:
 *  - Provide a getter/setter (via `__call` MagicMethod) named `getThing()` and `setThing()`.
 *  - Expose the param in the Api Explorer (be sure to add a doc-block as it displays in the help panel).
 *  - Require a value for the param if you add the "@required" annotation.
 *
 * @method bool getCheckPermissions()
 * @method $this setDebug(bool $value) Enable/disable debug output
 * @method bool getDebug()
 * @method $this setChain(array $chain)
 * @method array getChain()
 */
abstract class AbstractAction implements \ArrayAccess {

  use \Civi\Schema\Traits\MagicGetterSetterTrait;

  /**
   * Api version number; cannot be changed.
   *
   * @var int
   */
  protected $version = 4;

  /**
   * Additional api requests - will be called once per result.
   *
   * Keys can be any string - this will be the name given to the output.
   *
   * You can reference other values in the api results in this call by prefixing them with `$`.
   *
   * For example, you could create a contact and place them in a group by chaining the
   * `GroupContact` api to the `Contact` api:
   *
   * ```php
   * Contact::create()
   *   ->setValue('first_name', 'Hello')
   *   ->addChain('add_a_group', GroupContact::create()
   *     ->setValue('contact_id', '$id')
   *     ->setValue('group_id', 123)
   *   )
   * ```
   *
   * This will substitute the id of the newly created contact with `$id`.
   *
   * @var array
   */
  protected $chain = [];

  /**
   * Whether to enforce acl permissions based on the current user.
   *
   * Setting to FALSE will disable permission checks and override ACLs.
   * In REST/javascript this cannot be disabled.
   *
   * @var bool
   */
  protected $checkPermissions = TRUE;

  /**
   * Add debugging info to the api result.
   *
   * When enabled, `$result->debug` will be populated with information about the api call,
   * including sql queries executed.
   *
   * **Note:** with checkPermissions enabled, debug info will only be returned if the user has "view debug output" permission.
   *
   * @var bool
   */
  protected $debug = FALSE;

  /**
   * @var string
   */
  protected $_entityName;

  /**
   * @var string
   */
  protected $_actionName;

  /**
   * @var \ReflectionClass
   */
  private $_reflection;

  /**
   * @var array
   */
  private $_paramInfo;

  /**
   * @var array
   */
  private $_entityFields;

  /**
   * @var array
   */
  private $_arrayStorage = [];

  /**
   * @var int
   * Used to identify api calls for transactions
   * @see \Civi\Core\Transaction\Manager
   */
  private $_id;

  public $_debugOutput = [];

  /**
   * Action constructor.
   *
   * @param string $entityName
   * @param string $actionName
   * @throws \API_Exception
   */
  public function __construct($entityName, $actionName) {
    // If a namespaced class name is passed in
    if (strpos($entityName, '\\') !== FALSE) {
      $entityName = substr($entityName, strrpos($entityName, '\\') + 1);
    }
    $this->_entityName = $entityName;
    $this->_actionName = $actionName;
    $this->_id = \Civi\API\Request::getNextId();
  }

  /**
   * Strictly enforce api parameters
   * @param $name
   * @param $value
   * @throws \Exception
   */
  public function __set($name, $value) {
    throw new \API_Exception('Unknown api parameter');
  }

  /**
   * @param int $val
   * @return $this
   * @throws \API_Exception
   */
  public function setVersion($val) {
    if ($val !== 4 && $val !== '4') {
      throw new \API_Exception('Cannot modify api version');
    }
    return $this;
  }

  /**
   * @param bool $checkPermissions
   * @return $this
   */
  public function setCheckPermissions(bool $checkPermissions) {
    $this->checkPermissions = $checkPermissions;
    return $this;
  }

  /**
   * @param string $name
   *   Unique name for this chained request
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @param string|int|array $index
   *   See `civicrm_api4()` for documentation of `$index` param
   * @return $this
   */
  public function addChain($name, AbstractAction $apiRequest, $index = NULL) {
    $this->chain[$name] = [$apiRequest->getEntityName(), $apiRequest->getActionName(), $apiRequest->getParams(), $index];
    return $this;
  }

  /**
   * Magic function to provide automatic getter/setter for params.
   *
   * @param $name
   * @param $arguments
   * @return static|mixed
   * @throws \API_Exception
   */
  public function __call($name, $arguments) {
    $param = lcfirst(substr($name, 3));
    if (!$param || $param[0] == '_') {
      throw new \API_Exception('Unknown api parameter: ' . $name);
    }
    $mode = substr($name, 0, 3);
    if ($this->paramExists($param)) {
      switch ($mode) {
        case 'get':
          return $this->$param;

        case 'set':
          $this->$param = $arguments[0];
          return $this;
      }
    }
    throw new \API_Exception('Unknown api parameter: ' . $name);
  }

  /**
   * Invoke api call.
   *
   * At this point all the params have been sent in and we initiate the api call & return the result.
   * This is basically the outer wrapper for api v4.
   *
   * @return \Civi\Api4\Generic\Result
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function execute() {
    /** @var \Civi\API\Kernel $kernel */
    $kernel = \Civi::service('civi_api_kernel');
    $result = $kernel->runRequest($this);
    if ($this->debug && (!$this->checkPermissions || \CRM_Core_Permission::check('view debug output'))) {
      $result->debug['actionClass'] = get_class($this);
      $result->debug = array_merge($result->debug, $this->_debugOutput);
    }
    else {
      $result->debug = NULL;
    }
    return $result;
  }

  /**
   * @param \Civi\Api4\Generic\Result $result
   */
  abstract public function _run(Result $result);

  /**
   * Serialize this object's params into an array
   * @return array
   */
  public function getParams() {
    $params = [];
    $magicProperties = $this->getMagicProperties();
    foreach ($magicProperties as $name => $bool) {
      $params[$name] = $this->$name;
    }
    return $params;
  }

  /**
   * Get documentation for one or all params
   *
   * @param string $param
   * @return array of arrays [description, type, default, (comment)]
   */
  public function getParamInfo($param = NULL) {
    if (!isset($this->_paramInfo)) {
      $defaults = $this->getParamDefaults();
      $vars = [
        'entity' => $this->getEntityName(),
        'action' => $this->getActionName(),
      ];
      // For actions like "getFields" and "getActions" they are not getting the entity itself.
      // So generic docs will make more sense like this:
      if (substr($vars['action'], 0, 3) === 'get' && substr($vars['action'], -1) === 's') {
        $vars['entity'] = lcfirst(substr($vars['action'], 3, -1));
      }
      foreach ($this->reflect()->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
        $name = $property->getName();
        if ($name != 'version' && $name[0] != '_') {
          $docs = ReflectionUtils::getCodeDocs($property, 'Property', $vars);
          $docs['default'] = $defaults[$name];
          if (!empty($docs['optionsCallback'])) {
            $docs['options'] = $this->{$docs['optionsCallback']}();
            unset($docs['optionsCallback']);
          }
          $this->_paramInfo[$name] = $docs;
        }
      }
    }
    return $param ? $this->_paramInfo[$param] : $this->_paramInfo;
  }

  /**
   * @return string
   */
  public function getEntityName() {
    return $this->_entityName;
  }

  /**
   *
   * @return string
   */
  public function getActionName() {
    return $this->_actionName;
  }

  /**
   * @param string $param
   * @return bool
   */
  public function paramExists($param) {
    return array_key_exists($param, $this->getMagicProperties());
  }

  /**
   * @return array
   */
  protected function getParamDefaults() {
    return array_intersect_key($this->reflect()->getDefaultProperties(), $this->getMagicProperties());
  }

  /**
   * @inheritDoc
   */
  public function offsetExists($offset) {
    return in_array($offset, ['entity', 'action', 'params', 'version', 'check_permissions', 'id']) || isset($this->_arrayStorage[$offset]);
  }

  /**
   * @inheritDoc
   */
  public function &offsetGet($offset) {
    $val = NULL;
    if (in_array($offset, ['entity', 'action'])) {
      $offset .= 'Name';
    }
    if (in_array($offset, ['entityName', 'actionName', 'params', 'version'])) {
      $getter = 'get' . ucfirst($offset);
      $val = $this->$getter();
      return $val;
    }
    if ($offset == 'check_permissions') {
      return $this->checkPermissions;
    }
    if ($offset == 'id') {
      return $this->_id;
    }
    if (isset($this->_arrayStorage[$offset])) {
      return $this->_arrayStorage[$offset];
    }
    return $val;
  }

  /**
   * @inheritDoc
   */
  public function offsetSet($offset, $value) {
    if (in_array($offset, ['entity', 'action', 'entityName', 'actionName', 'params', 'version', 'id'])) {
      throw new \API_Exception('Cannot modify api4 state via array access');
    }
    if ($offset == 'check_permissions') {
      $this->setCheckPermissions($value);
    }
    else {
      $this->_arrayStorage[$offset] = $value;
    }
  }

  /**
   * @inheritDoc
   */
  public function offsetUnset($offset) {
    if (in_array($offset, ['entity', 'action', 'entityName', 'actionName', 'params', 'check_permissions', 'version', 'id'])) {
      throw new \API_Exception('Cannot modify api4 state via array access');
    }
    unset($this->_arrayStorage[$offset]);
  }

  /**
   * Is this api call permitted?
   *
   * This function is called if checkPermissions is set to true.
   *
   * @return bool
   * @internal Implement/override in civicrm-core.git only. Signature may evolve.
   */
  public function isAuthorized(): bool {
    $permissions = $this->getPermissions();
    return \CRM_Core_Permission::check($permissions);
  }

  /**
   * @return array
   */
  public function getPermissions() {
    $permissions = call_user_func([CoreUtil::getApiClass($this->_entityName), 'permissions']);
    $permissions += [
      // applies to getFields, getActions, etc.
      'meta' => ['access CiviCRM'],
      // catch-all, applies to create, get, delete, etc.
      'default' => ['administer CiviCRM'],
    ];
    $action = $this->getActionName();
    // Map specific action names to more generic versions
    $map = [
      'getActions' => 'meta',
      'getFields' => 'meta',
      'replace' => 'delete',
      'save' => 'create',
    ];
    $generic = $map[$action] ?? 'default';
    return $permissions[$action] ?? $permissions[$generic] ?? $permissions['default'];
  }

  /**
   * Returns schema fields for this entity & action.
   *
   * Here we bypass the api wrapper and run the getFields action directly.
   * This is because we DON'T want the wrapper to check permissions as this is an internal op.
   * @see \Civi\Api4\Action\Contact\GetFields
   *
   * @throws \API_Exception
   * @return array
   */
  public function entityFields() {
    if (!$this->_entityFields) {
      $allowedTypes = ['Field', 'Filter', 'Extra'];
      $getFields = \Civi\API\Request::create($this->getEntityName(), 'getFields', [
        'version' => 4,
        'checkPermissions' => FALSE,
        'action' => $this->getActionName(),
        'where' => [['type', 'IN', $allowedTypes]],
      ]);
      $result = new Result();
      // Pass TRUE for the private $isInternal param
      $getFields->_run($result, TRUE);
      $this->_entityFields = (array) $result->indexBy('name');
    }
    return $this->_entityFields;
  }

  /**
   * @return \ReflectionClass
   */
  public function reflect() {
    if (!$this->_reflection) {
      $this->_reflection = new \ReflectionClass($this);
    }
    return $this->_reflection;
  }

  /**
   * Validates required fields for actions which create a new object.
   *
   * @param $values
   * @return array
   * @throws \API_Exception
   */
  protected function checkRequiredFields($values) {
    $unmatched = [];
    foreach ($this->entityFields() as $fieldName => $fieldInfo) {
      if (!isset($values[$fieldName]) || $values[$fieldName] === '') {
        if (!empty($fieldInfo['required']) && !isset($fieldInfo['default_value'])) {
          $unmatched[] = $fieldName;
        }
        elseif (!empty($fieldInfo['required_if'])) {
          if ($this->evaluateCondition($fieldInfo['required_if'], ['values' => $values])) {
            $unmatched[] = $fieldName;
          }
        }
      }
    }
    return $unmatched;
  }

  /**
   * Replaces pseudoconstants in input values
   *
   * @param array $record
   * @throws \API_Exception
   */
  protected function formatWriteValues(&$record) {
    $optionFields = [];
    // Collect fieldnames with a :pseudoconstant suffix & remove them from $record array
    foreach (array_keys($record) as $expr) {
      $suffix = strrpos($expr, ':');
      if ($suffix) {
        $fieldName = substr($expr, 0, $suffix);
        $field = $this->entityFields()[$fieldName] ?? NULL;
        if ($field) {
          $optionFields[$fieldName] = [
            'val' => $record[$expr],
            'expr' => $expr,
            'field' => $field,
            'suffix' => substr($expr, $suffix + 1),
            'depends' => $field['input_attrs']['control_field'] ?? NULL,
          ];
          unset($record[$expr]);
        }
      }
    }
    // Sort option lookups by dependency, so e.g. country_id is processed first, then state_province_id, then county_id
    uasort($optionFields, function ($a, $b) {
      return $a['field']['name'] === $b['depends'] ? -1 : 1;
    });
    // Replace pseudoconstants. Note this is a reverse lookup as we are evaluating input not output.
    foreach ($optionFields as $fieldName => $info) {
      $options = FormattingUtil::getPseudoconstantList($info['field'], $info['expr'], $record, 'create');
      $record[$fieldName] = FormattingUtil::replacePseudoconstant($options, $info['val'], TRUE);
    }
  }

  /**
   * This function is used internally for evaluating field annotations.
   *
   * It should never be passed raw user input.
   *
   * @param string $expr
   *   Conditional in php format e.g. $foo > $bar
   * @param array $vars
   *   Variable name => value
   * @return bool
   * @throws \API_Exception
   * @throws \Exception
   */
  protected function evaluateCondition($expr, $vars) {
    if (strpos($expr, '}') !== FALSE || strpos($expr, '{') !== FALSE) {
      throw new \API_Exception('Illegal character in expression');
    }
    $tpl = "{if $expr}1{else}0{/if}";
    return (bool) trim(\CRM_Core_Smarty::singleton()->fetchWith('string:' . $tpl, $vars));
  }

  /**
   * When in debug mode, this logs the callback function being used by a Basic*Action class.
   *
   * @param callable $callable
   */
  protected function addCallbackToDebugOutput($callable) {
    if ($this->debug && empty($this->_debugOutput['callback'])) {
      if (is_scalar($callable)) {
        $this->_debugOutput['callback'] = (string) $callable;
      }
      elseif (is_array($callable)) {
        foreach ($callable as $key => $unit) {
          $this->_debugOutput['callback'][$key] = is_object($unit) ? get_class($unit) : (string) $unit;
        }
      }
      elseif (is_object($callable)) {
        $this->_debugOutput['callback'] = get_class($callable);
      }
    }
  }

}
