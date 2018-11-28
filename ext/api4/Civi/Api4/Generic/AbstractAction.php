<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
namespace Civi\Api4\Generic;

use Civi\API\Exception\UnauthorizedException;
use Civi\API\Kernel;
use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Utils\ReflectionUtils;
use CRM_Utils_Array as UtilsArray;

/**
 * Base class for all api actions.
 *
 * @method $this setCheckPermissions(bool $value)
 * @method bool getCheckPermissions()
 */
abstract class AbstractAction implements \ArrayAccess {

  /**
   * Api version number; cannot be changed.
   *
   * @var int
   */
  protected $version = 4;

  /**
   * Custom Group name if this is a CustomValue pseudo-entity.
   *
   * @var string
   */
  private $customGroup;

  /*
   * Todo: not implemented.
   *
   * @var array
   *
  protected $chain = [];
   */

  /**
   * Whether to enforce acl permissions based on the current user.
   *
   * Setting to FALSE will disable permission checks and override ACLs.
   * In REST/javascript this cannot be disabled.
   *
   * @var bool
   */
  protected $checkPermissions = TRUE;

  /* @var string */
  private $entity;

  /* @var \ReflectionClass */
  private $thisReflection;

  /* @var array */
  private $thisParamInfo;

  /* @var array */
  private $thisArrayStorage;

  /* @var array */
  private $entityFields;

  /**
   * Action constructor.
   * @param string $entity
   */
  public function __construct($entity) {
    $this->entity = $entity;
    $this->thisReflection = new \ReflectionClass($this);
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
   * @throws \API_Exception
   */
  public function setVersion() {
    throw new \API_Exception('Cannot modify api version');
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
          if (is_array($this->$param)) {
            // Don't overwrite any defaults
            $this->$param = $arguments[0] + $this->$param;
          }
          else {
            $this->$param = $arguments[0];
          }
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
   * @return Result|array
   * @throws UnauthorizedException
   */
  final public function execute() {
    /** @var Kernel $kernel */
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
    foreach ($this->thisReflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
      $name = $property->getName();
      $params[$name] = $this->$name;
    }
    return $params;
  }

  /**
   * @param $customGroup
   * @return static
   */
  public function setCustomGroup($customGroup) {
    $this->customGroup = $customGroup;
    return $this;
  }

  /**
   * @return string
   */
  public function getCustomGroup() {
    return $this->customGroup;
  }

  /**
   * Get documentation for one or all params
   *
   * @param string $param
   * @return array of arrays [description, type, default, (comment)]
   */
  public function getParamInfo($param = NULL) {
    if (!isset($this->thisParamInfo)) {
      $defaults = $this->getParamDefaults();
      foreach ($this->thisReflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
        $name = $property->getName();
        if ($name != 'version') {
          $this->thisParamInfo[$name] = ReflectionUtils::getCodeDocs($property, 'Property');
          $this->thisParamInfo[$name]['default'] = $defaults[$name];
        }
      }
    }
    return $param ? $this->thisParamInfo[$param] : $this->thisParamInfo;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   *
   * @return string
   */
  public function getAction() {
    $name = get_class($this);
    return lcfirst(substr($name, strrpos($name, '\\') + 1));
  }

  /**
   * @param string $param
   * @return bool
   */
  protected function paramExists($param) {
    return array_key_exists($param, $this->getParams());
  }

  /**
   * @return array
   */
  protected function getParamDefaults() {
    return array_intersect_key($this->thisReflection->getDefaultProperties(), $this->getParams());
  }

  /**
   * @return \CRM_Core_DAO|string
   */
  protected function getBaoName() {
    require_once 'api/v3/utils.php';
    return \_civicrm_api3_get_BAO($this->getEntity());
  }

  /**
   * @inheritDoc
   */
  public function offsetExists($offset) {
    return in_array($offset, ['entity', 'action', 'params', 'version', 'check_permissions']) || isset($this->thisArrayStorage[$offset]);
  }

  /**
   * @inheritDoc
   */
  public function &offsetGet($offset) {
    $val = NULL;
    if (in_array($offset, ['entity', 'action', 'params', 'version'])) {
      $getter = 'get' . ucfirst($offset);
      $val = $this->$getter();
      return $val;
    }
    if ($offset == 'check_permissions') {
      return $this->checkPermissions;
    }
    if (isset ($this->thisArrayStorage[$offset])) {
      return $this->thisArrayStorage[$offset];
    }
    else {
      return $val;
    }
  }

  /**
   * @inheritDoc
   */
  public function offsetSet($offset, $value) {
    if (in_array($offset, ['entity', 'action', 'params', 'version'])) {
      throw new \API_Exception('Cannot modify api4 state via array access');
    }
    if ($offset == 'check_permissions') {
      $this->setCheckPermissions($value);
    }
    else {
      $this->thisArrayStorage[$offset] = $value;
    }
  }

  /**
   * @inheritDoc
   */
  public function offsetUnset($offset) {
    if (in_array($offset, ['entity', 'action', 'params', 'check_permissions', 'version'])) {
      throw new \API_Exception('Cannot modify api4 state via array access');
    }
    unset($this->thisArrayStorage[$offset]);
  }

  /**
   * Extract the true fields from a BAO
   *
   * (Used by create and update actions)
   * @param object $bao
   * @return array
   */
  public static function baoToArray($bao) {
    $fields = $bao->fields();
    $values = [];
    foreach ($fields as $key => $field) {
      $name = $field['name'];
      if (property_exists($bao, $name)) {
        $values[$name] = $bao->$name;
      }
    }
    return $values;
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

  public function getPermissions() {
    $permissions = call_user_func(["\\Civi\\Api4\\" . $this->entity, 'permissions']);
    $permissions += [
      // applies to getFields, getActions, etc.
      'meta' => ['access CiviCRM'],
      // catch-all, applies to create, get, delete, etc.
      'default' => ['administer CiviCRM'],
    ];
    $action = $this->getAction();
    if (isset($permissions[$action])) {
      return $permissions[$action];
    }
    elseif (in_array($action, ['getActions', 'getFields'])) {
      return $permissions['meta'];
    }
    return $permissions['default'];
  }

  /**
   * Write a bao object as part of a create/update action.
   *
   * @param $params
   * @return array
   * @throws \API_Exception
   */
  protected function writeObject($params) {
    $entityId = UtilsArray::value('id', $params);
    FormattingUtil::formatWriteParams($params, $this->getEntity(), $this->getEntityFields());
    $this->formatCustomParams($params, $entityId);

    $baoName = $this->getBaoName();
    $bao = new $baoName();

    // For some reason the contact bao requires this
    if ($entityId && $this->getEntity() == 'Contact') {
      $params['contact_id'] = $entityId;
    }
    // Some BAOs are weird and don't support a straightforward "create" method.
    $oddballs = [
      'Address' => 'add',
      'GroupContact' => 'add',
      'Website' => 'add',
    ];
    $method = UtilsArray::value($this->getEntity(), $oddballs, 'create');
    if (!method_exists($bao, $method)) {
      $method = 'add';
    }
    if (method_exists($bao, $method)) {
      $createResult = $bao->$method($params);
    }
    else {
      $createResult = $this->genericCreateMethod($params);
    }

    if (!$createResult) {
      $errMessage = sprintf('%s write operation failed', $this->getEntity());
      throw new \API_Exception($errMessage);
    }

    if (!empty($this->reload) && is_a($createResult, 'CRM_Core_DAO')) {
      $createResult->find(TRUE);
    }

    // trim back the junk and just get the array:
    return $this->baoToArray($createResult);
  }

  /**
   * Fallback when a BAO does not contain create or add functions
   *
   * @param $params
   * @return mixed
   */
  private function genericCreateMethod($params) {
    $baoName = $this->getBaoName();
    $hook = empty($params['id']) ? 'create' : 'edit';

    \CRM_Utils_Hook::pre($hook, $this->getEntity(), UtilsArray::value('id', $params), $params);
    /** @var \CRM_Core_DAO $instance */
    $instance = new $baoName();
    $instance->copyValues($params, TRUE);
    $instance->save();
    \CRM_Utils_Hook::post($hook, $this->getEntity(), $instance->id, $instance);

    return $instance;
  }

  /**
   * Returns schema fields for this entity & action.
   *
   * @return array
   * @throws \API_Exception
   */
  public function getEntityFields() {
    if (!$this->entityFields) {
      $this->entityFields = civicrm_api4($this->getEntity(), 'getFields', ['action' => $this->getAction(), 'includeCustom' => FALSE])
        ->indexBy('name');
    }
    return $this->entityFields;
  }

  /**
   * @param array $params
   * @param int $entityId
   * @return mixed
   */
  private function formatCustomParams(&$params, $entityId) {
    $customParams = [];

    // $customValueID is the ID of the custom value in the custom table for this
    // entity (i guess this assumes it's not a multi value entity)
    foreach ($params as $name => $value) {
      if (strpos($name, '.') === FALSE) {
        continue;
      }

      list($customGroup, $customField) = explode('.', $name);

      $customFieldId = \CRM_Core_BAO_CustomField::getFieldValue(
        \CRM_Core_DAO_CustomField::class,
        $customField,
        'id',
        'name'
      );
      $customFieldType = \CRM_Core_BAO_CustomField::getFieldValue(
        \CRM_Core_DAO_CustomField::class,
        $customField,
        'html_type',
        'name'
      );
      $customFieldExtends = \CRM_Core_BAO_CustomGroup::getFieldValue(
        \CRM_Core_DAO_CustomGroup::class,
        $customGroup,
        'extends',
        'name'
      );

      // todo are we sure we don't want to allow setting to NULL? need to test
      if ($customFieldId && NULL !== $value) {

        if ($customFieldType == 'CheckBox') {
          // this function should be part of a class
          formatCheckBoxField($value, 'custom_' . $customFieldId, $this->getEntity());
        }

        \CRM_Core_BAO_CustomField::formatCustomField(
          $customFieldId,
          $customParams,
          $value,
          $customFieldExtends,
          NULL, // todo check when this is needed
          $entityId,
          FALSE,
          FALSE,
          TRUE
        );
      }
    }

    if ($customParams) {
      $params['custom'] = $customParams;
    }
  }

}
