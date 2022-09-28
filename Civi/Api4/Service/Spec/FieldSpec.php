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

namespace Civi\Api4\Service\Spec;

/**
 * Contains APIv4 field metadata
 */
class FieldSpec {

  // BasicSpecTrait: name, title, description
  use \Civi\Schema\Traits\BasicSpecTrait;

  // DataTypeSpecTrait: dataType, serialize, fkEntity
  use \Civi\Schema\Traits\DataTypeSpecTrait;

  // OptionsSpecTrait: options, optionsCallback, suffixes
  use \Civi\Schema\Traits\OptionsSpecTrait;

  // GuiSpecTrait: label, inputType, inputAttrs, helpPre, helpPost
  use \Civi\Schema\Traits\GuiSpecTrait;

  // SqlSpecTrait tableName, columnName, operators, sqlFilters
  use \Civi\Schema\Traits\SqlSpecTrait;

  // ArrayFormatTrait: toArray():array, loadArray($array)
  use \Civi\Schema\Traits\ArrayFormatTrait;

  /**
   * @var mixed
   */
  public $defaultValue;

  /**
   * Meta-type indicating how this field was defined/implemented.
   *
   * Ex: 'Field' (normal/standard DB field), 'Custom' (auxiliary DB field),
   * 'Filter' (read-oriented filter option), 'Extra' (special/programmatic field).
   *
   * @var string
   */
  public $type = 'Extra';

  /**
   * @var string
   */
  public $entity;

  /**
   * @var bool
   */
  public $required = FALSE;

  /**
   * @var bool
   */
  public $nullable = TRUE;

  /**
   * @var string
   */
  public $requiredIf;

  /**
   * @var array
   */
  public $permission;

  /**
   * @var bool
   */
  public $readonly = FALSE;

  /**
   * @var callable[]
   */
  public $outputFormatters;

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
   * @return bool
   */
  public function getNullable() {
    return $this->nullable;
  }

  /**
   * @param bool $nullable
   *
   * @return $this
   */
  public function setNullable(bool $nullable) {
    $this->nullable = $nullable;

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
   * @return string
   */
  public function getRequiredIf() {
    return $this->requiredIf;
  }

  /**
   * @param string $requiredIf
   *
   * @return $this
   */
  public function setRequiredIf($requiredIf) {
    $this->requiredIf = $requiredIf;

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

}
