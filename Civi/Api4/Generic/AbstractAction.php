<?php
namespace Civi\Api4\Generic;

use Civi\Api4\Utils\ReflectionUtils;
use Civi\Api4\Utils\ActionUtil;

/**
 * Base class for all api actions.
 *
 * @method $this setCheckPermissions(bool $value)
 * @method bool getCheckPermissions()
 * @method $this setChain(array $chain)
 * @method array getChain()
 */
abstract class AbstractAction implements \ArrayAccess {

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
   * You can reference other values in the api results in this call by prefixing them with $
   *
   * For example, you could create a contact and place them in a group by chaining the
   * GroupContact api to the Contact api:
   *
   * Contact::create()
   *   ->setValue('first_name', 'Hello')
   *   ->addChain('add_to_a_group', GroupContact::create()->setValue('contact_id', '$id')->setValue('group_id', 123))
   *
   * This will substitute the id of the newly created contact with $id.
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
    if ($val != 4) {
      throw new \API_Exception('Cannot modify api version');
    }
    return $this;
  }

  /**
   * @param string $name
   *   Unique name for this chained request
   * @param \Civi\Api4\Generic\AbstractAction $apiRequest
   * @param string|int $index
   *   Either a string for how the results should be indexed e.g. 'name'
   *   or the index of a single result to return e.g. 0 for the first result.
   * @return $this
   */
  public function addChain($name, AbstractAction $apiRequest, $index = NULL) {
    $this->chain[$name] = [$apiRequest->getEntityName(), $apiRequest->getActionName(), $apiRequest->getParams(), $index];
    return $this;
  }

  /**
   * Magic function to provide addFoo, getFoo and setFoo for params.
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
    // Handle plural when adding to e.g. $values with "addValue" method.
    if ($mode == 'add' && $this->paramExists($param . 's')) {
      $param .= 's';
    }
    if ($this->paramExists($param)) {
      switch ($mode) {
        case 'get':
          return $this->$param;

        case 'set':
          $this->$param = $arguments[0];
          return $this;

        case 'add':
          if (!is_array($this->$param)) {
            throw new \API_Exception('Cannot add to non-array param');
          }
          if (array_key_exists(1, $arguments)) {
            $this->{$param}[$arguments[0]] = $arguments[1];
          }
          else {
            $this->{$param}[] = $arguments[0];
          }
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
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function execute() {
    /** @var \Civi\API\Kernel $kernel */
    $kernel = \Civi::service('civi_api_kernel');

    return $kernel->runRequest($this);
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
    foreach ($this->reflect()->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
      $name = $property->getName();
      // Skip variables starting with an underscore
      if ($name[0] != '_') {
        $params[$name] = $this->$name;
      }
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
      foreach ($this->reflect()->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
        $name = $property->getName();
        if ($name != 'version' && $name[0] != '_') {
          $this->_paramInfo[$name] = ReflectionUtils::getCodeDocs($property, 'Property');
          $this->_paramInfo[$name]['default'] = $defaults[$name];
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
    return array_key_exists($param, $this->getParams());
  }

  /**
   * @return array
   */
  protected function getParamDefaults() {
    return array_intersect_key($this->reflect()->getDefaultProperties(), $this->getParams());
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
   */
  public function isAuthorized() {
    $permissions = $this->getPermissions();
    return \CRM_Core_Permission::check($permissions);
  }

  /**
   * @return array
   */
  public function getPermissions() {
    $permissions = call_user_func(["\\Civi\\Api4\\" . $this->_entityName, 'permissions']);
    $permissions += [
      // applies to getFields, getActions, etc.
      'meta' => ['access CiviCRM'],
      // catch-all, applies to create, get, delete, etc.
      'default' => ['administer CiviCRM'],
    ];
    $action = $this->getActionName();
    if (isset($permissions[$action])) {
      return $permissions[$action];
    }
    elseif (in_array($action, ['getActions', 'getFields'])) {
      return $permissions['meta'];
    }
    return $permissions['default'];
  }

  /**
   * Returns schema fields for this entity & action.
   *
   * Here we bypass the api wrapper and execute the getFields action directly.
   * This is because we DON'T want the wrapper to check permissions as this is an internal op,
   * but we DO want permissions to be checked inside the getFields request so e.g. the api_key
   * field can be conditionally included.
   * @see \Civi\Api4\Action\Contact\GetFields
   *
   * @return array
   */
  public function entityFields() {
    if (!$this->_entityFields) {
      $getFields = ActionUtil::getAction($this->getEntityName(), 'getFields');
      $result = new Result();
      if (method_exists($this, 'getBaoName')) {
        $getFields->setIncludeCustom(FALSE);
      }
      $getFields
        ->setCheckPermissions($this->checkPermissions)
        ->setAction($this->getActionName())
        ->_run($result);
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

}
