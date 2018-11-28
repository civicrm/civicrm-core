<?php

namespace Civi\Api4\Service\Spec;

use Civi\Api4\Utils\CoreUtil;

class FieldSpec {
  /**
   * @var mixed
   */
  protected $defaultValue;

  /**
   * @var string
   */
  protected $name;

  /**
   * @var string
   */
  protected $title;

  /**
   * @var string
   */
  protected $entity;

  /**
   * @var string
   */
  protected $description;

  /**
   * @var bool
   */
  protected $required = FALSE;

  /**
   * @var array|boolean
   */
  protected $options;

  /**
   * @var string
   */
  protected $dataType;

  /**
   * @var string
   */
  protected $fkEntity;

  /**
   * @var int
   */
  protected $serialize;

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
    $this->setName($name);
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
   */
  public function setSerialize($serialize) {
    $this->serialize = $serialize;
  }

  /**
   * Add valid types that are not not part of \CRM_Utils_Type::dataTypes
   *
   * @return array
   */
  private function getValidDataTypes() {
    $extraTypes = ['Boolean', 'Text', 'Float', 'Url'];
    $extraTypes = array_combine($extraTypes, $extraTypes);

    return array_merge(\CRM_Utils_Type::dataTypes(), $extraTypes);
  }

  /**
   * @return array
   */
  public function getOptions() {
    if (!isset($this->options) || $this->options === TRUE) {
      $fieldName = $this->getName();

      if ($this instanceof CustomFieldSpec) {
        // buildOptions relies on the custom_* type of field names
        $fieldName = sprintf('custom_%d', $this->getCustomFieldId());
      }

      $dao = CoreUtil::getDAOFromApiName($this->getEntity());
      $options = $dao::buildOptions($fieldName);

      if (!is_array($options) || !$options) {
        $options = FALSE;
      }

      $this->setOptions($options);
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
   * @param array $values
   * @return array
   */
  public function toArray($values = []) {
    $ret = [];
    foreach (get_object_vars($this) as $key => $val) {
      $key = strtolower(preg_replace('/(?=[A-Z])/', '_$0', $key));
      if (!$values || in_array($key, $values)) {
        $ret[$key] = $val;
      }
    }
    return $ret;
  }

}
