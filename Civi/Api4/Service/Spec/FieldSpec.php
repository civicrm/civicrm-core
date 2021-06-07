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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Service\Spec;

class FieldSpec {
  /**
   * @var mixed
   */
  public $defaultValue;

  /**
   * @var string
   */
  public $name;

  /**
   * @var string
   */
  public $label;

  /**
   * @var string
   */
  public $title;

  /**
   * @var string
   */
  public $type = 'Extra';

  /**
   * @var string
   */
  public $entity;

  /**
   * @var string
   */
  public $description;

  /**
   * @var bool
   */
  public $required = FALSE;

  /**
   * @var bool
   */
  public $requiredIf;

  /**
   * @var array|bool
   */
  public $options;

  /**
   * @var string
   */
  public $tableName;

  /**
   * @var callable
   */
  private $optionsCallback;

  /**
   * @var string
   */
  public $dataType;

  /**
   * @var string
   */
  public $inputType;

  /**
   * @var array
   */
  public $inputAttrs = [];

  /**
   * @var string[]
   */
  public $operators;

  /**
   * @var string
   */
  public $fkEntity;

  /**
   * @var int
   */
  public $serialize;

  /**
   * @var string
   */
  public $helpPre;

  /**
   * @var string
   */
  public $helpPost;

  /**
   * @var array
   */
  public $permission;

  /**
   * @var string
   */
  public $columnName;

  /**
   * @var bool
   */
  public $readonly = FALSE;

  /**
   * @var callable[]
   */
  public $outputFormatters;


  /**
   * @var callable[]
   */
  public $sqlFilters;

  /**
   * Aliases for the valid data types
   *
   * @var array
   */
  public static $typeAliases = [
    'Int' => 'Integer',
    'Link' => 'Url',
    'Memo' => 'Text',
  ];

  /**
   * @param string $name
   * @param string $entity
   * @param string $dataType
   */
  public function __construct($name, $entity, $dataType = 'String') {
    $this->entity = $entity;
    $this->name = $name;
    $this->setDataType($dataType);
  }

  /**
   * @return mixed
   */
  public function getDefaultValue() {
    return $this->defaultValue;
  }

  /**
   * @param mixed $defaultValue
   *
   * @return $this
   */
  public function setDefaultValue($defaultValue) {
    $this->defaultValue = $defaultValue;

    return $this;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   *
   * @return $this
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * @return string
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * @param string $label
   *
   * @return $this
   */
  public function setLabel($label) {
    $this->label = $label;

    return $this;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param string $title
   *
   * @return $this
   */
  public function setTitle($title) {
    $this->title = $title;

    return $this;
  }

  /**
   * @param string $entity
   *
   * @return $this
   */
  public function setEntity($entity) {
    $this->entity = $entity;

    return $this;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @return string
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->description = $description;

    return $this;
  }

  /**
   * @return bool
   */
  public function isRequired() {
    return $this->required;
  }

  /**
   * @param bool $required
   *
   * @return $this
   */
  public function setRequired($required) {
    $this->required = $required;

    return $this;
  }

  /**
   * @return bool
   */
  public function getRequiredIf() {
    return $this->requiredIf;
  }

  /**
   * @param bool $requiredIf
   *
   * @return $this
   */
  public function setRequiredIf($requiredIf) {
    $this->requiredIf = $requiredIf;

    return $this;
  }

  /**
   * @return string
   */
  public function getDataType() {
    return $this->dataType;
  }

  /**
   * @param $dataType
   *
   * @return $this
   * @throws \Exception
   */
  public function setDataType($dataType) {
    if (array_key_exists($dataType, self::$typeAliases)) {
      $dataType = self::$typeAliases[$dataType];
    }

    if (!in_array($dataType, $this->getValidDataTypes())) {
      throw new \Exception(sprintf('Invalid data type "%s', $dataType));
    }

    $this->dataType = $dataType;

    return $this;
  }

  /**
   * @return int
   */
  public function getSerialize() {
    return $this->serialize;
  }

  /**
   * @param int|null $serialize
   * @return $this
   */
  public function setSerialize($serialize) {
    $this->serialize = $serialize;

    return $this;
  }

  /**
   * @param array $permission
   * @return $this
   */
  public function setPermission($permission) {
    $this->permission = $permission;
    return $this;
  }

  /**
   * @return array
   */
  public function getPermission() {
    return $this->permission;
  }

  /**
   * @return string
   */
  public function getInputType() {
    return $this->inputType;
  }

  /**
   * @param string $inputType
   * @return $this
   */
  public function setInputType($inputType) {
    $this->inputType = $inputType;

    return $this;
  }

  /**
   * @return array
   */
  public function getInputAttrs() {
    return $this->inputAttrs;
  }

  /**
   * @param array $inputAttrs
   * @return $this
   */
  public function setInputAttrs($inputAttrs) {
    $this->inputAttrs = $inputAttrs;

    return $this;
  }

  /**
   * @param string[] $operators
   * @return $this
   */
  public function setOperators($operators) {
    $this->operators = $operators;

    return $this;
  }

  /**
   * @param callable[] $outputFormatters
   * @return $this
   */
  public function setOutputFormatters($outputFormatters) {
    $this->outputFormatters = $outputFormatters;

    return $this;
  }

  /**
   * @param callable $outputFormatter
   * @return $this
   */
  public function addOutputFormatter($outputFormatter) {
    if (!$this->outputFormatters) {
      $this->outputFormatters = [];
    }
    $this->outputFormatters[] = $outputFormatter;

    return $this;
  }

  /**
   * @param callable[] $sqlFilters
   * @return $this
   */
  public function setSqlFilters($sqlFilters) {
    $this->sqlFilters = $sqlFilters;

    return $this;
  }

  /**
   * @param callable $sqlFilter
   * @return $this
   */
  public function addSqlFilter($sqlFilter) {
    if (!$this->sqlFilters) {
      $this->sqlFilters = [];
    }
    $this->sqlFilters[] = $sqlFilter;

    return $this;
  }

  /**
   * @param string $type
   * @return $this
   */
  public function setType(string $type) {
    $this->type = $type;

    return $this;
  }

  /**
   * @param bool $readonly
   * @return $this
   */
  public function setReadonly($readonly) {
    $this->readonly = (bool) $readonly;

    return $this;
  }

  /**
   * @param string|NULL $helpPre
   */
  public function setHelpPre($helpPre) {
    $this->helpPre = is_string($helpPre) && strlen($helpPre) ? $helpPre : NULL;
  }

  /**
   * @param string|NULL $helpPost
   */
  public function setHelpPost($helpPost) {
    $this->helpPost = is_string($helpPost) && strlen($helpPost) ? $helpPost : NULL;
  }

  /**
   * @param string $tableName
   * @return $this
   */
  public function setTableName($tableName) {
    $this->tableName = $tableName;

    return $this;
  }

  /**
   * @return string
   */
  public function getTableName() {
    return $this->tableName;
  }

  /**
   * Add valid types that are not not part of \CRM_Utils_Type::dataTypes
   *
   * @return array
   */
  private function getValidDataTypes() {
    $extraTypes = ['Boolean', 'Text', 'Float', 'Url', 'Array', 'Blob', 'Mediumblob'];
    $extraTypes = array_combine($extraTypes, $extraTypes);

    return array_merge(\CRM_Utils_Type::dataTypes(), $extraTypes);
  }

  /**
   * @param array $values
   * @param array|bool $return
   * @param bool $checkPermissions
   * @return array
   */
  public function getOptions($values = [], $return = TRUE, $checkPermissions = TRUE) {
    if (!isset($this->options)) {
      if ($this->optionsCallback) {
        $this->options = ($this->optionsCallback)($this, $values, $return, $checkPermissions);
      }
      else {
        $this->options = FALSE;
      }
    }
    return $this->options;
  }

  /**
   * @param array|bool $options
   *
   * @return $this
   */
  public function setOptions($options) {
    $this->options = $options;
    return $this;
  }

  /**
   * @param callable $callback
   *
   * @return $this
   */
  public function setOptionsCallback($callback) {
    $this->optionsCallback = $callback;
    return $this;
  }

  /**
   * @return string
   */
  public function getFkEntity() {
    return $this->fkEntity;
  }

  /**
   * @param string $fkEntity
   *
   * @return $this
   */
  public function setFkEntity($fkEntity) {
    $this->fkEntity = $fkEntity;

    return $this;
  }

  /**
   * @return string|NULL
   */
  public function getColumnName(): ?string {
    return $this->columnName;
  }

  /**
   * @param string|null $columnName
   *
   * @return $this
   */
  public function setColumnName(?string $columnName) {
    $this->columnName = $columnName;
    return $this;
  }

  /**
   * Gets all public variables, converted to snake_case
   *
   * @return array
   */
  public function toArray() {
    // Anonymous class will only have access to public vars
    $getter = new class {

      function getPublicVars($object) {
        return get_object_vars($object);
      }

    };

    // If getOptions was never called, make options a boolean
    if (!isset($this->options)) {
      $this->options = isset($this->optionsCallback);
    }

    $ret = [];
    foreach ($getter->getPublicVars($this) as $key => $val) {
      $key = strtolower(preg_replace('/(?=[A-Z])/', '_$0', $key));
      $ret[$key] = $val;
    }
    return $ret;
  }

}
